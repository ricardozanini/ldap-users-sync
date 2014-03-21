<?php

/**
 * Displays the settings fields for the ldap connection.
 */
function lus_option_panel_connection() {
    extract(Lus_Options::get_options());
    ?>
    <form method="post" id="lus_options_panel_conn">
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="domainControllers"><?php _e('Server Address:', 'ldap-users-sync') ?></label></th>
                <td>
                    <input type='text' name='lusDomainControllers' id='domainControllers' value='<?php echo $lusDomainControllers ?>' style='width: 300px;' />
                    <br/>
                    <?php _e('The name or IP address of the LDAP server.  The protocol should be left out. (Ex. ldap.example.com). If you have more than one server, use commas. (Ex. ldap1.example.com, ldap2.example.com)', 'ldap-users-sync') ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="adPort"><?php _e('Server Port:', 'ldap-users-sync') ?></label></th>
                <td>
                    <input type='text' name='lusAdPort' id='adPort' value='<?php echo $lusAdPort ?>' style='width: 300px;' />
                    <br/>
                    <?php _e('Port Number of the LDAP server. (LDAP: Linux=389, Windows=3268) (LDAPS: Linux=636, Windows=3269). Use commas for more than one server. Same as above.', 'ldap-users-sync') ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="baseDn"><?php _e('Search DN:', 'ldap-users-sync') ?></label></th>
                <td>
                    <input type='text' name='lusBaseDn' id='baseDn' value='<?php echo $lusBaseDn; ?>' style='width: 450px;' />
                    <br/>
                    <?php _e('The base DN in which to carry out LDAP searches. (Ex. DC=example,DC=com)', 'ldap-users-sync') ?>

                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="accountSufix"><?php _e('Account Sufix:', 'ldap-users-sync') ?></label></th>
                <td>
                    <input type='text' name='lusAccountSufix' id='accountSufix' value='<?php echo $lusAccountSufix; ?>' style='width: 450px;' />
                    <br/>
                    <?php _e('The user account sufix. (Ex. @example.com)', 'ldap-users-sync') ?>

                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="adminUsername"><?php _e('Search User DN:', 'ldap-users-sync') ?></label></th>
                <td>
                    <input type='text' name='lusAdminUsername' id='adminUsername' value='<?php echo $lusAdminUsername; ?>' style='width: 450px;' />
                    <br/>
                    <?php _e('Some systems do not allow anonymous searching for attributes, and so this will set the account to use when connecting for searches.', 'ldap-users-sync') ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for='adminPassword'><?php _e('Search User Password:', 'ldap-users-sync') ?></label></th>
                <td>
                    <input type='password' name='lusAdminPassword' id='adminPassword' value='<?php echo $lusAdminPassword; ?>' />
                    <br/>
                    <?php _e('Password for the User DN above.', 'ldap-users-sync') ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Test Connection:', 'ldap-users-sync') ?></th>
                <td>
                    <input type='radio' name='lusTestConnection' id='testconnectionyes' value='1'> <label for="textconnectionyes"><?php _e('Yes') ?></label>
                    <input type='radio' name='lusTestConnection' checked='checked' id='testconnectionno' value='0'> <label for="textconnectionno"><?php _e('No') ?></label>
                    <br/>
                    <?php _e('Specify whether or not to test the ldap server connection on form submit.', 'ldap-users-sync') ?>
                </td>
            </tr>			
        </table>
        <p class="submit"><input class="button-primary" type="submit" name="lusOptionsSave" value="<?php _e('Save Options', 'ldap-users-sync') ?>" /></p>
    </form>
    <?php
}

/**
 * Displays the settings fields for the ldap attributes mapping.
 */
function lus_option_panel_attributes() {
    extract(Lus_Options::get_options());
    ?>
    <form method="post" id="lus_options_panel_attr">
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="attributeMapping"><?php _e('Attributes:', 'ldap-users-sync') ?></label></th>
                <td>
                    <textarea id="attributeMapping" name="lusAttributeMapping" style="width:300px;height:150px;"><?php echo $lusAttributeMapping ?></textarea>
                    <br/>
                    <?php _e('Inform all LDAP attributes you wish to synchronize within the network users profiles. Separate the user profile field from the LDAP attribute using an equal sign, one pair per line. (Ex. Phone=telephoneNumber)', 'ldap-users-sync') ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Capitalize Names:', 'ldap-users-sync') ?></th>
                <td>
                    <input type="hidden" name="lusCapitalizeNames" value="0" />
                    <input id="capitalizeNames" name="lusCapitalizeNames" value="1" type="checkbox" <?php echo $lusCapitalizeNames ? "checked='checked'" : "" ?> />
                    <br/>
                    <?php _e('Names should be capitalized when imported? (Ex. "JOHN DOE" or "john doe" will be replaced by "John Doe")', 'ldap-users-sync') ?>
                </td>
            </tr>
        </table>
        <p class="submit"><input class="button-primary" type="submit" name="lusOptionsSave" value="<?php _e('Save Options', 'ldap-users-sync') ?>" /></p>
    </form>

    <?php
}

/**
 * Displays the settings fields for the syncronization options.
 */
