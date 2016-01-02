<?php
/*
The MIT License (MIT)

Copyright (c) 2015, Slooffmaster

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

$controlleruser     = ''; // the user name for access to the Unifi Controller
$controllerpassword = ''; // the password for access to the Unifi Controller
$controllerurl      = ''; // full url to the Unifi Controller, eg. 'https://22.22.11.11:8443'
$controllerversion  = ''; // the version of the Controller software, eg. '4.6.6' (must be at least 4.0.0)
$cookietimeout      = '3600'; // time of inactivity in seconds, after which the PHP session cookie will be refreshed
                              // this means the site and data collection will need to be selected again
$debug              = false; // set to true (without quotes) to enable debug output to the browser and PHP error log
?>