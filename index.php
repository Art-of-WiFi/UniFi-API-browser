<?php
/**
 * UniFi API browser
 *
 * This tool is for browsing data that is exposed through Ubiquiti's UniFi Controller API,
 * written in PHP, JavaScript and the Bootstrap CSS framework.
 *
 * Please keep the following in mind:
 * - not all data collections/API endpoints are supported (yet), see the list of
 *   the currently supported data collections/API endpoints in the README.md file
 * - this tool currently supports versions 4.x and 5.x of the UniFi Controller software
 *
 * VERSION: 1.0.20
 *
 * ------------------------------------------------------------------------------------
 *
 * Copyright (c) 2017, Art of WiFi
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.md
 *
 */
define('API_BROWSER_VERSION', '1.0.20');

/**
 * check whether the PHP curl module is available
 */
if (!function_exists('curl_version')) {
    exit('The <b>PHP curl</b> module is not installed! Please correct this before you proceed!<br>');
}

/**
 * in order to use the PHP $_SESSION array for temporary storage of variables, session_start() is required
 */
session_start();

/**
 * check whether user has requested to clear (force expiry
 * ) the PHP session
 * - this function can be useful when login errors occur, mostly after upgrades or incorrect credential changes
 */
if (isset($_GET['reset_session']) && $_GET['reset_session'] == true) {
    $_SESSION = [];
    session_unset();
    session_destroy();
    session_start();
}

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
$data          = '';
$objects_count = '';
$alert_message = '';

/**
 * load the configuration file
 * - allows override of several of the previously declared variables
 * - if the config.php file is unreadable or does not exist, an alert is displayed on the page
 */
if (!is_readable('config.php')) {
    $alert_message = '<div class="alert alert-danger" role="alert">The file <code>config.php</code> is not readable or does not exist.'
                   . '<br>If you have not yet done so, please copy/rename the <code>config.template.php</code> file to <code>config.php</code> and follow '
                   . 'the instructions inside to enter your credentials and controller details.</div>';
} else {
    require_once('config.php');
}

/**
 * load the UniFi API client class
 */
require_once('phpapi/class.unifi.php');

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
 * if a GET variable is not set, get the values from the $_SESSION array (if available)
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
    /**
     * user has requested a controller switch
     */
    $controller                = $controllers[$_GET['controller_id']];
    $controller_id             = $_GET['controller_id'];
    $_SESSION['controller']    = $controller;
    $_SESSION['controller_id'] = $_GET['controller_id'];

    /**
     * clear the variables from the $_SESSION array that are associated with the previous controller session
     */
    unset($_SESSION['site_id']);
    unset($_SESSION['site_name']);
    unset($_SESSION['sites']);
    unset($_SESSION['action']);
    unset($_SESSION['detected_controller_version']);
    unset($_SESSION['unificookie']);
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
            $_SESSION['controller'] = [
                'user'     => $controlleruser,
                'password' => $controllerpassword,
                'url'      => $controllerurl,
                'name'     => 'Controller',
                'version'  => $controllerversion
            ];
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
 * get requested theme or use the theme stored in the $_SESSION array
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
 * get requested output_format or use the output_format stored in the $_SESSION array
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
 * get requested action or use the action stored in the $_SESSION array
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
 * placed here so they can be overwritten by more "severe" error messages later on
 */
if ($action === '') {
    $alert_message = '<div class="alert alert-info" role="alert">Please select a data collection/API endpoint from the dropdown menus'
                   . ' <i class="fa fa-arrow-circle-up"></i></div>';
}

if ($site_id === '' && isset($_SESSION['controller'])) {
    $alert_message = '<div class="alert alert-info" role="alert">Please select a site from the Sites dropdown menu <i class="fa fa-arrow-circle-up">'
                   . '</i></div>';
}

if (!isset($_SESSION['controller'])) {
    $alert_message = '<div class="alert alert-info" role="alert">Please select a controller from the Controllers dropdown menu <i class="fa fa-arrow-circle-up">'
                   . '</i></div>';
}

/**
 * Do this when a controller has been selected and was stored in the $_SESSION array
 */
