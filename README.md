## Unifi API browser
This tool is for browsing data that is accessible through Ubiquiti's Unifi Controller API, and is written in PHP and the  [Bootstrap] (http://getbootstrap.com/) CSS framework. Please keep the following in mind:
- not all data collections are supported (yet)
- there is still work to be done to add/improve functionality and usability of this tool so suggestions/comments are welcome. Please use the github issue list or the Ubiquiti Community forums (https://community.ubnt.com/t5/UniFi-Wireless/Unifi-API-browser-tool-released/m-p/1392651) for this.

If you'd like to buy me a beer, please use the donate button below. All donations go to the project maintainer (primarily for the procurement of liquid refreshments) :satisfied:

[![Donate](https://www.paypalobjects.com/en_GB/i/btn/btn_donate_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=M7TVNVX3Z44VN)

### Features
The Unifi API browser tool offers the following features:
- browse through data collections exposed by the Unifi Controller API in an easy manner
- switch between sites managed by the connected controller
- switch between output formats (currently json and PHP array)
- switch between default Bootstrap theme and the [Bootswatch] (https://bootswatch.com/) themes
- very easy setup with minimal dependencies
- timing details of API queries can help "benchmark" your controller
- useful tool when developing applications making use of the API
- the API exposes more data than is visible through the Controller's web interface which can be useful for troubleshooting purposes

### Data collections currently implemented
- Users/clients
  - list online clients
  - list guests
  - list users
  - stat all users
  - stat authorisations
  - stat sessions
- Access points
  - list access points
  - list rogue access points
- Stats
  - hourly site stats
  - hourly access point stats
  - daily site stats
- Configuration
  - wlan config
  - list site settings
  - health metrics
- Messages
  - list events
  - list alarms

### Credits
The phpapi that comes bundled with this tool is based on the work done by the following developers:
- domwo: http://community.ubnt.com/t5/UniFi-Wireless/little-php-class-for-unifi-api/m-p/603051
- fbagnol: https://github.com/fbagnol/class.unifi.php

### Installation
Installation of this tool is quite straightforward. The easiest way to do this is by using git clone which also allows for easy updates.
- open up a terminal window on your server and cd to the root folder of you web server (on Ubuntu this is /var/www/html) and execute the following command from your command prompt:
```
git clone https://github.com/malle-pietje/Unifi-API-browser.git
```
- when git is done cloning, follow the steps below to configure the settings for access to your Unifi Controller's API.

### Configuration
- credentials for access to the Unifi Controller API need to be configured in the file named "config.template.php" which should be copied/renamed to "config.php" before using the Unifi API browser tool
- please see the above file for further instructions
- after following these steps, you can open the tool in your browser (assuming you installed it in the root folder of your web server as suggested above) by going to this url: http://serverip/Unifi-API-browser/

### Requirements
- a web server with PHP and cURL modules installed (tested on PHP Version 5.6.1 and cURL 7.42.1)
- network access from this web server to the server and port on which the Unifi controller is running
- clients using this tool should have internet access to be able to load the required css files because they are loaded from CDN's.

### Security notice
The use of this tool is **not secured in any way**! Make sure to prevent unauthorised access to it, preventing exposure of details and credentials such as user names and passwords for access to the Unifi controller!

### Screenshot
Here's a screenshot of the tool in action showing the site's health metrics using the default Bootstrap theme:
![alt tag](https://cloud.githubusercontent.com/assets/12016131/11059979/8d0dbd5a-879d-11e5-9459-51749e471297.png "Sample screenshot")
