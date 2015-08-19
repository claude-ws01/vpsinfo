<?php
$version = 'v2.3.9 (2015 August 19)';
/*
 * Copyright 2006-2008 Douglas T. Robbins - http://www.labradordata.ca/
 * Copyright 2014-2015 Claude Nadon - https://github.com/claude-ws01/vpsinfo

    This script is intended as a general information/monitoring page for a Linux
    Virtuozza or OpenVZ VPS (Virtual Private Server). It also runs fine on a
    dedicated Linux machine or on KVM's.


    ACKNOWLEDGEMENTS:
        Original vpsinfo was developped by Douglas Robbins at labradordata.ca

        This script may utilize third party software, if installed:

            * MyTop by Jeremy D. Zawodny, GNU General Public License.
                http://jeremy.zawodny.com/mysql/mytop/

            * mysqlreport (a) by Daniel Nichter
                http://hackmysql.com/mysqlreport

            * mysqlreport (b) by Jean Weisbuch
                https://github.com/jb-boin/mariadb/blob/5.5.30/debian/additions/mysqlreport

            * vnstat by Teemu Toivola, GNU General Public License.
                http://humdi.net/vnstat/


    TERMS & CONDITIONS:
    This script is an original work by Douglas T. Robbins;

    An acknowledgement of the original vpsinfo in your release would be appreciated.

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    The GNU General Public License is available at:
    http://www.gnu.org/copyleft/gpl.html

*/

require 'config.php';


header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

$mtime  = explode(' ', microtime());
$tstart = $mtime[0] + $mtime[1];
global $scriptname;
$scriptname = $_SERVER['SCRIPT_NAME'];
$timestamp  = time() + ($timeoffset * 3600);
$localtime  = date('g:i a, M j', $timestamp);
$shorttime  = date('g:i a', $timestamp);

/*
 * Shell commands for main windows display ------------------------------------
 *
*/
$netstat_com = 'netstat -nt';
$vnstat_com  = 'vnstat';
$top_com     = 'top -n 1 -b';
$pstree_com  = 'env LANG=C pstree -c';
$df_com      = 'df -h --exclude-type=tmpfs';
$tmp_com     = 'ls -a --ignore=sess_* /tmp';
$mysql_rpt_name = '';

if ($mysql_mon === 1) { // mytop
    $mysql_com = "env HOME=$userhome env TERM=xterm mytop -u $my_user -p $my_pass -d $my_db -b --nocolor";
}
elseif ($mysql_mon > 1) { // mysqlreport
    $mysql_rpt_name = $mysql_mon === 2 ? 'mysqlreport_a.pl' : 'mysqlreport_b.pl';
    $mysql_com  = "./{$mysql_rpt_name} --host $my_host --socket $my_socket --port $my_port --user $my_user --password $my_pass --no-mycnf 2>&1";
    $mysql_com2 = "./{$mysql_rpt_name} --all --tab --host $my_host --socket $my_socket --port $my_port --user $my_user --password $my_pass --no-mycnf";
}
$allps_com = "ps -e | awk '{ print $4;}' | uniq";


// GET and POST requests to this page -----------------------------------------

// 'Sample current traffic' (vnstat):

if (array_key_exists('traffic', $_GET) && $_GET['traffic']) {
    $io = trim(`vnstat -tr | grep --after-context=3 Traffic`);
    echo "<html>\n<body bgcolor='#000000' text='#CCCCCC' style='margin:10px 0 0 4px;padding:0'>\n<pre style='font-family:vt7X13,\"Courier New\",monospace;font-size:11px;line-height:14px'>$io</pre>\n</body>\n</html>";
    exit;
}

// 'Ports List':
if (array_key_exists('showports', $_GET) && $_GET['showports']) {
    $p_len     = 12;
    $p_str     = str_pad('Port', $p_len, ' ', STR_PAD_LEFT);
    $port_text = substr($p_str, strlen($p_str) - $p_len) . "    What is it?\n";
    $port_text .= str_repeat('-', $p_len) . '    ' . str_repeat('-', $p_len * 2) . "\n";

    foreach ($port_list as $port => $desc) {
        $p_str = str_pad($port, $p_len, ' ', STR_PAD_LEFT);
        $port_text .= substr($p_str, strlen($p_str) - $p_len) . '    ' . $desc . "\n";
    }
    echo
    "<html>
        <head></head>
        <body style='background-color:#000;color:#ccc;margin:10px 0 0 30px;padding:0;'>
            <pre style='font-family:vt7X13,\"Courier New\",monospace;font-size:11px;line-height:14px;'>$port_text</pre>
        </body>
    </html>";
    exit;
}

// Show logged-in shell users:

if (array_key_exists('users', $_GET) && $_GET['users']) {
    $users = trim(`w`);
    echo "<html>\n<body bgcolor='#000000' text='#CCCCCC' style='margin:10px 0 0 6px;padding:0'>\n<pre style='font-family:vt7X13,\"Courier New\",monospace;font-size:11px;line-height:14px'>Logged-in Users\n---------------\n$users</pre>\n</body>\n</html>";
    exit;
}

// Whois lookup:
$whois = null;
if (array_key_exists('whois', $_REQUEST) && $_REQUEST['whois']) {
    $whois = escapeshellcmd(trim($_REQUEST['whois']));
}
if ($whois) {
    $whois  = preg_replace('/[^a-z0-9-.]/', '', $whois);
    $lookup = `whois $whois`;
    echo "<html>\n<body bgcolor='#000000' text='#CCCCCC' style='margin:10px 0 0 30px;padding:0'>\n<pre style='font-family:vt7X13,\"Courier New\",monospace;font-size:11px;line-height:14px'>$lookup</pre>\n</body>\n</html>";
    exit;
}

// ls -al /tmp:

if (array_key_exists('lsal', $_GET) && $_GET['lsal']) {
    $lsout = "Command: ls -al /tmp\n\n";
    $lsout .= `ls -al /tmp`;
    echo "<html>\n<body bgcolor='#000000' text='#CCCCCC' style='margin:10px 0 0 6px;padding:0'>\n<pre style='font-family:vt7X13,\"Courier New\",monospace;font-size:11px;line-height:14px'>$lsout</pre>\n</body>\n</html>";
    exit;
}

// ps -aux (and mem):

