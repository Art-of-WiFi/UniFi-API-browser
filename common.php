<?php
/**
 * Copyright © 2024, Art of WiFi
 * www.artofwifi.net
 *
 * @license This file is subject to the MIT license bundled with this package in the file LICENSE.md
 */

require_once 'vendor/autoload.php';

use UniFi_API\Client as ApiClient;

const TOOL_VERSION = '2.0.29';

/**
 * Gather some basic information for the About modal.
 */
$curl_info      = curl_version();
$unknown_string = 'unknown';

/**
 * Create the array to pass on to the twig templates.
 */
$about_modal_params = [
    'os_version'          => php_uname('s') . ' ' . php_uname('r'),
    'php_version'         => phpversion(),
    'memory_limit'        => ini_get('memory_limit') . 'B',
    'memory_used'         => round(memory_get_peak_usage() / 1024 / 1024, 2) . 'MB',
    'curl_version'        => $curl_info['version'],
    'openssl_version'     => $curl_info['ssl_version'],
    'api_client_version'  => getClientVersion(),
    'api_browser_version' => TOOL_VERSION,
];

/**
 * The version of the included API client class.
 *
 * @return string
 */
function getClientVersion(): string
{
    $unifi_connection = new ApiClient('user', 'password');
    return $unifi_connection->get_class_version();
}

/**
 * Output the results with correct JSON formatting.
 *
 * @param $results
 * @return void
 */
function returnJson($results): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo(json_encode($results));
}

