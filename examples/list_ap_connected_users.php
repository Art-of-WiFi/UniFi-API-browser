<?php
/**
 * PHP API usage example
 *
 * contributed by: slooffmaster
 * description: example to pull connected user numbers for Access Points from the Unifi controller and output the results
 *              in raw HTML format
 */

/**
 * include the config file (place your credentials etc there if not already present)
 *
 * NOTE:
 * this example will only work out of the box with a single controller config file!
 */
require_once('../config.php');

/**
 * the short name of the site which you wish to query
 */
$site_id = '<enter your site id here>';

/**
 * load the Unifi API connection class and log in to the controller and pull the requested data
 */
require_once('../phpapi/class.unifi.php');
$unifidata    = new unifiapi($controlleruser, $controllerpassword, $controllerurl, $site_id, $controllerversion);
$loginresults = $unifidata->login();
$aps_array    = $unifidata->list_aps();

/**
 * output the results in HTML format
 */
header('Content-Type: text/html; charset=utf-8');
foreach ($aps_array as $ap) {
	if ($ap->type === 'uap') {
		echo '<b>AP name:</b>' . $ap->name . ' <b>model:</b>' . $ap->model . ' <b># connected clients:</b>' . $ap->num_sta . '<br>';
	}
}