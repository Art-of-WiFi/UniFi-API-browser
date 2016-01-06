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

/*
to use the PHP $_SESSION array for temporary storage of variables, session_start() is required
*/
session_start();

/*
starting timing of the session here
*/
$time_start = microtime(true);

/*
assign variables which are required later on together with their default values
*/
$action         = '';
$siteid         = '';
$sitename       = '';
$selection      = '';
$outputformat   = 'json';
$theme          = 'bootstrap';
$data           = '';
$objectscount   = '';
$alertmessage   = '';
$cookietimeout  = '1800';
$debug          = false;

/*
load the settings file
- if the config.php file is unreadable or does not exist, an alert is displayed on the page
*/
if(!is_readable('config.php')) {
    $alertmessage = '<div class="alert alert-danger" role="alert">The file config.php is not readable or does not exist.'
                    . '<br>If you have not yet done so, please copy/rename the config.template.php file to config.php and modify'
                    . 'the contents as required.</div>';
}

include('config.php');

/*
determine whether we have reached the cookie timeout, if so, refresh the PHP session
else, update last activity time stamp
*/
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $cookietimeout)) {
    /*
    last activity was longer than "$cookietimeout" seconds ago
    */
    session_unset();
    session_destroy();
}
$_SESSION['last_activity'] = time();

/*
collect cURL version details for the info modal
*/
$curl_info      = curl_version();
$curl_version   = $curl_info['version'];

/*
process the GET variables and store them in the $_SESSION array,
if a GET variable is not set, get the value from $_SESSION (if available)
- siteid
Only process these after siteid is set:
- action
- outputformat
- theme
*/
if (isset($_GET['siteid'])) {
    $siteid = $_GET['siteid'];
    $_SESSION['siteid'] = $siteid;
    $sitename = $_GET['sitename'];
    $_SESSION['sitename'] = $sitename;
} else {
    if (isset($_SESSION['siteid'])) {
        $siteid = $_SESSION['siteid'];
        $sitename = $_SESSION['sitename'];
        
        if (isset($_GET['action'])) {
            $action = $_GET['action'];
            $_SESSION['action'] = $action;
        } else {
            if (isset($_SESSION['action'])) {
                $action = $_SESSION['action'];
            }
        }
        
        if (isset($_GET['outputformat'])) {
            $outputformat = $_GET['outputformat'];
            $_SESSION['outputformat'] = $outputformat;
        } else {
            if (isset($_SESSION['outputformat'])) {
                $outputformat = $_SESSION['outputformat'];
            }
        }
        
        if (isset($_GET['theme'])) {
            $theme = $_GET['theme'];
            $_SESSION['theme'] = $theme;
        } else {
            if (isset($_SESSION['theme'])) {
                $theme = $_SESSION['theme'];
            }
        }
    }
}

/*
display info message when no site is selected or no data collection is selected
placed here so they can be overwritten by more "severe" error messages later down
*/
if ($action === '') {
    $alertmessage = '<div class="alert alert-info" role="alert">Please select a data collection.</div>';
}
if ($siteid === '') {
    $alertmessage = '<div class="alert alert-info" role="alert">Please select a site from the menu above.</div>';
}

/*
load the Unifi API connection class and log in to the controller
- if an error occurs during the login process, an alert is displayed on the page
*/
require('phpapi/class.unifi.php');

$unifidata        = new unifiapi($controlleruser, $controllerpassword, $controllerurl, $siteid, $controllerversion);
$unifidata->debug = $debug;
$loginresults     = $unifidata->login();

if($loginresults === 400) {
    $alertmessage = '<div class="alert alert-danger" role="alert">HTTP response status: 400'
                    . '<br>This is probably caused by a Unifi controller login failure, please check your credentials in '
                    . 'config.php</div>';
}

/*
get the list of sites managed by the controller (if not already stored in $_SESSION)
*/
if (!isset($_SESSION['sites']) || $_SESSION['sites'] === '') {
    $sites  = $unifidata->list_sites();
    $_SESSION['sites'] = $sites;
} else {
    $sites = $_SESSION['sites'];
}

