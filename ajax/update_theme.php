<?php
/**
 * Copyright © 2024, Art of WiFi
 * www.artofwifi.net
 *
 * @license This file is subject to the MIT license bundled with this package in the file LICENSE.md
 */

/**
 * Load required packages using the composer autoloader together with the files containing shared functions
 * and the menu options.
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
    'status'  => 'success',
    'message' => 'theme updated',
];

/**
 * Get the POSTed new theme and store this in the global $_SESSION variable.
 */
if (!empty($_POST['new_theme'])) {
    $_SESSION['theme'] = $_POST['new_theme'];
} else {
    $results['status']  = 'error';
    $results['message'] = 'empty POST';
}

returnJson($results);
