<?php
/**
 * UniFi API Browser
 *
 * This tool is for browsing data that is exposed through Ubiquiti's UniFi Controller API,
 * written in PHP, javascript and the Bootstrap CSS framework.
 *
 * Please keep the following in mind:
 * - not all data collections/API endpoints are supported (yet), see the list below of
 *   the currently supported data collections/API endpoints
 * - currently only supports versions 4.x.x of the UniFi Controller software
 * - there is still work to be done to add/improve functionality and usability of this
 *   tool so suggestions/comments are welcome. Please use the github issue list or the
 *   Ubiquiti Community forums for this:
 *   https://community.ubnt.com/t5/UniFi-Wireless/UniFi-API-browser-tool-released/m-p/1392651
 *
 * VERSION: 1.0.5
 *
 * ------------------------------------------------------------------------------------
 *
 * Copyright (c) 2016, Slooffmaster
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.md
 *
 */

define('API_BROWSER_VERSION', '1.0.5');

/**
 * to use the PHP $_SESSION array for temporary storage of variables, session_start() is required
 */
session_start();

/**
 * starting timing of the session here
 */
$time_start = microtime(true);

/**
 * assign variables which are required later on together with their default values
 */
$controller_id = '';
$action        = '';
$site_id       = '';
$site_name     = '';
$selection     = '';
$output_format = 'json';
$theme         = 'bootstrap';
$data          = '';
$objects_count = '';
$alert_message = '';
$cookietimeout = '1800';
$debug         = FALSE;
$detected_controller_version = '';

/**
 * load the configuration file
 * - allows override of several of the previously declared variables
 * - if the config.php file is unreadable or does not exist, an alert is displayed on the page
 */
if(!is_readable('config.php')) {
    $alert_message = '<div class="alert alert-danger" role="alert">The file config.php is not readable or does not exist.'
                    . '<br>If you have not yet done so, please copy/rename the config.template.php file to config.php and modify'
                    . 'the contents as required.</div>';
} else {
    include('config.php');
}

/**
 * determine whether we have reached the cookie timeout, if so, refresh the PHP session
 * else, update last activity time stamp
 */
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $cookietimeout)) {
    /**
     * last activity was longer than $cookietimeout seconds ago
     */
    session_unset();
    session_destroy();
    if ($debug) {
        error_log('UniFi API browser INFO: session cookie timed out');
    }
}

$_SESSION['last_activity'] = time();

/**
 * collect cURL version details for the info modal
 */
$curl_info    = curl_version();
$curl_version = $curl_info['version'];

/**
 * process the GET variables and store them in the $_SESSION array
 * if a GET variable is not set, get the values from $_SESSION (if available)
 *
 * Process in this order:
 * - controller_id
 * Only process this after controller_id is set:
 * - site_id
 * Only process these after site_id is set:
 * - action
 * - output_format
 * - theme
 */
if (isset($_GET['controller_id'])) {
    $controller                = $controllers[$_GET['controller_id']];
    $controller_id             = $_GET['controller_id'];
    $_SESSION['controller']    = $controller;
    $_SESSION['controller_id'] = $_GET['controller_id'];

    unset($_SESSION['site_id']);
    unset($_SESSION['site_name']);
    unset($_SESSION['sites']);
    unset($_SESSION['action']);
    unset($_SESSION['detected_controller_version']);
} else {
    if (isset($_SESSION['controller']) && isset($controllers)) {
        $controller    = $_SESSION['controller'];
        $controller_id = $_SESSION['controller_id'];
    } else {
        if (!isset($controllers)) {
            /**
             * if the user has configured a single controller, we push it's details
             * to the $_SESSION and $controller arrays
             */
            $_SESSION['controller'] = array('user'     => $controlleruser,
                                            'password' => $controllerpassword,
                                            'url'      => $controllerurl,
                                            'name'     => 'Controller',
                                            'version'  => $controllerversion
                                        );
            $controller = $_SESSION['controller'];
        }
    }

    if (isset($_GET['site_id'])) {
        $site_id               = $_GET['site_id'];
        $_SESSION['site_id']   = $site_id;
        $site_name             = $_GET['site_name'];
        $_SESSION['site_name'] = $site_name;
    } else {
        if (isset($_SESSION['site_id'])) {
            $site_id   = $_SESSION['site_id'];
            $site_name = $_SESSION['site_name'];
        }
    }
}

