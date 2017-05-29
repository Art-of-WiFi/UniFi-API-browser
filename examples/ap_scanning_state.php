<?php
/**
 * PHP API usage example
 *
 * contributed by: slooffmaster
 * description: example basic PHP script to fetch an Access Point's scanning state/results
 */

/**
 * include the config file (place your credentials etc. there if not already present)
 *
 * NOTE:
 * this example will only work out of the box with a single controller config file!
 */
require_once('../config.php');

/**
 * site id and MAC address of AP to query
 */
$site_id = '<enter your site id here>';
$ap_mac  = '<enter MAC address of Access Point to check>';

/**
 * load the Unifi API connection class and log in to the controller and do our thing
 * spectrum_scan_state()
 */
require_once('phpapi/class.unifi.php');
$unifidata    = new UnifiApi($controlleruser, $controllerpassword, $controllerurl, $site_id, $controllerversion);
$loginresults = $unifidata->login();
$data         = $unifidata->spectrum_scan_state($ap_mac);

/**
 * provide feedback in json format
 */
echo json_encode($data, JSON_PRETTY_PRINT);