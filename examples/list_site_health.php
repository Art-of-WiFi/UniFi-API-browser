<?php
/**
 * PHP API usage example
 *
 * contributed by: slooffmaster
 * description: example to pull site health metrics from the Unifi controller and output the results
 *              in json format
 */

/**
 * include the config file (place your credentials etc. there if not already present)
 *
 * NOTE:
 * this example will only work out of the box with a single controller config file!
 */
require_once('../config.php');

/**
 * the short name of the site you wish to query
 */
$site_id = '<enter your site id here>';

/**
 * load the Unifi API connection class and log in to the controller and pull the requested data
 */
require_once('../phpapi/class.unifi.php');

$unifidata      = new UnifiApi($controlleruser, $controllerpassword, $controllerurl, $site_id, $controllerversion);
$set_debug_mode = $unifidata->set_debug($debug);
$loginresults   = $unifidata->login();
$result         = $unifidata->list_health();

/**
 * output the results in correct json formatting
 */
header('Content-Type: application/json');
echo (json_encode($result, JSON_PRETTY_PRINT));