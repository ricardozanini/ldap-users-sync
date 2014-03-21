<?php

class Lus_Database {

    protected static $instance = null;
    protected $table_name = null;

    const LUS_LOG_TABLE_NAME = "lus_log";
    const LUS_DB_VERSION = "1.1";

    private function __construct() {
        $this->table_name = Lus_Database::get_table_name();
    }

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . Lus_Database::LUS_LOG_TABLE_NAME;
    }

    /**
     * Creates or update the table used by this plugin
     * 
     * @global type $wpdb
     */
    public function setup() {
        if (get_site_option("lusDBVersion") != Lus_Database::LUS_DB_VERSION) {
            $sql = "CREATE TABLE $this->table_name (
            id int NOT NULL AUTO_INCREMENT,
            sync_timestamp datetime NOT NULL,
            status varchar(10) NOT NULL,
            error_msg longtext NULL,
            runtime mediumint NOT NULL,
            logins_updated longtext DEFAULT '' NOT NULL,
            logins_inactivated longtext DEFAULT '' NOT NULL,
            sync_full tinyint(1) NOT NULL,
            UNIQUE KEY id (id)
         );";

            require_once(ABSPATH . "wp-admin/includes/upgrade.php");
            if (dbDelta($sql) !== FALSE) {
                update_site_option("lusDBVersion", Lus_Database::LUS_DB_VERSION);
            }
        }
    }

    /**
     * Writes log parameters to DB.
     * 
     * @param array $log an array (key/pair) with: <br/>
     *  'sync_timestamp' => when sync starts (current_time('mysql') is fine), <br/>
     *  'status' => OK or WARNING, <br/>
     *  'error_msg' => the error msg,<br/>
     *  'runtime' => seconds taken for job to run <br/>
     *  'logins_updated' => array of logins <br/>
     *  'logins_inactivated' => array of logins that have been inactivated<br/>
     *  'sync_full' => TRUE if performing a full sync.
     */
    public function write_log($log_arr) {
        global $wpdb;

        if (is_null($log_arr) || empty($log_arr)) {
            lus_write_log("Could not perform a log insert. Invalid data.");
            lus_write_log($log_arr);
            return;
        }

        $sql = $wpdb->prepare("INSERT INTO $this->table_name
                            (sync_timestamp, status, error_msg, runtime, logins_updated, logins_inactivated, sync_full) 
                          VALUES (%s, %s, %s, %d, %s, %s, %d)", $log_arr['sync_timestamp'], $log_arr['status'], $log_arr['error_msg'], $log_arr['runtime'], implode(',', $log_arr['logins_updated']), implode(',', $log_arr['logins_inactivated']), $log_arr['sync_full']);

        $result = $wpdb->query($sql);

        if (FALSE === $result) {
            lus_write_log("Could not perform a log insert. Fail to execute insert statment at WP Database.");
            $wpdb->print_error();
        }
    }

}

?>
