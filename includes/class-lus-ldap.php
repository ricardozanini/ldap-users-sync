<?php

/**
 * Description of class-lus-ldap
 *
 * @author r_fernandes
 */
final class Lus_Ldap {

    public static function get_connection_settings() {
        $options = array(
            'account_suffix' => get_site_option('lusAccountSufix'),
            'base_dn' => get_site_option('lusBaseDn'),
            'domain_controllers' => explode(',', get_site_option('lusDomainControllers')),
            'admin_username' => get_site_option('lusAdminUsername'),
            'admin_password' => get_site_option('lusAdminPassword'),
            'recursive_groups' => 'false',
            'ad_port' => get_site_option('lusAdPort')
        );

        lus_write_log('LDAP Connection Options returned.');
        lus_write_log($options);

        return $options;
    }

}

?>