/*
get the version of the controller (if not already stored in $_SESSION or when empty)
only get the version once a site has been selected
*/
if($siteid != '') {
    if (!isset($_SESSION['detected_controller_version']) || $_SESSION['detected_controller_version'] === '') {
        $site_info = $unifidata->stat_sysinfo();
        $detected_controller_version = $site_info[0]->version;
        $_SESSION['detected_controller_version'] = $detected_controller_version;
    } else {
        $detected_controller_version = $_SESSION['detected_controller_version'];
    }
} else {
    $detected_controller_version = 'undetected';
}

/*
execute timing of controller login
*/
$time_1         = microtime(true);
$timeafterlogin = $time_1 - $time_start;

/*
select the required call to the Unifi Controller API based on the selected action
*/
switch ($action) {
    case 'list_clients':
        $selection  = 'list online clients';
        $data       = $unifidata->list_clients();
        break;
    case 'stat_allusers':
        $selection  = 'stat all users';
        $data       = $unifidata->stat_allusers();
        break;
    case 'stat_auths':
        $selection  = 'stat active authorisations';
        $data       = $unifidata->stat_auths();
        break;
    case 'list_guests':
        $selection  = 'list guests';
        $data       = $unifidata->list_guests();
        break;
    case 'stat_hourly_site':
        $selection  = 'hourly site stats';
        $data       = $unifidata->stat_hourly_site();
        break;
    case 'stat_sysinfo':
        $selection  = 'sysinfo';
        $data       = $unifidata->stat_sysinfo();
        break;
    case 'stat_hourly_aps':
        $selection  = 'hourly ap stats';
        $data       = $unifidata->stat_hourly_aps();
        break;
    case 'stat_daily_site':
        $selection  = 'daily site stats';
        $data       = $unifidata->stat_daily_site();
        break;
    case 'list_devices':
        $selection  = 'list devices';
        $data       = $unifidata->list_aps();
        break;
    case 'list_wlan_groups':
        $selection  = 'list wlan groups';
        $data       = $unifidata->list_wlan_groups();
        break;
    case 'stat_sessions':
        $selection  = 'stat sessions';
        $data       = $unifidata->stat_sessions();
        break;
    case 'list_users':
        $selection  = 'list users';
        $data       = $unifidata->list_users();
        break;
    case 'list_rogueaps':
        $selection  = 'list rogue access points';
        $data       = $unifidata->list_rogueaps();
        break;
    case 'list_events':
        $selection  = 'list events';
        $data       = $unifidata->list_events();
        break;
    case 'list_alarms':
        $selection  = 'list alerts';
        $data       = $unifidata->list_alarms();
        break;
    case 'list_wlanconf':
        $selection  = 'list wlan config';
        $data       = $unifidata->list_wlanconf();
        break;
    case 'list_health':
        $selection  = 'site health metrics';
        $data       = $unifidata->list_health();
        break;
    case 'list_settings':
        $selection  = 'list site settings';
        $data       = $unifidata->list_settings();
        break;
    case 'list_sites':
        $selection  = 'details of available sites';
        $data       = $sites;
        break;
    case 'list_extension':
        $selection  = 'list VoIP extensions';
        $data       = $unifidata->list_extension();
        break;
    case 'list_portconf':
        $selection  = 'list port configuration';
        $data       = $unifidata->list_portconf();
        break;
    case 'list_networkconf':
        $selection  = 'list network configuration';
        $data       = $unifidata->list_networkconf();
        break;
    case 'list_dynamicdns':
        $selection  = 'dynamic dns configuration';
        $data       = $unifidata->list_dynamicdns();
        break;
    case 'list_portforwarding':
        $selection  = 'list port forwarding rules';
        $data       = $unifidata->list_portforwarding();
        break;
    case 'stat_voucher':
        $selection  = 'list hotspot vouchers';
        $data       = $unifidata->stat_voucher();
        break;
    case 'stat_payment':
        $selection  = 'list hotspot payments';
        $data       = $unifidata->stat_payment();
        break;
    case 'list_hotspotop':
        $selection  = 'list hotspot operators';
        $data       = $unifidata->list_hotspotop();
        break;
    case 'list_self':
        $selection  = 'self';
        $data       = $unifidata->list_self();
        break;
    default:
        break;
}

