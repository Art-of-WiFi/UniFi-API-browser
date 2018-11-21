>**NOTICE**
>
>The repository has been transferred to this new GitHub organisation account, the project maintainer will remain the same and will continue to actively maintain it.
> If you have previously installed the API browser tool using `git clone`, you may consider pointing the clone to the new repository URL. You can do this with the following command from within the directory of your clone:
>
>```bash
>$ git remote set-url origin https://github.com/Art-of-WiFi/UniFi-API-browser.git
>```

## UniFi API browser

This tool is for browsing data that is exposed through Ubiquiti's UniFi Controller API, written in PHP, JavaScript and the [Bootstrap](http://getbootstrap.com/) CSS framework.

It comes bundled with a **PHP class for access to the UniFi Controller API**, which supports [more API endpoints](https://github.com/Art-of-WiFi/UniFi-API-client#methods-and-functions-supported) than the UniFi API browser tool does.

If you plan to create your own PHP code leveraging the UniFi controller API, it is recommended to use the standalone version of the API client class which can be found here: https://github.com/Art-of-WiFi/UniFi-API-client

You will find examples and detailed instructions there.

Please keep the following in mind:

- the API browser tool doesn't support all available data collections/API endpoints, see the list below of those that are currently supported
- currently, versions 4.x.x and 5.x.x of the UniFi Controller software are supported (version 5.8.24 has been confirmed to work)
- there is still work to be done to add/improve functionality and usability of this tool so suggestions/comments are welcome. Please use the github [issue](https://github.com/Art-of-WiFi/UniFi-API-browser/issues) list or the Ubiquiti Community forums (https://community.ubnt.com/t5/UniFi-Wireless/UniFi-API-browser-tool-released/m-p/1392651) to share your ideas/questions.
- please read the Security Notice below before installing this tool!

### Features

The UniFi API browser tool offers the following features:

- browse data collections/API endpoints exposed by the UniFi Controller API in an easy manner
- switch between sites managed by the connected controller
- switch between output formats (currently `json`, `PHP array`, `PHP var_dump`, `PHP var_export`, `json highlighted` and `PHP array using Kint` have been implemented)
- copy the results to clipboard (only output formats `json`, `PHP array`, `PHP var_dump` and `PHP var_export` are supported, will fail gracefully with large collections)
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
  - list_known_rogueaps
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
  - list wlan config
  - list VoIP extension
  - list network configuration
  - list port configurations
  - list port forwarding rules
  - list firewall groups
  - list current channels
  - list DPI stats
  - dynamic DNS configuration
  - list country codes
  - list Radius accounts (supported on controller version 5.5.19 and higher)
- Messages
  - list events
  - list alarms
  - count alarms

Please note that the bundled API client supports many more API endpoints, not all make sense to add to the API browser though.

### Requirements

- a web server with PHP and cURL modules installed (tested on apache2 with PHP Version 5.6.1 and cURL 7.42.1 and with PHP 7.0.7 and cURL 7.37.0)
- network connectivity between this web server and the server (and port) where the UniFi controller is running (in case you are seeing errors, please check out [this issue](https://github.com/Art-of-WiFi/UniFi-API-browser/issues/4))
- clients using this tool should have internet access because the CSS and JS files are loaded from CDNs.

### Installation

Installation of this tool is quite straightforward. The easiest way to do this is by using `git clone` which also allows for easy updates:
- open up a terminal window on your server and cd to the root folder of your web server (on Ubuntu this is `/var/www/html`) and execute the following command from your command prompt:
```bash
git clone https://github.com/Art-of-WiFi/UniFi-API-browser.git
```
- when git is done cloning, follow the configuration steps below to configure the settings for access to your UniFi Controller's API

Alternatively you may choose to download the zip file and unzip it in your directory of choice, then follow the configuration steps below.

### Configuration

- credentials for access to the UniFi Controller API can be configured, in part or in whole, in the file named `config.template.php` which should be copied/renamed to `config.php`
- starting with API browser tool version 1.0.34, if all or a portion of the credentials are not specified in the `config.php` file or this file is not present, then a login form will ask for the missing credential information
- starting with version 1.0.3, you can store **multiple controller configurations** in an array inside the `config.php` file
- please refer to the comments in the `config.template.php` file for further instructions
- after following these steps, you can open the tool in your browser (assuming you installed it in the root folder of your web server as suggested above) by going to this url: `http(s)://<server IP address>/UniFi-API-browser/`

### Updates

If you have installed the tool using the `git clone` command, you can install updates by going into the directory where the tool has been installed, and running the `git pull` command from there.

Otherwise you can simply copy the contents from the latest [zip file](https://github.com/Art-of-WiFi/UniFi-API-browser/archive/master.zip) to the directory where the tool has been installed.

### Credits

The PHP API client that comes bundled with this tool is based on the work done by the following developers:

- domwo: http://community.ubnt.com/t5/UniFi-Wireless/little-php-class-for-unifi-api/m-p/603051
- fbagnol: https://github.com/fbagnol/class.unifi.php
- and the API as published by Ubiquiti: https://dl.ubnt.com/unifi/5.6.18-8261dc5066/unifi_sh_api

Other included libraries:

- Bootstrap (version 3.3.7) https://getbootstrap.com
- Font-awesome (version 4.7.0) https://fortawesome.github.io/Font-Awesome
- jQuery (version 2.2.4) https://jquery.com
- jQuery JSONView (version 1.2.3) https://github.com/yesmeck/jquery-jsonview
- Kint (version 2.1.2) https://kint-php.github.io/kint
- clipboard.js (2.0.1) https://clipboardjs.com/

### Security notice

The use of this tool is **not secured in any way**! Make sure to prevent unauthorised access to it, preventing exposure of details and credentials such as user names and passwords for access to the UniFi Controller!

### Screenshots

Here are a couple of screenshots of the tool in action.

The controller selection dropdown menu:

![Controller selection](https://user-images.githubusercontent.com/12016131/48832366-45a3f980-ed79-11e8-82a4-e66225fba71e.png "Controller selection")

The site selection dropdown menu:

![Site selection](https://user-images.githubusercontent.com/12016131/48832367-45a3f980-ed79-11e8-8a91-70f505720ea1.png "Site selection")

Showing the online device data collection using the default Bootstrap theme:

![Online device collection](https://user-images.githubusercontent.com/12016131/48832368-463c9000-ed79-11e8-9d17-4005cf142cbe.png "Online device collection")

With one of the Bootswatch themes selected:

![Dark theme selected](https://user-images.githubusercontent.com/12016131/48832369-463c9000-ed79-11e8-841b-07e7842a23a5.png "Dark theme selected")

The "About" modal:

![About modal](https://user-images.githubusercontent.com/12016131/48832371-463c9000-ed79-11e8-90f4-faee7958cd2c.png "About modal")

The Login form when a controller password has not been configured in the config.php file:

![Login form](https://user-images.githubusercontent.com/12016131/48832373-463c9000-ed79-11e8-990f-04e9ae36e171.png "Login form")
