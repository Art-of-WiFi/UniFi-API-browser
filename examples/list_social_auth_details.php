<?php
/**
 * PHP API usage example
 *
 * contributed by: slooffmaster
 * description: example basic PHP script to pull Facebook social auth details from the UniFi controller and output
 *              them in basic HTML format
 */

/**
 * include the config file (place you credentials etc. there if not already present)
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
$unifidata    = new unifiapi($controlleruser, $controllerpassword, $controllerurl, $site_id, $controllerversion);
$loginresults = $unifidata->login();
$data         = $unifidata->stat_payment();

/**
 * cycle through the results and print social auth details if set,
 * at this stage you can choose to do with the payment objects whatever is needed
 */
echo 'Results from Facebook social auth:<br>';
foreach ($data as $payment) {
    if (isset($payment->gateway) && $payment->gateway == 'facebook') {
        echo 'First name: ' . $payment->first_name . ' Last name: ' . $payment->last_name . ' E-mail address: ' . $payment->email . '<br>';
    }
}

echo '<hr><br>';