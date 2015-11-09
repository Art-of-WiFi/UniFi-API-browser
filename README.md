## Unifi API browser
This tool is for browsing data that is accessible through Ubiquiti's Unifi Controller API. Please keep the following in mind:
- not all data collections are supported (yet)
- there is still work to be done to add/improve functionality and usability of this tool so suggestions/comments are welcome. Please use the github issue list or the Ubiquiti Community forums (https://community.ubnt.com/t5/UniFi-Wireless/Unifi-API-browser-tool-released/m-p/1392651) for this.

### CREDITS
The phpapi that comes bundled with this tool is based on the work done by the following developers:
- domwo: http://community.ubnt.com/t5/UniFi-Wireless/little-php-class-for-unifi-api/m-p/603051
- fbagnol: https://github.com/fbagnol/class.unifi.php
    
### CONFIGURATION
- credentials for access to the Unifi Controller API need to be configured in the file named "config.template.php" which should be copied/renamed to "config.php" before using the Unifi API browser tool
- please see the above file for further instructions

### REQUIREMENTS
- a web server with PHP and cURL modules installed (tested on PHP Version 5.6.1 and cURL 7.42.1)
- network access from this web server to the server and port on which the Unifi controller is running
- clients using this tool should have internet access to be able to load the required css files because they are loaded from CDN's.

### SECURITY NOTICE
The use of this tool is not secured in any way! Make sure to prevent unauthorised access to it, preventing exposure of details and credentials such as user names and passwords for access to the Unifi controller!
