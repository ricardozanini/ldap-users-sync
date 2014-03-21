# LDAP Users Sync

A simple plugin to synchronize users from a given MS Active Directory into Wordpress Database. Currently only supported on MultiSite installations.

This plugin **WILL NOT** write on your LDAP directory. It's only for Wordpress Users Database update.

This plugin **WILL NOT** perform any kind of user authentication. For this, you could use one of these excellent plugins: 

 * [WPMU Ldap Authentication](http://wordpress.org/plugins/wpmuldap/)
 * [Simple LDAP Plugin](https://wordpress.org/plugins/simple-ldap-login/)
 * [Active Directory Integration](https://wordpress.org/plugins/active-directory-integration/)

## Description

If you need users data from your LDAP directory to be on your Wordpress database, this plugin is just for you. 

Once the users' profile data being on your database, other plugin or theme could use them to display more information about users or you could perform a query on `wp_usermeta` table to address any needs. You tell.

At a given schedule this plugin will perform a search on your LDAP directory querying every user from a given base DN. Every returned user will have his/her email, first and last name plus any attribute you specified on plugin options being updated.

After the first run, the next synchronization process will update only the new users or any users who have his/her data changed since the last run.

**IMPORTANT!**

Every user who doesn't has an account will be a **subscriber** in your main site with a **random** password. The plugin assume that you use any kind of LDAP authentication (we plan this for future releases).

Users who doesn't have a valid email (`mail` attribute) will not be add.

That's it. Enjoy. :)

## Requirements
 * PHP 5 and the LDAP (http://php.net/ldap) library
 * WordPress 3.8.1 (tested up to 3.8 - but should work fine on 3.4 as well)

## Installation

### Upload

1. Download the latest tagged archive (choose the "zip" option).
2. Go to the __Plugins -> Add New__ screen and click the __Upload__ tab.
3. Upload the zipped archive directly.
4. Go to the Plugins screen and click __Activate__.

### Manual

1. Download the latest tagged archive (choose the "zip" option).
2. Unzip the archive.
3. Copy the folder to your `/wp-content/plugins/` directory.
4. Go to the Plugins screen and click __Activate__.

Check out the Codex for more information about [installing plugins manually](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

### Git

Using git, browse to your `/wp-content/plugins/` directory and clone this repository:

`git clone git@github.com:ricardozanini/ldap-users-sync.git`

Then go to your Plugins screen and click __Activate__.

## Usage

1. Once you've activated the plugin, go to your `Network Panel` and then access `Configuration > LDAP Sync Options`. 
2. Fill in all information regarding your LDAP connectivity: server URL, port, base DN, user and password for non anonymous access and so on. Test the connection and submit. 
3. Go to the `Attribute Mapping` tab and add all fields you need to be synchronized within LDAP directory. Put every field on a new line, informing the label and value on a key/pair fashion. Example: `Phone=telephoneNumber`. The value should be a valid LDAP attribute name. For LDAP attributes consult your system administrator or this [article from Microsoft](http://support.microsoft.com/kb/257203).
4. Set up the synchronization schedule. Go to the `Job Sync` tab and inform a start date and time for the job to run. You may run the job anytime you want choosing the `Run Now` option. Keep in mind that we use the WP Cron API, so we have the same limitations. Check [this article](https://codex.wordpress.org/Function_Reference/wp_schedule_event) for more information about it.
5. If you need to see the synchronization logs, go to the last tab `Logs`. There you'll see the synchronization results like users updated, time taken to job to run and so on. If you need detailed information, enable the debug log on your Wordpress installation. Every log message will be on `/wp-contents/debug.log` file.
6. Code your plugin or theme to use the new User Meta Data. Every attribute for a LDAP user will be at `wp_usermeta` table under a `lus_` prefix.

## Known Limitations

 * Supported only on Network installations.
 * LDAP connectivity only with Active Directory.
 * The job run only if users access your site at the schedule time or later.
 * No support for LDAP SSL connections.

We expect to surpass these limitations on future releases. If you want to contribute, feel free to [pull a request](https://github.com/ricardozanini/ldap-users-sync/pulls). :)

## Issues

Please log issues on the GitHub at https://github.com/ricardozanini/ldap-users-sync/issues

## ChangeLog

See [ChangeLog.md](ChangeLog.md).

## Credits

Includes [adLDAP](https://github.com/adldap/adLDAP) for AD connectivity.

Plugin structure based on [Tom McFarlin](https://github.com/tommcfarlin)'s [Wordpress Plugin Boilerplate](https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate).

Has update support using [Andy Fragen](https://github.com/afragen)'s [GitHub Updater](https://github.com/afragen/github-updater).

Views based on [WPMU Ldap Authentication](http://wordpress.org/plugins/wpmuldap/) excellent plugin.