/**
 * get requested theme or use the theme stored in $_SESSION
 */
if (isset($_GET['theme'])) {
    $theme             = $_GET['theme'];
    $_SESSION['theme'] = $theme;
} else {
    if (isset($_SESSION['theme'])) {
        $theme = $_SESSION['theme'];
    }
}

/**
 * get requested output_format or use the output_format stored in $_SESSION
 */
if (isset($_GET['output_format'])) {
    $output_format             = $_GET['output_format'];
    $_SESSION['output_format'] = $output_format;
} else {
    if (isset($_SESSION['output_format'])) {
        $output_format = $_SESSION['output_format'];
    }
}

/**
 * get requested action or use the action stored in $_SESSION
 */
if (isset($_GET['action'])) {
    $action             = $_GET['action'];
    $_SESSION['action'] = $action;
} else {
    if (isset($_SESSION['action'])) {
        $action = $_SESSION['action'];
    }
}

/**
 * display info message when no controller, site or data collection is selected
 * placed here so they can be overwritten by more "severe" error messages later down
 */
if ($action === '') {
    $alert_message = '<div class="alert alert-info" role="alert">Please select a data collection/API endpoint from the drop-down menus'
                    . ' <i class="fa fa-arrow-circle-up"></i></div>';
}

if ($site_id === '' && isset($_SESSION['controller'])) {
    $alert_message = '<div class="alert alert-info" role="alert">Please select a site from the drop-down menu <i class="fa fa-arrow-circle-up">'
                    . '</i></div>';
}

if (!isset($_SESSION['controller'])) {
    $alert_message = '<div class="alert alert-info" role="alert">Please select a controller from the drop-down menu <i class="fa fa-arrow-circle-up">'
                    . '</i></div>';
}

/**
 * load the UniFi API client class and log in to the controller
 * - if an error occurs during the login process, an alert is displayed on the page
 */
require('phpapi/class.unifi.php');

/**
 * Do this when a controller has been selected and was stored in $_SESSION
 */
if (isset($_SESSION['controller'])) {
    $unifidata        = new unifiapi($controller['user'], $controller['password'], $controller['url'], $site_id, $controller['version']);
    $unifidata->debug = $debug;
    $loginresults     = $unifidata->login();

    if($loginresults === 400) {
        $alert_message = '<div class="alert alert-danger" role="alert">HTTP response status: 400'
                        . '<br>This is probably caused by a UniFi controller login failure, please check your credentials in '
                        . 'config.php. After correcting your credentials, please restart your browser before attempting to use the API Browser tool again.</div>';
    }

    /**
     * Get the list of sites managed by the controller (if not already stored in $_SESSION)
     */
    if (!isset($_SESSION['sites']) || $_SESSION['sites'] === '') {
        $sites  = $unifidata->list_sites();
        $_SESSION['sites'] = $sites;
    } else {
        $sites = $_SESSION['sites'];
    }

    /**
     * Get the version of the controller (if not already stored in $_SESSION or when 'undetected')
     */
    if (!isset($_SESSION['detected_controller_version']) || $_SESSION['detected_controller_version'] === 'undetected') {
        $site_info = $unifidata->stat_sysinfo();

        if (isset($site_info[0]->version)) {
            $detected_controller_version             = $site_info[0]->version;
            $_SESSION['detected_controller_version'] = $detected_controller_version;
        } else {
            $detected_controller_version             = 'undetected';
            $_SESSION['detected_controller_version'] = 'undetected';
        }
    } else {
        $detected_controller_version = $_SESSION['detected_controller_version'];
    }
}

/**
 * execute timing of controller login
 */
$time_1           = microtime(true);
$time_after_login = $time_1 - $time_start;

