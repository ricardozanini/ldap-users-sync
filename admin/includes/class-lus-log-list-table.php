<?php

require_once(dirname(__FILE__) . '/../../includes/class-lus-database.php');

if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Description of lusLogListTable
 *
 * @link http://wp.smashingmagazine.com/2011/11/03/native-admin-tables-wordpress/
 * @author r_fernandes
 */
class Lus_Log_List_Table extends WP_List_Table {

    protected static $instance = null;
    protected $table_name = null;

    public function __construct() {
        parent::__construct(array(
            'singular' => 'log', //Singular label
            'plural' => 'logs', //plural label, also this well be one of the table css class
            'ajax' => true,
        ));

        $lusdb = Lus_Database::get_instance();
        $this->table_name = $lusdb->get_table_name();

        add_thickbox();
    }

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    function ajax_user_can() {
        return current_user_can('manage_network_options');
    }

    /**
     * Define the columns that are going to be used in the table
     * @return array $columns, the array of columns to use with the table
     */
    function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />',
            'sync_timestamp' => __('Date', 'ldap-users-sync'),
            'status' => __('Status', 'ldap-users-sync'),
            'runtime' => __('Time Taken', 'ldap-users-sync'),
            'logins_updated' => __('Updated', 'ldap-users-sync'),
            'logins_inactivated' => __('Inactivated', 'ldap-users-sync'),
            'sync_full' => __('Full Sync', 'ldap-users-sync'),
        );
    }

    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'logins_updated' :
            case 'logins_inactivated' :
                $users = explode(',', $item[$column_name], -1);

                if (empty($users) || !is_array($users)) {
                    return __('No one', 'ldap-users-sync');
                } else {
                    $len = count($users);
                    $result = '';

                    for ($i = 0; $i < 5 && $i < $len; $i++) {
                        $result .= "<span>$users[$i]</span><br/>";
                    }

                    if ($len >= 5) {
                        $result .= '<strong><a class="thickbox" href="' . admin_url('admin-ajax.php') . '?&column=' . $column_name . '&action=lus_log_view_users&log_id=' . $item['id'] . '&nonce=' . wp_create_nonce('ajax_view_users') . '&amp;TB_iframe=true&amp;width=640&amp;height=440">' . __('More') . '</a></strong><br/>';
                    }

                    return $result;
                }
            default :
                return $item[$column_name];
        }
    }

    function column_cb($item) {
        return '<input type="checkbox" name="log[]" value="' . esc_attr($item['id']) . '" />';
    }

    function column_status($item) {
        switch ($item['status']) {
            case Lus_Sync_Status::OK_STATUS :
                return "<span style='color:green;font-weight:bold;'>" . $item['status'] . "</span>";
            case Lus_Sync_Status::WARN_STATUS :
                $r = "<span style='color:yellow;font-weight:bold;'>" . $item['status'] . "</span>";
                $r .= $this->status_view_action_link($item['id']);

                return $r;
            case Lus_Sync_Status::ERROR_STATUS :
                $r = "<span style='color:red;font-weight:bold;'>" . $item['status'] . "</span>";
                $r .= $this->status_view_action_link($item['id']);

                return $r;
            default :
                return $item['status'];
        }
    }

    protected function status_view_action_link($log_id) {
        $actions = array();
        $actions['view'] =
                '<a class="thickbox" href="' . admin_url('admin-ajax.php') . '?action=lus_log_view_errors&log_id=' . $log_id . '&nonce=' . wp_create_nonce('ajax_view_errors') . '&TB_iframe=true&amp;width=640&amp;height=440">' . __('View') . '</a>';
        return $this->row_actions($actions);
    }

    function column_sync_full($item) {
        if ($item['sync_full'] === TRUE) {
            return __('Yes');
        } else {
            return __('No');
        }
    }

    function column_runtime($item) {
        return $item['runtime'] . ' ' . __('seconds', 'ldap-users-sync');
    }

    function column_sync_timestamp($item) {
        $r = $item['sync_timestamp'];
        $actions = array();
        $actions['delete'] = '<a class="submitdelete" href="' . wp_nonce_url(network_admin_url('admin.php')) . '&page=' . $_GET['page'] . '&tab=' . $_GET['tab'] . '&action=delete&log_id=' . $item['id'] . '&paged=' . $this->get_pagenum() . '">' . __('Delete') . '</a>';

        $r .= $this->row_actions($actions);

        return $r;
    }

    /**
     * Decide which columns to activate the sorting functionality on
     * @return array $sortable, the array of columns that can be sorted by the user
     */
    public function get_sortable_columns() {
        return array(
            'sync_timestamp' => array('sync_timestamp', true),
            'status' => array('status', false),
        );
    }

    /**
     *
     */
    function no_items() {
        _e('No logs.', 'ldap-users-sync');
    }

    /**
     * @return array
     */
    function get_bulk_actions() {
        if (!$this->has_items())
            return array();

        $actions = array();
        $actions['delete'] = __('Delete', 'ldap-users-sync');

        return $actions;
    }

    /**
     * Prepare the table with different parameters, pagination, columns and table elements
     */
    public function prepare_items() {
        global $wpdb;

        /* -- Preparing your query -- */
        $query = "SELECT id, sync_timestamp, status, runtime, logins_updated, logins_inactivated, sync_full FROM $this->table_name";

        /* -- Ordering parameters -- */
        $orderby = !empty($_GET["orderby"]) ? mysql_real_escape_string($_GET["orderby"]) : 'sync_timestamp';
        $order = !empty($_GET["order"]) ? mysql_real_escape_string($_GET["order"]) : 'DESC';
        if (!empty($orderby) & !empty($order)) {
            $query.=' ORDER BY ' . $orderby . ' ' . $order;
        }

        /* -- Pagination parameters -- */
        //Number of elements in your table?
        $totalitems = $wpdb->query($query); //return the total number of affected rows
        //How many to display per page?
        $perpage = 10;
        //Which page is this?
        $paged = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
        //Page Number
        if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
            $paged = 1;
        }
        //How many pages do we have in total?
        $totalpages = ceil($totalitems / $perpage);
        //adjust the query to take pagination into account
        if (!empty($paged) && !empty($perpage)) {
            $offset = ($paged - 1) * $perpage;
            $query.=' LIMIT ' . (int) $offset . ',' . (int) $perpage;
        }

        /* -- Register the pagination -- */
        $this->set_pagination_args(array(
            "total_items" => $totalitems,
            "total_pages" => $totalpages,
            "per_page" => $perpage,
        ));
        //The pagination links are automatically built according to those parameters

        /* -- Register the Columns -- */
        $columns = $this->get_columns();
        $this->_column_headers = array($columns, array(), $this->get_sortable_columns());

        /* -- Fetch the items -- */
        //echo 'Quering table data for logs: ' . $query;

        $this->items = $wpdb->get_results($query, ARRAY_A);
    }

    public static function ajax_view_errors() {
        if (!current_user_can('manage_network_options')) {
            die(-1);
        }

        $nonce = $_REQUEST['nonce'];
        if (!wp_verify_nonce($nonce, 'ajax_view_errors'))
            die(-1);

        global $wpdb;

        $table_name = Lus_Database::get_table_name();
        $log_id = $_REQUEST['log_id'];

        $query = $wpdb->prepare("SELECT error_msg FROM $table_name WHERE id = %d", $log_id);

        $msg = $wpdb->get_var($query);
        echo '<style>' . file_get_contents(LUS_BASE_PATH . 'admin/assets/css/lus_thickbox.css') . '</style>';

        if ($msg) {
            echo '<div>' . nl2br($msg, true) . '</div>';
            die();
        } else {
            lus_write_log("[ERROR] Could not retrieve data.");
        }

        die(-1);
    }

    public static function ajax_view_users() {
        if (!current_user_can('manage_network_options')) {
            die(-1);
        }

        $nonce = $_REQUEST['nonce'];
        if (!wp_verify_nonce($nonce, 'ajax_view_users'))
            die(-1);

        global $wpdb;

        $table_name = Lus_Database::get_table_name();
        $log_id = $_REQUEST['log_id'];
        $column = $_REQUEST['column'];

        $query = $wpdb->prepare(
                "SELECT $column FROM $table_name WHERE id = %d", $log_id
        );

        $users = $wpdb->get_var($query);

        if ($users) {
            $users_arr = explode(',', $users);

            $query = "SELECT a.user_id, a.meta_key, a.meta_value FROM " . $wpdb->prefix . "usermeta as a inner join 
                     " . $wpdb->prefix . "users as b on (a.user_id = b.ID)
                     WHERE b.user_login = %s and a.meta_key like %s";

            echo '<style>' . file_get_contents(LUS_BASE_PATH . 'admin/assets/css/lus_thickbox.css') . '</style>';

            echo '<div>
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Data</th>
                             <tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>User</th>
                                <th>Data</th>
                            </tr>
                        </tfoot>
                        <tbody>';

            $even = 0;
            foreach ($users_arr as $user) {
                $user_data = $wpdb->get_results($wpdb->prepare($query, strtolower($user), 'lus_%'));

                if ($user_data) {
                    echo "<tr class=\"" . ($even % 2 == 0 ? '' : 'alternate') . "\"><td><a href='" . get_edit_user_link($user_data[0]->user_id) . "' target='_blank'>$user</a></td>";
                    echo '<td><ul>';
                    foreach ($user_data as $data) {
                        echo "<li>$data->meta_key=$data->meta_value</li>";
                    }
                    echo '</ul></td>';
                } else {
                    echo "<tr class=\"" . ($even % 2 == 0 ? '' : 'alternate') . "\"><td>$user</td>";
                    echo '<td><strong>' . __('No data available.', 'ldap-users-sync') . '</strong></td></tr>';
                }

                $even += 1;
            }

            echo '      </tbody>
                    </table>
                 </div>';

            die();
        }

        die(-1);
    }

}

?>
