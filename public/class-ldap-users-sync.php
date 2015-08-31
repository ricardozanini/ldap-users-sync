<?php

require_once(dirname(__FILE__) . '/../includes/class-lus-ldap.php');
require_once(dirname(__FILE__) . '/../includes/class-lus-database.php');
require_once(dirname(__FILE__) . '/includes/class-lus-job-sync.php');

/**
 * Description of class-ldap-users-sync
 *
 * @author r_fernandes
 */
class LDAP_Users_Sync {

    const VERSION = '1.1.5';

    protected $plugin_slug = 'ldap-users-sync';
    protected static $instance = null;

    /**
     *
     * @var Lus_Database
     */
    protected $lus_db = null;
    
    /**
     *
     * @var Lus_Job_Sync
     */
    protected $lus_sync = null;

    private function __construct() {
        $this->lus_db = Lus_Database::get_instance();
        $this->lus_sync = Lus_Job_Sync::get_instance();

        # Load plugin text domain
        add_action('init', array($this, 'load_plugin_textdomain'));
        # Register the Job Action
        add_action(LUS_SYNC_JOB_NAME, array($this, 'run_sync_job'));
        # hook for setup database
        register_activation_hook(__FILE__, array($this, 'setup_database'));
    }

    public function load_plugin_textdomain() {
        $domain = $this->plugin_slug;
        $locale = apply_filters('plugin_locale', get_locale(), $domain);

        load_textdomain($domain, trailingslashit(WP_LANG_DIR) . $domain . '/' . $domain . '-' . $locale . '.mo');
        load_plugin_textdomain($domain, FALSE, basename(plugin_dir_path(dirname(__FILE__))) . '/languages/');
    }

    public function get_plugin_slug() {
        return $this->plugin_slug;
    }

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public static function activate() {
        $me = LDAP_Users_Sync::get_instance();
        $me->setup_database();
    }

    public static function deactivate() {
        //clear the schedule
        wp_clear_scheduled_hook(LUS_SYNC_JOB_NAME);
    }

    public function setup_database() {
        $this->lus_db->setup();
    }

    public function run_sync_job() {
        lus_write_log('Preparing to start LDAP Syncronization.');

        try {
            $this->lus_sync->set_adldap(new adLDAP(Lus_Ldap::get_connection_settings()));
        } catch (adLDAPException $e) {
            //writes log
            lus_write_log('Could not perfom LDAP Sync: ' . $e->getTraceAsString());
            $this->lus_sync->write_error_log('[ERROR] Could not connect to LDAP: ' . $e->getTraceAsString());
            return;
        }

        lus_write_log('Performing LDAP Syncronization.');
        $this->lus_sync->perform_sync();
    }

}

?>
