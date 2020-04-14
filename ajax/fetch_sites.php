<?php
/**
 * Copyright (c) 2019, Art of WiFi
 * www.artofwifi.net
 *
 * This file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.md
 *
 */

/**
 * load required packages using the composer autoloader together with the files containing shared functions
 * and the collections
 */
require_once('../common.php');
require_once('../collections.php');
require_once('../vendor/autoload.php');

/**
 * load the configuration file if readable
 */
if (is_file('../config/config.php') && is_readable('../config/config.php')) {
    include('../config/config.php');
} else {
    die();
}

/**
 * in order to use the PHP $_SESSION array for temporary storage of variables, session_start() is required
 */
session_start();

/**
 * initialize the results array
 */
$results = [
    'state'   => 'success',
    'message' => 'successfully fetched sites',
    'count'   => 0,
    'data'    => []
];

if (!empty($_SESSION['controller'])) {
    $controller = $_SESSION['controller'];

    /**
     * we first check for connectivity to the host and port provided in the URL
     */
    $host = parse_url($controller['url'], PHP_URL_HOST);
    $port = parse_url($controller['url'], PHP_URL_PORT) ?: 443;

    if (!empty($host) && !empty($port)) {
        $fp = @fsockopen($host, $port, $errno, $errstr, 2);
        if(!$fp) {
            error_log("we have a connection error {$errstr} ({$errno})");
            $results['state']   = 'error';
            $results['message'] = "we are unable to connect to the UniFi controller, {$errstr} ({$errno})!";
        } else {
            /**
             * and we can continue
             */
            fclose($fp);

            /**
             * create an instance of the Unifi API client class, log in to the controller and pull the requested data
             */
            $unifi_connection = new UniFi_API\Client(trim($controller['user']), trim($controller['password']), trim(rtrim($controller['url'], "/")), 'default');
            $loginresults     = $unifi_connection->login();

            /**
             * check for login errors
             */
            if ($loginresults === 400) {
                $results['state']   = 'error';
                $results['message'] = 'UniFi controller login failure, please check your credentials in config/config.php!';
            } else {
                /**
                 * we can safely continue
                 */
                $sites_array = $unifi_connection->list_sites();

                if (!empty($sites_array)) {
                    if ($debug) {
                        error_log('DEBUG: ' . count($sites_array) . ' sites collected');
                    }

                    if (empty($sites_array)) {
                        $sites_array = [];
                    }

                    /**
                     * store the cookies from the controller for faster reconnects
                     */
                    $_SESSION['unificookie'] = $unifi_connection->get_cookie();

                    /**
                     * loop through the fetched sites
                     */
                    foreach ($sites_array as $site) {
                        $results['data'][] = [
                            'site_id'        => isset($site->name) ? $site->name : $unknown_string,
                            'site_full_name' => isset($site->desc) ? $site->desc : $unknown_string
                        ];
                    }

                    /**
                     * sort the sites array by full name
                     */
                    usort($results['data'], function($a, $b) {
                        if ($a['site_full_name'] == $b['site_full_name']) {
                            return 0;
                        }

                        return ($a['site_full_name'] < $b['site_full_name']) ? -1 : 1;
                    });

                    /**
                     * get the first site from the results array, just to be sure we use a valid site
                     */
                    $switch_site = $unifi_connection->set_site(($results['data'][0]['site_id']));
                    $site_info   = $unifi_connection->stat_sysinfo();

                    if (!empty($site_info) && isset($site_info[0]->version)) {
                        $_SESSION['controller']['detected_version'] = $site_info[0]->version;
                    } else {
                        $_SESSION['controller']['detected_version'] = 'undetected';
                    }
                }
            }
        }
    } else {
        error_log('we have an invalid URL! ' . $controller['url']);
        $results['state']   = 'error';
        $results['message'] = 'the UniFi controller URL (' . $controller['url'] . ') provided in the config/config.php file is invalid!';
    }
}

/**
 * output the results with correct json formatting
 */
header('Content-Type: application/json; charset=utf-8');
echo (json_encode($results));

$_SESSION['memory_used'] = round(memory_get_peak_usage(false) / 1024 / 1024, 2) . 'M';