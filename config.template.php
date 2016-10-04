<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2016, Slooffmaster
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 */

/**
 * Single controller configuration
 * ===============================
 * Update this section with your UniFi controller details and credentials
 * Remove or comment out this section when using the Multi controller configuration method
 */
$controlleruser     = ''; // the user name for access to the UniFi Controller
$controllerpassword = ''; // the password for access to the UniFi Controller
$controllerurl      = ''; // full url to the UniFi Controller, eg. 'https://22.22.11.11:8443'
$controllerversion  = ''; // the version of the Controller software, eg. '4.6.6' (must be at least 4.0.0)

/**
 * Multi controller configuration
 * ==============================
 * Only modify and uncomment this section when NOT using the above Single controller configuration method.
 * The number of controllers that can be added is unlimited, just take care to correctly increment the
 * index values (0,1,2 etc.)
 *
 * Please remember to only have one of either two methods active!
 */
/*
$controllers[0] = [
   'user'     => '', // the user name for access to the UniFi Controller
   'password' => '', // the password for access to the UniFi Controller
   'url'      => '', // full url to the UniFi Controller, eg. 'https://22.22.11.11:8443'
   'name'     => '',
   'version'  => ''  // the version of the Controller software, eg. '4.6.6' (must be at least 4.0.0)
];

$controllers[1] = [
   'user'     => '', // the user name for access to the UniFi Controller
   'password' => '', // the password for access to the UniFi Controller
   'url'      => '', // full url to the UniFi Controller, eg. 'https://22.22.11.11:8443'
   'name'     => '',
   'version'  => ''  // the version of the Controller software, eg. '4.6.6' (must be at least 4.0.0)
];

$controllers[2] = [
   'user'     => '', // the user name for access to the UniFi Controller
   'password' => '', // the password for access to the UniFi Controller
   'url'      => '', // full url to the UniFi Controller, eg. 'https://22.22.11.11:8443'
   'name'     => '',
   'version'  => ''  // the version of the Controller software, eg. '4.6.6' (must be at least 4.0.0)
];
*/

$cookietimeout      = '3600';      // time of inactivity in seconds, after which the PHP session cookie will be refreshed
                                   // after the cookie refresh the site and data collection will need to be selected again

$theme              = 'bootstrap'; // your default theme of choice, pick one from the list below:
                                   // bootstrap, cerulean, cosmo, cyborg, darkly, flatly, journal, lumen, paper
                                   // readable, sandstone, simplex, slate, spacelab, superhero, united, yeti

$debug              = false;       // set to true (without quotes) to enable debug output to the browser and the PHP error log
?>