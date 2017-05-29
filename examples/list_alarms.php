<?php
/**
 * PHP API usage example
 *
 * contributed by: slooffmaster
 * description: example basic PHP script to pull current alarms from the UniFi controller and output in json format
 */

/**
 * include the config file (place your credentials etc. there if not already present)
 *
 * NOTE:
 * this example will only work out of the box with a single controller config file!
 */
require_once('../config.php');

/**
 * the site to use
 */
$site_id = '<enter your site id here>';

/**
 * load the Unifi API connection class and log in to the controller and do our thing
 */
require_once('../phpapi/class.unifi.php');
$unifidata      = new UnifiApi($controlleruser, $controllerpassword, $controllerurl, $site_id, $controllerversion);
$set_debug_mode = $unifidata->set_debug($debug);
$loginresults   = $unifidata->login();
$data           = $unifidata->list_alarms();

/**
 * provide feedback in json format
 */
echo json_encode($data, JSON_PRETTY_PRINT);