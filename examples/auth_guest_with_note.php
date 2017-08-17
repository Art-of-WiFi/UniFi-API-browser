<?php
/**
 * PHP API usage example
 *
 * contributed by: Art of WiFi
 * description: example basic PHP script to auth a guest device and attach a note to it
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
 * the note to attach to the device
 */
$note = 'Note to attach to newly authorized device';

/**
 * load the Unifi API connection class and log in to the controller
 */
require_once('../phpapi/class.unifi.php');
$unifidata      = new UnifiApi($controlleruser, $controllerpassword, $controllerurl, $site_id, $controllerversion);
$set_debug_mode = $unifidata->set_debug($debug);
$loginresults   = $unifidata->login();

/**
 * we authorize the device for the requested duration and attach the note to it's object
 */
$auth_result  = $unifidata->authorize_guest($mac, $duration);
$getid_result = $unifidata->stat_client($mac);
$user_id      = $getid_result[0]->_id;
$note_result  = $unifidata->set_sta_note($user_id, $note);

/**
 * When using older Controller versions (< 5.5.x) to attach a note to a new (unconnected) device, we instead need to take the
 * following steps before authorizing the device:
 * - first block the device to get an entry in the user collection
 * - get the device id from the user collection
 * - attach note to the device
 * - then unblock the device again **after the authorization has taken place**
 */
//$block_result   = $unifidata->block_sta($mac);
//$getid_result   = $unifidata->stat_client($mac);
//$user_id        = $getid_result[0]->_id;
//$note_result    = $unifidata->set_sta_note($user_id, $note);
//$unblock_result = $unifidata->unblock_sta($mac);
//$auth_result    = $unifidata->authorize_guest($mac, $duration);

/**
 * provide feedback in json format
 */
echo json_encode($auth_result, JSON_PRETTY_PRINT);