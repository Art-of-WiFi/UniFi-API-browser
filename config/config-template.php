<?php
/**
 * Copyright © 2024, Art of WiFi
 * www.artofwifi.net
 *
 * @license This file is subject to the MIT license bundled with this package in the file LICENSE.md
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
 * the array structure by following the PHP syntax shown below.
 *
 * **All fields are required for each controller**
 *
 * If a controller configuration is incomplete, an error will the thrown upon selection
 */
$controllers = [
    [
        'user'     => 'demo',                    // Username for access to the UniFi Controller
        'password' => 'demo',                    // Password for access to the UniFi Controller
        'url'      => 'https://demo.ui.com:443', // Full URL to the UniFi Controller, e.g., 'https://22.22.11.11:8443'
        'name'     => 'demo.ubnt.com',           // Name for this controller which will be used in the dropdown menu
    ],
    [
        'user'     => 'demo2',                   // Username for access to the UniFi Controller
        'password' => 'demo2',                   // Password for access to the UniFi Controller
        'url'      => 'https://demo.ui.com:443', // Full URL to the UniFi Controller, e.g., 'https://22.22.11.11:8443'
        'name'     => 'demo2.ubnt.com',          // Name for this controller which will be used in the dropdown menu
    ],
];

$theme = 'bootstrap'; // Your default theme of choice, pick one from the list below:
// bootstrap, cerulean, cosmo, cyborg, darkly, flatly, journal, lumen, paper, readable, sandstone, simplex, slate, spacelab, superhero, united, yeti

$navbar_class    = 'dark';      // Class for the main navigation bar, valid options are: light, dark
$navbar_bg_class = 'dark';      // Class for the main navigation bar background, valid options are: primary, secondary, success, danger, warning, info, light, dark, white, transparent

$debug = false; // Set to true (without quotes) to enable debug output to the browser and the PHP error log
// when fetching the site collection after selecting a controller.
