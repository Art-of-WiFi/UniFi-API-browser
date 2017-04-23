<?php
/**
 * PHP API usage example
 *
 * contributed by: slooffmaster
 * description: example to toggle the locate function (flashing LED) on an Access Point and
 *              output the response in json format
 */

/**
 * include the config file (place your credentials etc. there if not already present)
 *
 * NOTE:
 * this example will only work out of the box with a single controller config file!
 */
require_once('../config-local.php');

/**
 * site id to use
 */
$site_id = '<enter your site id here>';

/**
 * other specific variables to be used
 */
$mac = '<enter MAC address of your AP here>';

/**
 * load the Unifi API connection class and log in to the controller to do our thing
 */
require_once('../phpapi/class.unifi.php');
$unifidata      = new unifiapi($controlleruser, $controllerpassword, $controllerurl, $site_id, $controllerversion); // initialize the class instance
$set_debug_mode = $unifidata->set_debug($debug);
$loginresults   = $unifidata->login(); // log into the controller

/**
 * using the "old" deprecated methods/functions
 */
//$data = $unifidata->set_locate_ap($mac); // uncomment to switch locating on
//$data = $unifidata->unset_locate_ap($mac); // uncomment to switch locating off (choose either of these two lines!)

/**
 * using the new method/function
 */
$data = $unifidata->locate_ap($mac, true); // uncomment to switch locating on
//$data = $unifidata->locate_ap($mac, false); // uncomment to switch locating off (choose either of these two lines!)

if ($data) {
    /**
     * provide feedback in json format
     */
    echo json_encode($data, JSON_PRETTY_PRINT);
} else {
    /**
     * method returned false so we display the raw results in json format
     */
    echo '<pre>';
    print_r($unifidata->get_last_results_raw(true));
    echo '</pre>';
}