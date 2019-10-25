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
 * load required files
 */
require_once('vendor/autoload.php');
require_once('common.php');
require_once('collections.php');

/**
 * load the configuration file if readable
 */
if (is_file('config/config.php') && is_readable('config/config.php')) {
    require_once('config/config.php');
} else {
    exit;
}

/**
 * load the file containing user accounts, if readable
 */
if (is_file('config/users.php') && is_readable('config/users.php')) {
    require_once('config/users.php');
} else {
    exit;
}

/**
 * in order to use the PHP $_SESSION array for temporary storage of variables, session_start() is required
 */
session_start();

$_SESSION['login_error_message'] = 'user name and password do not match, please try again';
$_SESSION['logged_in'] = false;

/**
 * load login form data if present, then login to test
 */
if (isset($_POST['user_name']) && !empty($_POST['user_name']) && isset($_POST['password']) && !empty($_POST['password'])) {
    $user_name = $_POST['user_name'];
    $password  = $_POST['password'];

    /**
     * check the user accounts whether we allow the user to login, if so, we change the value of $_SESSION['logged_in'] to true
     */
    $password_hash = hash('sha512', $password);

    if (!empty($users)) {
        foreach ($users as $user) {
            if ($user['user_name'] === $user_name && strtoupper($user['password']) === strtoupper($password_hash)) {
                /**
                 * we have a matching user_name/password combination
                 */
                error_log('successful login for user ' . $user_name);
                $_SESSION['logged_in'] = true;
            }
        }

        if ($_SESSION['logged_in'] === false) {
            error_log('failed login attempt for user ' . $user_name);
        }
    }
}

header('Location: ' . dirname($_SERVER['REQUEST_URI']));
exit;