if (array_key_exists('psaux', $_GET) && $_GET['psaux']) {
    $psout = "Command: ps -aux\n\n";
    $psout .= `ps -aux`;
    $psout = str_replace('<', '&lt;', $psout);
    echo "<html>\n<body bgcolor='#000000'
            text='#CCCCCC'
            style='margin:10px 0 0 6px;padding:0'>\n
            <pre style='font-family:vt7X13,\"Courier New\",monospace;font-size:11px;line-height:14px'>$psout</pre>\n
            </body>\n
            </html>";
    exit;
}

if (array_key_exists('psmem', $_GET) && $_GET['psmem']) {
    $psout = "Command: ps -auxh --sort=size | tac\n\n";
    $psout .= "USER       PID %CPU %MEM   VSZ  RSS TTY      STAT START   TIME COMMAND\n";
    $psout .= `ps -auxh --sort=size | tac`;
    $psout = str_replace('<', '&lt;', $psout);
    echo "<html>\n<body bgcolor='#000000' text='#CCCCCC' style='margin:10px 0 0 6px;padding:0'>\n<pre style='font-family:vt7X13,\"Courier New\",monospace;font-size:11px;line-height:14px'>$psout</pre>\n</body>\n</html>";
    exit;
}

// Command windows:

if (array_key_exists('cmd', $_GET) && $_GET['cmd']) {
    $out   = null;
    $title = null;
    $cmd   = $_GET['cmd'];
    if ($cmd === 'top') {
        $out = trim(`top -n 1 -b`);
        $out = top_highlite($out);

        $meta = "<meta http-equiv=\"refresh\" content=\"" . ($top_refresh * 60) . "\">";
    }
    elseif ($cmd === 'vpsstat') {
        list($out, $opages, $ppages) = vpsstat();
        $meta = "<meta http-equiv=\"refresh\" content=\"" . ($vpsstat_refresh * 60) . "\">";
    }
    elseif ($cmd === 'netstat') {
        $out     = netstat($netstat_com);
        $meta    = "<meta http-equiv=\"refresh\" content=\"" . ($netstat_refresh * 60) . "\">";
        $buttons = "<input type='button' value='Listening' onClick=\"window.location.replace('$scriptname?cmd=netstat2');\" class='button' title='show listening ports'>\n";
        $title   = 'netstat -nt (TCP connections)';
    }
    elseif ($cmd === 'netstat2') {
        $out     = trim(`netstat -ntl`);
        $meta    = "<meta http-equiv=\"refresh\" content=\"" . ($netstat_refresh * 60) . "\">";
        $buttons = "<input type='button' value='Active' onClick=\"window.location.replace('$scriptname?cmd=netstat');\" class='button' title='show active connections'>\n";
        $title   = 'netstat -ntl (listening TCP ports)';
    }
    elseif ($cmd === 'mytop') {
        $out  = trim(`$mysql_com`);
        $meta = "<meta http-equiv=\"refresh\" content=\"" . ($mysql_refresh * 60) . "\">";
    }
    elseif ($cmd === 'mysqlreport') {
        $out  = trim(`$mysql_com2`);
        $out  = str_replace('_', '-', $out);
        $meta = "<meta http-equiv=\"refresh\" content=\"" . ($mysql_refresh * 60) . "\">";
    }
    elseif ($cmd === 'vnstat') {
        $out  = trim(`vnstat`);
        $meta = "<meta http-equiv=\"refresh\" content=\"" . ($vnstat_refresh * 60) . "\">";
    }
    elseif ($cmd === 'vnstat2') {
        $out   = trim(`vnstat -d`);
        $meta  = "<meta http-equiv=\"refresh\" content=\"" . ($vnstat_refresh * 60) . "\">";
        $title = 'vnstat -d';
    }
    elseif ($cmd === 'vnstat3') {
        $out   = trim(`vnstat -m`);
        $meta  = "<meta http-equiv=\"refresh\" content=\"" . ($vnstat_refresh * 60) . "\">";
        $title = 'vnstat -m';
    }
    elseif ($cmd === 'vnstat4') {
        $out   = trim(`vnstat -tr | grep --after-context=3 Traffic`);
        $meta  = '';
        $title = 'vnstat -tr';
    }

    $buttons = "<input type='button' value='Reload' onClick='window.location.reload();' class='button' title='reload $cmd'> <input type='button' value='Close' onClick='window.close();' class='button' title='close window'>";
    if (stristr($cmd, 'vnstat')) {
        $buttons = "<input type='button' value='Sample' onClick=\"window.location.replace('$scriptname?cmd=vnstat4');\" class='button' title='sample current traffic - 5 second delay'>
                    <input type='button' value='Today' onClick=\"window.location.replace('$scriptname?cmd=vnstat');\" class='button' title='today & yesterday'>
                    <input type='button' value='Days' onClick=\"window.location.replace('$scriptname?cmd=vnstat2');\" class='button' title='daily totals'>
                    <input type='button' value='Months' onClick=\"window.location.replace('$scriptname?cmd=vnstat3');\" class='button' title='monthly totals'>
                    <input type='button' value='Close' onClick='window.close();' class='button' title='close window'>";
    }

    if ( ! $title) {
        $title = $cmd;
    }
    poppage($cmd, $out, $meta, $shorttime, $buttons, $title);
    exit;
}

// Run the commands now (except vnstat & mysql) -------------------------------

$top      = trim(`$top_com`);
$hostname = trim(`hostname`);
$netstat  = netstat($netstat_com);
$pstree   = trim(`$pstree_com`);
$df_full  = trim(`$df_com`);
$tmp_full = trim(`$tmp_com`);
$allps    = trim(`$allps_com`);

// Clean up / prep output -----------------------------------------------------

$netstat  = preg_replace("/ {1,99}\n/", "\n", $netstat);
$tmp_full = preg_replace('/ {1,99}/', "\n", $tmp_full);

// df - Disk Usage:

$lines    = explode("\n", $df_full);
$prev     = null;
$allfs    = '';
$nb_lines = count($lines);
for ($i = 0; $i < $nb_lines; $i ++) {
    $line  = preg_replace('/ {1,99}/', '|', $lines[$i]);
    $parts = explode('|', $line);
    if ($parts[0] !== $prev) {
        $mnt    = $parts[5];
        $actual = " ($parts[2])";
        if ( ! stristr($line, 'Filesystem')) {
            $per = substr($parts[4], 0, - 1);
            if ($per > 90) {
                $allfs .= "<span class='warn'>$mnt $parts[4]$actual</span>,&nbsp;";
            }
            else {
                $allfs .= "$mnt $parts[4]$actual,&nbsp;";
            }
        }
    }
    $prev = $parts[0];;
}
if (substr($allfs, - 7) === ',&nbsp;') {
    $allfs = substr($allfs, 0, - 7);
}

