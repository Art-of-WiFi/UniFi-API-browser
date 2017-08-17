<?php
/**
 * PHP API usage example
 *
 * contributed by: Art of WiFi
 * description: example basic PHP script to create a set of vouchers
 */

/**
 * include the config file (place your credentials etc. there if not already present)
 *
 * NOTE:
 * this example will only work out of the box with a single controller config file!
 */
require_once('../config.php');

/**
 * the voucher duration in minutes
 */
$voucher_duration = 2000;

/**
 * the number of vouchers to create
 */
$voucher_count = 1;

/**
 * The site where you want to create the voucher(s)
 */
$site_id = '<enter your site id here>';

/**
 * load the Unifi API connection class and log in to the controller
 */
require_once('../phpapi/class.unifi.php');
$unifidata      = new UnifiApi($controlleruser, $controllerpassword, $controllerurl, $site_id, $controllerversion);
$set_debug_mode = $unifidata->set_debug($debug);
$loginresults   = $unifidata->login();

/**
 * then we create the required number of vouchers for the requested duration
 */
$voucher_result = $unifidata->create_voucher($voucher_duration, $voucher_count);

/**
 * provide feedback (the newly created voucher code, without the dash) in json format
 */
echo json_encode($voucher_result, JSON_PRETTY_PRINT);
