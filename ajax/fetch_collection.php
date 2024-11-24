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
require_once '../common.php';
require_once '../collections.php';

use Kint\Renderer\TextRenderer;
use Kint\Renderer\RichRenderer;
use UniFi_API\Client as ApiClient;

/**
 * Load the configuration file if readable.
 *
 * @var bool $debug
 */
if (!is_file('../config/config.php') || !is_readable('../config/config.php')) {
    exit;
}

include '../config/config.php';

/**
 * To use the PHP $_SESSION array for temporary storage of variables, session_start() is required.
 */
session_start();

/**
 * An array containing attributes to fetch for the gateway stats, overriding
 * the default attributes.
 */
$gateway_stats_attribs = [
    'time',
    'mem',
    'cpu',
    'loadavg_5',
    'lan-rx_errors',
    'lan-tx_errors',
    'lan-rx_bytes',
    'lan-tx_bytes',
    'lan-rx_packets',
    'lan-tx_packets',
    'lan-rx_dropped',
    'lan-tx_dropped',
    'wan-tx_bytes',
    'wan-rx_bytes',
    'max_rx_bytes-r',
    'max_tx_bytes-r',
    'wan2-tx_bytes',
    'wan2-rx_bytes',
    'latency_min',
    'latency_avg',
    'latency_max',
];

/**
 * Initialize the $results array.
 */
$results = [
    'state'   => 'success',
    'message' => 'successfully fetched collection',
    'timings' => [
        'login'      => 0,
        'load'       => 0,
        'login_perc' => 0,
        'load_perc'  => 0,
    ],
    'count'   => 0,
    'data'    => [],
];

$output_method = 'json';

if (!empty($_SESSION['controller'])) {
    $method = '';
    $params = [];

    /**
     * POSTed object properties:
     * selected_collection_method
     * selected_collection_label
     * selected_collection_key
     * selected_collection_params
     * selected_collection_group
     */
    error_log('fetching results for collection:' . $_POST['selected_collection_label']);

    if (!empty($_POST['selected_collection_method'])) {
        $method = $_POST['selected_collection_method'];
    }

    if (!empty($_POST['selected_collection_params'])) {
        $params = json_decode($_POST['selected_collection_params']);
    }

    if (!empty($_POST['selected_site_id'])) {
        $site_id = $_POST['selected_site_id'];
    }

    if (!empty($_POST['selected_output_method'])) {
        $output_method = $_POST['selected_output_method'];
    }

    $gateway_stats_methods = [
        'stat_5minutes_gateway',
        'stat_hourly_gateway',
        'stat_daily_gateway',
        'stat_monthly_gateway',
    ];

    if (empty($params) && in_array($method, $gateway_stats_methods)) {
        $params = [null, null, $gateway_stats_attribs];
    }

    if (!empty($method) && !empty($site_id)) {
        $time_start = microtime(true);
        $controller = $_SESSION['controller'];

        /**
         * Create an instance of the Unifi API client class, log in to the controller and pull the requested data.
         */
        $unifi_connection = new ApiClient(
            trim($controller['user']),
            trim($controller['password']),
            trim(rtrim($controller['url'], "/")),
            $site_id
        );

        $login_results = $unifi_connection->login();

        /**
         * Check for login errors.
         */
        if ($login_results === 400) {
            $results['state']   = 'error';
            $results['message'] = 'UniFi controller login failure, please check your credentials in config/config.php!';
        } else {
            /**
             * We can safely continue.
             */
            $time_1           = microtime(true);
            $time_after_login = $time_1 - $time_start;

            /**
             * We then determine which method is required and which parameters to pass.
             * https://stackoverflow.com/questions/1005857/how-to-call-a-function-from-a-string-stored-in-a-variable
             */
            if (count($params) === 0) {
                $data_array = $unifi_connection->{$method}();
            } else {
                $data_array = $unifi_connection->{$method}(...$params);
            }

            if (!empty($data_array)) {
                /**
                 * Count the array items and inject $data_array into $results.
                 */
                $results['count'] = count($data_array);

                /**
                 * For results returned from API v2, we need to check for the 'data' key and count items in that array.
                 */
                if(key_exists('data', $data_array)) {
                    $results['count'] = count($data_array['data']);
                }

                if ($debug) {
                    error_log('DEBUG: ' . $results['count'] . ' objects collected');
                }

                if ($output_method === 'kint') {
                    /**
                     * For Kint, we need to return the results in a slightly different manner.
                     *
                     * @note using Rich render mode
                     */
                    Kint::$display_called_from = false;
                    RichRenderer::$folder      = false;
                    $results['data']           = @d($data_array);
                } else {
                    if ($output_method === 'kint_plain') {
                        /**
                         * @note using Plain render mode
                         */
                        Kint::$display_called_from = false;
                        RichRenderer::$folder      = false;
                        TextRenderer::$decorations = false;
                        $results['data']           = @s($data_array);
                    } else {
                        $results['data'] = $data_array;
                    }
                }
            }

            /**
             * Execute timing of data collection from UniFi controller.
             */
            $time_2          = microtime(true);
            $time_after_load = $time_2 - $time_start;

            /**
             * Calculate all the timings/percentages.
             */
            $time_end         = microtime(true);
            $time_total       = $time_end - $time_start;
            $login_percentage = ($time_after_login / $time_total) * 100;
            $load_percentage  = (($time_after_load - $time_after_login) / $time_total) * 100;

            $results['timings']['login']      = $time_after_login;
            $results['timings']['load']       = $time_after_load;
            $results['timings']['login_perc'] = $login_percentage;
            $results['timings']['load_perc']  = $load_percentage;
        }
    }
}

returnJson($results);

$_SESSION['memory_used'] = round(memory_get_peak_usage(false) / 1024 / 1024, 2) . 'MB';