// Other summary stats:

$num_mysql = substr_count($pstree, 'mysqld');

$httpd_title = 'number of apache processes and threads';
$httpd_label = 'apache thds';
$num_httpd = substr_count($pstree, 'httpd');
if ($num_httpd === 0) {
    $httpd_title = 'number of nginx processes and threads';
    $httpd_label = 'nginx thds';
    $num_httpd = substr_count($pstree, 'nginx');
}
$num_tcp   = substr_count($netstat, 'tcp');

//Main page buttons:

// Box buttons to command windows:
$topcmdlink = "<a href='$scriptname?cmd=top' onClick=\"window.open('$scriptname?cmd=top', 'top', 'width=600, height=480, resizable'); return false\" title='open a top window' class='open'>&nbsp;+&nbsp;</a>";
$vpscmdlink = "<a href='$scriptname?cmd=vpsstat' onClick=\"window.open('$scriptname?cmd=vpsstat', 'vpsstat', 'width=540, height=180, resizable'); return false\" title='open a vpsstat window' class='open'>&nbsp;+&nbsp;</a>";
$netcmdlink = "<a href='$scriptname?cmd=netstat' onClick=\"window.open('$scriptname?cmd=netstat', 'netstat', 'width=600, height=480, resizable'); return false\" title='open a netstat window' class='open'>&nbsp;+&nbsp;</a>";

if ($mysql_mon === 1) {
    $mycmdlink = "<a href='$scriptname?cmd=mytop' onClick=\"window.open('$scriptname?cmd=mytop', 'mytop', 'width=600, height=345, resizable'); return false\" title='open a mytop window' class='open'>&nbsp;+&nbsp;</a>";
}
elseif ($mysql_mon > 1) {
    $mycmdlink = "<a href='$scriptname?cmd=mysqlreport' onClick=\"window.open('$scriptname?cmd=mysqlreport', 'mysqlreport', 'width=600, height=480, resizable'); return false\" title='open a mysqlreport window' class='open'>&nbsp;+&nbsp;</a>";
}

$vncmdlink = "<a href='$scriptname?cmd=vnstat' onClick=\"window.open('$scriptname?cmd=vnstat', 'vnstat', 'width=525, height=345, resizable'); return false\" title='open a vnstat window' class='open'>&nbsp;+&nbsp;</a>";

// Button for 'ls -al /tmp':
$lsal = "<input type='button' value='ls -al /tmp' onClick=\"window.open('$scriptname?lsal=1', 'lsal', 'width=730, height=400, scrollbars, resizable'); return false\" title='show detailed list' class='button' style='width:75px'>\n";

// Button for 'ps -aux':
$psaux = "<input type='button' value='ps -aux' onClick=\"window.open('$scriptname?psaux=1', 'psaux', 'width=730, height=480, scrollbars, resizable'); return false\" title='show process list' class='button' style='width:85px;'>\n";

// Button for 'ps -aux --sort=size | tac' :)
$psmem = "<input type='button' value='ps -aux (mem)' onClick=\"window.open('$scriptname?psmem=1', 'psmem', 'width=730, height=480, scrollbars, resizable'); return false\" title='show process list, sorted by memory usage' class='button' style='width:85px;'>\n";

// Buttons for vnstat:
$vn_sampl = "<input type='button' value='Sample' onClick=\"window.open('$scriptname?cmd=vnstat4', 'vnstat', 'width=525, height=380, resizable'); return false\" class='button' title='sample current traffic - 5 second delay'>";
$vn_days  = "<input type='button' value='Days'   onClick=\"window.open('$scriptname?cmd=vnstat2', 'vnstat', 'width=525, height=380, resizable'); return false\" class='button' title='show daily totals'>";
$vn_mons  = "<input type='button' value='Months' onClick=\"window.open('$scriptname?cmd=vnstat3', 'vnstat', 'width=525, height=380, resizable'); return false\" class='button' title='show monthly totals'>";

// Buttons for netstat:
$netstat_ntl = "<input type='button' value='Listening' onClick=\"window.open('$scriptname?cmd=netstat2', 'netstat', 'width=600, height=480, resizable'); return false\" class='button' title='show listening ports'>";
$portslink   = "<input type='button' value='Port List' onClick=\"window.open('$scriptname?showports=1', 'portlist', 'width=400, height=330'); return false\" class='button' title='show explanatory list of ports'>";

// Button for mysqlreport:
$mysqlrep_det = "<input type='button' value='Full Report' onClick=\"window.open('$scriptname?cmd=mysqlreport', 'mysqlreport', 'width=600, height=480, resizable'); return false\" class='button' title='show detailed mysqlreport'>";

// Auto-refresh meta tag:

if ($page_refresh) {
    if ($page_refresh < 1) {
        $page_refresh = 1;
    }
    $page_refresh *= 60;
    $meta_refresh = "<meta http-equiv=\"refresh\" content=\"$page_refresh\">\n";
}

// Load bar indicators:

$pattern = "/^.*\b(average)\b.*$/mi";
preg_match($pattern, $top, $hits);
$loadline = $hits[0];

$load_bits   = explode('average:', $loadline);
$load_parts  = explode(',', $load_bits[1]);
$load1       = trim($load_parts[0]);
$loadlabel1  = $load1;
$load5       = trim($load_parts[1]);
$loadlabel5  = $load5;
$load15      = trim($load_parts[2]);
$loadlabel15 = $load15;

if ($load1 > 10) {
    $load1 = 10;
}
if ($load5 > 10) {
    $load5 = 10;
}
if ($load15 > 10) {
    $load15 = 10;
}

if ($load1 > 1) {
    $load1_width = round(($load1 - 1) * 22.22);
    $bgcolor1    = '#82826E';
    $fgcolor1    = '#CC0000';
}
else {
    $load1_width = round($load1 * 200);
    $bgcolor1    = '#222222';
    $fgcolor1    = '#82826E';
}
if ($load5 > 1) {
    $load5_width = round(($load5 - 1) * 22.22);
    $bgcolor5    = '#82826E';
    $fgcolor5    = '#CC0000';
}
else {
    $load5_width = round($load5 * 200);
    $bgcolor5    = '#222222';
    $fgcolor5    = '#82826E';
}
if ($load15 > 1) {
    $load15_width = round(($load15 - 1) * 22.22);
    $bgcolor15    = '#82826E';
    $fgcolor15    = '#CC0000';
}
else {
    $load15_width = round($load15 * 200);
    $bgcolor15    = '#222222';
    $fgcolor15    = '#82826E';
}

