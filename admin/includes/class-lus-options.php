<?php

require_once(dirname(__FILE__) . '/../../includes/class-lus-database.php');
require_once(dirname(__FILE__) . '/../../includes/class-lus-ldap.php');

/**
 * Description of class-lus-options
 *
 * @author r_fernandes
 */
class Lus_Options {

    protected static $instance = null;
    protected $table_name = null;

    private function __construct() {
        $lus_db = Lus_Database::get_instance();
        $this->table_name = $lus_db->get_table_name();
    }

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Defines the plugin options. 
     * 
     * @return type
     */
    public static function get_options() {
        $ret = array();

        //--conn
        $ret['lusDomainControllers'] = get_site_option('lusDomainControllers');
        $ret['lusAdminUsername'] = get_site_option('lusAdminUsername');
        $ret['lusAdminPassword'] = get_site_option('lusAdminPassword'); //TODO: encrypt
        $ret['lusBaseDn'] = get_site_option('lusBaseDn');
        $ret['lusAdPort'] = get_site_option('lusAdPort');
        $ret['lusAccountSufix'] = get_site_option('lusAccountSufix');
        //--attr
        $ret['lusAttributeMapping'] = get_site_option('lusAttributeMapping');
        $ret['lusCapitalizeNames'] = get_site_option('lusCapitalizeNames');
        //--sync
        $ret['lusSyncStartDateTime'] = get_site_option('lusSyncStartDateTime');
        $ret['lusSyncRecurrence'] = get_site_option('lusSyncRecurrence');
        $ret['lusPerformedFullSync'] = get_site_option('lusPerformedFullSync');
        $ret['lusDisableSync'] = get_site_option('lusDisableSync');

        return $ret;
    }

    /**
     * Process the settings updates after form submit.
     */
    function process_updates() {
        $this->process_update_options();
        $this->process_log_delete();
    }

    protected function process_update_options() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['lusOptionsSave']) {
            foreach ($_POST as $key => $item) {

                if ($key != 'lusOptionsSave' || $key != 'lusTestConnection') {
                    update_site_option($key, stripslashes($item));
                }

                //converting to array format (so we can use later on WP filters)
                if ($key == 'lusAttributeMapping') {
                    $attrs = array_filter(array_map('trim', explode("\n", stripslashes($item))));

                    $profile_fields = array();

                    foreach ($attrs as $value) {
                        if (strpos($value, '=') > 0) {
                            $pairs = explode('=', $value);
                            $profile_fields[strtolower($pairs[1])] = $pairs[0];
                        }
                    }

                    update_site_option('lusUserProfileFields', $profile_fields);
                }
            }

            if ($_POST['lusSyncStartDateTime']) {
                $this->create_cron_job();
            }

            # Run Sync now
            if ($_POST['lusRunNow']) {
                wp_schedule_single_event(time() - 1, LUS_SYNC_JOB_NAME);
                spawn_cron();
                echo "<div id='message' class='updated fade'><p>" . __('<b>Synchronization Process:</b> Started Successfully!', 'ldap-users-sync') . "</p></div>";
            }

            # Test Ldap Connection
            if ($_POST['lusTestConnection']) {
                if ($this->test_connection()) {
                    echo "<div id='message' class='updated fade'><p>" . __('<b>LDAP Connection Test:</b> Successful!', 'ldap-users-sync') . "</p></div>";
                } else {
                    echo "<div id='message' class='error fade'><p>" . __('<b>LDAP Connection Test:</b> Failed!', 'ldap-users-sync') . "</div>";
                }
            }

            echo "<div id='message' class='updated fade'><p>" . __('Saved Options!', 'ldap-users-sync') . "</p></div>";
        }
    }

    protected function process_log_delete() {
        if (!isset($_GET['tab']) ||  $_GET['tab'] !== 'logs') {
            return;
        }

        if (isset($_GET['action']) && $_GET['action'] === 'delete') {
            if (isset($_GET['log_id']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'])) {
                $result = $this->delete_log($_GET['log_id']);
            } else if (isset($_GET['log'])) {
                foreach ($_GET['log'] as $log) {
                    $result = $this->delete_log($log);
                    if (!$result) {
                        break;
                    }
                }
            }

            if ($result) {
                echo "<div id='message' class='updated fade'><p>" . __('Log(s) deleted!', 'ldap-users-sync') . "</p></div>";
            }
        }
    }

    protected function delete_log($log_id) {
        global $wpdb;

        if (!$wpdb->delete($this->table_name, array('id' => $log_id), '%d')) {
            echo "<div id='message' class='error fade'><p>" . __('<b>Log deletion:</b> Failed!', 'ldap-users-sync') . "</div>";
            return FALSE;
        }

        return TRUE;
    }

    protected function create_cron_job() {
        extract(Lus_Options::get_options());

        //clear the schedule
        wp_clear_scheduled_hook(LUS_SYNC_JOB_NAME);

        if (!$lusDisableSync) {
            //new schedule
            //ref.: http://wp.smashingmagazine.com/2013/10/16/schedule-events-using-wordpress-cron/
            $time = strtotime(get_gmt_from_date($lusSyncStartDateTime));
            wp_schedule_event($time, $lusSyncRecurrence, LUS_SYNC_JOB_NAME);
        }
    }

    /**
     * Tests if the connection settings are correct or not.
     * 
     * @return boolean
     */
    protected function test_connection() {
        try {
            $adldap = new adLDAP(Lus_Ldap::get_connection_settings());
            $adldap->connect();
            $adldap->close();
        } catch (adLDAPException $e) {
            if (isset($adldap)) {
                $adldap->close();
            }

            lus_write_log($e);
            return false;
        }

        return true;
    }

}

?>
