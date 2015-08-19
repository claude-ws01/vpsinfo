<?php
/*
 * Copyright 2006-2008 Douglas Robbins - http://www.labradordata.ca/
 * Copyright 2014-2015 Claude Nadon - https://github.com/claude-ws01/vpsinfo
*/


/* Error reporting
 *      Uncomment to get more debug info
 *      Leave commented in production
 */
// error_reporting(E_ALL);
// ini_set('display_errors', 'on');
// ini_set('error_log', '/var/log/vpsinfo.log');



/* Vnstat
 *      0 = disabled
 *      1 = enabled
 */
$vnstat = 1;


/* Mysql report:
 *      0 = none;
 *      1 = mytop;
 *      2 = mysqlreport_a   (mysql/percona)
 *      3 = mysqlreport_b   (mysql/percona, MariaDB)
 */
$mysql_mon = 3;

/* Database access:
 *      ignored if previous $mysql_mon = 0
 */
$my_socket = '/var/lib/mysql/mysql.sock';           // socket will be used in priority, if not empty.
$my_host   = '127.0.0.1';                           // host:port will be used if no socket
$my_port   = '3306';                                // prefer '127.0.0.1' to 'localhost'
$my_db     = 'mysql';                               // only for mytop
$my_user   = 'USERNAME';
$my_pass   = 'PASSWORD';

$userhome = '/USERNAME';                            // The account home directory for this mysql user:


/* Processes to monitor:
 *      - appears in page header
 *      - process name must appear in `ps -e` command
 *
 * examples: ftpd cppop cpsrvd exim named sshd ...
 *
 * List is space delimited
 */
$processes = 'crond dovecot nginx master memcached monitorix mysqld php-fpm rsyslogd sshd vsftpd miniserv';

/* Left column width
 *      Adjust if leftside boxes are too wide or too narrow
 *
 *      Untested
 */
$leftcol = 590;


/* PHP timezone
 *      - refer to php documentation for possible values
 *      - comment line to use php.ini's timezone
 *      - affects:
 *          ~ php log
 *          ~ most date/time functions called during this process
 */
date_default_timezone_set('America/New_York');
/* Server - local time offset:
 *      value in hour
 *      + or -  0..23
 *
 * Time displayed in page header
 */
$timeoffset = 0;


/* Main page auto-refresh time:
 *      value in minutes
 *      0 = disabled
 */
$page_refresh = 3;
/* Popup windows auto-refresh times:
 *      value in minutes
 *      0 = disabled
 */
$top_refresh     = 5;
$vpsstat_refresh = 5;
$netstat_refresh = 5;
$mysql_refresh   = 5;
$vnstat_refresh  = 15;


/* Bandwidth alert:
 *      When the daily data transfer is greater, it will be highlighted in red.
 *      value in MB
 */
$bw_alert = 1000;

/* gzip output compression:
 *      0 = disabled
 *      1 = enabled
*/
$gzip = 1;

/* Port list helper/reminder
 *      'Port List' button in the netstat bloc
 *      Will display this information.
 *
 * Modify it to what is more relevant to your system (and you)
 */
$port_list = array(
    '25'          => 'SMTP',
    '53'          => 'Bind nameserver',
    '80'          => 'HTTP',
    '110'         => 'POP',
    '143'         => 'IMAP',
    '443'         => 'HTTPS',
    '465'         => 'SMTPS',
    '993'         => 'IMAPS',
    '995'         => 'POPS',
    '3306'        => 'MySQL',
    '11211'       => 'memcached',
);


//CN..to keep my own config private while pushing to git
if (file_exists('_config.php')) {
    include '_config.php';
}
