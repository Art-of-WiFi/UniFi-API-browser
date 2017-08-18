>**NOTICE**
>
>The repository has been transferred to this new GitHub organisation account, the project maintainer will remain the same and will continue to actively maintain it.
> If you have previously installed the API Browser tool using `git clone`, you may consider pointing the clone to the new repository URL. You can do this with the following command from within the directory of your clone:
>```bash
>$ git remote set-url origin https://github.com/Art-of-WiFi/UniFi-API-browser.git
>```

## UniFi API browser
This tool is for browsing data that is exposed through Ubiquiti's UniFi Controller API, written in PHP, JavaScript and the [Bootstrap](http://getbootstrap.com/) CSS framework.

It comes bundled with **an extensive PHP class for access to the UniFi Controller API**, which supports [more API endpoints](https://github.com/malle-pietje/UniFi-API-browser/tree/master/phpapi) than the UniFi API browser tool does. Please refer to the code samples in the `examples` directory for a starting point if you wish to develop your own PHP code.

Please keep the following in mind:
- not all data collections/API endpoints are supported (yet), see the list below of currently supported data collections/API endpoints
- currently supports versions 4.x.x and 5.x.x of the UniFi Controller software (version 5.5.20 has been confirmed to work)
- there is still work to be done to add/improve functionality and usability of this tool so suggestions/comments are welcome. Please use the github issue list or the Ubiquiti Community forums (https://community.ubnt.com/t5/UniFi-Wireless/UniFi-API-browser-tool-released/m-p/1392651) to share your ideas.
- please read the Security Notice below before installing this tool!

### Donations
If you'd like to support further development of the API browser tool and the PHP API client class, please use the donate button below. All donations go to the project maintainer.

[![Donate](https://www.paypalobjects.com/en_GB/i/btn/btn_donate_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=M7TVNVX3Z44VN)

### Features
The UniFi API browser tool offers the following features:
- browse data collections/API endpoints exposed by the UniFi Controller API in an easy manner
- switch between sites managed by the connected controller
- switch between output formats (currently `json`, `PHP array`, `PHP var_dump`, `PHP var_export` and `json highlighted` have been implemented)
- switch between default Bootstrap theme and the [Bootswatch](https://bootswatch.com/) themes
- an "About" modal which shows version information for PHP, cURL and the UniFi Controller
- very easy setup with minimal dependencies
- timing details of API calls can be useful to "benchmark" your UniFi Controller
- useful tool when developing applications that make use of the UniFi Controller API
- the API exposes more data than is visible through the UniFi controller's web interface which makes the tool useful for troubleshooting purposes
- debug mode to troubleshoot cURL connections (set `$debug` to `true` in the config file to enable debug mode)

### Data collections/API endpoints currently implemented in the API browser
- Clients/users
  - list online clients
  - list guests
  - list users
  - list user groups
  - stat all users
  - stat authorisations
  - stat sessions
- Devices
  - list devices (access points, USG routers and USW switches)
  - list wlan groups
  - list rogue access points
  - list devices tags (supported on controller version 5.5.19 and higher)
- Stats
  - hourly site stats
  - daily site stats
  - hourly access point stats
  - daily access point stats
  - all sites stats (supported on controller version 5.2.9 and higher)
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
  - list admins for current site
  - sysinfo
  - self
  - wlan config
  - list VoIP extension
  - list network configuration
  - list port configurations
  - list port forwarding rules
  - list current channels
  - list DPI stats
  - dynamic DNS configuration
  - list Radius accounts (supported on controller version 5.5.19 and higher)
- Messages
  - list events
  - list alarms
  - count alarms

Please note that the bundled API client supports many more API endpoints, not all make sense to add to the API browser though.

### Credits
The PHP API client that comes bundled with this tool is based on the work done by the following developers:
- domwo: http://community.ubnt.com/t5/UniFi-Wireless/little-php-class-for-unifi-api/m-p/603051
- fbagnol: https://github.com/fbagnol/class.unifi.php
- and the API as published by Ubiquiti: https://www.ubnt.com/downloads/unifi/5.5.20/unifi_sh_api

Other included libraries:
- Bootstrap (version 3.3.7) http://getbootstrap.com/
- Font-awesome (version 4.7.0) https://fortawesome.github.io/Font-Awesome/
- jQuery (version 2.2.4) https://jquery.com/
- jQuery JSONView (version 1.2.3) https://github.com/yesmeck/jquery-jsonview

### Requirements
- a web server with PHP and cURL modules installed (tested on apache2 with PHP Version 5.6.1 and cURL 7.42.1)
- network connectivity between this web server and the server (and port) where the UniFi controller is running (in case you are seeing errors, please check out [this issue](https://github.com/malle-pietje/UniFi-API-browser/issues/4))
- clients using this tool should have internet access because the CSS and JS files are loaded from CDNs.

### Installation
Installation of this tool is quite straightforward. The easiest way to do this is by using `git clone` which also allows for easy updates:
- open up a terminal window on your server and cd to the root folder of your web server (on Ubuntu this is `/var/www/html`) and execute the following command from your command prompt:
```bash
git clone https://github.com/malle-pietje/UniFi-API-browser.git
```
- when git is done cloning, follow the configuration steps below to configure the settings for access to your UniFi Controller's API

Alternatively you may choose to download the zip file and unzip it in your directory of choice, then follow the configuration steps below.

### Configuration
- credentials for access to the UniFi Controller API need to be configured in the file named `config.template.php` which should be copied/renamed to `config.php` before using the UniFi API browser tool
- starting with API browser tool version 1.0.3 you can store **multiple controller configurations** in a single `config.php` file
- please refer to the comments in the `config.template.php` file for further instructions
- after following these steps, you can open the tool in your browser (assuming you installed it in the root folder of your web server as suggested above) by going to this url: `http://<server IP address>/UniFi-API-browser/`

### Updates
If you have installed the tool using the `git clone` command, you can install updates by going into the directory where the tool has been installed, and running the `git pull` command from there.

### Security notice
The use of this tool is **not secured in any way**! Make sure to prevent unauthorised access to it, preventing exposure of details and credentials such as user names and passwords for access to the UniFi Controller!

### Screenshots
Here are a couple of screenshots of the tool in action.

The controller selection dropdown menu:

![alt tag](https://user-images.githubusercontent.com/12016131/29446799-7081ad00-83ee-11e7-93df-37f31fb28391.PNG "Controller selection")

The site selection dropdown menu:

![alt tag](https://user-images.githubusercontent.com/12016131/29446798-707f17a2-83ee-11e7-8ea7-c273862787f4.PNG "Site selection")

Showing the online device data collection using the default Bootstrap theme:

![alt tag](https://user-images.githubusercontent.com/12016131/29446796-7077a76a-83ee-11e7-9b96-aa58209e572d.PNG "Online device collection")

With one of the Bootswatch themes selected:

![alt tag](https://user-images.githubusercontent.com/12016131/29446800-708476d4-83ee-11e7-9cfd-c6e18f02a217.PNG "Dark theme selected")

The "About" modal:

![alt tag](https://user-images.githubusercontent.com/12016131/29446797-707d1e02-83ee-11e7-8c4a-a80ad08a72a0.PNG "About modal")
