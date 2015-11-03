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
in order to use PHP $_SESSION for temporary storage of variables
session_start is required
*/
session_start();
$time_start = microtime(true);

// define defaults
$action = '';
$siteid = 'none';
$sitename = 'no site selected';
$selection = 'nothing selected';
$data = '';
$result = 0;

// process the GET variables
if(isset($_GET['action'])){
    $action = $_GET['action'];
    $_SESSION['action'] = $action;
} else {
    if(isset($_SESSION['action'])){
        $action = $_SESSION['action'];
    }
}

if(isset($_GET['siteid'])){
    $siteid = $_GET['siteid'];
    $_SESSION['siteid'] = $siteid;
    $sitename = $_GET['sitename'];
    $_SESSION['sitename'] = $sitename;
} else {
    if(isset($_SESSION['siteid'])) {
        $siteid = $_SESSION['siteid'];
        $sitename = $_SESSION['sitename'];
    }
}
/*
load the unifi api connection class as well as the settings files
and log in to the controller to load the sites data
*/
require('phpapi/class.unifi.php');
require('config.php');
$unifidata = new unifiapi($controlleruser, $controllerpassword, $controllerurl, $siteid, $controllerversion);
$unifidata->login();

$sites = $unifidata->list_sites();

$time_1 = microtime(true);

switch ($action) {
    case 'list_clients':
        $selection = 'list online clients';
        $data = $unifidata->list_clients();
        break;
    case 'stat_allusers':
        $selection = 'stat all users';
        $data = $unifidata->stat_allusers();
        break;
    case 'stat_auths':
        $selection = 'stat active authorisations';
        $data = $unifidata->stat_auths();
        break;
    case 'list_guests':
        $selection = 'list guests';
        $data = $unifidata->list_guests();
        break;
    case 'stat_hourly_site':
        $selection = 'hourly site stats';
        $data = $unifidata->stat_hourly_site();
        break;
    case 'stat_hourly_aps':
        $selection = 'hourly ap stats';
        $data = $unifidata->stat_hourly_aps();
        break;
    case 'stat_daily_site':
        $selection = 'daily site stats';
        $data = $unifidata->stat_daily_site();
        break;
    case 'list_aps':
        $selection = 'list access points';
        $data = $unifidata->list_aps();
        break;
    case 'stat_sessions':
        $selection = 'stat sessions';
        $data = $unifidata->stat_sessions();
        break;
    case 'list_users':
        $selection = 'list users';
        $data = $unifidata->list_users();
        break;
    case 'list_rogueaps':
        $selection = 'list rogue access points';
        $data = $unifidata->list_rogueaps();
        break;
    case 'list_events':
        $selection = 'list events';
        $data = $unifidata->list_events();
        break;
    case 'list_alarms':
        $selection = 'list alarms';
        $data = $unifidata->list_alarms();
        break;
    case 'list_wlanconf':
        $selection = 'wlan config';
        $data = $unifidata->list_wlanconf();
        break;
    case 'list_health':
        $selection = 'health metrics';
        $data = $unifidata->list_health();
        break;
    case 'list_settings':
        $selection = 'list site settings';
        $data = $unifidata->list_settings();
        break;
    default:
        break;
}

$timeafterlogin = $time_1 - $time_start;
if($action!=''){
    $result = count($data);
}

$time_2 = microtime(true);
$timeafterload = $time_2 - $time_start;

// calculate all the timings/percentages
$time_end = microtime(true);
$timetotal = $time_end - $time_start;
$loginperc = ($timeafterlogin/$timetotal)*100;
$loadperc = (($timeafterload - $timeafterlogin)/$timetotal)*100;
$remainperc = 100-$loginperc-$loadperc;

// construct the HTML5 progress bar which will present the timings in a graphical manner
$progressbarcontent = '\
Total elapsed time: '.$timetotal.' seconds<br>\
<div class="progress">\
  <div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="'.$loginperc.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$loginperc.'%;">\
    login time: '.$timeafterlogin.'\
  </div>\
  <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="'.$loadperc.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$loadperc.'%;">\
    load time: '.($timeafterload - $timeafterlogin).'\
  </div>\
  <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuenow="'.$remainperc.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$remainperc.'%;">\
    PHP\
  </div>\
</div>\
';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Unifi API browser</title>
    <!-- Latest compiled and minified Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
