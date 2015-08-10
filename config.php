<?php
// ERROR REPORTING
// error_reporting(E_ALL);
// ini_set('display_errors', 'on');
// ini_set('error_log', '/var/log/php-fpm/vpsinfo.log');

// vpsinfo by Douglas Robbins
// Email: drobbins [at] labradordata.ca
// Website: http://www.labradordata.ca/vpsinfo/

date_default_timezone_set('America/New_York');

// Mysql monitoring: 0 = none; 1 = mytop; 2 = mysqlreport
$mysql_mon = 2;

// Enable or disable vnstat. 0 = disable 1 = enable:
$vnstat = 1;

// MyTop/mysqlreport needs MySQL access to read the processlist.
// You may use any MySQL database.
// If you don't use MyTop or mysqlreport just ignore this.
$my_host   = '127.0.0.1';
$my_port   = '3306';
$my_socket = '/var/lib/mysql/mysql.sock';
$my_db     = 'mysql';
$my_user   = 'username';
$my_pass   = 'password';

// The account home directory for this mysql user:
$userhome = '/root';

// Processes to monitor. Include any process that should normally appear in the
// COMMAND column of the 'top' output. You can match a partial name, eg. 'ftpd'
// matches 'pure-ftpd' or 'proftpd'. Possible additions include: 'cppop',
// 'cpsrvd', 'exim', 'named'. This is a space-delimited list:
$processes = 'crond dovecot nginx master memcached monitorix mysqld opendkim perl-fcgi php-fpm postfwd postgrey rsyslogd sshd lsyncd uvncrepeater vsftpd miniserv';

// Width of the left column in page display. You can adjust this if the
// leftside boxes are too wide or too narrow:
$leftcol = 590;

// Difference in hours between your local time and server time:
$timeoffset = 0;

// Auto-refresh of the main page.
// Set to 0 to disable, or specify a number of minutes:
$refresh = 3;

// Auto-refresh of command windows.
// Set to 0 to disable, or specify a number of minutes:
$top_refresh     = 5;
$vpsstat_refresh = 5;
$netstat_refresh = 5;
$mysql_refresh   = 5;
$vnstat_refresh  = 15;

// Bandwidth alert. When the daily data transfer is greater than this, it is
// highlighted in red. In MB:
$bw_alert = 1000;

// Enable gzip compression for page output. 0 = disabled  1 = enabled
$gzip = 1;

if (file_exists('_config.php')) {
    include '_config.php';
}
