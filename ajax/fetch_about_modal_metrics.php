<?php
/**
 * Copyright © 2024, Art of WiFi
 * www.artofwifi.net
 *
 * @license This file is subject to the MIT license bundled with this package in the file LICENSE.md
 */

/**
 *  Load required packages using the composer autoloader together with the files containing shared functions
 *  and the menu options.
 */
require_once '../common.php';
require_once '../collections.php';

/**
 * Load the configuration file if readable.
 */
if (!is_file('../config/config.php') || !is_readable('../config/config.php')) {
    exit;
}

include '../config/config.php';

/**
 * To use the PHP $_SESSION array for temporary storage of variables, session_start() is required.
 */
session_start();

/**
 * Initialize the $results array.
 */
$results = [
    'controller_url'     => 'unknown',
    'controller_user'    => 'unknown',
    'controller_version' => 'not detected',
    'memory_used'        => $_SESSION['memory_used'],
];

/**
 * Fill in the details for the controller, if available.
 */
if (!empty($_SESSION['controller'])) {
    $results['controller_url']  = $_SESSION['controller']['url'];
    $results['controller_user'] = $_SESSION['controller']['user'];

    if (!empty($_SESSION['controller']['detected_version'])) {
        $results['controller_version'] = $_SESSION['controller']['detected_version'];
    }
}

returnJson($results);
