<?php

require_once(dirname(__FILE__) . '/../../includes/adLDAP/adLDAP.php');
require_once(dirname(__FILE__) . '/../../includes/class-lus-sync-status.php');

/**
 * Responsible for the synchronization operations with LDAP Server.
 */
class Lus_Job_Sync {

    const WINDOWS_TIME_FORMAT = 'YmdHis';
    const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';

    protected static $instance = null;
    protected $default_ldap_params;
    protected $last_error;

    /**
     *
     * @var Lus_Database
     */
    protected $lus_db;

    /**
     * 
     * @var adLDAP
     */
    protected $adldap = null;

    private function __construct() {
        if (function_exists('get_option')) {
            //WP context
            date_default_timezone_set(get_option('timezone_string'));
        }
        $this->default_ldap_params = array('name', 'givenName', 'sn', 'samaccountname', 'mail');
        $this->last_error = array();
        $this->lus_db = Lus_Database::get_instance();
    }

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function set_adldap($adLDAP) {
        if (isset($adLDAP)) {
            $this->adldap = $adLDAP;
        }
    }

    public function perform_sync() {
        if (!isset($this->adldap)) {
            lus_write_log('adLDAP not set. Sync cancelled.');
            return;
        }

        $start = time();

        if (!get_site_option('lusPerformedFullSync')) {
            lus_write_log('Performing Sync for All LDAP Active Users.');
            $users = $this->get_all_active_ldap_users();
        } else {
            lus_write_log('Performing Sync for All LDAP Active Users changed since yesterday.');
            $users = $this->get_all_active_ldap_users_changed_yesterday();
        }

        if (FALSE !== $users) {
            foreach ($users as $user) {
                $this->update_active_wp_user($user);
            }
        }

        if (!get_site_option('lusPerformedFullSync')) {
            lus_write_log('Inactivating all LDAP inative users.');
            $inactive_users = $this->get_all_inactive_ldap_users();
        } else {
            lus_write_log('Inactivating all LDAP inative users since yesterday.');
            $inactive_users = $this->get_all_inactive_ldap_users_changed_yesterday();
        }

        if (FALSE !== $inactive_users) {
            foreach ($inactive_users as $user) {
                $this->inactivate_wp_user($user);
            }
        }

        //writes log
        $log_arr = array(
            'sync_timestamp' => date(Lus_Job_Sync::MYSQL_DATETIME_FORMAT, $start),
            'status' => $this->get_sync_status(),
            'error_msg' => $this->format_sync_err_msgs(),
            'runtime' => (time() - $start),
            'logins_updated' => $users,
            'logins_inactivated' => $inactive_users,
            'sync_full' => !get_site_option('lusPerformedFullSync')
        );
        $this->lus_db->write_log($log_arr);

        //in the end
        lus_write_log('End of LDAP Syncronization.');
        update_site_option('lusPerformedFullSync', true);
        $this->last_error = array();
    }

    public function write_error_log($err_msg) {
        //writes log
        $log_arr = array(
            'sync_timestamp' => date(Lus_Job_Sync::MYSQL_DATETIME_FORMAT, time()),
            'status' => Lus_Sync_Status::ERROR_STATUS,
            'error_msg' => $err_msg,
            'runtime' => 0,
            'logins_updated' => '',
            'logins_inactivated' => '',
            'sync_full' => FALSE
        );
        $this->lus_db->write_log($log_arr);
    }

    /**
     * Retrieves all active users changed on LDAP since yesterday.
     * 
     * @see Lus_Job_Sync::getAllLDAPUsers
     * @return array list of LDAP users
     */
    protected function get_all_active_ldap_users_changed_yesterday() {
        return $this->get_all_ldap_users(true, $this->get_yesterday_win_format());
    }

    protected function get_all_inactive_ldap_users_changed_yesterday() {
        return $this->get_all_ldap_users(false, $this->get_yesterday_win_format());
    }

    /**
     * 
     * @see Lus_Job_Sync::getAllLDAPUsers
     * @param type $changed
     * @return array list of LDAP users
     */
    protected function get_all_active_ldap_users() {
        return $this->get_all_ldap_users(true, false);
    }

