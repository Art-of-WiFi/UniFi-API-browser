<?php
/**
 * Copyright (c) 2017, Art of WiFi
 *
 * This file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.md
 *
 */

/**
 * Optionally copy/rename this configuration template file to config.php and store all or part of your credentials
 * If credential information is not specific in this file, or the file is not copied to config.php,
 * or only part of the crential information is given, then the API browser will show a form to complete the login
 */

/**
 * Single controller configuration
 * ===============================
 * Update this section with your UniFi controller details and credentials
 * Remove or comment out this section when using the Multi controller configuration method
 */
//$controlleruser     = ''; // the user name for access to the UniFi Controller
//$controllerpassword = ''; // the password for access to the UniFi Controller
//$controllerurl      = ''; // full url to the UniFi Controller, eg. 'https://22.22.11.11:8443'
//$controllername     = ''; // name for this controller

/**
 * Multi controller configuration
 * ==============================
 * Only modify and uncomment this section when NOT using the above Single controller configuration method.
 * The number of controllers that can be added is unlimited, just take care to correctly increment the
 * index values (0,1,2 etc.)
 *
 * Please remember to only have one of either two methods active!
 *
 * NOTE: If you use the multi controller method, then at least the 'name' value is required
 *
 */
/*
$controllers[0] = [
   'user'     => '', // the user name for access to the UniFi Controller
   'password' => '', // the password for access to the UniFi Controller
   'url'      => '', // full url to the UniFi Controller, eg. 'https://22.22.11.11:8443'
   'name'     => ''  // REQUIRED: name for this controller which will be used in the dropdown menu
];

$controllers[1] = [
   'user'     => '', // the user name for access to the UniFi Controller
   'password' => '', // the password for access to the UniFi Controller
   'url'      => '', // full url to the UniFi Controller, eg. 'https://22.22.11.11:8443'
   'name'     => ''  // REQUIRED: name for this controller which will be used in the dropdown menu
];

$controllers[2] = [
   'user'     => 'demo', // the user name for access to the Unifi Controller
   'password' => 'demo', // the password for access to the Unifi Controller
   'url'      => 'https://demo.ubnt.com:443', // full url to the Unifi Controller, eg. 'https://22.22.11.11:8443'
   'name'     => 'demo.ubnt.com' // REQUIRED: name for this controller which will be used in the dropdown menu
];
*/

/**
 * Optionally uncomment and change the default options below
 */

//$cookietimeout      = '3600';      // time of inactivity in seconds, after which the PHP session cookie will be refreshed
                                     // after the cookie refresh the site and data collection will need to be selected again

//$theme              = 'bootstrap'; // your default theme of choice, pick one from the list below:
                                     // bootstrap, cerulean, cosmo, cyborg, darkly, flatly, journal, lumen, paper
                                     // readable, sandstone, simplex, slate, spacelab, superhero, united, yeti

//$debug              = false;       // set to true (without quotes) to enable debug output to the browser and the PHP error log