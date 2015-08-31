<?php
/*
Plugin Name: LDAP Users Sync
Plugin URI: http://github.com/ricardozanini/ldap-users-sync
Description: A simple plugin to synchronize users from a given MS Active Directory into Wordpress Database. Currently only supported on MultiSite installations.
Version: 1.1.5
Author: Ricardo Zanini
Author URI: http://github.com/ricardozanini/
License: GPLv2 or later
Network: true
GitHub Plugin URI: https://github.com/ricardozanini/ldap-users-sync
GitHub Branch:     master

License:

Copyright 2012 RICARDO ZANINI (ricardozanini@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

//TODO: figure out another way to mantain this
define("LUS_BASE_PATH", plugin_dir_path(__FILE__));
define("LUS_SYNC_JOB_NAME", 'lus_synchronization');

require_once(plugin_dir_path(__FILE__) . 'public/class-ldap-users-sync.php');

register_activation_hook(__FILE__, array('LDAP_Users_Sync', 'activate'));
register_deactivation_hook(__FILE__, array('LDAP_Users_Sync', 'deactivate'));

add_action('plugins_loaded', array('LDAP_Users_Sync', 'get_instance'));

if (is_admin()) {
    require_once( plugin_dir_path(__FILE__) . 'admin/class-ldap-users-sync-admin.php' );
    add_action('plugins_loaded', array('LDAP_Users_Sync_Admin', 'get_instance'));
}

//TODO: move it
/**
 * 
 * @link http://www.stumiller.me/sending-output-to-the-wordpress-debug-log/ Reference
 * @param $log something to output to /wp-content/debug.log file
 */
function lus_write_log($log) {
    if (true === WP_DEBUG) {
        if (is_array($log) || is_object($log)) {
            error_log('[LUS] ' . print_r($log, true));
        } else {
            error_log('[LUS] ' . $log);
        }
    }
}

?>
