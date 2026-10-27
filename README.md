## UniFi API browser

This tool allows you to browse data exposed through the UniFi Controller API, developed using PHP, JavaScript,
and the [Bootstrap](http://getbootstrap.com/) CSS framework. It comes bundled with two API clients:

- The **classic PHP API client** ([UniFi-API-client](https://github.com/Art-of-WiFi/UniFi-API-client)) for
  username/password authentication against the classic UniFi Controller API
- The **official UniFi Network Application API client** ([unifi-network-application-api-client](https://github.com/Art-of-WiFi/unifi-network-application-api-client))
  for API key authentication against the official UniFi Network Application API

Please keep the following in mind when using the UniFi API browser:

- The tool does not support all available data collections and API endpoints. See the list below for those currently
  supported.
- **Classic controllers**: versions 5.X.X through 11.X.X of the UniFi Controller/Network Application software are
  supported (version **11.0.89** has been confirmed to work)
- **Network Application 11.x** moved all time-series statistics to a new statistics engine and removed several legacy
  endpoints. On 11.x the legacy interval stats collections (5-minute/hourly/daily/monthly site, access point and gateway
  stats, speedtest results) return empty results, and the dashboard metrics, events, alarms and IDS/IPS events
  collections return an error. Use the "(11.x)" collections in the Stats menu and the system log collections in the
  Messages menu instead; the "statistics source" collection shows which engine a controller uses (`LEGACY` or `MODERN`)
- **Official API controllers**: requires a UniFi Network Application that supports API key authentication
- Network Application on UniFi OS-based consoles and servers is also supported
- When accessing UniFi OS-based controllers through this tool, please read the remarks regarding UniFi OS support
- Please read the Security Notice before installing this tool.


### Upgrading from 2.x to 3.x

Version 3.0.0 introduces the following major changes:

- **PHP 8.2 or higher is now required** (previously PHP 7.4)
- **Official UniFi Network Application API support**: you can now configure controllers that use API key
  authentication alongside the classic username/password controllers
- **Twig 3.x** replaces Twig 2.x (end of life)
- **Kint 5.x** replaces Kint 3.x for PHP 8.1+ compatibility
- **Auto-select single site**: when a controller has only one site, it is automatically selected
- **Custom menu entries** added in `config.php` only apply to classic controllers and are hidden when an official
  API controller is selected

When upgrading, update your `config/config.php` based on the new `config/config-template.php` file. Your existing
classic controller entries will continue to work without changes. To add official API controllers, see the
Configuration section below.


### Features

The UniFi API browser tool offers the following features:
- Browse data collections and API endpoints exposed by the UniFi Controller API in an easy manner
- Support for both **classic** (username/password) and **official** (API key) controller authentication
- Switch between multiple controllers of different types from the dropdown menu
- Switch between sites managed by the connected controller (auto-selects when only one site is available)
- Switch between output formats (currently **JSON**, **JSON highlighted**, **PHP array**, **interactive**, and
  **PHP array, highlighted**)
- Copy the results to clipboard (this is only supported with the JSON output format and will fail gracefully with
  large collections)
- Switch between the default Bootstrap theme and the [Bootswatch](https://bootswatch.com/) themes
- An **About** modal that shows version information for PHP, cURL, and the UniFi Controller
- Very easy setup with minimal dependencies
- Timing details of API calls can be useful to "benchmark" your UniFi Controller
- A useful tool when developing applications that make use of the UniFi Controller API
- The API exposes more data than is visible through the UniFi controller's web interface, making it useful for
  troubleshooting purposes
- Debug mode to troubleshoot cURL connections (set `$debug` to `true` in the config file to enable debug mode)


### Data collections/API endpoints currently implemented in the API browser

#### Official API controllers

- Application
  - Application info
- Sites
  - List sites
- Devices
  - List adopted devices
  - List pending devices
  - Device statistics
- Clients
  - List clients
- Networks
  - List networks
- WiFi
  - List WiFi broadcasts
- Hotspot
  - List vouchers
- Firewall
  - List firewall zones
  - List firewall policies
  - List ACL rules
  - List traffic matching lists
  - List DNS policies
- Switching
  - List switch stacks
  - List MC-LAG domains
  - List LAGs
- Supporting Resources
  - WAN interfaces
  - Site-to-site VPN tunnels
  - VPN servers
  - RADIUS profiles
  - Device tags
  - DPI categories
  - DPI applications
  - Countries

#### Classic controllers

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
  - speedtest results
  - statistics source (`LEGACY`/`MODERN`, Network Application 10.x and higher)
  - statistics schemas (Network Application 11.x)
  - WAN stats, WAN stats per gateway (11.x)
  - wireless site stats per radio band, per access point, per SSID and access point (11.x)
  - client counts, wireless client counts per WLAN (11.x)
  - client activity per client device (11.x)
  - system stats per device (11.x)
  - ISP metrics per WAN (11.x)
  - aggregated dashboard (Network Application 10.x and higher)
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
  - list system log entries (all, per category, device alerts, critical alerts, Next AI alerts, VPN alerts, admin
    activity, update alerts, client alerts, threat alerts, triggers)

Please note that the bundled API client supports many more API endpoints, not all make sense to add to the API browser
though.


### Requirements

- A web server with PHP (8.2.0 or higher) and the php-curl module installed
- Network connectivity between this web server and the server (and port) where the UniFi controller is running (in case
  you are seeing errors, please check out [this issue](https://github.com/Art-of-WiFi/UniFi-API-browser/issues/4))
- Web browsers accessing this tool should have full internet access because several CSS and JS files are loaded from
  public CDNs.
- Using an administrator account with **read-only** permissions can limit visibility on certain collection/object
  properties. See this [issue](https://github.com/Art-of-WiFi/UniFi-API-client/issues/129) and this [issue](https://github.com/Art-of-WiFi/UniFi-API-browser/issues/94) for an example where the WPA2 password isn't accessible for
  **read-only** administrator accounts.


### Installation

Installation of this tool is quite straightforward. The easiest way to do this is by using `git clone` which also allows
for easy updates:
- open up a terminal window on your server and `cd` to the root folder of your web server (on Ubuntu this is
  `/var/www/html`) and execute the following command from your command prompt:
```bash
git clone https://github.com/Art-of-WiFi/UniFi-API-browser.git
```
- when git is done cloning, follow the configuration steps below to configure the settings for access to your UniFi
  Controller's API

Alternatively, you may choose to download the zip file and unzip it in your directory of choice, then follow the
configuration steps below.


### Installation using Docker

@scyto maintains Docker containers for quick and easy deployment of the UniFi API browser tool. Please refer to
[this Wiki page](https://github.com/Art-of-WiFi/UniFi-API-browser/wiki/Docker-Hosting) within the repository for more details. Please note we don't provide support related to
Docker-based installs.


### Configuration

- Copy `config/config-template.php` to `config/config.php` and edit it with your controller details
- You can configure **multiple controllers** of **different types** in the `$controllers` array
- Two controller types are supported:
  - **`classic`** (default): uses username/password authentication with the classic UniFi API client
  - **`official`**: uses API key authentication with the official UniFi Network Application API client

#### Classic controller example

```php
[
    'user'     => 'admin',
    'password' => 'your-password',
    'url'      => 'https://192.168.1.1:8443',
    'name'     => 'Home Controller',
    'type'     => 'classic',
],
```

#### Official API controller example

To use the official API, you need to generate an API key in the UniFi Network Application (Settings > ) API section.
Then add a controller entry in the `config.php` like this:

```php
[
    'api_key'    => 'your-api-key-here',
    'url'        => 'https://192.168.1.1',
    'name'       => 'Office (Official API)',
    'type'       => 'official',
    'verify_ssl' => false, // optional, set to false to disable SSL verification (default: true)
],
```

Note: official API controllers do **not** require a username or password, only an API key.
The `type` field must be set to `'official'` for API key authentication to work.

#### Additional configuration

- If the `type` field is omitted from a controller entry, it defaults to `classic` for backward compatibility
- You can restrict access to the tool by creating user accounts and passwords. Please refer to the instructions
  in the `config/users-template.php` file for further details
- After following these steps, you can open the tool in your browser (assuming you installed it in the root folder of
  your web server as suggested above) by going to this url: `http(s)://<server IP address>/UniFi-API-browser/`


### UniFi OS support

Support for UniFi OS-based controllers (for example, the UniFi Dream Machine Pro or UniFi OS Server) has been added with
version 2.0.7. When adding the details for a UniFi OS console to the `config/config.php` file, please make sure not to
add trailing slashes to the URL. For UniFi OS Consoles (e.g., UDM PRO) use port 443 for the connection.

When using the UniFi API browser to connect to a Network Application on a UniFi OS-based gateway via the WAN interface, 
it is necessary to create a specific firewall rule to allow external access to port 443 on the gateway's local
interface. For more information, please refer to the following blog post for further details:
https://artofwifi.net/2022/04/07/how-to-access-the-unifi-controller-by-wan-ip-or-hostname-on-a-udm-pro/

When connecting to a **UniFi OS Server**, make sure to use port **11443** for the connection.

### Extending the Collections dropdown menu

Since version 2.0.0 you can extend the Collections dropdown menu with your own options by adding them to the
`config.php` file.

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

Note: custom menu entries only apply to **classic** controllers. They are automatically hidden when an **official** API controller is selected.

This is what the result looks like for the above example:

![Custom sub menu](https://user-images.githubusercontent.com/12016131/69611554-4fb4a400-102e-11ea-9175-99618c1e1f98.png "Custom sub menu")


### Updates

If you installed the tool using the `git clone` command, you can apply updates by going into the directory where the
tool is installed, and running the `git pull` command from there.

Otherwise, you can simply copy the contents from the latest [zip file](https://github.com/Art-of-WiFi/UniFi-API-browser/archive/master.zip) to the directory where the tool has been
installed.


### Credits

The PHP API client that comes bundled with this tool is based on the work by the following developers:

- domwo: http://community.ubnt.com/t5/UniFi-Wireless/little-php-class-for-unifi-api/m-p/603051
- fbagnol: https://github.com/fbagnol/class.unifi.php

and the API as published by Ubiquiti:

- https://dl.ui.com/unifi/7.3.81-1529bd4a64/unifi_sh_api

Other included libraries:

- Bootstrap 4 (version 4.5.3) https://getbootstrap.com
- Bootswatch themes (version 4.5.3) https://bootswatch.com
- Font Awesome icons (version 5.15.1) https://fontawesome.com
- jQuery (version 3.5.1) https://jquery.com
- Twig template engine (version 3.x) https://twig.symfony.com
- Highlight.js (version 10.4.1) https://highlightjs.org
- Kint (version 5.x) https://kint-php.github.io/kint
- clipboard.js (version 2.0.6) https://clipboardjs.com
- Moment.js (version 2.29.1) https://momentjs.com


### Security notice

> [!CAUTION]
> It is important to note that the UniFi API browser tool is a powerful tool that allows access to sensitive data and configuration options on your UniFi controller. It is therefore important you take appropriate security measures, such as limiting access to the tool to trusted individuals. Additionally, you should be aware of the security risks associated with running PHP code on your server.
> 
> We **highly recommend** enabling the username/password authentication feature by creating a `config/users.php` based on the included `config/users-template.php` file. When creating passwords and their SHA512 hashes for entry in the `config/users.php` file, make sure to use **strong random passwords**. Please refer to the instructions in the `config/users-template.php` file for further details


### Support and Feedback
This project is actively maintained, and feedback and suggestions are always welcome. If you encounter any issues or
have any suggestions for improvements, please use the GitHub [issue](https://github.com/Art-of-WiFi/UniFi-API-browser/issues) list or the Ubiquiti Community forums
(https://community.ubnt.com/t5/UniFi-Wireless/UniFi-API-browser-tool-released/m-p/1392651) to share your ideas and
questions.


### Screenshots

Here are a couple of screenshots of the tool in action.

The Login form when user authentication is enabled:

![Login form](https://user-images.githubusercontent.com/12016131/67685140-cfe6db80-f994-11e9-872f-286701df7aee.png "Login form")

The controller selection dropdown menu:

![Controller selection](https://user-images.githubusercontent.com/12016131/67584184-59a46800-f74d-11e9-88b4-36e333f388a1.png "Controller selection")

Now with different controller types:

<img width="1292" height="439" alt="Image" src="https://github.com/user-attachments/assets/a0c2f4d3-baa9-443e-87f4-f0da241287b4" />

The site selection dropdown menu:

![Site selection](https://user-images.githubusercontent.com/12016131/67584192-5d37ef00-f74d-11e9-906d-69c11f046037.png "Site selection")

The collection dropdown menu:

![Select collection](https://user-images.githubusercontent.com/12016131/67584206-64f79380-f74d-11e9-8d3d-6cb414179653.png "Select a collection")

Showing the site settings collection in JSON format:

![Site settings in JSON format](https://user-images.githubusercontent.com/12016131/67584222-6cb73800-f74d-11e9-99fb-e1726944bd24.png "JSON format")

Showing the site settings collection in interactive PHP format:

![Site settings in PHP format](https://user-images.githubusercontent.com/12016131/67584232-704abf00-f74d-11e9-9907-a1cadd00bf1b.png "Interactive PHP format")

Showing a collection from the Official API (initial page):

<img width="1278" height="1107" alt="Image" src="https://github.com/user-attachments/assets/07338d06-935d-4e7b-9baa-273b7861c9cf" />

Showing a collection from the Official API (all pages):

<img width="1274" height="1088" alt="Image" src="https://github.com/user-attachments/assets/33ec86ae-976f-4a25-8fad-d34e6b270963" />

The "About" modal:

![About modal](https://user-images.githubusercontent.com/12016131/67586311-9e320280-f751-11e9-9576-c0590c951edc.png "About modal")

