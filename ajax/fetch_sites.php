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
 *
 * @var string $unknown_string
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

/**
 * Initialize the $results array.
 */
$results = [
    'state'   => 'success',
    'message' => 'successfully fetched sites',
    'count'   => 0,
    'data'    => [],
];

if (!empty($_SESSION['controller'])) {
    $controller = $_SESSION['controller'];

    /**
     * We first check for connectivity to the host and port provided in the URL.
     */
    $host = parse_url($controller['url'], PHP_URL_HOST);
    $port = parse_url($controller['url'], PHP_URL_PORT) ?: 443;

    if (!empty($host) && !empty($port)) {
        /**
         * Create an instance of the Unifi API client class, log in to the controller and pull the requested data.
         *
         * @note the error *messages* are for consumption by the user, not for logging
         */
        try {
            $unifi_connection = new UniFi_API\Client(
                trim($controller['user']),
                trim($controller['password']),
                trim(rtrim($controller['url'], "/")),
                'default'
            );

            $unifi_connection->login();
        } catch (CurlExtensionNotLoadedException $e) {
            $results['state']   = 'error';
            $results['message'] = 'cURL is not available in your PHP installation!';
            error_log('Exception: ' . get_class($e) . ' - Message: ' . $e->getMessage());
        } catch (CurlGeneralErrorException $e) {
            $results['state']   = 'error';
            $results['message'] = 'We have encountered a general cURL error: ' . $e->getMessage();
            error_log('Exception: ' . get_class($e) . ' - Message: ' . $e->getMessage());
        } catch (CurlTimeoutException $e) {
            $results['state']   = 'error';
            $results['message'] = 'UniFi controller connection timeout!';
            error_log('Exception: ' . get_class($e) . ' - Message: ' . $e->getMessage());
        } catch (InvalidBaseUrlException $e) {
            $results['state']   = 'error';
            $results['message'] = 'UniFi controller login failure, base URL is invalid!';
            error_log('Exception: ' . get_class($e) . ' - Message: ' . $e->getMessage());
        } catch (InvalidSiteNameException $e) {
            $results['state']   = 'error';
            $results['message'] = 'UniFi controller login failure, site name is invalid!';
            error_log('Exception: ' . get_class($e) . ' - Message: ' . $e->getMessage());
        } catch (LoginFailedException $e) {
            $results['state']   = 'error';
            $results['message'] = 'UniFi controller login failure, please check the URL and credentials in config/config.php!';
            error_log('Exception: ' . get_class($e) . ' - Message: ' . $e->getMessage());
        } catch (Exception $e) {
            $results['state']   = 'error';
            $results['message'] = 'An Exception was thrown:' . $e->getMessage();
            error_log('Exception: ' . get_class($e) . ' - Message: ' . $e->getMessage());
        }

        if ($results['state'] === 'error' || empty($unifi_connection)) {
            error_log('we have an error, bailing out');
            $_SESSION['memory_used'] = round(memory_get_peak_usage(false) / 1024 / 1024, 2) . 'MB';
            returnJson($results);

            exit;
        }

        /**
         * We can safely continue.
         *
         * @note the error *messages* are for consumption by the user, not for logging
         */
        try {
            $sites_array = $unifi_connection->list_sites();
        } catch (JsonDecodeException $e) {
            $results['state']   = 'error';
            $results['message'] = 'JSON decode error!';
            error_log('Exception: ' . get_class($e) . ' - Message: ' . $e->getMessage());
            return;
        } catch (LoginRequiredException $e) {
            $results['state']   = 'error';
            $results['message'] = 'Login is required for this endpoint';
            error_log('Exception: ' . get_class($e) . ' - Message: ' . $e->getMessage());
            return;
        } catch (LoginFailedException $e) {
            $results['state']   = 'error';
            $results['message'] = 'UniFi controller login failure, please check your credentials in config/config.php!';
            error_log('Exception: ' . get_class($e) . ' - Message: ' . $e->getMessage());
            return;
        } catch (Exception $e) {
            $results['state']   = 'error';
            $results['message'] = 'An Exception was thrown:' . $e->getMessage();
            error_log('Exception: ' . get_class($e) . ' - Message: ' . $e->getMessage());
            return;
        }

        if (!empty($sites_array) && is_array($sites_array)) {
            if ($debug) {
                error_log('DEBUG: ' . count($sites_array) . ' sites collected');
            }

            /**
             * Store the cookies from the controller for faster reconnecting.
             */
            $_SESSION['unificookie'] = $unifi_connection->get_cookie();

            /**
             * Loop through the fetched sites.
             */
            foreach ($sites_array as $site) {
                $results['data'][] = [
                    'site_id'        => $site->name ?? $unknown_string,
                    'site_full_name' => $site->desc ?? $unknown_string,
                ];
            }

            /**
             * Sort the site array by full name.
             */
            usort($results['data'], function ($a, $b) {
                if ($a['site_full_name'] == $b['site_full_name']) {
                    return 0;
                }

                return ($a['site_full_name'] < $b['site_full_name']) ? -1 : 1;
            });

            /**
             * Get the first site from the $results array, just to be sure we use a valid site.
             */
            try {
                $switch_site = $unifi_connection->set_site(($results['data'][0]['site_id']));
                $site_info   = $unifi_connection->stat_sysinfo();
            } catch (InvalidSiteNameException $e) {
                $results['state']   = 'error';
                $results['message'] = 'The site name is invalid!';
                error_log('Exception: ' . get_class($e) . ' - Message: ' . $e->getMessage());
                return;
            } catch (Exception $e) {
                $results['state']   = 'error';
                $results['message'] = 'An Exception was thrown:' . $e->getMessage();
                error_log('Exception: ' . get_class($e) . ' - Message: ' . $e->getMessage());
                return;
            }

            if (!empty($site_info) && isset($site_info[0]->version)) {
                $_SESSION['controller']['detected_version'] = $site_info[0]->version;
            } else {
                $_SESSION['controller']['detected_version'] = 'undetected';
            }
        }
    } else {
        error_log('we have an invalid URL! ' . $controller['url']);
        $results['state']   = 'error';
        $results['message'] = 'the UniFi controller URL (' . $controller['url'] . ') provided in the config/config.php file is invalid!';
    }
}

$_SESSION['memory_used'] = round(memory_get_peak_usage(false) / 1024 / 1024, 2) . 'MB';

returnJson($results);