![vpsinfo_logo.png](.\wiki\images\vpsinfo_logo.png)
# Introduction

vpsinfo is a Linux server monitoring script, written in PHP, that provides web access to system status information. It gathers the output from several common Linux commands into one web page, providing a quick overview of the system's current state.

While designed for use on a Linux Virtuozzo or OpenVZ VPS (Virtual Private Server), vpsinfo also works fine on a dedicated server. When installed on a dedicated machine VPS-specific information is automatically excluded.

Please note that, on Virtuozzo and OpenVZ servers, the small beanc helper app may be required to access VPS status information.

vpsinfo shows the following outputs:
- `top`
- `/proc/user_beancounters` (VPS resources)
- `netstat -nt` (current TCP connections)
- `netstat -ntl` (listening TCP ports)
- `pstree` (tree view of running processes)
- `ls -a /tmp` (and ls -al /tmp )
- `vnstat` (network traffic at the interface)
- `mytop` (MySQL stats)
- `mysqlreport` (perl script, MySQL stats)
- Status of daemon processes
- Top summary section:
	- Values for oomguarpages and privvmpages (free RAM and swap usage)
	- Data transfer today through the network interface (from vnstat)
	- Current number of TCP connections
	- Web server threads, MySQL threads and queries (from mytop or mysqlreport)
	- Disk usage


## Optional Third-party Software
These applications are not required to run vpsinfo, but if installed they are used to gather additional information
- vnstat (data transfert monitoring at the network interface) Highly recommended!
- mytop  (MySql monitoring)
- mysqlreport  (perl script, MySql monitoring).