</head>
<body>
<!-- top navbar -->
<div class="bs-example">
    <nav id="navbar-example" class="navbar navbar-default">
      <div class="container-fluid">
        <div class="navbar-header">
          <button class="navbar-toggle collapsed" type="button" data-toggle="collapse" data-target=".bs-example-js-navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
          </button>
          <a class="navbar-brand" href="index.php">Unifi API browser</a>
        </div>
        <div class="collapse navbar-collapse bs-example-js-navbar-collapse">
          <ul class="nav navbar-nav">
            <li id="site-menu" class="dropdown">
              <a id="drop6" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                Select a site here
                <span class="caret"></span>
              </a>
              <ul class="dropdown-menu" id="siteslist">
              </ul>
            </li>            
            <li class="dropdown">
              <a id="drop1" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                Users
                <span class="caret"></span>
              </a>
              <ul class="dropdown-menu">
                <li><a href="?action=list_clients">list online clients</a></li>
                <li><a href="?action=list_guests">list guests</a></li>
                <li><a href="?action=list_users">list users</a></li>
                <li><a href="?action=stat_allusers">stat all users</a></li>
                <li><a href="?action=stat_auths">stat authorisations</a></li>
                <li><a href="?action=stat_sessions">stat sessions</a></li>
              </ul>
            </li>
            <li class="dropdown">
              <a id="ap-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                Access Points
                <span class="caret"></span>
              </a>
              <ul class="dropdown-menu">
                <li><a href="?action=list_aps">list access points</a></li>
                <li><a href="?action=list_rogueaps">list rogue access points</a></li>
              </ul>
            </li>
            <li id="stats-menu" class="dropdown">
              <a id="drop3" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                Stats
                <span class="caret"></span>
              </a>
              <ul class="dropdown-menu">
                <li><a href="?action=stat_hourly_site">hourly site stats</a></li>
                <li><a href="?action=stat_daily_site">daily site stats</a></li>
                <li><a href="?action=stat_hourly_aps">hourly access point stats</a></li>
                <li><a href="?action=list_health">health metrics</a></li>
              </ul>
            </li>
            <li id="config-menu" class="dropdown">
              <a id="drop4" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                Configuration
                <span class="caret"></span>
              </a>
              <ul class="dropdown-menu">
                <li><a href="?action=list_wlanconf">wireless configuration</a></li>
                <li><a href="?action=list_settings">list site settings</a></li>
              </ul>
            </li>
            <li id="msg-menu" class="dropdown">
              <a id="drop5" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                Messages
                <span class="caret"></span>
              </a>
              <ul class="dropdown-menu">
                <li><a href="?action=list_alarms">list alarms</a></li>
                <li><a href="?action=list_events">list events</a></li>
              </ul>
            </li>
          </ul>
        </div><!-- /.nav-collapse -->
      </div><!-- /.container-fluid -->
    </nav><!-- /navbar-example -->
  </div><!-- top navbar -->
<div class="col-md-10 col-md-offset-1">
<div class="panel panel-default">
    <div class="panel-heading">site id: <b><?php echo $siteid ?></b>, site name: <b><?php echo $sitename ?></b>, query: <b><?php echo $selection ?></b>, # of objects: <b><?php echo $result ?></b></div>
    <div class="panel-body">
        <span id = "progressbar"></span>
        <pre><?php echo json_encode($data, JSON_PRETTY_PRINT) ?></pre>
    </div>
    </div>
</div>
<!-- Latest compiled and minified JavaScript versions -->
<script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
<script>
    // push the progress bar constructed earlier to the div at the top of the widget
    var progressbarcontent = '<?php echo $progressbarcontent?>';
    $('#progressbar').html(progressbarcontent);
    
    // populate the sites pull-down list with active site names with corresponding urls
    var sites = <?php echo json_encode($sites) ?>;
    populateSitesList(sites);
    function populateSitesList(data) {
        var items = [];
        $.each(data, function (id, option) {
            items.push('<li><a href="?siteid=' + option.name + '&sitename=' + option.desc + '">' + option.desc + '</li>');
        });  
        $('#siteslist').html(items.join(''));
    }
</script>
</body>
</html>
