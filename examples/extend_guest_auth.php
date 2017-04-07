<?php
/**
 * PHP API usage example
 *
 * contributed by: mtotone
 * description: example of how to extend validity of guest authorizations
 */

require_once ("../phpapi/class.unifi.php");
require_once ("../config.php");

// must be adapted to your site!
$site_id = "default";
$site_name = "*enter your site name*";

$unifidata = new unifiapi ($controlleruser, $controllerpassword, $controllerurl, $site_id, $controllerversion);
$unifidata->debug = false;
$loginresults = $unifidata->login();

if ($loginresults === 400) {
	print "UniFi controller login failure, please check your credentials in config.php.\n";
}
else {
	$guestlist = $unifidata->list_guests();
	// print "<pre>"; print_r ($guestlist); print "</pre>";
	// loop thru all known guests
	foreach ($guestlist as $guest) {
		// print "<pre>"; print_r ($guest); print "</pre>";
		print "<pre>" . $guest->_id . " (" . $guest->mac . "), valid until " . date (DATE_ATOM, $guest->end) . " (" . $guest->end . ")</pre>";

		// just a sample: only extend validity of guests which have end date after 2017-04-02
		if ($guest->end > 1491166482) {
			// extend clients five times = five days
			if (!$unifidata->extend_guest_validity ($guest->_id)) print "Extend failed for guest with id " . $guest->_id . "\n";
			if (!$unifidata->extend_guest_validity ($guest->_id)) print "Extend failed for guest with id " . $guest->_id . "\n";
			if (!$unifidata->extend_guest_validity ($guest->_id)) print "Extend failed for guest with id " . $guest->_id . "\n";
			if (!$unifidata->extend_guest_validity ($guest->_id)) print "Extend failed for guest with id " . $guest->_id . "\n";
			if (!$unifidata->extend_guest_validity ($guest->_id)) print "Extend failed for guest with id " . $guest->_id . "\n";
		}
	}

	$logout_results = $unifidata->logout();
}