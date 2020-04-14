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
 * INSTRUCTIONS
 * =============
 * If you wish to implement restricted access to this tool based on user name and password,
 * please follow these steps:
 *
 * - create a copy of this file, name it users.php and store in the same directory
 * - in this new file, populate the array below with user accounts as required
 * - the value for password entered must be the SHA512 hash of the password
 * - please take care in keeping the PHP syntax for the $users array intact
 * - please make sure not to create any duplicate user_name values
 * - to generate the password hash string you can use an online tool such as this one:
 *     https://passwordsgenerator.net/sha512-hash-generator/
 *
 * IMPORTANT NOTE:
 * If you do not create the users.php file or do no create any user accounts, the API browser tool
 * will be accessible without providing any means of authentication.
 */
$users = [
    [
       'user_name' => '', // string, the user name
       'password'  => '', // string, the SHA512 hash of the password
    ],
    [
       'user_name' => '', // string, the user name
       'password'  => '', // string, the SHA512 hash of the password
    ],
];