$top = top_highlite($top);


// Mytop/mysqlreport and vnstat ------------------------------------------------
// Run or produce a useful message if not installed.
$my_parts   = null;
$mysql_head = '';
if ($mysql_mon === 1) {
    exec('which mytop', $output, $return);
    $mysql = '';

    if ($return === 0) {
        $mysql      = "\n\nMytop is not installed. See the <a href='http://jeremy.zawodny.com/mysql/mytop/'>mytop website</a> for information.\n\n";
        $mycmdlink  = '';
        $mysql_head = '';
    }
    elseif ($return === 1) {
        $mysql   = trim(`$mysql_com`);
        $pattern = "/^.*\bQueries\b.*$/mi";
        preg_match($pattern, $mysql, $hits);
        $queryline = trim($hits[0]);
        $my_parts  = explode(' ', $queryline);
    }
    $mysql_div = "<div class='subleftcmd'>$mycmdlink</div><div class='subleft'>mytop</div><div class='left'><pre>$mysql</pre></div>\n";
}
elseif ($mysql_mon > 1) {
    $full_report = '';

    if (file_exists($mysql_rpt_name)) {
        clearstatcache();

        if (is_executable($mysql_rpt_name)) {

            $mysql = trim(`$mysql_com`);

            if (stristr($mysql, 'uptime')) {
                // Get total queries for topbar display
                $parts    = explode("_\n", $mysql);
                $parts    = explode("\n", $parts[2]);
                $qline    = preg_replace('/ {1,99}/', '|', $parts[0]);
                $my_parts = explode('|', $qline);
                // The 'Full report' button
                $full_report = "\n<div class='toolbar'>$mysqlrep_det</div>";
                // Change underscores to dashes for readability
                $mysql = str_replace('_', '-', $mysql);
            }

            elseif (stristr($mysql, 'Access denied for user')) {
                $mysql     = "\n\nThe {$mysql_rpt_name} script was denied access to mysql. Check that the mysql username &amp; password (in the vpsinfo configuration) are valid.\n\n";
                $mycmdlink = '';
            }

            elseif (stristr($mysql, 'bad interpreter')) {
                $mysql     = "\n\nThe {$mysql_rpt_name} script encountered a problem -- the first line does not have the correct path for perl on your system.\n\n";
                $mycmdlink = '';
            }

            else {
                $mysql     = "\n\nAn unknown error occurred with the {$mysql_rpt_name} script.\n\n";
                $mycmdlink = '';
            }
        }

        else {
            $mysql     = "\n\nThe {$mysql_rpt_name} script could not be executed. Check the file ownership &amp; permissions.\n\n";
            $mycmdlink = '';
        }
    }

    else {
        $mysql     = "\n\nThe {$mysql_rpt_name} script was not found.\n
You need to get it from <a href='https://github.com/claude-ws01/vpsinfo'>https://github.com/claude-ws01/vpsinfo</a>, store it in the same
directory as vpsinfo, and set correct ownership &amp; permissions.\n\n";
        $mycmdlink = '';
    }

    $mysql_div =
        "<div class='subleftcmd'>$mycmdlink</div>
        <div class='subleft'>MySQL Report <span style=\"font-weight:normal;font-size: 0.85em;color: #aaa;\">({$mysql_rpt_name})</span></div>
        <div class='left'><pre>$mysql</pre></div>$full_report\n";
}

if ($my_parts) {

    $mysql_queries = 0;

    if (is_numeric($my_parts[1])) {
        $mysql_queries = round($my_parts[1]);
        $mysql_units   = '';
    }
    else {
        $mysql_units = strtoupper(substr($my_parts[1], - 1));
        if ($mysql_units === 'M') {
            $mysql_units = 'MB';
            $mysql_queries = round(substr($my_parts[1], 0, - 1), 2);
        }
        elseif ($mysql_units === 'K') {
            $mysql_units = 'KB';
            $mysql_queries = round(substr($my_parts[1], 0, - 1));
        }
    }
    $mysql_head =
        "<td valign='top' nowrap>
            <div class='head_label' style='padding-right:5px' title='number of mysql queries'>mysql queries</div>
            <div class='head_num2' style='padding-right:5px'>$mysql_queries<span class='head_units'> $mysql_units</span></div>
        </td>";
}

$vnstat_div  = '';
$vnstat_head = '';
if ($vnstat) {
    exec('which vnstat', $output, $return);

    if ($return === 0) {
        $vnstat     = "\n\nVnstat is not installed. See the <a href='http://humdi.net/vnstat/'>vnstat website</a> for information.\n\n";
        $vncmdlink  = '';
        $vn_sampl   = '';
        $vn_days    = '';
        $vn_mons    = '';
        $vnstat_div = "<div class='subleft'>vnstat</div><div class='left'><pre>$vnstat</pre></div>";
    }
    elseif ($return === 1) {
        $vnstat        = trim(`$vnstat_com`);
        $today_pattern = '/^.*\btoday\b.*$/mi';
        $today_mb      = 0;
        //vnstat may not display 'today' because it stopped gathering info.
        if (preg_match_all($today_pattern, $vnstat, $hits) > 0) {
            $todayline = $hits[0][0];
            $today     = explode('|', $todayline);
            $today_mb  = str_replace('MB', '', $today[2]);
            $today_mb  = trim($today_mb);
        }

        if (stristr($today_mb, ',')) {
            $today_mb = str_replace(',', '', $today_mb);
        }
        $today_mb = round($today_mb);

        if ($today_mb > 999) {
            $bw_today = round(($today_mb / 1024), 1);
            $bw_units = 'GB';
        }
        else {
            $bw_today = $today_mb;
            $bw_units = 'MB';
        }

        if ($today_mb > $bw_alert) {
            $bw_today = "<span class='warn'>$bw_today</span>";
        }

        $vnstat_head =
            "<td valign='top' nowrap>
                <div class='head_label' title='amount of data transferred today'>transfer today</div>
                <div class='head_num'>$bw_today<span class='head_units'> $bw_units</span></div>
            </td>";

        $vnstat_div  =
            "<div class='subleftcmd'>$vncmdlink</div>
            <div class='subleft'>vnstat</div>
            <div class='leftscroll'><pre>$vnstat</pre></div>
            <div class='toolbar'>$vn_sampl $vn_days $vn_mons</div>";
    }
}
// vpsstat-like processing of user_beancounters or RAM & swap -----------------

