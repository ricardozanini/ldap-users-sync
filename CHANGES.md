# LDAP Users Sync Releases #

### Version 1.1.5 ###

* [[69df568e76b]](https://github.com/ricardozanini/ldap-users-sync/commit/69df568e76b801921d5a0dd26cd6c78de7fb02bc) Detailed setup of `default timezone`; added more trace logs.

### Version 1.1.4 ###

* [[503b476467]](https://github.com/ricardozanini/ldap-users-sync/commit/503b4764673a3624c9597678db377538cf02770a) When adding new users, set property `ldap_login` to `true`. Required for `WPMU Ldap Authentication` plugin.

### Version 1.1.3 ###

* [[012fef3a87]](https://github.com/ricardozanini/ldap-users-sync/commit/012fef3a87bb9b7e71ae6c80408cd5c9ca546d6f) Minor bug when fetching for users on BaseDN using the `get_site_option` function.