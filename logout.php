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
 * load required file
 */
require_once('common.php');

/**
 * in order to use the PHP $_SESSION array for temporary storage of variables, session_start() is required
 */
session_start();

/**
 * we clear the session completely and redirect back to the index page
 */
$_SESSION = [];
session_unset();
session_destroy();
session_start();

header('Location: ' . dirname($_SERVER['REQUEST_URI']));
exit;