list($vpsstat, $mem1, $mem1_units, $mem1_label, $mem1_tip, $mem2, $mem2_units, $mem2_label, $mem2_tip) = vpsstat();
$vpsstat_div = '';
if ($vpsstat) {
    $vpsstat_div =
        "<div class='subleftcmd'>$vpscmdlink</div>
        <div class='subleft'>vpsstat <span style=\"font-weight:normal;font-size: 0.85em;color: #aaa;\">(/proc/bean_counters)</span></div>
        <div class='left'><pre>$vpsstat</pre></div>\n";
}

// Process/daemon monitor -----------------------------------------------------

$allprocs  = explode(' ', $processes);
$tcpstatus = '';
foreach ($allprocs as $proc) {
    $proc = trim($proc);
    if (stristr($allps, $proc)) {
        $tcpstatus .= "<span class='servup' title='$proc is up'>&nbsp;$proc&nbsp;</span>&nbsp;";
    }
    else {
        $tcpstatus .= "<span class='servdown' title='$proc is down!'>&nbsp;$proc&nbsp;</span>&nbsp;";
    }
}

// FUNCTIONS ===================================================================

function top_highlite($top)
{
    global $scriptname;

    if ( ! stristr($top, '0 users,')) {
        $top = preg_replace('/(user|users),/', "<a href='$scriptname?users=1' onClick=\"window.open('$scriptname?users=1', 'users', 'width=625, height=300, scrollbars'); return false\" title='show users'>$1</a>,", $top);
    }
    else {
        $top = preg_replace('/(user|users),/', '<span class="top_highlite">$1</span>', $top);

    }

        //$out = preg_replace('/(^top | up | days,|load average:|Tasks:|total,|running,|sleeping,|stopped,|zombie|Cpu\(s\):|us,|sy,|ni,|id,|wa,|hi,|si,|st|Mem:|used,|free,|buffers|Swap:| cached)/', '<span class="top_highlite">$1</span>', $out);

    $top = preg_replace('/(^top | up | days,|load average:|Tasks:|total,|running,|sleeping,|stopped,|zombie|Cpu\(s\):|Mem:|used,|free,|buffers|Swap:| cached)/', "<span class=\"top_highlite\">$1</span>", $top);
    $top = preg_replace('/\%(us,|sy,|ni,|id,|wa,|hi,|si,|st)/', "%<span class=\"top_highlite\">$1</span>", $top);
    return $top;
}
function netstat($netstat_com) {
    global $scriptname;
    $out      = trim(`$netstat_com`);
    $out      = str_replace(' Address', '_Address', $out);
    $lines    = explode("\n", $out);
    $all      = '';

    $first_line = true;
    foreach ($lines as $line) {
        if (!$first_line) {
            $line   = preg_replace('/ {1,99}/', '|', $line);
            $line   = str_replace('::ffff:', '', $line);
            $parts  = explode('|', $line);
            $col_0  = str_pad($parts[0], 5, ' ', STR_PAD_RIGHT);
            $col_1  = str_pad($parts[1], 6, ' ', STR_PAD_LEFT);
            $col_2  = str_pad($parts[2], 6, ' ', STR_PAD_LEFT);
            $col_3  = str_pad($parts[3], 23, ' ', STR_PAD_RIGHT);
            $ip_str = null;
            if (stristr($parts[4], ':')) {
                $col_4_parts = explode(':', $parts[4]);
                $ip_str      = $col_4_parts[0];
            }
            $col_4 = str_pad($parts[4], 23, ' ', STR_PAD_RIGHT);
            if (!preg_match('/(127\.0\.0\.1|0\.0\.0\.0)/',$ip_str)) {
                $link  = "<a href='$scriptname?whois=$ip_str' onClick=\"window.open('$scriptname?whois=$ip_str', 'netstat', 'width=650, height=350, resizable, scrollbars'); return false\" title='whois $ip_str'>$ip_str</a>";
                $col_4 = str_replace($ip_str, $link, $col_4);
            }
            $col_5 = $parts[5];
            $cols  = $col_0 . ' ' . $col_1 . ' ' . $col_2 . ' ' . $col_3 . ' ' . $col_4 . ' ' . $col_5;
        }
        else {
            $first_line = false;
            $cols = $line;
        }
        $all .= "\n" . $cols;
    }
    $all = str_replace('_Address', ' Address', $all);

    return $all;
}

function poppage($cmd, $out, $meta, $shorttime, $buttons, $title) {
    echo "
<!DOCTYPE HTML>
<html>
<head>
    <title>$cmd</title>
    $meta
    <style type='text/css'>
        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        body {
            background-color: #000000;
            color: #CCCCCC;
            margin: 0;
            padding: 0;
        }

        #scroll {
            clear: both;
            overflow: auto;
            border: none;
            margin: 0;
            padding: 0;
            overflow-X: visible;
            scrollbar-face-color: #666666;
            scrollbar-track-color: #999999;
            scrollbar-3dlight-color: #999999;
            scrollbar-highlight-color: #666666;
        }

        pre {
            font-family: vt7X13, \"Courier New\", Courier, monospace;
            font-size: 11px;
            line-height: 14px;
            padding: 5px 5px 10px 6px;
            margin: 0;
        }

        div.title {
            float: left;
            font-family: Verdana, Arial, Helvetica, sans-serif;
            background-color: #333333;
            color: #DDDDDD;
            font-size: 13px;
            font-weight: bold;
            padding: 4px 0 2px 6px;
        }

        div.commands {
            font-family: Verdana, Arial, Helvetica, sans-serif;
            background-color: #333333;
            text-align: right;
            font-size: 13px;
            padding: 4px 10px 5px 0;
            border-bottom: 1px solid #666666;
        }

        .button {
            width: 60px;
            font-size: 11px;
            border: 1px solid #999999;
            background-color: #666666;
            color: #FFFFFF;
        }

        a:link, a:visited, a:active {
            color: #BBBB00;
            text-decoration: underline;
        }
        .top_highlite {
            color: #00BBAE;
        }
    </style>
    <script  type=\"text/javascript\">
        function fullHgt() {
            if (document.getElementById('scroll')) {
                var hgt = document.body.clientHeight - 27;
                document.getElementById('scroll').style.height = hgt + 'px';
            }
        }
    </script>

</head>
<body onload='fullHgt()' onresize='fullHgt()'>
<div class='title'>$title @ $shorttime</div>
<div class='commands'>$buttons</div>
<div id='scroll'>
    <pre>$out</pre>
</div>

</body>
\n
</html>";
}

