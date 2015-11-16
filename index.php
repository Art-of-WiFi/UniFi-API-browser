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
$outputformat = 'json';
$theme = 'bootstrap';
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

if(isset($_GET['outputformat'])){
    $outputformat = $_GET['outputformat'];
    $_SESSION['outputformat'] = $outputformat;
} else {
    if(isset($_SESSION['outputformat'])){
        $outputformat = $_SESSION['outputformat'];
    }
}

if(isset($_GET['theme'])){
    $theme = $_GET['theme'];
    $_SESSION['theme'] = $theme;
} else {
    if(isset($_SESSION['theme'])){
        $theme = $_SESSION['theme'];
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

// select the required call to the Unifi Controller API based on the action selected
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

// create the url to the css file based on the selected theme (standard bootstrap or one of the bootswatch themes)
if($theme === 'bootstrap') {
    $cssurl = 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css';
} else {
    $cssurl = 'https://maxcdn.bootstrapcdn.com/bootswatch/3.3.5/' . $theme . '/bootstrap.min.css';
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
total elapsed time: '.$timetotal.' seconds<br>\
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
    <!-- Latest compiled and minified Bootstrap and Font-awesome CSS -->
    <link rel="stylesheet" href="<?php echo $cssurl ?>">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
    <style>
    body {
      min-height: 2000px;
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
            Select site
            <span class="caret"></span>
          </a>
          <ul class="dropdown-menu" id="siteslist">
          </ul>
        </li>
        <li id="output-menu" class="dropdown">
          <a id="output-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
            Output format
            <span class="caret"></span>
          </a>
          <ul class="dropdown-menu" id="outputselection">
            <li id="json"><a href="?outputformat=json">json</a></li>
            <li id="phparray"><a href="?outputformat=phparray">PHP array</a></li>
          </ul>
        </li>
        <li id="user-menu" class="dropdown">
          <a id="user-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
            Users
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
            APs
            <span class="caret"></span>
          </a>
          <ul class="dropdown-menu">
            <li id="list_aps"><a href="?action=list_aps">list access points</a></li>
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
            <li id="list_health"><a href="?action=list_health">health metrics</a></li>
          </ul>
        </li>
        <li id="config-menu" class="dropdown">
          <a id="config-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
            Configuration
            <span class="caret"></span>
          </a>
          <ul class="dropdown-menu">
            <li id="list_wlanconf"><a href="?action=list_wlanconf">wireless configuration</a></li>
            <li role="separator" class="divider"></li>
            <li id="list_settings"><a href="?action=list_settings">list site settings</a></li>
          </ul>
        </li>
        <li id="msg-menu" class="dropdown">
          <a id="msg-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
            Messages
            <span class="caret"></span>
          </a>
          <ul class="dropdown-menu">
            <li id="list_alarms"><a href="?action=list_alarms">list alarms</a></li>
            <li id="list_events"><a href="?action=list_events">list events</a></li>
          </ul>
        </li>
      </ul>
      <ul class="nav navbar-nav navbar-right">
        <li id="theme-menu" class="dropdown">
          <a id="theme-menu" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-cog fa-lg"></i>
          </a>
          <ul class="dropdown-menu">
            <li class="dropdown-header">Select a theme</li>
            <li id="bootstrap"><a href="?theme=bootstrap">default bootstrap</a></li>
            <li id="cerulean"><a href="?theme=cerulean">cerulean</a></li>
            <li id="cosmo"><a href="?theme=cosmo">cosmo</a></li>
            <li id="cyborg"><a href="?theme=cyborg">cyborg</a></li>
            <li id="darkly"><a href="?theme=darkly">darkly</a></li>
            <li id="flatly"><a href="?theme=flatly">flatly</a></li>
            <li id="journal"><a href="?theme=journal">journal</a></li>
            <li id="lumen"><a href="?theme=lumen">lumen</a></li>
            <li id="paper"><a href="?theme=paper">paper</a></li>
            <li id="readable"><a href="?theme=readable">readable</a></li>
            <li id="sandstone"><a href="?theme=sandstone">sandstone</a></li>
            <li id="simplex"><a href="?theme=simplex">simplex</a></li>
            <li id="slate"><a href="?theme=slate">slate</a></li>
            <li id="spacelab"><a href="?theme=spacelab">spacelab</a></li>
            <li id="superhero"><a href="?theme=superhero">superhero</a></li>
            <li id="united"><a href="?theme=united">united</a></li>
            <li id="yeti"><a href="?theme=yeti">yeti</a></li>
          </ul>
        </li>
      </ul>
    </div><!-- /.nav-collapse -->
  </div><!-- /.container-fluid -->
</nav><!-- /navbar-example -->
<div class="container-fluid">
    <div class="panel panel-default">
        <div class="panel-heading">site id: <b><?php echo $siteid ?></b>, site name: <b><?php echo $sitename ?></b>, query: <b><?php echo $selection ?></b>, output: <b><?php echo $outputformat ?></b>, # of objects: <b><?php echo $result ?></b></div>
        <div class="panel-body">
            <span id = "progressbar"></span>
            <pre><?php
            // switch depending on the selected $outputformat
            switch ($outputformat) {
                case 'json':
                    echo json_encode($data, JSON_PRETTY_PRINT);
                    break;
                case 'phparray':
                    print_r ($data);
                    break;
                default:
                    echo json_encode($data, JSON_PRETTY_PRINT);
                    break;
            }
            ?></pre>
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
            items.push('<li id="' + option.name + '"><a href="?siteid=' + option.name + '&sitename=' + option.desc + '">' + option.desc + '</li>');
        });  
        $('#siteslist').html(items.join(''));
    }
    
    /*
    highlight selected options in the pull down menus
    for $action, $siteid, $theme and $outputformat:
    */
    $('#<?php echo $action ?>').addClass('active');
    $('#<?php echo $siteid ?>').addClass('active');
    $('#<?php echo $outputformat ?>').addClass('active');
    $('#<?php echo $theme ?>').addClass('active');
</script>
</body>
</html>
