<?php
/**
 * PHP API usage example
 *
 * contributed by: slooffmaster
 * description: example basic PHP script to perform a basic auth of a guest device
 */

/**
 * include the config file (place your credentials etc. there if not already present)
 *
 * NOTE:
 * this example will only work out of the box with a single controller config file!
 */
require_once('../config.php');

/**
 * the MAC address of the device to authorize
 */
$mac = '<enter MAC address of guest device to auth>';

/**
 * the duration to authorize the device for in minutes
 */
$duration = 2000;

/**
 * The site to authorize the device with
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
 * then we authorize the device for the requested duration
 */
$auth_result = $unifidata->authorize_guest($mac, $duration);

/**
 * provide feedback in json format
 */
echo json_encode($auth_result, JSON_PRETTY_PRINT);