## Unifi API browser
This tool is for browsing data that is accessible through Ubiquiti's Unifi Controller API. Please keep the following in mind:
- not all data collections are supported (yet)
- there is still work to be done to add/improve functionality and usability of this tool so suggestions/comments are welcome. Please use the github issue list or the Ubiquiti Community forums (https://community.ubnt.com/t5/UniFi-Wireless/Unifi-API-browser-tool-released/m-p/1392651) for this.

If you'd like to buy me a beer, please use the donate button below. All donations go to the project maintainer (primarily for the procurement of liquid refreshments).
[![Donate](https://www.paypalobjects.com/en_GB/i/btn/btn_donate_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=M7TVNVX3Z44VN)

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
    
### Configuration
- credentials for access to the Unifi Controller API need to be configured in the file named "config.template.php" which should be copied/renamed to "config.php" before using the Unifi API browser tool
- please see the above file for further instructions

### Requirements
- a web server with PHP and cURL modules installed (tested on PHP Version 5.6.1 and cURL 7.42.1)
- network access from this web server to the server and port on which the Unifi controller is running
- clients using this tool should have internet access to be able to load the required css files because they are loaded from CDN's.

### Security notice
The use of this tool is **not secured in any way**! Make sure to prevent unauthorised access to it, preventing exposure of details and credentials such as user names and passwords for access to the Unifi controller!

### Screenshot
Here's a screenshot of the tool in action showing this site's health metrics:
![alt tag](https://cloud.githubusercontent.com/assets/12016131/11031627/1a74a712-86d6-11e5-966d-c9ff3de0e8c3.jpg "Sample screenshot")
