<?php
/**
 * Copyright © 2024, Art of WiFi
 * www.artofwifi.net
 *
 * @license This file is subject to the MIT license bundled with this package in the file LICENSE.md
 */

/**
 * Load required packages using the composer autoloader together with the files containing shared functions
 * and the menu options.
 */
use UniFi_API\Exceptions\CurlExtensionNotLoadedException;
use UniFi_API\Exceptions\CurlGeneralErrorException;
use UniFi_API\Exceptions\CurlTimeoutException;
use UniFi_API\Exceptions\InvalidBaseUrlException;
use UniFi_API\Exceptions\InvalidSiteNameException;
use UniFi_API\Exceptions\JsonDecodeException;
use UniFi_API\Exceptions\LoginFailedException;
use UniFi_API\Exceptions\LoginRequiredException;

require_once '../common.php';
require_once '../collections.php';

/**
 * Load the configuration file if readable.
 */
if (!is_file('../config/config.php') || !is_readable('../config/config.php')) {
    exit;
}

/**
 * Include the configuration file.
 *
 * @var array $controllers
 * @var bool $debug
 */
include '../config/config.php';

/**
 * To use the PHP $_SESSION array for temporary storage of variables, session_start() is required.
 */
session_start();

if ($debug === true) {
    if (!empty($_SESSION['controller'])) {
        $controller = $_SESSION['controller'];

        /**
         * We first check for connectivity to the host and port provided in the URL.
         */
        $host = parse_url($controller['url'], PHP_URL_HOST);
        $port = parse_url($controller['url'], PHP_URL_PORT) ?: 443;

        if (!empty($host) && !empty($port)) {
            /**
             * Create an instance of the Unifi API client class, log in to the controller and pull the sites.
             */
            try {
                $unifi_connection = new UniFi_API\Client(
                    trim($controller['user']),
                    trim($controller['password']),
                    trim(rtrim($controller['url'], "/")),
                    'default'
                );

                $unifi_connection->set_debug(true);
                $unifi_connection->login();
            } catch (CurlExtensionNotLoadedException $e) {
                error_log('DEBUG - CurlExtensionNotLoadedException: ' . $e->getMessage());
                echo 'cURL not available in your PHP installation!' . PHP_EOL . PHP_EOL;
                exit;
            } catch (CurlGeneralErrorException $e) {
                error_log('DEBUG - CurlGeneralErrorException: ' . $e->getMessage());
                echo 'General cURL error! Response code: ' . $e->getHttpResponseCode() . PHP_EOL . PHP_EOL;
                exit;
            } catch (CurlTimeoutException $e) {
                error_log('DEBUG - CurlTimeoutException: ' . $e->getMessage());
                echo 'UniFi controller login failure, cURL timeout!' . PHP_EOL . PHP_EOL;
                exit;
            } catch (InvalidBaseUrlException $e) {
                error_log('DEBUG - InvalidBaseUrlException: ' . $e->getMessage());
                echo 'UniFi controller login failure, invalid base URL!' . PHP_EOL . PHP_EOL;
                exit;
            } catch (InvalidSiteNameException $e) {
                error_log('DEBUG - InvalidSiteNameException: ' . $e->getMessage());
                echo 'UniFi controller login failure, invalid site name!' . PHP_EOL . PHP_EOL;
                exit;
            } catch (LoginFailedException $e) {
                error_log('DEBUG - LoginFailedException: ' . $e->getMessage());
                echo 'UniFi controller login failure, please check your credentials in config/config.php!' . PHP_EOL . PHP_EOL;
                exit;
            } catch (Exception $e) {
                error_log('DEBUG - Exception: ' . $e->getMessage());
                echo 'We have an Exception!' . PHP_EOL . PHP_EOL;
                exit;
            }

            /**
             * We can safely continue.
             */
            try {
                $sites_array = $unifi_connection->list_sites();
            } catch (JsonDecodeException $e) {
                error_log('DEBUG - JsonDecodeException: ' . $e->getMessage());
                echo 'we have a JSON decode exception!' . PHP_EOL . PHP_EOL;
            } catch (LoginRequiredException $e) {
                error_log('DEBUG - LoginRequiredException: ' . $e->getMessage());
                echo 'we have a login required exception!' . PHP_EOL . PHP_EOL;
            } catch (LoginFailedException $e) {
                error_log('DEBUG - LoginFailedException: ' . $e->getMessage());
                echo 'UniFi controller login failure, please check your credentials in config/config.php!' . PHP_EOL . PHP_EOL;
            } catch (Exception $e) {
                error_log('DEBUG - Exception: ' . $e->getMessage());
                echo 'we have an Exception!' . PHP_EOL . PHP_EOL;
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