if (isset($unifidata)) {
    /**
     * select the required call to the UniFi Controller API based on the selected action
     */
    switch ($action) {
        case 'list_clients':
            $selection = 'list online clients';
            $data      = $unifidata->list_clients();
            break;
        case 'stat_allusers':
            $selection = 'stat all users';
            $data      = $unifidata->stat_allusers();
            break;
        case 'stat_auths':
            $selection = 'stat active authorisations';
            $data      = $unifidata->stat_auths();
            break;
        case 'list_guests':
            $selection = 'list guests';
            $data      = $unifidata->list_guests();
            break;
        case 'list_usergroups':
            $selection = 'list usergroups';
            $data      = $unifidata->list_usergroups();
            break;
        case 'stat_hourly_site':
            $selection = 'hourly site stats';
            $data      = $unifidata->stat_hourly_site();
            break;
        case 'stat_sysinfo':
            $selection = 'sysinfo';
            $data      = $unifidata->stat_sysinfo();
            break;
        case 'stat_hourly_aps':
            $selection = 'hourly ap stats';
            $data      = $unifidata->stat_hourly_aps();
            break;
        case 'stat_daily_site':
            $selection = 'daily site stats';
            $data      = $unifidata->stat_daily_site();
            break;
        case 'list_devices':
            $selection = 'list devices';
            $data      = $unifidata->list_aps();
            break;
        case 'list_wlan_groups':
            $selection = 'list wlan groups';
            $data      = $unifidata->list_wlan_groups();
            break;
        case 'stat_sessions':
            $selection = 'stat sessions';
            $data      = $unifidata->stat_sessions();
            break;
        case 'list_users':
            $selection = 'list users';
            $data      = $unifidata->list_users();
            break;
        case 'list_rogueaps':
            $selection = 'list rogue access points';
            $data      = $unifidata->list_rogueaps();
            break;
        case 'list_events':
            $selection = 'list events';
            $data      = $unifidata->list_events();
            break;
        case 'list_alarms':
            $selection = 'list alerts';
            $data      = $unifidata->list_alarms();
            break;
        case 'list_wlanconf':
            $selection = 'list wlan config';
            $data      = $unifidata->list_wlanconf();
            break;
        case 'list_health':
            $selection = 'site health metrics';
            $data      = $unifidata->list_health();
            break;
        case 'list_dashboard':
            $selection = 'site dashboard metrics';
            $data      = $unifidata->list_dashboard();
            break;
        case 'list_settings':
            $selection = 'list site settings';
            $data      = $unifidata->list_settings();
            break;
        case 'list_sites':
            $selection = 'details of available sites';
            $data      = $sites;
            break;
        case 'list_extension':
            $selection = 'list VoIP extensions';
            $data      = $unifidata->list_extension();
            break;
        case 'list_portconf':
            $selection = 'list port configuration';
            $data      = $unifidata->list_portconf();
            break;
        case 'list_networkconf':
            $selection = 'list network configuration';
            $data      = $unifidata->list_networkconf();
            break;
        case 'list_dynamicdns':
            $selection = 'dynamic dns configuration';
            $data      = $unifidata->list_dynamicdns();
            break;
        case 'list_portforwarding':
            $selection = 'list port forwarding rules';
            $data      = $unifidata->list_portforwarding();
            break;
        case 'list_portforward_stats':
            $selection = 'list port forwarding stats';
            $data      = $unifidata->list_portforward_stats();
            break;
        case 'stat_voucher':
            $selection = 'list hotspot vouchers';
            $data      = $unifidata->stat_voucher();
            break;
        case 'stat_payment':
            $selection = 'list hotspot payments';
            $data      = $unifidata->stat_payment();
            break;
        case 'list_hotspotop':
            $selection = 'list hotspot operators';
            $data      = $unifidata->list_hotspotop();
            break;
        case 'list_self':
            $selection = 'self';
            $data      = $unifidata->list_self();
            break;
        case 'stat_sites':
            $selection = 'all site stats';
            $data      = $unifidata->stat_sites();
            break;
        default:
            break;
    }
}

/**
 * count the number of objects collected from the controller
 */
if($action!=''){
    $objects_count = count($data);
}

/**
 * create the url to the css file based on the selected theme (standard Bootstrap or one of the Bootswatch themes)
 */
if ($theme === 'bootstrap') {
    $cssurl = 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css';
} else {
    $cssurl = 'https://maxcdn.bootstrapcdn.com/bootswatch/3.3.6/' . $theme . '/bootstrap.min.css';
}