    protected function get_all_inactive_ldap_users() {
        return $this->get_all_ldap_users(false, false);
    }

    /**
     * Returns a array of active users on LDAP.
     * 
     * @param $changed initial change date (on Windows Format) to retrieve users.
     * @return array list of LDAP users
     * @link http://forums.devshed.com/ldap-programming-76/ldapsearch-for-ad-disabled-accounts-466619.html
     * @link http://social.technet.microsoft.com/Forums/windowsserver/en-US/8ae398a4-705e-43f8-9dc8-79001670ff61/search-for-whenchanged?forum=winserverDS
     */
    protected function get_all_ldap_users($active = true, $changed = false) {
        $users = array();

        try {
            $this->adldap->connect();

            $alphabet = range('a', 'z');

            //TODO: figure out a better way to do this
            foreach ($alphabet as $letter) {
                $filter = ($changed ? 'whenchanged>=' . $changed . ')' : '');
                $filter .= (empty($filter) ? '' : '(') . ($active ? '!(userAccountControl:1.2.840.113556.1.4.803:=2))' : 'userAccountControl:1.2.840.113556.1.4.803:=2)');
                $filter .= (empty($filter) ? '' : '(') . 'displayName';
                $users = array_merge($users, $this->adldap->user()->find(false, $filter, $letter . '*'));
            }

            $this->adldap->close();
        } catch (Exception $exc) {
            $err_msg = '[ERROR] Could not connect to LDAP: ' . $exc->getTraceAsString();
            lus_write_log($err_msg);
            $this->last_error[Lus_Sync_Status::ERROR_STATUS][] = $err_msg;
            $this->adldap->close();

            return FALSE;
        }

        return $users;
    }

    /**
     * Inactate a WP user from a LUS point of view.
     * 
     * @param type $user_name The user name.
     */
    protected function inactivate_wp_user($user_name) {
        $user_id = username_exists(strtolower($user_name));

        if ($user_id) {
            update_user_meta($user_id, 'lus_active', false);
        }
    }

    /**
     * Update a WP user using LDAP information.
     * 
     * @param type $user the samAccountName (on Windows).
     */
    protected function update_active_wp_user($user_name) {
        $ldap_attr = get_site_option('lusUserProfileFields');
        $user_name = strtolower($user_name);

        lus_write_log('Updating active user ' . $user_name);

        $fields = $this->default_ldap_params;

        foreach (array_keys($ldap_attr) as $key) {
            $fields[] = $key;
        }

        lus_write_log('Retrieving user "' . $user_name . '" info: ');
        lus_write_log($fields);

        try {
            $this->adldap->connect();
            $user_ldap = $this->adldap->user()->info($user_name, $fields);

            lus_write_log('Detailed information from user "' . $user_name . '" retrieved.');
            lus_write_log($user_ldap);

            if (count($user_ldap) == 0) {
                lus_write_log('Could not retrieve the user info from LDAP. Update canceled.');
                return;
            }

            $user_email = strtolower($this->get_ldap_attr($user_ldap, 'mail'));

            if (empty($user_email)) {
                lus_write_log('User email is empty. Could not mantain a user without a email. Update canceled.');
                return;
            }

            $user_id = username_exists($user_name);

            if (!$user_id and email_exists($user_email) == false) {
                lus_write_log('User DOES NOT exist at WP database. Creating.');
                $random_password = wp_generate_password(12, true, true);
                //insert
                $userdata = array(
                    'user_pass' => $random_password,
                    'user_login' => $user_name,
                    'user_nicename' => $user_name,
                    'user_email' => $user_email,
                    'display_name' => $this->format_user_display_name($this->get_ldap_attr($user_ldap, 'givenname')),
                    'nickname' => $this->format_user_display_name($this->get_ldap_attr($user_ldap, 'givenname')),
                    'first_name' => $this->format_user_display_name($this->get_ldap_attr($user_ldap, 'givenname')),
                    'last_name' => $this->format_user_display_name($this->get_ldap_attr($user_ldap, 'sn')),
                    'user_registered' => date("Y-m-d H:i:s")
                );

                $user_id = wp_insert_user($userdata);

                if (is_wp_error($user_id)) {
                    lus_write_log('Error while creating user ' . $user_name . ': ');
                    lus_write_log($user_id);
                    $this->last_error[Lus_Sync_Status::WARN_STATUS][] = 'Error while creating user ' . $user_name;
                    return;
                }
            } else {
                $userdata = array(
                    'ID' => $user_id,
                    'user_nicename' => $user_name,
                    'user_email' => $user_email,
                    'display_name' => $this->format_user_display_name($this->get_ldap_attr($user_ldap, 'givenname')),
                    'nickname' => $this->format_user_display_name($this->get_ldap_attr($user_ldap, 'givenname')),
                    'first_name' => $this->format_user_display_name($this->get_ldap_attr($user_ldap, 'givenname')),
                    'last_name' => $this->format_user_display_name($this->get_ldap_attr($user_ldap, 'sn'))
                );

                lus_write_log('User "' . $user_name . '" DOES EXIST at WP database. Updating.');
                $user_id = wp_update_user($userdata);

                if (is_wp_error($user_id)) {
                    lus_write_log('Error while updating user ' . $user_name . ' info: ');
                    lus_write_log($user_id);
                    $this->last_error[Lus_Sync_Status::WARN_STATUS][] = 'Error while updating user ' . $user_name;
                    return;
                }
            }

            lus_write_log('Updating user meta info.');

            //usermeta
            foreach (array_keys($ldap_attr) as $key) {
                update_user_meta($user_id, 'lus_' . $key, $this->get_ldap_attr($user_ldap, $key));
            }

            update_user_meta($user_id, 'lus_active', true);
            update_user_meta($user_id, 'lus_user', true);
            $this->adldap->close();
        } catch (Exception $exc) {
            $err_msg = '[ERROR] Could not connect to LDAP: ' . $exc->getTraceAsString();
            lus_write_log($err_msg);
            $this->last_error[Lus_Sync_Status::ERROR_STATUS][] = $err_msg;
            $this->adldap->close();
        }
    }