function vpsstat() {
    $vpsstat    = null;
    $mem1       = null;
    $mem1_units = null;
    $mem1_label = null;
    $mem1_tip   = null;
    $mem2       = null;
    $mem2_units = null;
    $mem2_label = null;
    $mem2_tip   = null;

    $rawbeans     = `/bin/beanc 2> /dev/null`;
    $beans_exists = file_exists('/proc/user_beancounters');

    if ( ! $rawbeans && $beans_exists) {
        $rawbeans = `cat /proc/user_beancounters 2> /dev/null`;
    }

    if ($rawbeans) {
        $mb_per_page = shell_exec('getconf PAGESIZE') / (1024 * 1024);
        $lines       = explode("\n", $rawbeans);
        $beans       = '';

        foreach ($lines as $line) {
            if (preg_match('/oomg|privv|numpr|numt|numo|numfi/', $line)) {

                $line       = preg_replace('/ {1,99}/', '|', $line);
                $line_parts = explode('|', $line);

                $cur = $cur_mb = $line_parts[2];
                $max = $max_mb = $line_parts[3];
                $bar = $bar_mb = $line_parts[4];
                $lim = $lim_mb = $line_parts[5];


                $is_oomg = (bool) stristr($line, 'oomg');
                if ($is_oomg || stristr($line, 'privv')) {

                    $cur_mb = round($cur * $mb_per_page, 1) . ' MB';
                    $max_mb = round($max * $mb_per_page, 1) . ' MB';
                    $bar_mb = round($bar * $mb_per_page) . ' MB';

                    if ($is_oomg) {
                        $lim_mb = 'n/a';
                        $mem1   = round($cur * $mb_per_page);
                        if ($cur > $bar) {
                            $mem1 = "<span class='warn'>$cur_mb</span>";
                        }
                        $mem1_label = 'oomguarpages';
                        $oomg_per   = round(($cur / $bar) * 100);
                        $mem1_tip   = "title='oomguarpages is guaranteed memory; you are using $oomg_per% of your quota'";
                        $mem1_units = 'MB';
                    }

                    else {
                        $lim_mb     = round($lim * $mb_per_page) . ' MB';
                        $mem2       = round($cur * $mb_per_page);
                        $mem2_label = 'privvmpages';
                        $pmg_per    = round(($cur / $lim) * 100);
                        $mem2_tip   = "title='privvmpages is burstable memory; you are using $pmg_per% of your limit'";
                        $mem2_units = 'MB';
                    }
                }

                else {
                    $bar_mb = 'n/a';
                }


                // vzopen LONG_MAX (32bits || 64bits)
                if ($bar = 2147483647 || $bar === 9223372036854775807) {
                    $bar_mb = 'Max';
                }
                if ($lim = 2147483647 || $lim === 9223372036854775807) {
                    $lim_mb = 'Max';
                }


                $beans .=
                    str_pad($line_parts[1], 12) .
                    str_pad($cur_mb, 12, ' ', STR_PAD_LEFT) .
                    str_pad($max_mb, 12, ' ', STR_PAD_LEFT) .
                    str_pad($bar_mb, 12, ' ', STR_PAD_LEFT) .
                    str_pad($lim_mb, 12, ' ', STR_PAD_LEFT) .
                    str_pad($line_parts[6], 12, ' ', STR_PAD_LEFT) . "\n";
            }
        }

        $parts   = explode("\n", $beans);
        $vpsstat = "Resource         Current  Recent Max     Barrier       Limit    Failures\n";
        $vpsstat .= "------------  ----------  ----------  ----------  ----------  ----------\n";
        $vpsstat .= "$parts[2]\n$parts[0]\n$parts[1]\n$parts[3]\n$parts[4]\n$parts[5]";
    }

    if ( ! $vpsstat && $beans_exists) {
        $D             = DIRECTORY_SEPARATOR;
        $beanc_install = "PATH-TO-VPSINFO{$D}installation{$D}beanc{$D}install.sh";

        $vpsstat = "\n
It seems you're running Virtuozzo 3 or OpenVZ. In order to read the VPS stats
(beancounters) you need a small 'helper' app. To install it do the following at
a shell prompt as root:

[root@vps] sh {$beanc_install}\n\n";
    }


    elseif ( ! $beans_exists) {
        $free = `free`;
        if ($free) {
            $pattern = "/^.*\bMem\b.*$/mi";
            preg_match($pattern, $free, $hits);
            $memline = $hits[0];
            $memline = preg_replace('/ {1,99}/', '|', $memline);
            $parts   = explode('|', $memline);
            $kbytes  = $parts[3];
            $mbytes  = round($kbytes / 1024);
            if ($mbytes > 999) {
                $mem1       = round(($mbytes / 1024), 1);
                $mem1_units = 'GB';
            }
            else {
                $mem1       = $mbytes;
                $mem1_units = 'MB';
            }
            $mem1_label = 'free RAM';
            $mem1_tip   = 'title=\'amount of free memory\'';
            $pattern    = "/^.*\bSwap\b.*$/mi";
            preg_match($pattern, $free, $hits);
            $memline = $hits[0];
            $memline = preg_replace('/ {1,99}/', '|', $memline);
            $parts   = explode('|', $memline);
            $kbytes  = $parts[2];
            $mbytes  = round($kbytes / 1024);
            if ($mbytes > 999) {
                $mem2       = round(($mbytes / 1024), 1);
                $mem2_units = 'GB';
            }
            else {
                $mem2       = $mbytes;
                $mem2_units = 'MB';
            }
            $mem2_label = 'swap used';
            $mem2_tip   = 'title=\'amount of swap space currently used\'';
        }
    }

    return array($vpsstat, $mem1, $mem1_units, $mem1_label, $mem1_tip, $mem2, $mem2_units, $mem2_label, $mem2_tip);
}

$mtime     = explode(' ', microtime());
$tend      = $mtime[0] + $mtime[1];
$totaltime = round(($tend - $tstart), 4);
$pagegen   = "page generated in $totaltime sec.";

// MAIN PAGE OUTPUT ============================================================

