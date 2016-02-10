## Unifi API browser
This tool is for browsing data that is exposed through Ubiquiti's Unifi Controller API, written in PHP, javascript and the [Bootstrap] (http://getbootstrap.com/) CSS framework. Please keep the following in mind:
- not all data collections/API endpoints are supported (yet), see the list below of currently supported data collections/API endpoints
- currently only supports versions 4.x.x of the Unifi Controller software
- there is still work to be done to add/improve functionality and usability of this tool so suggestions/comments are welcome. Please use the github issue list or the Ubiquiti Community forums (https://community.ubnt.com/t5/UniFi-Wireless/Unifi-API-browser-tool-released/m-p/1392651) for this.

If you'd like to buy me a beer, please use the donate button below. All donations go to the project maintainer (primarily for the procurement of liquid refreshments) :satisfied:

[![Donate](https://www.paypalobjects.com/en_GB/i/btn/btn_donate_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=M7TVNVX3Z44VN)

### Features
The Unifi API browser tool offers the following features:
- browse data collections/API endpoints exposed by the Unifi Controller API in an easy manner
- switch between sites managed by the connected controller
- switch between output formats (currently `json`, `PHP array`, `PHP var_dump`, `PHP var_export` and `json highlighted` have been implemented)
- switch between default Bootstrap theme and the [Bootswatch] (https://bootswatch.com/) themes
- an "About" modal which shows version information for PHP, cURL and the Unifi Controller
- very easy setup with minimal dependencies
- timing details of API queries can help "benchmark" your controller
- useful tool when developing applications that make use of the API
- the API exposes more data than is visible through the Controller's web interface which makes the tool useful for troubleshooting purposes
- comes bundled with an extensive PHP API client for accessing the Unifi Controller API
- debug mode to troubleshoot cURL connections (set `$debug` to `true` in the config file to enable debug mode)

### Data collections/API endpoints currently implemented
- Clients/users
  - list online clients
  - list guests
  - list users
  - stat all users
  - stat authorisations
  - stat sessions
- Devices
  - list devices (access points, USGs and switches)
  - list wlan groups
  - list rogue access points
- Stats
  - hourly site stats
  - hourly access point stats
  - daily site stats
  - health metrics
  - dashboard metrics (supported on controller version 4.9.1.alpha and higher)
  - port forward stats
- Hotspot
  - stat vouchers
  - stat payments
  - list hotspot operators
- Configuration
  - list sites on this controller
  - list site settings
  - sysinfo
  - self
  - wlan config
  - list VoIP extension
  - list network configuration
  - list port configurations
  - list port forwarding rules
  - dynamic DNS configuration
- Messages
  - list events
  - list alarms

### Credits
The PHP API client that comes bundled with this tool is based on the work done by the following developers:
- domwo: http://community.ubnt.com/t5/UniFi-Wireless/little-php-class-for-unifi-api/m-p/603051
- fbagnol: https://github.com/fbagnol/class.unifi.php
- and the API as published by Ubiquiti: https://dl.ubnt.com/unifi/4.7.6/unifi_sh_api

Other included libraries:
- Bootstrap (version 3.3.5) http://getbootstrap.com/
- Font-awesome (version 4.4.0) https://fortawesome.github.io/Font-Awesome/
- jQuery (version 2.1.4) https://jquery.com/
- Highlight.js (version 9.0.0) https://highlightjs.org/

### Requirements
- a web server with PHP and cURL modules installed (tested on PHP Version 5.6.1 and cURL 7.42.1)
- network connectivity between this web server and the server (and port) where the Unifi controller is running (in case you are seeing errors, please check out [this issue] (https://github.com/malle-pietje/Unifi-API-browser/issues/4))
- clients using this tool should have internet access to be able to load the required css files because they are loaded from CDNs.

### Installation
Installation of this tool is quite straightforward. The easiest way to do this is by using `git clone` which also allows for easy updates:
- open up a terminal window on your server and cd to the root folder of your web server (on Ubuntu this is `/var/www/html`) and execute the following command from your command prompt:
```bash
git clone https://github.com/malle-pietje/Unifi-API-browser.git
```
- when git is done cloning, follow the configuration steps below to configure the settings for access to your Unifi Controller's API

Alternatively you may choose to download the zip file and unzip it in your directory of choice, then follow the configuration steps below.

### Configuration
- credentials for access to the Unifi Controller API need to be configured in the file named `config.template.php` which should be copied/renamed to `config.php` before using the Unifi API browser tool
- please see the `config.template.php` file for further instructions
- after following these steps, you can open the tool in your browser (assuming you installed it in the root folder of your web server as suggested above) by going to this url: `http://serverip/Unifi-API-browser/`

### Updates
If you have installed the tool using the `git clone` command, you can install updates by going into the directory where the tool has been installed, and running the `git pull` command from there.

### Security notice
The use of this tool is **not secured in any way**! Make sure to prevent unauthorised access to it, preventing exposure of details and credentials such as user names and passwords for access to the Unifi controller!

### Screenshots
Here's a screenshot of the tool in action showing the site's health metrics using the default Bootstrap theme:

![alt tag](https://cloud.githubusercontent.com/assets/12016131/12074555/f0ec7c08-b15a-11e5-9f9c-bb5662ec47ba.JPG "Sample screenshot")

and here with one of the Bootswatch themes selected:

![alt tag](https://cloud.githubusercontent.com/assets/12016131/12074556/f3f03944-b15a-11e5-8299-b63d55dbd3ed.JPG "Sample screenshot with theme selected")

this is the "About" modal:

![alt tag](https://cloud.githubusercontent.com/assets/12016131/12512141/bc82ead0-c116-11e5-9bb2-f037e3f26a5f.JPG "Screenshot of the "About" modal")