function lus_option_panel_sync() {
    extract(Lus_Options::get_options());
    ?>
    <form method="post" id="lus_options_panel_sync">
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="datepicker"><?php _e('Job schedule:', 'ldap-users-sync') ?></label></th>
                <td>
                    <input id="datetimepicker" name="lusSyncStartDateTime" onkeypress="return false;" type="text" value="<?php echo $lusSyncStartDateTime; ?>" />
                    <select id="syncRecurrence" name="lusSyncRecurrence">
                        <?php
                        $schedules = wp_get_schedules();

                        foreach ($schedules as $sched_name => $sched_data) {
                            echo '<option ' . selected($lusSyncRecurrence, $sched_name) . ' value="' . $sched_name . '">' . $sched_data['display'] . '</option>';
                        }
                        ?>
                    </select>
                    <br/>
                    <?php _e('The synchronization schedule. Use the built in calendar to choose the date and time.', 'ldap-users-sync') ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label><?php _e('Next Run on:', 'ldap-users-sync') ?></label></th>
                <td>
                    <?php
                    $timestamp = wp_next_scheduled('lus_synchronization');

                    if ($timestamp) {
                        echo get_date_from_gmt(date('Y-m-d H:i:s', $timestamp), get_option('date_format') . ' ' . get_option('time_format'));
                    } else {
                        _e('No jobs scheduled.', 'ldap-users-sync');
                    }
                    ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Perform a full Sync:', 'ldap-users-sync') ?></th>
                <td>
                    <input type='radio' name='lusPerformedFullSync' <?php checked($lusPerformedFullSync, '0'); ?> id='fullsyncyes' value='0'> <label for="fullsyncyes"><?php _e('Yes') ?></label>
                    <input type='radio' name='lusPerformedFullSync' <?php checked($lusPerformedFullSync, '1'); ?> id='fullsyncno' value='1'> <label for="fullsyncno"><?php _e('No') ?></label>
                    <br/>
                    <?php _e('Specify whether or not to perform a full synchronization on next run. The default behavior, the job will perform a full sync only on the very first time. On next run only LDAP users that have some info changed will be updated.', 'ldap-users-sync') ?>
                </td>
            </tr>	
            <tr valign="top">
                <th scope="row"><?php _e('Disable Sync:', 'ldap-users-sync') ?></th>
                <td>
                    <input type="hidden" name="lusDisableSync" value="0" />
                    <input id="disableSync" name="lusDisableSync" value="1" type="checkbox" <?php echo $lusDisableSync ? "checked='checked'" : "" ?> />
                    <br/>
                    <?php _e('Check if you want to disable the synchronization process.', 'ldap-users-sync') ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Run Now:', 'ldap-users-sync') ?></th>
                <td>
                    <input type='radio' name='lusRunNow' id='runnowyes' value='1'> <label for="runnowyes"><?php _e('Yes') ?></label>
                    <input type='radio' name='lusRunNow' checked='checked' id='runnowno' value='0'> <label for="runnowno"><?php _e('No') ?></label>
                    <br/>
                    <?php _e('Specify whether or not to run the synchronization process on form submit.', 'ldap-users-sync') ?>
                </td>
            </tr>	
        </table>
        <p class="submit"><input class="button-primary" type="submit" name="lusOptionsSave" value="<?php _e('Save Options', 'ldap-users-sync') ?>" /></p>
    </form>

    <script type="text/javascript">
                        //http://xdsoft.net/jqplugins/datetimepicker/
                        jQuery(document).ready(function($) {
                            // Check to make sure the input box exists
                            if (0 < $('#datetimepicker').length) {
                                $('#datetimepicker').datetimepicker({
                                    format: 'Y-m-d H:i:s',
                                    minDate: 0
                                });

                            } // end if

                        });

    </script>
    <?php
}

/**
 * Displays the log information regarding the syncronization process.
 */
function lus_option_panel_logs() {
    ?>
    <form method="get" id="lus_option_panel_logs">
        <input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
        <input type="hidden" name="tab" value="<?php echo $_GET['tab']; ?>" />
        <?php
        /**
         * @var Lus_Log_List_Table
         */
        $luslog_list_table = Lus_Log_List_Table::get_instance();
        $luslog_list_table->prepare_items();
        $luslog_list_table->display();
        ?>
    </form>
    <?php
}

if (isset($_GET['tab']))
    $current = $_GET['tab'];
else
    $current = 'connection';

$tabs = array(
    'connection' => __('Connection Settings', 'ldap-users-sync'),
    'attributes' => __('Attribute Mapping', 'ldap-users-sync'),
    'sync' => __('Synchronization Job', 'ldap-users-sync'),
    'logs' => __('Logs', 'ldap-users-sync'),
);

echo '<h2>' . __('Ldap Synchronization Options', 'ldap-users-sync') . '</h2>';
echo '<h2 class="nav-tab-wrapper">';
foreach ($tabs as $tab => $name) {
    $class = ( $tab == $current ) ? ' nav-tab-active' : '';
    echo "<a class='nav-tab$class' href='?page=" . $_GET['page'] . "&tab=$tab'>$name</a>";
}
echo '</h2>';

echo '<div class="wrap">';

// Process POST Updates
$lus_opts = Lus_Options::get_instance();
$lus_opts->process_updates();

if ($current === 'attributes') {
    lus_option_panel_attributes();
} elseif ($current === 'sync') {
    lus_option_panel_sync();
} elseif ($current === 'logs') {
    lus_option_panel_logs();
} else {
    lus_option_panel_connection();
}

echo '</div>';
?>