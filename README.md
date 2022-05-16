## UniFi API browser

This tool is for browsing data that is exposed through Ubiquiti's UniFi Controller API, written in PHP, JavaScript and the [Bootstrap](http://getbootstrap.com/) CSS framework.

It comes bundled with a **PHP class for access to the UniFi Controller API**, which supports [more API endpoints](https://github.com/Art-of-WiFi/UniFi-API-client#functionsmethods-supported) than the UniFi API browser tool does.

If you plan on creating your own PHP code to leverage the UniFi controller API, it is recommended to use the standalone version of the API client class which can be found here: https://github.com/Art-of-WiFi/UniFi-API-client

You will find examples and detailed instructions there.

Please keep the following in mind:

- the API browser tool doesn't support all available data collections/API endpoints, see the list below of those currently supported
- currently, versions 4.X.X, 5.X.X, and 6.X.X of the UniFi Controller software are supported as well as UniFi OS-based controllers (version 6.5.55 has been confirmed to work)
- when accessing UniFi OS-based controllers (e.g. UDM, UDM PRO, UDR) through this tool, please read the remarks below regarding UniFi OS support
- there is still work to be done to add/improve the functionality and usability of this tool and your suggestions/comments are welcome. Please use the GitHub [issue](https://github.com/Art-of-WiFi/UniFi-API-browser/issues) list or the Ubiquiti Community forums (https://community.ubnt.com/t5/UniFi-Wireless/UniFi-API-browser-tool-released/m-p/1392651) to share your ideas/questions.
- please read the Security Notice below before installing this tool!


### Upgrading from 1.x to 2.x

Because the structure of the configuration file has changed, we recommend creating a fresh install when upgrading from 1.x to 2.x.


### Features

The UniFi API browser tool offers the following features and benefits:

- browse data collections/API endpoints exposed by the UniFi Controller API in an easy manner
- switch between sites managed by the connected controller
- switch between output formats (currently `JSON`, `JSON highlighted`, `PHP array, interactive` and `PHP array, highlighted` have been implemented)
- copy the results to clipboard (this is only supported with the `JSON` output format, will fail gracefully with large collections)
- switch between the default Bootstrap theme and the [Bootswatch](https://bootswatch.com/) themes
- an "About" modal which shows version information for PHP, cURL, and the UniFi Controller
- very easy setup with minimal dependencies
- timing details of API calls can be useful to "benchmark" your UniFi Controller
- useful tool when developing applications that make use of the UniFi Controller API
- the API exposes more data than is visible through the UniFi controller's web interface which makes the tool useful for troubleshooting purposes
- debug mode to troubleshoot cURL connections (set `$debug` to `true` in the config file to enable debug mode)

### Data collections/API endpoints currently implemented in the API browser

- Configuration
  - list sites on this controller
  - list site settings
  - list admins for the current site
  - system information (sysinfo)
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
  - list AP groups (supported on controller version 6.0.X and higher)
  - list rogue access points
  - list devices tags (supported on controller version 5.5.19 and higher)
- Stats
  - all sites stats
  - 5-minute site stats
  - hourly site stats
  - daily site stats
  - monthly site stats
  - 5-minute access point stats
  - hourly access point stats
  - daily access point stats
  - monthly access point stats
  - 5-minute gateway stats
  - hourly gateway stats
  - daily gateway stats
  - monthly gateway stats
  - 5-minute dashboard metrics
  - hourly dashboard metrics
  - site health metrics
  - port forward stats
  - DPI stats
- Hotspot
  - stat vouchers
  - stat payments
  - list hotspot operators
- Messages
  - list events
  - list alarms
  - count alarms
  - list IDS/IPS events

Please note that the bundled API client supports many more API endpoints, not all make sense to add to the API browser though.


### Requirements

- a web server with PHP (7.2.5 or higher) and the php-curl module installed (confirmed to work on Apache with PHP 7.4.9 and cURL 7.58.0)
- network connectivity between this web server and the server (and port) where the UniFi controller is running (in case you are seeing errors, please check out [this issue](https://github.com/Art-of-WiFi/UniFi-API-browser/issues/4))
- clients using this tool should have internet access because several CSS and JS files are loaded from public CDNs.
- Using an administrator account with **read-only** permissions can limit visibility on certain collection/object properties. 
  See this [issue](https://github.com/Art-of-WiFi/UniFi-API-client/issues/129) and this [issue](https://github.com/Art-of-WiFi/UniFi-API-browser/issues/94) 
  for an example where the WPA2 password isn't accessible for **read-only** administrator accounts.


### Installation

Installation of this tool is quite straightforward. The easiest way to do this is by using `git clone` which also allows for easy updates:
- open up a terminal window on your server and cd to the root folder of your web server (on Ubuntu this is `/var/www/html`) and execute the following command from your command prompt:
```bash
git clone https://github.com/Art-of-WiFi/UniFi-API-browser.git
```
- when git is done cloning, follow the configuration steps below to configure the settings for access to your UniFi Controller's API

Alternatively, you may choose to download the zip file and unzip it in your directory of choice, then follow the configuration steps below.


### Installation using Docker

@scyto maintains Docker containers for quick and easy deployment of the UniFi API browser tool. Please refer to [this Wiki page](https://github.com/Art-of-WiFi/UniFi-API-browser/wiki/Docker-Hosting) within the repository for more details. Please note we don't provide support related to Docker-based installs.


### Configuration

- credentials for access to the UniFi Controller API are configured in the file named `config/config-template.php` which should be copied/renamed to `config/config.php`
- starting with version 1.0.3, you can store **multiple controller configurations** in an array inside the `config/config.php` file
- please refer to the instructions in the `config/config-template.php` file for further configuration instructions
- starting with API browser tool version 2.0.0 you can restrict access to the tool by creating user accounts and passwords, please refer to the instructions in the `config/users-template.php` file for further details
- after following these steps, you can open the tool in your browser (assuming you installed it in the root folder of your web server as suggested above) by going to this url: `http(s)://<server IP address>/UniFi-API-browser/`


### UniFi OS support

Support for UniFi OS-based controllers (UniFi Dream Machine Pro) has been added with version 2.0.7. When adding the details for a UniFi OS device to the `config/config.php` file, please make sure not to add a port suffix or trailing slashes to the URL.


### Extending the Collections dropdown menu

Since version 2.0.0 you can extend the Collections dropdown menu with your own options by adding them to the `config.php` file.

Here's an example:
```php
/**
 * adding a custom sub-menu example
 */
$collections = array_merge($collections, [
    [
        'label' => 'Custom Menu', // length of this string is limited due to dropdown menu width
        'options' => [
            [
                'type' => 'collection', // either 'collection' or 'divider'
                'label' => 'hourly site stats past 24 hours', // string that is displayed in the dropdown menu
                'method' => 'stat_hourly_site', // name of the method/function in the API client class that is called
                'params' => [(time() - (24 * 60 *60)) * 1000, time() * 1000], // an array containing the parameters as they are passed to the method/function
            ],
            [
                'type' => 'collection',
                'label' => 'daily site stats past 31 days',
                'method' => 'stat_daily_site',
                'params' => [(time() - (31 * 24 * 60 *60)) * 1000, time() * 1000],
            ],
            [
                'type' => 'divider', // dividers have no other properties
            ],
            [
                'type' => 'collection',
                'label' => 'enable the site LEDs',
                'method' => 'site_leds', // don't go too wild when adding such calls, this example is simply to show the flexibility
                'params' => [true]
            ],
            [
                'type' => 'collection',
                'label' => 'disable the site LEDs',
                'method' => 'site_leds', // don't go too wild when adding such calls, this example is simply to show the flexibility
                'params' => [false]
            ],
        ],
    ],
]);
```

Note: for a `collection` type menu option the `type`, `label`, `method`, and `params` "properties" are required.

This is what the result looks like for the above example:

![Custom sub menu](https://user-images.githubusercontent.com/12016131/69611554-4fb4a400-102e-11ea-9175-99618c1e1f98.png "Custom sub menu")


### Updates

If you installed the tool using the `git clone` command, you can apply updates by going into the directory where the tool is installed, and running the `git pull` command from there.

Otherwise, you can simply copy the contents from the latest [zip file](https://github.com/Art-of-WiFi/UniFi-API-browser/archive/master.zip) to the directory where the tool has been installed.


### Credits

The PHP API client that comes bundled with this tool is based on the work by the following developers:

- domwo: http://community.ubnt.com/t5/UniFi-Wireless/little-php-class-for-unifi-api/m-p/603051
- fbagnol: https://github.com/fbagnol/class.unifi.php

and the API as published by Ubiquiti:

- https://dl.ui.com/unifi/6.0.41/unifi_sh_api

Other included libraries:

- Bootstrap 4 (version 4.5.3) https://getbootstrap.com
- Bootswatch themes (version 4.5.3) https://bootswatch.com
- Font Awesome icons (version 5.15.1) https://fontawesome.com
- jQuery (version 3.5.1) https://jquery.com
- Twig template engine (version 1.44.1) https://twig.symfony.com
- Highlight.js (version 10.4.1) https://highlightjs.org
- Kint (version 2.2) https://kint-php.github.io/kint
- clipboard.js (version 2.0.6) https://clipboardjs.com
- Moment.js (version 2.29.1) https://momentjs.com


### Security notice

We **highly recommend** enabling the user name/password authentication feature by creating a `config/users.php` based on the included `config/users-template.php` file. When creating passwords and their SHA512 hashes for entry in the `config/users.php` file, please make sure to use **strong random passwords**.

Please refer to the instructions in the `config/users-template.php` file for further details

It is your own responsibility to implement the necessary additional controls in securing this application and preventing unwanted access.


### Screenshots

Here are a couple of screenshots of the tool in action.

The Login form when user authentication is enabled:

![Login form](https://user-images.githubusercontent.com/12016131/67685140-cfe6db80-f994-11e9-872f-286701df7aee.png "Login form")

The controller selection dropdown menu:

![Controller selection](https://user-images.githubusercontent.com/12016131/67584184-59a46800-f74d-11e9-88b4-36e333f388a1.png "Controller selection")

The site selection dropdown menu:

![Site selection](https://user-images.githubusercontent.com/12016131/67584192-5d37ef00-f74d-11e9-906d-69c11f046037.png "Site selection")

The collection dropdown menu:

![Select collection](https://user-images.githubusercontent.com/12016131/67584206-64f79380-f74d-11e9-8d3d-6cb414179653.png "Select a collection")

Showing the site settings collection in JSON format:

![Site settings in JSON format](https://user-images.githubusercontent.com/12016131/67584222-6cb73800-f74d-11e9-99fb-e1726944bd24.png "JSON format")

Showing the site settings collection in interactive PHP format:

![Site settings in PHP format](https://user-images.githubusercontent.com/12016131/67584232-704abf00-f74d-11e9-9907-a1cadd00bf1b.png "Interactive PHP format")

The "About" modal:

![About modal](https://user-images.githubusercontent.com/12016131/67586311-9e320280-f751-11e9-9576-c0590c951edc.png "About modal")
