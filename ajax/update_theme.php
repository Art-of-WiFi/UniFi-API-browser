<?php
/**
 * Copyright (c) 2024, Art of WiFi
 * www.artofwifi.net
 *
 * @license This file is subject to the MIT license bundled with this package in the file LICENSE.md
 */

/**
 * load required files containing shared functions and the menu options
 */
require_once '../common.php';
require_once '../collections.php';

/**
 * load the configuration file if readable
 */
if (!is_file('../config/config.php') || !is_readable('../config/config.php')) {
    exit;
}

include '../config/config.php';

/**
 * in order to use the PHP $_SESSION array for temporary storage of variables, session_start() is required
 */
session_start();

/**
 * initialize the $results array
 */
$results = [
    'status'  => 'success',
    'message' => 'theme updated',
];

/**
 * get the POSTed new theme and store this in the global $_SESSION variable
 */
if (!empty($_POST['new_theme'])) {
    $_SESSION['theme'] = $_POST['new_theme'];
} else {
    $results['status']  = 'error';
    $results['message'] = 'empty POST';
}

returnJson($results);