    //----------------------------------------------------------------
    //                      Util Functions
    //----------------------------------------------------------------

    protected function format_user_display_name($name) {
        if (get_site_option('lusCapitalizeNames')) {
            if (function_exists('mb_convert_case')) {
                return mb_convert_case(strtolower($name), MB_CASE_TITLE, 'UTF-8');
            }
        }

        return $name;
    }

    protected function get_ldap_attr($ldap_array, $attr) {
        if (empty($ldap_array) || empty($attr))
            return '';

        if (array_key_exists($attr, $ldap_array[0]) && count($ldap_array[0][$attr]) > 0) {
            return $ldap_array[0][$attr][0];
        }

        return '';
    }

    protected function get_yesterday_win_format() {
        //ref.: http://br1.php.net/manual/en/datetime.createfromformat.php
        $dateObj = new DateTime(date("Y-m-d"));
        $dateObj->sub(DateInterval::createFromDateString('1 days'));

        return $dateObj->format(Lus_Job_Sync::WINDOWS_TIME_FORMAT) . '.0Z';
    }

    private function format_sync_err_msgs() {
        if (!empty($this->last_error)) {
            $result = '';
            $result .= (array_key_exists(Lus_Sync_Status::ERROR_STATUS, $this->last_error) ? implode(PHP_EOL, $this->last_error[Lus_Sync_Status::ERROR_STATUS]) : ''); 
            $result .= (empty($result) ? '' : PHP_EOL . PHP_EOL);
            $result .= (array_key_exists(Lus_Sync_Status::WARN_STATUS, $this->last_error) ? implode(PHP_EOL, $this->last_error[Lus_Sync_Status::WARN_STATUS]) : ''); 
            
            return $result;
        } else {
            return '';
        }
    }

    private function get_sync_status() {
        if (!empty($this->last_error)) {
            if (array_key_exists(Lus_Sync_Status::ERROR_STATUS, $this->last_error)) {
                return Lus_Sync_Status::ERROR_STATUS;
            } else {
                return Lus_Sync_Status::WARN_STATUS;
            }
        } else {
            return Lus_Sync_Status::OK_STATUS;
        }
    }

}

?>