/**
 * execute timing of data collection from controller
 */
$time_2          = microtime(true);
$time_after_load = $time_2 - $time_start;

/**
 * calculate all the timings/percentages
 */
$time_end    = microtime(true);
$time_total  = $time_end - $time_start;
$login_perc  = ($time_after_login/$time_total)*100;
$load_perc   = (($time_after_load - $time_after_login)/$time_total)*100;
$remain_perc = 100-$login_perc-$load_perc;

/**
 * shared functions
 */
function print_output($output_format, $data)
{
    /**
     * function to print the output
     * switch depending on the selected $output_format
     */
    switch ($output_format) {
        case 'json':
            echo json_encode($data, JSON_PRETTY_PRINT);
            break;
        case 'json_color':
            echo '<code class="json">';
            echo json_encode($data, JSON_PRETTY_PRINT);
            echo '</code>';
            break;
        case 'php_array':
            print_r ($data);
            break;
        case 'php_var_dump':
            var_dump ($data);
            break;
        case 'php_var_export':
            var_export ($data);
            break;
        default:
            echo json_encode($data, JSON_PRETTY_PRINT);
            break;
    }
}

/**
 * function to sort the sites collection
 */
function sites_sort($a, $b)
{
    return strcmp($a->desc, $b->desc);
}

