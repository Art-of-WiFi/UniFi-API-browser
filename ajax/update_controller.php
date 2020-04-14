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
 * load required files containing shared functions and the collections
 */
require_once('../common.php');
require_once('../collections.php');

/**
 * load the configuration file if readable
 */
if (is_file('../config/config.php') && is_readable('../config/config.php')) {
    include('../config/config.php');
} else {
    die();
}

/**
 * in order to use the PHP $_SESSION array for temporary storage of variables, session_start() is required
 */
session_start();

/**
 * initialize the results array
 */
$results = [
    'status'  => 'success',
    'message' => 'controller updated'
];

/**
 * get the POST parameters that were passed by the calling AJAX function
 */
if (!empty($_POST['new_controller_idx'])) {
    $_SESSION['controller']        = $controllers[($_POST['new_controller_idx'] - 1)];
    $_SESSION['controller']['idx'] = $_POST['new_controller_idx'];

    /**
     * we also unset the cookie for access to the UniFi controller
     */
    $_SESSION['unificookie'] = '';
} else {
    $results['status']  = 'error';
    $results['message'] = 'empty POST';
}

/**
 * output the results with correct JSON formatting
 */
header('Content-Type: application/json; charset=utf-8');
echo (json_encode($results));