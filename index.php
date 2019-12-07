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
 * in order to use the PHP $_SESSION array for temporary storage of variables, session_start() is required
 */
session_start();

/**
 * check whether user has requested to clear (force expiry) the PHP session, if so we
 * clear the session and reload the page without the query string
 * - this feature can be useful when login errors occur, mostly after upgrades or credential changes
 */
if (isset($_GET['reset_session']) && $_GET['reset_session'] == true) {
    $_SESSION = [];
    session_unset();
    session_destroy();
    session_start();
    $current_url = $_SERVER['REQUEST_URI'];
    $current_url = strtok($current_url, '?');
    header("refresh: 0; url = $current_url");
}

/**
 * load required packages using the composer autoloader together with the files containing shared functions
 * and the collections
 */
require_once('vendor/autoload.php');
require_once('common.php');
require_once('collections.php');

/**
 * initialize the Twig loader early on in case we need to render the error page
 */
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig   = new \Twig\Environment($loader);

/**
 * load the configuration file, if readable
 * - if not, stop and display an error message
 */
if (is_file('config/config.php') && is_readable('config/config.php')) {
    require_once('config/config.php');
} else {
    /**
     * render the config error page
     */
    echo $twig->render('config_error.html.twig', [
        'error_message' => 'The file <b>config/config.php</b> does not exist! Please create one based on the <b>config/config-template.php</b> file!<br>',
    ]);

    exit();
}

/**
 * inject Twig global variables for use across the templates
 */
$twig->addGlobal('tool_version', TOOL_VERSION);
$twig->addGlobal('debug', $debug);
$twig->addGlobal('session', $_SESSION);
$twig->addGlobal('navbar_class', $navbar_class);
$twig->addGlobal('navbar_bg_class', $navbar_bg_class);
$twig->addGlobal('about_modal_params', $about_modal_params);

/**
 * check whether the required PHP curl module is available
 * - if not, stop and display an error message
 */
if (!function_exists('curl_version')) {
    /**
     * render the config error page
     */
    echo $twig->render('config_error.html.twig', [
        'error_message' => 'The <b>PHP curl</b> module is not installed! Please correct this before proceeding!<br>',
    ]);

    exit();
}

/**
 * check whether the minimum required PHP version (5.6.0) is met
 * - if not, stop and display an error message
 */
if (version_compare(PHP_VERSION, '5.6.0') < 0) {
    /**
     * render the config error page
     */
    echo $twig->render('config_error.html.twig', [
        'error_message' => 'The current PHP version (' . PHP_VERSION . ') does not meet the minimum required version which is 5.6.0. Please upgrade before proceeding!<br>',
    ]);

    exit();
}

/**
 * load the file containing user accounts, if readable
 */
if (is_file('config/users.php') && is_readable('config/users.php')) {
    require_once('config/users.php');
    $user_authentication = true;
} else {
    $user_authentication = false;
    error_log('The file config/users.php does not exist, proceeding without user authentication.');
}

/**
 * if needed, we request the user to login
 */
if ($user_authentication && (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] === false)) {
    if (!empty($_SESSION['login_error_message'])) {
        $login_error = $_SESSION['login_error_message'];
        $_SESSION['login_error_message'] = '';
    } else {
        $login_error = '';
    }

    /**
     * render the login page
     */
    echo $twig->render('login.html.twig', [
        'login_error' => $login_error,
    ]);

    exit;
}

if (empty($_SESSION['controller'])) {
    /**
     * the user needs to select a controller first so we render the appropriate template
     */
    echo $twig->render('controller_select.html.twig', [
        'controllers' => $controllers,
    ]);
} else {
    /**
     * otherwise we proceed and we render the collections view page
     */
    echo $twig->render('collections_view.html.twig', [
        'controllers' => $controllers,
        'controller'  => $_SESSION['controller'],
        'collections' => $collections,
    ]);
}

exit;