/*
count the number of objects collected from the controller
*/
if($action!=''){
    $objectscount = count($data);
}

/*
create the url to the css file based on the selected theme (standard Bootstrap or one of the Bootswatch themes)
*/
if ($theme === 'bootstrap') {
    $cssurl = 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css';
} else {
    $cssurl = 'https://maxcdn.bootstrapcdn.com/bootswatch/3.3.5/' . $theme . '/bootstrap.min.css';
}

/*
execute timing of data collection from controller
*/
$time_2         = microtime(true);
$timeafterload  = $time_2 - $time_start;

/*
calculate all the timings/percentages
*/
$time_end   = microtime(true);
$timetotal  = $time_end - $time_start;
$loginperc  = ($timeafterlogin/$timetotal)*100;
$loadperc   = (($timeafterload - $timeafterlogin)/$timetotal)*100;
$remainperc = 100-$loginperc-$loadperc;

/*
shared functions
*/
function print_output($outputformat, $data)
{
    /*
    function to print the output
    switch depending on the selected $outputformat
    */
    switch ($outputformat) {
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

/*
log off from the Unifi controller API
*/
$logoutresults = $unifidata->logout();
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta charset="utf-8">
  <title>Unifi API browser</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <!-- Latest compiled and minified Bootstrap, Font-awesome and Highlight CSS loaded from CDN -->
  <link rel="stylesheet" href="<?php echo $cssurl ?>">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.0.0/styles/default.min.css">
  <style>
  body {
    padding-top: 70px;
  }
  </style>
</head>
<body>
<!-- top navbar -->
<nav id="navbar" class="navbar navbar-default navbar-fixed-top">
  <div class="container-fluid">
    <div class="navbar-header">
      <button class="navbar-toggle collapsed" type="button" data-toggle="collapse" data-target=".bs-example-js-navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
      </button>
      <a class="navbar-brand" href="index.php">Unifi API browser</a>
    </div>
    <div class="collapse navbar-collapse bs-example-js-navbar-collapse">
      <ul class="nav navbar-nav navbar-left">
        <li id="site-menu" class="dropdown">
          <a id="site-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
            Sites
            <span class="caret"></span>
          </a>
          <ul class="dropdown-menu" id="siteslist">
            <li class="dropdown-header">Select a site</li>
            <?php
            foreach ($sites as $site) {
              echo '<li id="' . $site->name . '"><a href="?siteid=' . $site->name . '&sitename=' . $site->desc . '">' . $site->desc . '</a></li>' . "\n";
            }
            ?>
           </ul>
        </li>
        <!-- only show the data collection menus when a siteid is selected -->
        <?php if ($siteid) { ?>
          <li id="output-menu" class="dropdown">
            <a id="output-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
              Output
              <span class="caret"></span>
            </a>
            <ul class="dropdown-menu" id="outputselection">
              <li class="dropdown-header">Select an output format</li>
              <li id="json"><a href="?outputformat=json">json (default)</a></li>
              <li role="separator" class="divider"></li>
              <li id="php_array"><a href="?outputformat=php_array">PHP array</a></li>
              <li id="php_var_dump"><a href="?outputformat=php_var_dump">PHP var_dump</a></li>
              <li id="php_var_export"><a href="?outputformat=php_var_export">PHP var_export</a></li>
              <li role="separator" class="divider"></li>
              <li class="dropdown-header">Nice but slow with large collections</li>
              <li id="json_color"><a href="?outputformat=json_color">json highlighted</a></li>
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
              <li role="separator" class="divider"></li>
              <li id="stat_hourly_aps"><a href="?action=stat_hourly_aps">hourly access point stats</a></li>
              <li role="separator" class="divider"></li>
              <li id="list_health"><a href="?action=list_health">site health metrics</a></li>
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
    <?php echo $alertmessage ?>
  </div>
  <div class="panel panel-default">
    <div class="panel-heading">
      <?php if ($siteid) { ?>
        site id: <span class="label label-primary"><?php echo $siteid ?></span>
        site name: <span class="label label-primary"><?php echo $sitename ?></span>
      <?php } ?>
      <?php if ($selection) { ?>
        collection: <span class="label label-primary"><?php echo $selection ?></span>
      <?php } ?>
      output: <span class="label label-primary"><?php echo $outputformat ?></span>
      <?php if ($objectscount) { ?>
        # of objects: <span class="badge"><?php echo $objectscount ?></span>
      <?php } ?>
    </div>
    <div class="panel-body">
      <!-- present the timing results using an HTML5 progress bar -->
      total elapsed time: <?php echo $timetotal ?> seconds<br>
      <div class="progress">
        <div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="<?php echo $loginperc ?>"
        aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $loginperc ?>%;" data-toggle="tooltip"
        data-placement="bottom" data-original-title="<?php echo $timeafterlogin ?> seconds">
          API login time
        </div>
        <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="<?php echo $loadperc ?>"
        aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $loadperc ?>%;" data-toggle="tooltip"
        data-placement="bottom" data-original-title="<?php echo ($timeafterload - $timeafterlogin) ?> seconds">
          API load time
        </div>
        <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuenow="<?php echo $remainperc ?>"
        aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $remainperc ?>%;" data-toggle="tooltip"
        data-placement="bottom" data-original-title="PHP overhead: <?php echo $remainperc ?> seconds">
          PHP overhead
        </div>
      </div>
      <pre><?php print_output($outputformat, $data) ?></pre>
    </div>
  </div>
</div>
<!-- Modal -->
<div class="modal fade" id="aboutModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel"><i class="fa fa-info-circle"></i> About Unifi API Browser</h4>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-sm-10 col-sm-offset-1">
            A tool for browsing the data collections which are exposed through Ubiquiti's Unifi Controller API.
          </div>
        </div>
        <hr>
        <dl class="dl-horizontal col-sm-offset-1">
          <dt>controller user</dt>
          <dd><span class="label label-primary"><?php echo $controlleruser ?></span></dd>
          <dt>controller url</dt>
          <dd><span class="label label-primary"><?php echo $controllerurl ?></span></dd>
          <dt>version detected</dt>
          <dd><span class="label label-primary"><?php echo $detected_controller_version ?></span></dd>
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
          <dt>cURL version</dt>
          <dd><span class="label label-primary"><?php echo $curl_version ?></span></dd>
          <dt>operating system</dt>
          <dd><span class="label label-primary"><?php echo (php_uname('s') . ' ' . php_uname('r')) ?></span></dd>
        </dl>
        <hr>
        <div class="row">
          <div class="col-sm-8 col-sm-offset-2"><a href="https://github.com/malle-pietje/Unifi-API-browser"
          target="_blank">Unifi API browser on Github</a></div>
          <div class="col-sm-8 col-sm-offset-2"><a href="http://community.ubnt.com/t5/UniFi-Wireless/Unifi-API-browser-tool-updates-and-discussion/m-p/1392651#U1392651"
          target="_blank">Unifi API browser on Ubiquiti Community forum</a></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<!-- Latest compiled and minified JavaScript versions, loaded from CDN's -->
<script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.0.0/highlight.min.js"></script>
<script>
  /*
  initialise the Highlighting.js library
  */
  hljs.initHighlightingOnLoad();
</script>
<script>
  /*
  highlight selected options in the pull down menus
  for $action, $siteid, $theme and $outputformat:
  */
  $('#<?php echo $theme ?>').addClass('active').find('a').append(' <i class="fa fa-check"></i>');
  $('#<?php echo $action ?>').addClass('active').find('a').append(' <i class="fa fa-check"></i>');
  $('#<?php echo $siteid ?>').addClass('active').find('a').append(' <i class="fa fa-check"></i>');
  $('#<?php echo $outputformat ?>').addClass('active').find('a').append(' <i class="fa fa-check"></i>');

  $(function () {
    $('[data-toggle="tooltip"]').tooltip()
  })
</script>
</body>
</html>