<?php
/**
 * PHP API usage example
 *
 * contributed by: slooffmaster
 * description: example basic PHP script to auth a guest device and attach a note to it
 */

/**
 * include the config file (place you credentials etc. there if not already present)
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
 * the note to attach to the device
 */
$note = 'Note to attach to newly authorized device';

/**
 * The site to authorize the device with
 */
$site_id = '<enter your site id here>';

/**
 * load the Unifi API connection class and log in to the controller
 */
require_once('../phpapi/class.unifi.php');
$unifidata    = new unifiapi($controlleruser, $controllerpassword, $controllerurl, $site_id, $controllerversion);
$loginresults = $unifidata->login();

/**
 * To add note to a new device we need to do the following before authorizing the device:
 * - first block the device to get an entry in the user collection
 * - get the device id from the user collection
 * - add note to the device
 * - then unblock the device again
 */
$block_result   = $unifidata->block_sta($mac);
$getid_result   = $unifidata->stat_client($mac);
$user_id        = $getid_result[0]->_id;
$note_result    = $unifidata->set_sta_note($user_id, $note);
$unblock_result = $unifidata->unblock_sta($mac);

/**
 * then we authorize the device for the requested duration
 */
$auth_result = $unifidata->authorize_guest($mac, $duration);

/**
 * provide feedback in json format
 */
echo json_encode($auth_result, JSON_PRETTY_PRINT);