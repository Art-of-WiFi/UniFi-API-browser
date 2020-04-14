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

if ($debug === true) {
    if (!empty($_SESSION['controller']) && $debug === true) {
        $controller = $_SESSION['controller'];

        /**
         * we first check for connectivity to the host and port provided in the URL
         */
        $host = parse_url($controller['url'], PHP_URL_HOST);
        $port = parse_url($controller['url'], PHP_URL_PORT) ?: 443;

        if (!empty($host) && !empty($port)) {
            $fp = @fsockopen($host, $port, $errno, $errstr, 2);
            if(!$fp) {
                echo "we are unable to connect to the UniFi controller {$errstr} ({$errno})" . PHP_EOL . PHP_EOL;
            } else {
                /**
                 * and we can continue
                 */
                fclose($fp);

                /**
                 * create an instance of the Unifi API client class, log in to the controller and pull the sites
                 */
                $unifi_connection = new UniFi_API\Client(trim($controller['user']), trim($controller['password']), trim(rtrim($controller['url'], "/")), 'default');
                $set_debug_mode   = $unifi_connection->set_debug($debug);
                $loginresults     = $unifi_connection->login();

                /**
                 * check for login errors
                 */
                if ($loginresults === 400) {
                    echo 'UniFi controller login failure, please check your credentials in config/config.php!' . PHP_EOL . PHP_EOL;
                } else {
                    /**
                     * we can safely continue
                     */
                    $sites_array = $unifi_connection->list_sites();
                }
            }
        } else {
            echo 'we have an invalid URL! ' . $controller['url'] . PHP_EOL . PHP_EOL;
        }
    } else {
        echo 'no UniFi controller selected!' . PHP_EOL . PHP_EOL;
    }
} else {
    echo 'ignore';
}