if ($gzip) {
    ini_set('zlib.output_compression_level', 1);
    ob_start('ob_gzhandler');
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php echo($meta_refresh); ?>
    <style type='text/css'>
        BODY {
            font-family: Verdana, Arial, Helvetica, sans-serif;
            background-color: #31311B;
            color: #CCCCCC;
            margin: 5px 5px 30px 5px;
            padding: 0;
        }

        /* General layout ---------------------------- */

        div.space {
            font-size: 1px;
            height: 3px;
        }

        td.head {
            border: 1px solid #666666;
            background-color: #000000;
        }

        td.tdleft {
        }

        td.tdright {
            padding-left: 5px;
        }

        /* Header section ---------------------------- */

        div.hostname {
            font-size: 16px;
            font-weight: bold;
            color: #DDDDDD;
            padding: 2px 0 2px 5px;
        }

        div.date {
            font-size: 13px;
            font-weight: bold;
            color: #DDDDDD;
            padding: 0 0 2px 5px;
        }

        div.head_label {
            font-family: Tahoma, "MS Sans Serif", Arial, Helvetica, sans-serif;
            font-size: 11px;
            padding-left: 13px;
            text-align: right;
            cursor: help;
        }

        div.head_num {
            font-size: 22px;
            padding-left: 13px;
            padding-right: 1px;
            text-align: right;
        }

        div.head_num2 {
            font-size: 18px;
            padding-left: 13px;
            padding-right: 1px;
            text-align: right;
        }

        .head_units {
            font-family: Tahoma, "MS Sans Serif", Arial, Helvetica, sans-serif;
            font-size: 10px;
            vertical-align: super;
        }

        /* Service monitoring in the header */
        table.roof {
            background-color: #333333;
        }

        div.servstatus {
            font-family: Tahoma, "MS Sans Serif", Arial, Helvetica, sans-serif;
            font-size: 11px;
            padding: 2px 0 2px 2px;
        }

        span.servup,
        span.servdown {
            display: inline-block;
            margin: 1px 0;
        }

        span.servup {
            background-color: #006900;
            color: #CCCCCC;
            cursor: help;
        }

        span.servdown {
            background-color: #CC0000;
            color: #FFFFFF;
            cursor: help;
        }

        div.disk {
            font-family: Tahoma, "MS Sans Serif", Arial, Helvetica, sans-serif;
            font-size: 11px;
            text-align: right;
            background-color: #333333;
            padding: 2px 5px 2px;
            white-space: nowrap;
        }

        .warn {
            background-color: #CC0000;
        }

        /* Load bars */
        div.load_label {
            height: 12px;
            font-family: Tahoma, "MS Sans Serif", Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #CCCCCC;
            line-height: 11px;
            text-align: right;
            cursor: help;
        }

        div.load_bg {
            font-size: 2px;
            height: 10px;
            width: 200px;
            cursor: help;
        }

        div.load_fg {
            height: 10px;
        }

        /* Box layouts ------------------------------- */

        div.subleft, div.subright {
            font-size: 13px;
            font-weight: bold;
            background-color: #333333;
            color: #DDDDDD;
        }

        div.subleft {
            /*width: auto;*/
            border: 1px solid #666666;
            border-bottom: none;
            margin-top: 5px;
            padding: 0 0 3px 6px;
        }

        div.subright {
            /*width: auto;*/
            border: 1px solid #666666;
            border-bottom: none;
            margin-top: 5px;
            padding: 0 0 3px 6px;
        }

        div.left {
            clear: right;
            margin: 0;
            background-color: #000000;
            border: 1px solid #666666;
            border-top: none;
        }

        div.leftscroll {
            clear: right;
            height: 230px;
            overflow: auto;
            margin-right: -1px;
            background-color: #000000;
            border: 1px solid #666666;
            border-bottom: 1px solid #444444;
            border-top: none;
            /* IE-specific hacks */
            overflow-X: visible;
            scrollbar-face-color: #666666;
            scrollbar-track-color: #999999;
            scrollbar-3dlight-color: #999999;
            scrollbar-highlight-color: #666666;
        }

        div.right, div.toolbar, div.toolbar_left {
            width: auto;
            background-color: #000000;
            border: 1px solid #666666;
            border-top: none;
        }

        div.toolbar {
            border-top: none;
            padding: 3px 5px 4px 0;
            text-align: right;
        }

        div.toolbar_left {
            border-top: none;
            padding: 3px 0 4px 5px;
            text-align: left;
        }

        /* Box buttons to command windows */

        div.subleftcmd {
            text-align: right;
            font-size: 10px;
            line-height: 14px;
            float: right;
            margin-top: 5px;
            margin-right: 0;
            padding-bottom: 1px;
            border: 1px solid #777777;
            border-top: none;
            border-right: none;
            background-color: #666666;
        }

        /* Box button links: "+" */

        a:link.open, a:visited.open, a:active.open {
            color: #EEEEEE;
            text-decoration: none;
        }

        /* Whois lookup */

        .whois_title {
            font-size: 13px;
            font-weight: bold;
            color: #BBBBBB;
        }

        form.whois {
            margin: 0;
            padding: 0;
        }

        input.whois_input {
            width: 150px;
            font-family: vt7X13, "Courier New", Courier, monospace;
            font-size: 11px;
            line-height: 13px;
            border: 1px solid #999999;
            background-color: #CCCCCC;
        }

        input.button {
            width: 65px;
            font-family: Tahoma, "MS Sans Serif", Arial, Helvetica, sans-serif;
            font-size: 11px;
            border: 1px solid #999999;
            background-color: #666666;
            color: #FFFFFF;
            cursor: pointer;
        }

        /* Content formatting ------------------------ */

        pre {
            font-family: vt7X13, "Courier New", Courier, monospace;
            font-size: 11px;
            line-height: 14px;
            padding: 5px 5px 10px 6px;
            margin: 0;
            overflow: auto;
        }

        a:link, a:visited, a:active {
            color: #BBBB00;
            text-decoration: underline;
        }

        div.note {
            font-size: 11px;
            font-style: italic;
            padding: 5px 0 0 5px;
        }

        div.sig {
            font-size: 11px;
            color: #999999;
            padding: 25px 0 0 0;
            text-align: center;
        }

        /* use inside $top variable */
        .top_highlite {
            color: #00BBAE;
        }

    </style>
    <title><?php echo($hostname); ?> : vpsinfo</title>
</head>

<body>

<table width='100%' cellspacing=0 cellpadding=0 border=0>
    <tr>
        <td class='head'>
            <table class='roof' width='100%' cellspacing=0 cellpadding=0 border=0>
                <tr>
                    <td>
                        <div class='servstatus'><?php echo($tcpstatus); ?></div>
                    </td>
                    <td align='right'>
                        <div class='disk'>Disk Usage: <?php echo($allfs); ?></div>
                    </td>
                </tr>
            </table>
            <table width='100%' cellspacing=0 cellpadding=0 border=0>
                <tr>
                    <td valign='top' nowrap>
                        <div class='hostname'><?php echo($hostname); ?></div>
                        <div class='date'><?php echo($localtime); ?></div>
                    </td>
                    <td>
                        <div style='padding-left:20px'>
                            <table cellspacing=0 cellpadding=0 border=0>
                                <tr>
                                    <td nowrap>
                                        <div class='load_label'
                                             title='load average during last 1 minute'><?php echo($loadlabel1); ?>
                                            &nbsp;</div>
                                    </td>
                                    <td>
                                        <div class='load_bg' style='background-color: <?php echo($bgcolor1); ?>'
                                             title='load average during last 1 minute'>
                                            <div class='load_fg'
                                                 style='width: <?php echo($load1_width); ?>px; background-color: <?php echo($fgcolor1); ?>'>
                                                &nbsp;</div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td nowrap>
                                        <div class='load_label'
                                             title='load average during last 5 minutes'><?php echo($loadlabel5); ?>
                                            &nbsp;</div>
                                    </td>
                                    <td>
                                        <div class='load_bg' style='background-color: <?php echo($bgcolor5); ?>'
                                             title='load average during last 5 minutes'>
                                            <div class='load_fg'
                                                 style='width: <?php echo($load5_width); ?>px; background-color: <?php echo($fgcolor5); ?>'>
                                                &nbsp;</div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td nowrap>
                                        <div class='load_label'
                                             title='load average during last 15 minutes'><?php echo($loadlabel15); ?>
                                            &nbsp;</div>
                                    </td>
                                    <td>
                                        <div class='load_bg' style='background-color: <?php echo($bgcolor15); ?>'
                                             title='load average during last 15 minutes'>
                                            <div class='load_fg'
                                                 style='width: <?php echo($load15_width); ?>px; background-color: <?php echo($fgcolor15); ?>'>
                                                &nbsp;</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                    <td valign='top' nowrap>
                        <div class='head_label' <?php echo($mem1_tip); ?>><?php echo($mem1_label); ?></div>
                        <div class='head_num'><?php echo($mem1); ?><span
                                class='head_units'> <?php echo($mem1_units); ?></span></div>
                    </td>
                    <td valign='top' nowrap>
                        <div class='head_label' <?php echo($mem2_tip); ?>><?php echo($mem2_label); ?></div>
                        <div class='head_num'><?php echo($mem2); ?><span
                                class='head_units'> <?php echo($mem2_units); ?></span></div>
                    </td>
                    <?php echo($vnstat_head); ?>
                    <td valign='top' nowrap>
                        <div class='head_label' title='number of current TCP connections'>tcp conn</div>
                        <div class='head_num2'><?php echo($num_tcp); ?></div>
                    </td>
                    <?php if ($num_httpd > 0) { ?>
                        <td valign='top' nowrap>
                           <div class='head_label' title='<?php echo($httpd_title); ?>'><?php echo($httpd_label); ?></div>
                           <div class='head_num2'><?php echo($num_httpd); ?></div>
                       </td>
                    <?php } ?>
                    <td valign='top' nowrap>
                        <div class='head_label' title='number of mysql processes and threads'>mysql thds</div>
                        <div class='head_num2'><?php echo($num_mysql); ?></div>
                    </td>
                    <?php echo($mysql_head); ?>
                    <td width='25%'>
                        <div class='space'>&nbsp;</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table width='100%' cellspacing=0 cellpadding=0 border=0 style='margin-top: -3px'>
    <tr>
        <td>
            <div class='space' style='width:<?php echo($leftcol); ?>px'>&nbsp;</div>
        </td>
        <td width='100%'>
            <div class='space'>&nbsp;</div>
        </td>
    </tr>
    <tr>
        <td valign='top' class='tdleft'>
            <div style='width:<?php echo($leftcol); ?>px'>

                <div class='subleftcmd'><?php echo($topcmdlink); ?></div>
                <div class='subleft'> top</div>
                <div class='leftscroll'>
                    <pre><?php echo($top); ?></pre>
                </div>
                <div class='toolbar'><?php echo($psaux); ?><?php echo($psmem); ?></div>

                <?php echo($vpsstat_div); ?>
                <div class='subleftcmd'><?php echo($netcmdlink); ?></div>
                <div class='subleft'><?php echo($netstat_com); ?></div>
                <div class='leftscroll'>
                    <pre><?php echo($netstat); ?></pre>
                </div>
                <div class='toolbar_left'>
                    <table width='100%' cellspacing=0 cellpadding=0 border=0>
                        <tr>
                            <td>
                                <form method='post'
                                      action='<?php echo($scriptname); ?>'
                                      class='whois'
                                      name='whois_form'>
                                    <span class='whois_title'>Whois: </span><input type='text' name='whois'
                                                                                   class='whois_input'
                                                                                   title='enter an IP address or domain'>
                                    <input type='submit' value='Lookup' class='button' title='do the lookup'
                                           onClick="if (whois_form.whois.value=='') { alert('Please enter an IP address or domain');return false; }
                                           else {window.open('/vpsinfo/index.php?whois='+whois_form.whois.value, 'netstat', 'width=650, height=350, resizable, scrollbars'); return false }">
                                    <input type='reset'
                                           name='clear'
                                           value='Clear'
                                           class='button'
                                           title='clear the entry'>
                                </form>
                            </td>
                            <td align='right'
                                style='padding-right:5px'><?php echo($netstat_ntl); ?><?php echo($portslink); ?></td>
                        </tr>
                    </table>
                </div>
                <?php echo($vnstat_div); ?>
                <?php echo($mysql_div); ?>
            </div>
        </td>
        <td valign='top' class='tdright'>
            <div class='subright'>Processes <span style="font-weight:normal;font-size: 0.85em;color: #aaa;">(pstree)</span></div>
            <div class='right'>
                <pre><?php echo($pstree); ?></pre>
            </div>
            <div class='subright'>Temp Files <span style="font-weight:normal;font-size: 0.85em;color: #aaa;">(ls -a /tmp)</span></div>
            <div class='right' style='border-bottom:1px solid #444444'>
                <div class='note'>Ignoring PHP session files (sess_*)</div>
                <pre><?php echo($tmp_full); ?></pre>
            </div>
            <div class='toolbar_left'><?php echo($lsal); ?></div>
        </td>
    </tr>
</table>

<div class='sig'>Originally written by Douglas Robbins<br/>
                 <a href="https://github.com/claude-ws01/vpsinfo" target="_blank">vpsinfo</a>&nbsp;<?php echo($version); ?> Claude Nadon<br/>
    <?php echo($pagegen); ?><br>
</div>

</body>
</html>