if (isset($_SESSION['controller'])) {
    /**
     * create a new instance of the API client class and log in to the UniFi controller
     * - if an error occurs during the login process, an alert is displayed on the page
     */
    $unifidata      = new UnifiApi($controller['user'], $controller['password'], $controller['url'], $site_id, $controller['version']);
    $set_debug_mode = $unifidata->set_debug(trim($debug));
    $loginresults   = $unifidata->login();

    if ($loginresults === 400) {
        $alert_message = '<div class="alert alert-danger" role="alert">HTTP response status: 400'
                       . '<br>This is probably caused by a UniFi controller login failure, please check your credentials in '
                       . 'config.php. After correcting your credentials, please restart your browser or use the <b>Reset PHP session</b> function '
                       . 'in the dropdown menu on the right, before attempting to use the API browser tool again.</div>';

        /**
         * to prevent unwanted errors we assign empty values to the following variables
         */
        $sites                       = [];
        $detected_controller_version = 'undetected';
    } else {
        /**
         * Remember authentication cookie to the controller.
         */
        $_SESSION['unificookie'] = $unifidata->getcookie();

        /**
         * Get the list of sites managed by the UniFi controller (if not already stored in the $_SESSION array)
         */
        if (!isset($_SESSION['sites']) || empty($_SESSION['sites'])) {
            $sites = $unifidata->list_sites();
            if (is_array($sites)) {
                $_SESSION['sites'] = $sites;
            } else {
                $sites = [];

                $alert_message = '<div class="alert alert-danger" role="alert">No sites available'
                               . '<br>This is probably caused by incorrect access rights in the UniFi controller for the credentials provided in '
                               . 'config.php. After updating your credentials, please restart your browser or use the <b>Reset PHP session</b> '
                               . 'function in the dropdown menu on the right, before attempting to use the API browser tool again.</div>';
            }

        } else {
            $sites = $_SESSION['sites'];
        }

        /**
         * Get the version of the UniFi controller (if not already stored in the $_SESSION array or when 'undetected')
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
        case 'stat_daily_site':
            $selection = 'daily site stats';
            $data      = $unifidata->stat_daily_site();
            break;
        case 'stat_hourly_aps':
            $selection = 'hourly ap stats';
            $data      = $unifidata->stat_hourly_aps();
            break;
        case 'stat_daily_aps':
            $selection = 'daily ap stats';
            $data      = $unifidata->stat_daily_aps();
            break;
        case 'stat_sysinfo':
            $selection = 'sysinfo';
            $data      = $unifidata->stat_sysinfo();
            break;
        case 'list_devices':
            $selection = 'list devices';
            $data      = $unifidata->list_devices();
            break;
        case 'list_tags':
            $selection = 'list tags';
            $data      = $unifidata->list_tags();
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
            $selection = 'list alarms';
            $data      = $unifidata->list_alarms();
            break;
        case 'count_alarms':
            $selection = 'count all alarms';
            $data      = $unifidata->count_alarms();
            break;
        case 'count_active_alarms':
            $selection = 'count active alarms';
            $data      = $unifidata->count_alarms(false);
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
        case 'list_current_channels':
            $selection = 'current channels';
            $data      = $unifidata->list_current_channels();
            break;
        case 'list_portforwarding':
            $selection = 'list port forwarding rules';
            $data      = $unifidata->list_portforwarding();
            break;
        case 'list_portforward_stats':
            $selection = 'list port forwarding stats';
            $data      = $unifidata->list_portforward_stats();
            break;
        case 'list_dpi_stats':
            $selection = 'list DPI stats';
            $data      = $unifidata->list_dpi_stats();
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
        case 'list_admins':
            $selection = 'list_admins';
            $data      = $unifidata->list_admins();
            break;
        case 'list_radius_accounts':
            $selection = 'list_radius_accounts';
            $data      = $unifidata->list_radius_accounts();
            break;
        default:
            break;
    }
}

/**
 * count the number of objects collected from the UniFi controller
 */
if ($action != '') {
    $objects_count = count($data);
}

/**
 * construct the url for the Bootstrap CSS file based on the selected theme (standard Bootstrap or one of the Bootswatch themes)
 */
if ($theme === 'bootstrap') {
    $css_url = 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css';
} else {
    $css_url = 'https://maxcdn.bootstrapcdn.com/bootswatch/3.3.7/' . trim($theme) . '/bootstrap.min.css';
}

/**
 * execute timing of data collection from UniFi controller
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
$remain_perc = 100 - $login_perc-$load_perc;

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
            echo json_encode($data);
            break;
        case 'php_array':
            print_r($data);
            break;
        case 'php_var_dump':
            var_dump($data);
            break;
        case 'php_var_export':
            var_export($data);
            break;
        default:
            echo json_encode($data, JSON_PRETTY_PRINT);
            break;
    }
}

/**
 * function to sort the sites collection alpabetically by description
 */
function sites_sort($site_a, $site_b)
{
    return strcmp($site_a->desc, $site_b->desc);
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
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">

    <!-- Load the appropriate Bootstrap CSS file from CDN -->
    <link rel="stylesheet" href="<?php echo $css_url ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-jsonview/1.2.3/jquery.jsonview.min.css" integrity="sha256-OhImf+9TMPW5iYXKNT4eRNntf3fCtVYe5jZqo/mrSQA=" crossorigin="anonymous">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">

    <!-- Load the base64 encoded favicon file  -->
    <link rel="icon" type="image/x-icon" sizes="16x16" href="data:image/x-icon;base64,AAABAAEAEBAAAAEAIABoBAAAFgAAACgAAAAQAAAAIAAAAAEAIAAAAAAAAAQAABILAAASCwAAAAAAAAAAAADgpAn/4KQJ/+CkCf/gpAn/4KQJ/+CkCf/gpAn/4KQJ/+CkCf/gown/4KQL/+CkDP/enQD/8NOK///////y2Zr/358A/9+fAP/fnwD/358A/9+gAP/foAH/36EB/9+gAP/fnwD/36AA/9+gAv/dmQD/79CB/////////v3//vz4/9+gAP/foAD/36ED/9+hA//engD/3ZsA/92bAP/enAD/36EC/9+hBP/dmgD/8dSM///////+/Pj///////Pbnv/foAD/36EC/96dAf/dmwD/46sc/+i6Rf/ovEr/5bAr/9+fBP/dmgD/8deS///////+/Pb///////LYlf/enAD/36ED/92bAP/jrSf/9eKw//779v/////////////////36MH/9N+q///////+/fn///////HXkv/dmwD/36AD/92bAP/mtj///vv0//////////7////////////////////////////+/fv///////DUiv/dmgD/36AD/9+hAf/iqBv/+/Ti///////+/fr/8tiX/+e3Pf/mszT/7cpx//z36P///v3///////Pbn//cmAD/36EF/9+gAf/foAD/8dWR////////////68Vi/9yWAP/enAL/3pwC/9yXAP/krij//Pbn///////36MP/358A/9+gAf/foAD/36AA//ry2///////9uW7/92aAP/gogX/36ED/9+hA//gowj/3JcA/+3Jc/////////77/+SwK//enAD/36EC/9+gAP/+/Pf//////+7Of//dmgD/4KIE/9+gAP/foAD/36EC/96cAP/lszf////////////ou0n/3ZsA/9+hA//foAD//frz///////w0or/3ZkA/+CiBf/foAD/36AA/9+hA//enAD/5rZA////////////57lE/92bAP/foQP/36AA//jry///////+u/U/9+fB//engD/4KIF/+CiBf/gogX/3JYA//HWk////////frx/+OrHv/enQD/36EC/9+gAP/syG3////////////z253/358C/92ZAP/dmgL/3ZoA/+vEYP/+/fv///////Xgrv/dmwD/36EC/9+gAP/foAD/358C//fmvP////////////rw1v/w0ob/7s16//bluv///v3///////368f/krir/3p0A/9+hAv/foAD/36AA/96eAP/hpRP/9+jA/////////v3///////////////7///////z36f/mtj7/3ZsA/9+hAv/foAD/36AA/9+gAP/foQH/3p4B/96eBf/tyWz/+e3Q//779f/+/fn/+/Pg//HXkv/iqBr/3ZsA/9+hA//foAD/36AA/9+gAP/foAD/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==">

    <!-- custom CSS styling -->
    <style>
        body {
            padding-top: 70px;
        }

        .scrollable-menu {
            height: auto;
            max-height: 600px;
            overflow-x: hidden;
            overflow-y: auto;
        }

        #output_panel_loading {
            color: rgba(0,0,0,.4);
        }

        .back-to-top {
            cursor: pointer;
            position: fixed;
            bottom: 20px;
            right: 20px;
            display:none;
        }
    </style>
    <!-- /custom CSS styling -->
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
            <a class="navbar-brand hidden-sm hidden-md" href="index.php"><i class="fa fa-search fa-fw fa-lg" aria-hidden="true"></i> UniFi API browser</a>
        </div>
        <div id="navbar-main" class="collapse navbar-collapse">
            <ul class="nav navbar-nav navbar-left">
                <!-- controllers dropdown, only show when multiple controllers have been configured -->
                <?php if (isset($controllers)) { ?>
                    <li id="site-menu" class="dropdown">
                        <a id="controller-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            <?php
                            /**
                             * here we display the UniFi controller name, if selected, else just label it
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
                             * here we loop through the configured UniFi controllers
                             */
                            foreach ($controllers as $key => $value) {
                                echo '<li id="controller_' . $key . '"><a href="?controller_id=' . $key . '">' . $value['name'] . '</a></li>' . "\n";
                            }
                            ?>
                         </ul>
                    </li>
                <?php } ?>
                <!-- /controllers dropdown -->
                <!-- sites dropdown, only show when a controller has been selected -->
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
                <!-- /sites dropdown -->
                <!-- data collection dropdowns, only show when a site_id is selected -->
                <?php if ($site_id && isset($_SESSION['controller'])) { ?>
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
                            <li class="dropdown-header">Select a data collection</li>
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
                            <li class="dropdown-header">Select a data collection</li>
                            <li id="list_devices"><a href="?action=list_devices">list devices</a></li>
                            <li id="list_wlan_groups"><a href="?action=list_wlan_groups">list wlan groups</a></li>
                            <li id="list_rogueaps"><a href="?action=list_rogueaps">list rogue access points</a></li>
                            <!-- all sites stats, only to be displayed when we have detected a capable controller version -->
                            <?php if ($detected_controller_version != 'undetected' && version_compare($detected_controller_version, '5.5.0') >= 0) { ?>
                                <li role="separator" class="divider"></li>
                                <li id="list_tags"><a href="?action=list_tags">list tags</a></li>
                            <?php } ?>
                        </ul>
                    </li>
                    <li id="stats-menu" class="dropdown">
                        <a id="stats-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            Stats
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="dropdown-header">Select a data collection</li>
                            <li id="stat_hourly_site"><a href="?action=stat_hourly_site">hourly site stats</a></li>
                            <li id="stat_daily_site"><a href="?action=stat_daily_site">daily site stats</a></li>
                            <!-- all sites stats, only to be displayed when we have detected a capable controller version -->
                            <?php if ($detected_controller_version != 'undetected' && version_compare($detected_controller_version, '5.2.9') >= 0) { ?>
                                <li id="stat_sites"><a href="?action=stat_sites">all sites stats</a></li>
                            <?php } ?>
                            <!-- /all sites stats -->
                            <li role="separator" class="divider"></li>
                            <li id="stat_hourly_aps"><a href="?action=stat_hourly_aps">hourly access point stats</a></li>
                            <li id="stat_daily_aps"><a href="?action=stat_daily_aps">daily access point stats</a></li>
                            <li role="separator" class="divider"></li>
                            <li id="list_health"><a href="?action=list_health">site health metrics</a></li>
                            <!-- site dashboard metrics, only to be displayed when we have detected a capable controller version -->
                            <?php if ($detected_controller_version != 'undetected' && version_compare($detected_controller_version, '4.9.1') >= 0) { ?>
                                <li id="list_dashboard"><a href="?action=list_dashboard">site dashboard metrics</a></li>
                            <?php } ?>
                            <!-- /site dashboard metrics -->
                            <li id="list_portforward_stats"><a href="?action=list_portforward_stats">port forwarding stats</a></li>
                            <li id="list_dpi_stats"><a href="?action=list_dpi_stats">DPI stats</a></li>
                        </ul>
                    </li>
                    <li id="hotspot-menu" class="dropdown">
                        <a id="msg-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            Hotspot
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="dropdown-header">Select a data collection</li>
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
                            <li class="dropdown-header">Select a data collection</li>
                            <li id="list_sites"><a href="?action=list_sites">list sites on this controller</a></li>
                            <li id="stat_sysinfo"><a href="?action=stat_sysinfo">sysinfo</a></li>
                            <li id="list_self"><a href="?action=list_self">self</a></li>
                            <li role="separator" class="divider"></li>
                            <li id="list_settings"><a href="?action=list_settings">list site settings</a></li>
                            <li id="list_admins"><a href="?action=list_admins">list admins for current site</a></li>
                            <li role="separator" class="divider"></li>
                            <li id="list_wlanconf"><a href="?action=list_wlanconf">list wlan configuration</a></li>
                            <li role="separator" class="divider"></li>
                            <li id="list_extension"><a href="?action=list_extension">list VoIP extensions</a></li>
                            <li role="separator" class="divider"></li>
                            <li id="list_networkconf"><a href="?action=list_networkconf">list network configuration</a></li>
                            <li id="list_portconf"><a href="?action=list_portconf">list port configuration</a></li>
                            <li id="list_portforwarding"><a href="?action=list_portforwarding">list port forwarding rules</a></li>
                            <li id="list_current_channels"><a href="?action=list_current_channels">list current channels</a></li>
                            <li id="list_dynamicdns"><a href="?action=list_dynamicdns">dynamic DNS configuration</a></li>
                            <li role="separator" class="divider"></li>
                            <li id="list_radius_accounts"><a href="?action=list_radius_accounts">list Radius accounts</a></li>
                        </ul>
                    </li>
                    <li id="msg-menu" class="dropdown">
                        <a id="msg-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            Messages
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="dropdown-header">Select a data collection</li>
                            <li id="list_alarms"><a href="?action=list_alarms">list alarms</a></li>
                            <li id="count_alarms"><a href="?action=count_alarms">count all alarms</a></li>
                            <li id="count_active_alarms"><a href="?action=count_active_alarms">count active alarms</a></li>
                            <li id="list_events"><a href="?action=list_events">list events</a></li>
                        </ul>
                    </li>
                <?php } ?>
                <!-- /data collection dropdowns -->
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li id="theme-menu" class="dropdown">
                    <a id="theme-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-bars fa-lg"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">Select a theme</li>
                        <li id="bootstrap"><a href="?theme=bootstrap">Bootstrap (default)</a></li>
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
                        <li id="reset_session" data-toggle="tooltip" data-placement="top" data-original-title="In some cases this will fix login errors (e.g. empty sites list)">
                            <a href="?reset_session=true"><i class="fa fa-refresh"></i> Reset PHP session</a>
                        </li>
                        <li role="separator" class="divider"></li>
                        <li id="info" data-toggle="modal" data-target="#about_modal"><a href="#"><i class="fa fa-info-circle"></i> About UniFi API browser</a></li>
                    </ul>
                </li>
            </ul>
        </div><!-- /.nav-collapse -->
    </div><!-- /.container-fluid -->
</nav><!-- /top navbar -->
<div class="container-fluid">
    <div id="alert_placeholder"></div>
    <!-- data-panel, only to be displayed once a controller has been configured and an action has been selected, while loading we display a temp div -->
    <?php if (isset($_SESSION['controller']) && $action) { ?>
    <div id="output_panel_loading" class="text-center">
        <br>
        <h2><i class="fa fa-spinner fa-spin fa-fw"></i></h2>
    </div>
    <div id="output_panel" class="panel panel-default" style="display: none">
        <div class="panel-heading">
            <!-- site info, only to be displayed when a site has been selected -->
            <?php if ($site_id) { ?>
                site id: <span id="span_site_id" class="label label-primary"></span>
                site name: <span id="span_site_name" class="label label-primary"></span>
            <?php } ?>
            <!-- /site info -->
            <!-- selection, only to be displayed when a selection has been made -->
            <?php if ($selection) { ?>
                collection: <span id="span_selection" class="label label-primary"></span>
            <?php } ?>
            <!-- /selection -->
            output: <span id="span_output_format" class="label label-primary"></span>
            <!-- objects_count, only to be displayed when we have results -->
            <?php if ($objects_count !== '') { ?>
                # of objects: <span id="span_objects_count" class="badge"></span>
            <?php } ?>
            <!-- /objects_count -->
        </div>
        <div class="panel-body">
            <!--only display panel content when an action has been selected-->
            <?php if ($action !== '' && isset($_SESSION['controller'])) { ?>
                <!-- present the timing results using an HTML5 progress bar -->
                <span id="span_elapsed_time"></span>
                <br>
                <div class="progress">
                    <div id="timing_login_perc" class="progress-bar progress-bar-warning" role="progressbar" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="bottom"></div>
                    <div id="timing_load_perc" class="progress-bar progress-bar-success" role="progressbar" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="bottom"></div>
                    <div id="timing_remain_perc" class="progress-bar progress-bar-primary" role="progressbar" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="bottom"></div>
                </div>
                <div id="toggle_button" style="display: none">
                    <button id="toggle-btn" type="button" class="btn btn-primary btn-xs"><i id="i_toggle-btn" class="fa fa-minus" aria-hidden="true"></i> Expand/collapse</button>
                    <button id="toggle-level2-btn" type="button" class="btn btn-primary btn-xs"><i id="i_toggle-level2-btn" class="fa fa-minus" aria-hidden="true"></i> Expand/collapse level 2</button>
                    <br><br>
                </div>
                <div id="output" style="display: none">
                    <pre id="pre_output"><?php print_output($output_format, $data) ?></pre>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
    <!-- /data-panel -->
</div>
<!-- Back to Top button element -->
<a id="back-to-top" href="#" class="btn btn-primary back-to-top" role="button" title="Back to top" data-toggle="tooltip" data-placement="left"><i class="fa fa-chevron-up" aria-hidden="true"></i></a>
<!-- /Back to Top button element -->
<!-- Modal -->
<div class="modal fade" id="about_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"><i class="fa fa-info-circle"></i> About UniFi API browser</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-10 col-sm-offset-1">
                        A tool for browsing the data collections which are exposed through Ubiquiti's UniFi Controller API.
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-8 col-sm-offset-1"><a href="http://www.dereferer.org/?https://github.com/Art-of-WiFi/UniFi-API-browser"
                    target="_blank">UniFi API browser on Github</a></div>
                    <div class="col-sm-8 col-sm-offset-1"><a href="http://www.dereferer.org/?http://community.ubnt.com/t5/UniFi-Wireless/UniFi-API-browser-tool-updates-and-discussion/m-p/1392651#U1392651"
                    target="_blank">UniFi API browser on Ubiquiti Community forum</a></div>
                </div>
                <hr>
                <dl class="dl-horizontal col-sm-offset-2">
                    <dt>API browser version</dt>
                    <dd><span id="span_api_browser_version" class="label label-primary"></span> <span id="span_api_browser_update" class="label label-success"><i class="fa fa-spinner fa-spin fa-fw"></i> checking for updates</span></dd>
                    <dt>API client version</dt>
                    <dd><span id="span_api_class_version" class="label label-primary"></span></dd>
                </dl>
                <hr>
                <dl class="dl-horizontal col-sm-offset-2">
                    <dt>controller user</dt>
                    <dd><span id="span_controller_user" class="label label-primary"></span></dd>
                    <dt>controller URL</dt>
                    <dd><span id="span_controller_url" class="label label-primary"></span></dd>
                    <dt>version detected</dt>
                    <dd><span id="span_controller_version" class="label label-primary"></span></dd>
                </dl>
                <hr>
                <dl class="dl-horizontal col-sm-offset-2">
                    <dt>PHP version</dt>
                    <dd><span id="span_php_version" class="label label-primary"></span></dd>
                    <dt>PHP memory_limit</dt>
                    <dd><span id="span_memory_limit" class="label label-primary"></span></dd>
                    <dt>PHP memory used</dt>
                    <dd><span id="span_memory_used" class="label label-primary"></span></dd>
                    <dt>cURL version</dt>
                    <dd><span id="span_curl_version" class="label label-primary"></span></dd>
                    <dt>operating system</dt>
                    <dd><span id="span_os_version" class="label label-primary"></span></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<!-- Latest compiled and minified JavaScript versions, loaded from CDN's, now including Source Integrity hashes, just in case... -->
<script src="https://code.jquery.com/jquery-2.2.4.min.js" integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-jsonview/1.2.3/jquery.jsonview.min.js" integrity="sha256-yB+xHoEi5PoOnEAgHNbRMIbN4cNtOXAmBzkhNE/tQlI=" crossorigin="anonymous"></script>
<script>
$(document).ready(function() {
    /**
     * we hide the loading div and show the output panel
     */
    $("#output_panel_loading").hide();
    $("#output_panel").show();

    /**
     * populate some Javascript variables with PHP output for cleaner code
     */
    var alert_message       = '<?php echo $alert_message ?>';
    var action              = '<?php echo $action ?>';
    var site_id             = '<?php echo $site_id ?>';
    var site_name           = '<?php echo $site_name ?>';
    var controller_id       = '<?php echo $controller_id ?>';
    var output_format       = '<?php echo $output_format ?>';
    var selection           = '<?php echo $selection ?>';
    var objects_count       = '<?php echo $objects_count ?>';
    var timing_login_perc   = '<?php echo $login_perc ?>';
    var time_after_login    = '<?php echo $time_after_login ?>';
    var timing_load_perc    = '<?php echo $load_perc ?>';
    var time_for_load       = '<?php echo ($time_after_load - $time_after_login) ?>';
    var timing_remain_perc  = '<?php echo $remain_perc ?>';
    var timing_total_time   = '<?php echo $time_total ?>';
    var theme               = '<?php echo $theme ?>';
    var php_version         = '<?php echo (phpversion()) ?>';
    var memory_limit        = '<?php echo (ini_get('memory_limit')) ?>';
    var memory_used         = '<?php echo round(memory_get_peak_usage(false)/1024/1024, 2) . 'M' ?>';
    var curl_version        = '<?php echo $curl_version ?>';
    var os_version          = '<?php echo (php_uname('s') . ' ' . php_uname('r')) ?>';
    var api_browser_version = '<?php echo API_BROWSER_VERSION ?>';
    var api_class_version   = '<?php echo API_CLASS_VERSION ?>';
    var controller_user     = '<?php if (isset($_SESSION['controller'])) echo $controller['user'] ?>';
    var controller_url      = '<?php if (isset($_SESSION['controller'])) echo $controller['url'] ?>';
    var controller_version  = '<?php if (isset($_SESSION['controller'])) echo $detected_controller_version ?>';

    /**
     * update dynamic elements in the DOM using some of the above variables
     */
    $('#alert_placeholder').html(alert_message);
    $('#span_site_id').html(site_id);
    $('#span_site_name').html(site_name);
    $('#span_output_format').html(output_format);
    $('#span_selection').html(selection);
    $('#span_objects_count').html(objects_count);

    $('#span_elapsed_time').html('total elapsed time: ' + timing_total_time + ' seconds');

    $('#timing_login_perc').attr('aria-valuenow', timing_login_perc);
    $('#timing_login_perc').css('width', timing_login_perc + '%');
    $('#timing_login_perc').attr('data-original-title', time_after_login + ' seconds');
    $('#timing_login_perc').html('API login time');

    $('#timing_load_perc').attr('aria-valuenow', timing_load_perc);
    $('#timing_load_perc').css('width', timing_load_perc + '%');
    $('#timing_load_perc').attr('data-original-title', time_for_load + ' seconds');
    $('#timing_load_perc').html('data load time');

    $('#timing_remain_perc').attr('aria-valuenow', timing_remain_perc);
    $('#timing_remain_perc').css('width', timing_remain_perc + '%');
    $('#timing_remain_perc').attr('data-original-title', 'PHP overhead: ' + timing_remain_perc + '%');
    $('#timing_remain_perc').html('PHP overhead');

    $('#span_api_browser_version').html(api_browser_version);
    $('#span_api_class_version').html(api_class_version);
    $('#span_controller_user').html(controller_user);
    $('#span_controller_url').html(controller_url);
    $('#span_controller_version').html(controller_version);
    $('#span_php_version').html(php_version);
    $('#span_curl_version').html(curl_version);
    $('#span_os_version').html(os_version);
    $('#span_memory_limit').html(memory_limit);
    $('#span_memory_used').html(memory_used);

    /**
     * highlight and mark the selected options in the dropdown menus for $controller_id, $action, $site_id, $theme and $output_format
     *
     * NOTE:
     * these actions are performed conditionally
     */
    (action != '') ? $('#' + action).addClass('active').find('a').append(' <i class="fa fa-check"></i>') : false;
    (site_id != '') ? $('#' + site_id).addClass('active').find('a').append(' <i class="fa fa-check"></i>') : false;
    (controller_id != '') ? $('#controller_' + controller_id).addClass('active').find('a').append(' <i class="fa fa-check"></i>') : false;

    /**
     * these two options have default values so no tests needed here
     */
    $('#' + output_format).addClass('active').find('a').append(' <i class="fa fa-check"></i>');
    $('#' + theme).addClass('active').find('a').append(' <i class="fa fa-check"></i>');

    /**
     * initialise the jquery-jsonview library, only when required
     */
    if (output_format == 'json_color') {
        $('#toggle_button').show();
        $('#pre_output').JSONView($('#pre_output').text());

        /**
         * the expand/collapse toggle buttons to control the json view
         */
        $('#toggle-btn').on('click', function() {
            $('#pre_output').JSONView('toggle');
            $('#i_toggle-btn').toggleClass('fa-plus').toggleClass('fa-minus');
            $(this).blur();
        });

        $('#toggle-level2-btn').on('click', function() {
            $('#pre_output').JSONView('toggle', 2);
            $('#i_toggle-level2-btn').toggleClass('fa-plus').toggleClass('fa-minus');
            $(this).blur();
        });
    }

    /**
     * only now do we display the output
     */
    $('#output').show();

    /**
     * enable Bootstrap tooltips
     */
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })

    /**
     * check latest version of API browser tool and inform user when it's more recent than the current,
     * but only when the "about" modal is opened
     */
    $('#about_modal').on('shown.bs.modal', function (e) {
        $.getJSON('https://api.github.com/repos/Art-of-WiFi/UniFi-API-browser/releases/latest', function(external) {
            if (api_browser_version != '' && typeof(external.tag_name) !== 'undefined') {
                if (api_browser_version < external.tag_name.substring(1)) {
                    $('#span_api_browser_update').html('an update is available: ' + external.tag_name.substring(1));
                    $('#span_api_browser_update').removeClass('label-success').addClass('label-warning');
                } else {
                    $('#span_api_browser_update').html('up to date');
                }
            }
        }).fail(function(d, textStatus, error) {
            $('#span_api_browser_update').html('error checking updates');
            $('#span_api_browser_update').removeClass('label-success').addClass('label-danger');
            console.error("getJSON failed, status: " + textStatus + ", error: " + error);
        });;
    })

    /**
     * and reset the span again when the "about" modal is closed
     */
    $('#about_modal').on('hidden.bs.modal', function (e) {
        $('#span_api_browser_update').html('<i class="fa fa-spinner fa-spin fa-fw"></i> checking for updates</span>');
        $('#span_api_browser_update').removeClass('label-warning').removeClass('label-danger').addClass('label-success');
    })

    /**
     * manage display of the "back to top" button element
     */
    $(window).scroll(function () {
        if ($(this).scrollTop() > 50) {
            $('#back-to-top').fadeIn();
        } else {
            $('#back-to-top').fadeOut();
        }
    });

    /**
     * scroll body to 0px (top) on click on the "back to top" button
     */
    $('#back-to-top').click(function () {
        $('#back-to-top').tooltip('hide');
        $('body,html').animate({
            scrollTop: 0
        }, 500);
        return false;
    });

    $('#back-to-top').tooltip('show');
});
</script>
</body>
</html>