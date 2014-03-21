<?php

//lib
require_once(dirname(__FILE__) . '/includes/class-lus-options.php');
require_once(dirname(__FILE__) . '/includes/class-lus-log-list-table.php');

class LDAP_Users_Sync_Admin {

    protected static $instance = null;
    protected $plugin_screen_hook_suffix = null;

    private function __construct() {
        $plugin = LDAP_Users_Sync::get_instance();
        $this->plugin_slug = $plugin->get_plugin_slug();

        //setup
        add_action('network_admin_menu', array(&$this, 'add_plugin_admin_menu'));
        add_action('admin_enqueue_scripts', array(&$this, 'enqueue_admin_scripts'));

        //ajax
        add_action('wp_ajax_lus_log_view_users', array('Lus_Log_List_Table', 'ajax_view_users'));
        add_action('wp_ajax_lus_log_view_errors', array('Lus_Log_List_Table', 'ajax_view_errors'));
    }

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function enqueue_admin_scripts() {
        if (!isset($this->plugin_screen_hook_suffix)) {
            return;
        }

        wp_enqueue_script('jquery-ui-datetimepicker', plugins_url('assets/js/jquery.datetimepicker.js', __FILE__), 'jquery');
        wp_enqueue_style('jquery-ui-datetimepicker', plugins_url('assets/css/jquery.datetimepicker.css', __FILE__));
    }

    public function add_plugin_admin_menu() {
        if (function_exists('add_submenu_page') && is_super_admin()) {
            // does not use add_options_page, because it is site-wide configuration,
            //  not blog-specific config, but side-wide
            $this->plugin_screen_hook_suffix = add_submenu_page('settings.php', __('LDAP Sync Options', $this->plugin_slug), __('LDAP Sync Options', $this->plugin_slug), 'manage_network', $this->plugin_slug, array($this, 'display_plugin_admin_page'));
        }
    }

    public function display_plugin_admin_page() {
        include_once('views/admin.php');
    }

}

?>
