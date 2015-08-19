# Configuration
This is a partial list of configuration entries. For the complete list and description, please refer to `config.php`.

**$vnstat**
`0 = disabled`
`1 = enabled`

**$mysql_mon**
`0 = none`
`1 = mytop`
`2 = mysqlreport_a   (mysql/percona)`
`3 = mysqlreport_b   (mysql/percona, MariaDB)`

**$mysql_mon**
`0 = none`
`1 = mytop`
`2 = mysqlreport_a   (mysql/percona)`
`3 = mysqlreport_b   (mysql/percona, MariaDB)`

**Database Access**
Not needed if `$mysql_mon = 0`.

Socket has priority if defined.
`$my_socket = '/var/lib/mysql/mysql.sock';`

Then host IP if no socket. Avoid 'localhost', save a dns lookup.
`$my_host   = '127.0.0.1';`
`$my_port   = '3306';`

Only required by mytop
`$my_db     = 'mysql';`

And authentication.
`$my_user   = 'USERNAME';`
`$my_pass   = 'PASSWORD';`
`$userhome = '/USERNAME';`

**$processes**
`crond dovecot nginx master memcached monitorix mysqld php-fpm rsyslogd sshd vsftpd miniserv`
Process names that appear in a `ps -e` command output are shown as 'up'.