if (isset($_SESSION['controller'])) {
    /**
     * log off from the UniFi controller API
     */
    $logout_results = $unifidata->logout();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <title>UniFi API browser</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <!-- Latest compiled and minified versions of Bootstrap, Font-awesome and Highlight.js CSS, loaded from CDN -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?php echo $cssurl ?>">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.0.0/styles/default.min.css">
    <!-- custom CSS styling -->
    <style>
        body {
            padding-top: 70px;
        }

        .scrollable-menu {
            height: auto;
            max-height: 600px;
            overflow-x: hidden;
        }
    </style>
</head>
<body>
<!-- top navbar -->
<nav id="navbar" class="navbar navbar-default navbar-fixed-top">
    <div class="container-fluid">
        <div class="navbar-header">
            <button class="navbar-toggle collapsed" type="button" data-toggle="collapse" data-target="#navbar-main">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand hidden-sm hidden-md" href="index.php">UniFi API browser</a>
        </div>
        <div id="navbar-main" class="collapse navbar-collapse">
            <ul class="nav navbar-nav navbar-left">
                <!-- only show the controllers dropdown when multiple controllers have been configured -->
                <?php if (isset($controllers)) { ?>
                    <li id="site-menu" class="dropdown">
                        <a id="controller-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            <?php
                            /**
                             * here we display the controller name, if selected, else just label it
                             */
                            if (isset($controller)) {
                                echo $controller['name'];
                            } else {
                                echo 'Controllers';
                            }
                            ?>
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu scrollable-menu" id="controllerslist">
                            <li class="dropdown-header">Select a controller</li>
                            <?php
                            /**
                             * here we loop through the configured controllers
                             */
                            foreach ($controllers as $key => $value) {
                                echo '<li id="controller_' . $key . '"><a href="?controller_id=' . $key . '">' . $value['name'] . '</a></li>' . "\n";
                            }
                            ?>
                         </ul>
                    </li>
                <?php } ?>
                <!-- only show the sites dropdown when a controller is selected -->
                <?php if (isset($_SESSION['controller'])) { ?>
                    <li id="site-menu" class="dropdown">
                        <a id="site-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            Sites
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu scrollable-menu" id="siteslist">
                            <li class="dropdown-header">Select a site</li>
                            <?php
                            /**
                             * here we loop through the available sites, after we have sorted the sites collection
                             */
                            usort($sites, "sites_sort");

                            foreach ($sites as $site) {
                                echo '<li id="' . $site->name . '"><a href="?site_id=' . $site->name . '&site_name=' . $site->desc . '">' . $site->desc . '</a></li>' . "\n";
                            }
                            ?>
                         </ul>
                    </li>
                <?php } ?>
                <!-- only show the data collection dropdowns when a site_id is selected -->
                <?php if ($site_id) { ?>
                    <li id="output-menu" class="dropdown">
                        <a id="output-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            Output
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu" id="outputselection">
                            <li class="dropdown-header">Select an output format</li>
                            <li id="json"><a href="?output_format=json">json (default)</a></li>
                            <li role="separator" class="divider"></li>
                            <li id="php_array"><a href="?output_format=php_array">PHP array</a></li>
                            <li id="php_var_dump"><a href="?output_format=php_var_dump">PHP var_dump</a></li>
                            <li id="php_var_export"><a href="?output_format=php_var_export">PHP var_export</a></li>
                            <li role="separator" class="divider"></li>
                            <li class="dropdown-header">Nice but slow with large collections</li>
                            <li id="json_color"><a href="?output_format=json_color">json highlighted</a></li>
                        </ul>
                    </li>
                    <li id="user-menu" class="dropdown">
                        <a id="user-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            Clients
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li id="list_clients"><a href="?action=list_clients">list online clients</a></li>
                            <li id="list_guests"><a href="?action=list_guests">list guests</a></li>
                            <li id="list_users"><a href="?action=list_users">list users</a></li>
                            <li id="list_usergroups"><a href="?action=list_usergroups">list user groups</a></li>
                            <li role="separator" class="divider"></li>
                            <li id="stat_allusers"><a href="?action=stat_allusers">stat all users</a></li>
                            <li id="stat_auths"><a href="?action=stat_auths">stat authorisations</a></li>
                            <li id="stat_sessions"><a href="?action=stat_sessions">stat sessions</a></li>
                        </ul>
                    </li>
                    <li id="ap-menu" class="dropdown">
                        <a id="ap-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            Devices
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li id="list_devices"><a href="?action=list_devices">list devices</a></li>
                            <li id="list_wlan_groups"><a href="?action=list_wlan_groups">list wlan groups</a></li>
                            <li id="list_rogueaps"><a href="?action=list_rogueaps">list rogue access points</a></li>
                        </ul>
                    </li>
                    <li id="stats-menu" class="dropdown">
                        <a id="stats-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            Stats
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li id="stat_hourly_site"><a href="?action=stat_hourly_site">hourly site stats</a></li>
                            <li id="stat_daily_site"><a href="?action=stat_daily_site">daily site stats</a></li>
                            <?php if ($detected_controller_version != 'undetected' && version_compare($detected_controller_version, '5.2.9') >= 0) { ?>
                                <li id="list_dashboard"><a href="?action=stat_sites">all sites stats</a></li>
                            <?php } ?>
                            <li role="separator" class="divider"></li>
                            <li id="stat_hourly_aps"><a href="?action=stat_hourly_aps">hourly access point stats</a></li>
                            <li role="separator" class="divider"></li>
                            <li id="list_health"><a href="?action=list_health">site health metrics</a></li>
                            <?php if ($detected_controller_version != 'undetected' && version_compare($detected_controller_version, '4.9.1') >= 0) { ?>
                                <li id="list_dashboard"><a href="?action=list_dashboard">site dashboard metrics</a></li>
                            <?php } ?>
                            <li id="list_portforward_stats"><a href="?action=list_portforward_stats">port forwarding stats</a></li>
                        </ul>
                    </li>
                    <li id="hotspot-menu" class="dropdown">
                        <a id="msg-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            Hotspot
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li id="stat_voucher"><a href="?action=stat_voucher">stat vouchers</a></li>
                            <li id="stat_payment"><a href="?action=stat_payment">stat payments</a></li>
                            <li role="separator" class="divider"></li>
                            <li id="list_hotspotop"><a href="?action=list_hotspotop">list hotspot operators</a></li>
                        </ul>
                    </li>
                    <li id="config-menu" class="dropdown">
                        <a id="config-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            Configuration
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li id="list_sites"><a href="?action=list_sites">list sites on this controller</a></li>
                            <li id="stat_sysinfo"><a href="?action=stat_sysinfo">sysinfo</a></li>
                            <li id="list_self"><a href="?action=list_self">self</a></li>
                            <li role="separator" class="divider"></li>
                            <li id="list_settings"><a href="?action=list_settings">list site settings</a></li>
                            <li role="separator" class="divider"></li>
                            <li id="list_wlanconf"><a href="?action=list_wlanconf">list wlan configuration</a></li>
                            <li role="separator" class="divider"></li>
                            <li id="list_extension"><a href="?action=list_extension">list VoIP extensions</a></li>
                            <li role="separator" class="divider"></li>
                            <li id="list_networkconf"><a href="?action=list_networkconf">list network configuration</a></li>
                            <li id="list_portconf"><a href="?action=list_portconf">list port configuration</a></li>
                            <li id="list_portforwarding"><a href="?action=list_portforwarding">list port forwarding rules</a></li>
                            <li id="list_dynamicdns"><a href="?action=list_dynamicdns">dynamic DNS configuration</a></li>
                        </ul>
                    </li>
                    <li id="msg-menu" class="dropdown">
                        <a id="msg-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            Messages
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li id="list_alarms"><a href="?action=list_alarms">list alerts</a></li>
                            <li id="list_events"><a href="?action=list_events">list events</a></li>
                        </ul>
                    </li>
                <?php } ?>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li id="theme-menu" class="dropdown">
                    <a id="theme-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-bars fa-lg"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">Select a theme</li>
                        <li id="bootstrap"><a href="?theme=bootstrap">default Bootstrap</a></li>
                        <li id="cerulean"><a href="?theme=cerulean">Cerulean</a></li>
                        <li id="cosmo"><a href="?theme=cosmo">Cosmo</a></li>
                        <li id="cyborg"><a href="?theme=cyborg">Cyborg</a></li>
                        <li id="darkly"><a href="?theme=darkly">Darkly</a></li>
                        <li id="flatly"><a href="?theme=flatly">Flatly</a></li>
                        <li id="journal"><a href="?theme=journal">Journal</a></li>
                        <li id="lumen"><a href="?theme=lumen">Lumen</a></li>
                        <li id="paper"><a href="?theme=paper">Paper</a></li>
                        <li id="readable"><a href="?theme=readable">Readable</a></li>
                        <li id="sandstone"><a href="?theme=sandstone">Sandstone</a></li>
                        <li id="simplex"><a href="?theme=simplex">Simplex</a></li>
                        <li id="slate"><a href="?theme=slate">Slate</a></li>
                        <li id="spacelab"><a href="?theme=spacelab">Spacelab</a></li>
                        <li id="superhero"><a href="?theme=superhero">Superhero</a></li>
                        <li id="united"><a href="?theme=united">United</a></li>
                        <li id="yeti"><a href="?theme=yeti">Yeti</a></li>
                        <li role="separator" class="divider"></li>
                        <li id="info" data-toggle="modal" data-target="#aboutModal"><a href="#"><i class="fa fa-info-circle"></i> About</a></li>
                    </ul>
                </li>
            </ul>
        </div><!-- /.nav-collapse -->
    </div><!-- /.container-fluid -->
</nav><!-- /navbar-example -->
<div class="container-fluid">
    <div id="alertPlaceholder">
        <?php echo $alert_message ?>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <?php if ($site_id) { ?>
                site id: <span class="label label-primary"><?php echo $site_id ?></span>
                site name: <span class="label label-primary"><?php echo $site_name ?></span>
            <?php } ?>
            <?php if ($selection) { ?>
                collection: <span class="label label-primary"><?php echo $selection ?></span>
            <?php } ?>
            output: <span class="label label-primary"><?php echo $output_format ?></span>
            <?php if ($objects_count !== '') { ?>
                # of objects: <span class="badge"><?php echo $objects_count ?></span>
            <?php } ?>
        </div>
        <div class="panel-body">
            <!--only display panel content when an action has been selected-->
            <?php if ($action !== '') { ?>
            <!-- present the timing results using an HTML5 progress bar -->
            total elapsed time: <?php echo $time_total ?> seconds<br>
            <div class="progress">
                <div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="<?php echo $login_perc ?>"
                aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $login_perc ?>%;" data-toggle="tooltip"
                data-placement="bottom" data-original-title="<?php echo $time_after_login ?> seconds">
                    API login time
                </div>
                <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="<?php echo $load_perc ?>"
                aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $load_perc ?>%;" data-toggle="tooltip"
                data-placement="bottom" data-original-title="<?php echo ($time_after_load - $time_after_login) ?> seconds">
                    data load time
                </div>
                <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuenow="<?php echo $remain_perc ?>"
                aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $remain_perc ?>%;" data-toggle="tooltip"
                data-placement="bottom" data-original-title="PHP overhead: <?php echo $remain_perc ?> seconds">
                    PHP overhead
                </div>
            </div>
            <pre><?php print_output($output_format, $data) ?></pre>
            <?php } ?>
        </div>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="aboutModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"><i class="fa fa-info-circle"></i> About UniFi API Browser</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-10 col-sm-offset-1">
                        A tool for browsing the data collections which are exposed through Ubiquiti's UniFi Controller API.
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-8 col-sm-offset-2"><a href="https://github.com/malle-pietje/UniFi-API-browser"
                    target="_blank">UniFi API browser on Github</a></div>
                    <div class="col-sm-8 col-sm-offset-2"><a href="http://community.ubnt.com/t5/UniFi-Wireless/UniFi-API-browser-tool-updates-and-discussion/m-p/1392651#U1392651"
                    target="_blank">UniFi API browser on Ubiquiti Community forum</a></div>
                </div>
                <hr>
                <dl class="dl-horizontal col-sm-offset-1">
                    <dt>API Browser version</dt>
                    <dd><span class="label label-primary"><?php echo API_BROWSER_VERSION ?></span></dd>
                    <dt>API Class version</dt>
                    <dd><span class="label label-primary"><?php echo API_CLASS_VERSION ?></span></dd>
                </dl>
                <hr>
                <dl class="dl-horizontal col-sm-offset-1">
                    <dt>controller user</dt>
                    <dd><span class="label label-primary"><?php if (isset($_SESSION['controller'])) { echo $controller['user']; } ?></span></dd>
                    <dt>controller url</dt>
                    <dd><span class="label label-primary"><?php if (isset($_SESSION['controller'])) { echo $controller['url']; } ?></span></dd>
                    <dt>version detected</dt>
                    <dd><span class="label label-primary"><?php if (isset($_SESSION['controller'])) { echo $detected_controller_version; } ?></span></dd>
                </dl>
                <hr>
                <dl class="dl-horizontal col-sm-offset-1">
                    <dt>cookie timeout setting</dt>
                    <dd><span class="label label-primary"><?php echo $cookietimeout ?> seconds</span></dd>
                </dl>
                <hr>
                <dl class="dl-horizontal col-sm-offset-1">
                    <dt>PHP version</dt>
                    <dd><span class="label label-primary"><?php echo (phpversion()) ?></span></dd>
                    <dt>PHP memory_limit</dt>
                    <dd><span class="label label-primary"><?php echo (ini_get('memory_limit')) ?></span></dd>
                    <dt>PHP memory used</dt>
                    <dd><span class="label label-primary"><?php echo round(memory_get_peak_usage(false)/1024/1024, 2) . 'M' ?></span></dd>
                    <dt>cURL version</dt>
                    <dd><span class="label label-primary"><?php echo $curl_version ?></span></dd>
                    <dt>operating system</dt>
                    <dd><span class="label label-primary"><?php echo (php_uname('s') . ' ' . php_uname('r')) ?></span></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<!-- Latest compiled and minified JavaScript versions, loaded from CDN's -->
<script src="https://code.jquery.com/jquery-2.2.0.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.0.0/highlight.min.js"></script>
<script>
    /**
    * initialise the Highlighting.js library
    */
    hljs.initHighlightingOnLoad();
</script>
<script>
    /**
    * highlight selected options in the pull down menus
    * for $action, $site_id, $theme and $output_format:
    */
    $('#<?php echo $theme ?>').addClass('active').find('a').append(' <i class="fa fa-check"></i>');
    $('#<?php echo $action ?>').addClass('active').find('a').append(' <i class="fa fa-check"></i>');
    $('#<?php echo $site_id ?>').addClass('active').find('a').append(' <i class="fa fa-check"></i>');
    $('#<?php echo $output_format ?>').addClass('active').find('a').append(' <i class="fa fa-check"></i>');
    $('#controller_<?php echo $controller_id ?>').addClass('active').find('a').append(' <i class="fa fa-check"></i>');

    /**
    * enable Bootstrap tooltips
    */
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
</script>
</body>
</html>