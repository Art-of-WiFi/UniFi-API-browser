<?php
/**
 * Copyright (c) 2019, Art of WiFi
 * www.artofwifi.net
 *
 * This file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.md
 *
 */

/**
 * Configuration instructions
 * ===========================
 * Create a copy of this configuration template file within the same directory, name it config.php and enter your
 * UniFi controller details and credentials below
 *
 * Multi controller configuration options
 * =======================================
 * The number of UniFi controllers that can be added is unlimited, just take care to correctly maintain
 * the array structure by following PHP syntax shown below.
 *
 * **All fields are required for each controller**
 *
 * If a controller configuration is incomplete, an error will the thrown upon selection
 */
$controllers = [
    [
        'user'     => 'demo', // the user name for access to the Unifi Controller
        'password' => 'demo', // the password for access to the Unifi Controller
        'url'      => 'https://demo.ui.com:443', // full url to the Unifi Controller, eg. 'https://22.22.11.11:8443'
        'name'     => 'demo.ubnt.com' // name for this controller which will be used in the dropdown menu
    ],
    [
        'user'     => 'demo2', // the user name for access to the UniFi Controller
        'password' => 'demo2', // the password for access to the UniFi Controller
        'url'      => 'https://demo.ui.com:443', // full url to the UniFi Controller, eg. 'https://22.22.11.11:8443'
        'name'     => 'demo2.ubnt.com'  // name for this controller which will be used in the dropdown menu
    ],
];

/**
 * Optionally change the default values for options below
 */
$theme           = 'bootstrap'; // your default theme of choice, pick one from the list below:
                                // bootstrap, cerulean, cosmo, cyborg, darkly, flatly, journal, lumen, paper
                                // readable, sandstone, simplex, slate, spacelab, superhero, united, yeti

$navbar_class    = 'dark';      // class for the main navigation bar, valid options are: light, dark
$navbar_bg_class = 'dark';      // class for the main navigation bar background, valid options are:
                                // primary, secondary, success, danger, warning, info, light, dark, white, transparent

$debug           = false;       // set to true (without quotes) to enable debug output to the browser and the PHP error log
                                // when fetching the sites collection after selecting a controller