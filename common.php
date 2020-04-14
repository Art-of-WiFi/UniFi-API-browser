<?php
/**
 * Copyright (c) 2019, Art of WiFi
 * www.artofwifi.net
 *
 * This file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.md
 *
 */
define('TOOL_VERSION', '2.0.9');

/**
 * gather some basic information for the About modal
 */
$curl_info = curl_version();
$unknown_string = 'unknown';

/**
 * create the array to pass on to the twig templates
 */
$about_modal_params = [
    'os_version'          => php_uname('s') . ' ' . php_uname('r'),
    'php_version'         => phpversion(),
    'memory_limit'        => ini_get('memory_limit'),
    'memory_used'         => round(memory_get_peak_usage(false) / 1024 / 1024, 2) . 'M',
    'curl_version'        => $curl_info['version'],
    'openssl_version'     => $curl_info['ssl_version'],
    'api_client_version'  => getClientVersion(),
    'api_browser_version' => TOOL_VERSION,
];

/**
 * common functions from here
 */

/**
 * function which returns the version of the included API client class by
 * extracting it from the composer.lock file
 */
function getClientVersion()
{
    if (is_readable('composer.lock')) {
        $composer_lock = file_get_contents('composer.lock');
        $json_decoded = json_decode($composer_lock, true);
        if (isset($json_decoded['packages'])) {
            foreach ($json_decoded['packages'] as $package) {
                if ($package['name'] === 'art-of-wifi/unifi-api-client') {
                    return substr($package['version'], 1);
                }
            }
        }
    }
    return 'unknown';
}