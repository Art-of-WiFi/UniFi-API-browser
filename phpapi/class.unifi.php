<?php
/**
 * UniFi PHP API client
 *
 * This UniFi API client comes bundled with the UniFi API Browser tool and is based on the work done by
 * the following developers:
 *    domwo: http://community.ubnt.com/t5/UniFi-Wireless/little-php-class-for-unifi-api/m-p/603051
 *    fbagnol: https://github.com/fbagnol/class.unifi.php
 * and the API as published by Ubiquiti:
 *    https://www.ubnt.com/downloads/unifi/5.3.8/unifi_sh_api
 *
 * VERSION: 1.0.14
 *
 * NOTES:
 * - this Class will only work with UniFi Controller versions 4.x and 5.x. There are no checks to prevent
 *   you from trying to use it with other versions of the UniFi Controller.
 *
 * IMPORTANT CHANGES:
 * - function/method "get_vouchers" has been removed and has been replaced by "stat_voucher"
 *
 * ------------------------------------------------------------------------------------
 *
 * Copyright (c) 2016, Slooffmaster
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.md
 *
 */
define('API_CLASS_VERSION', '1.0.14');

class unifiapi
{
    public  $user         = '';
    public  $password     = '';
    public  $site         = 'default';
    public  $baseurl      = 'https://127.0.0.1:8443';
    public  $version      = '4.8.20';
    public  $is_loggedin  = false;
    public  $debug        = false;
    private $cookies      = '';
    private $request_type = 'POST';

    function __construct($user = '', $password = '', $baseurl = '', $site = '', $version = '')
    {
        if (!empty($user)) $this->user         = $user;
        if (!empty($password)) $this->password = $password;
        if (!empty($baseurl)) $this->baseurl   = $baseurl;
        if (!empty($site)) $this->site         = $site;
        if (!empty($version)) $this->version   = $version;
    }

    function __destruct()
    {
        if ($this->is_loggedin) {
            $this->logout();
        }
    }

    /**
     * Login to UniFi Controller
     */
    public function login()
    {
        $this->cookies = '';

        $ch = $this->get_curl_obj();

        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_REFERER, $this->baseurl.'/login');
        curl_setopt($ch, CURLOPT_URL, $this->baseurl.'/api/login');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('username' => $this->user, 'password' => $this->password)));

        if ($this->debug === true) {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
        }

        if (($content = curl_exec($ch)) === false) {
            error_log('curl error: ' . curl_error($ch));
        }

        if ($this->debug === true) {
            print '<pre>';
            print PHP_EOL.'-----------LOGIN-------------'.PHP_EOL;
            print_r (curl_getinfo($ch));
            print PHP_EOL.'----------RESPONSE-----------'.PHP_EOL;
            print $content;
            print PHP_EOL.'-----------------------------'.PHP_EOL;
            print '</pre>';
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body        = trim(substr($content, $header_size));
        $code        = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close ($ch);

        preg_match_all('|Set-Cookie: (.*);|U', substr($content, 0, $header_size), $results);
        if (isset($results[1])) {
            $this->cookies = implode(';', $results[1]);
            if (!empty($body)) {
                if (($code >= 200) && ($code < 400)) {
                    if (strpos($this->cookies,'unifises') !== false) {
                        $this->is_loggedin = true;
                    }
                }
                if ($code === 400) {
                     error_log('we have received an HTTP response status: 400. Probably a controller login failure');
                     return $code;
                }
            }
        }

        return $this->is_loggedin;
    }

    /**
     * Logout from UniFi Controller
     */
    public function logout()
    {
        if (!$this->is_loggedin) return false;
        $content           = $this->exec_curl($this->baseurl.'/logout');
        $this->is_loggedin = false;
        $this->cookies     = '';
        return true;
    }

    /**
     * Authorize a client device
     * -------------------------
     * return true on success
     * required parameter <mac>     = client MAC address
     * required parameter <minutes> = minutes (from now) until authorization expires
     * optional parameter <up>      = upload speed limit in kbps
     * optional parameter <down>    = download speed limit in kbps
     * optional parameter <MBytes>  = data transfer limit in MB
     * optional parameter <ap_mac>  = AP MAC address to which client is connected, should result in faster authorization
     */
    public function authorize_guest($mac, $minutes, $up = null, $down = null, $MBytes = null, $ap_mac = null)
    {
        if (!$this->is_loggedin) return false;
        $mac    = strtolower($mac);
        $return = false;
        $json   = array('cmd' => 'authorize-guest', 'mac' => $mac, 'minutes' => $minutes);

        /**
         * if we have received values for up/down/MBytes we append them to the payload array to be submitted
         */
        if (isset($up))     $json['up']     = $up;
        if (isset($down))   $json['down']   = $down;
        if (isset($MBytes)) $json['bytes']  = $MBytes;
        if (isset($ap_mac)) $json['ap_mac'] = $ap_mac;
        $json            = json_encode($json);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/stamgr','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Unauthorize a client device
     * ---------------------------
     * return true on success
     * required parameter <mac> = client MAC address
     */
    public function unauthorize_guest($mac)
    {
        if (!$this->is_loggedin) return false;
        $return          = false;
        $mac             = strtolower($mac);
        $json            = json_encode(array('cmd' => 'unauthorize-guest', 'mac' => $mac));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/stamgr','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
     }

    /**
     * Reconnect a client device
     * -------------------------
     * return true on success
     * required parameter <mac> = client MAC address
     */
    public function reconnect_sta($mac)
    {
        if (!$this->is_loggedin) return false;
        $return          = false;
        $mac             = strtolower($mac);
        $json            = json_encode(array('cmd' => 'kick-sta', 'mac' => $mac));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/stamgr','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Block a client device
     * ---------------------
     * return true on success
     * required parameter <mac> = client MAC address
     */
    public function block_sta($mac)
    {
        if (!$this->is_loggedin) return false;
        $return          = false;
        $mac             = strtolower($mac);
        $json            = json_encode(array('cmd' => 'block-sta', 'mac' => $mac));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/stamgr','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Unblock a client device
     * -----------------------
     * return true on success
     * required parameter <mac> = client MAC address
     */
    public function unblock_sta($mac)
    {
        if (!$this->is_loggedin) return false;
        $return          = false;
        $mac             = strtolower($mac);
        $json            = json_encode(array('cmd' => 'unblock-sta', 'mac' => $mac));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/stamgr','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Add/modify a client device note
     * -------------------------------
     * return true on success
     * required parameter <user_id> = id of the user device to be modified
     * optional parameter <note>    = note to be applied to the user device
     *
     * NOTES:
     * - when note is empty or not set, the existing note for the user will be removed and "noted" attribute set to false
     */
    public function set_sta_note($user_id, $note = null)
    {
        if (!$this->is_loggedin) return false;
        $return          = false;
        $noted           = (is_null($note)) || (empty($note)) ? false : true;
        $json            = json_encode(array('note' => $note, 'noted' => $noted));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/upd/user/'.$user_id,'json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Add/modify a client device name
     * -------------------------------
     * return true on success
     * required parameter <user_id> = id of the user device to be modified
     * optional parameter <name>    = name to be applied to the user device
     *
     * NOTES:
     * - when name is empty or not set, the existing name for the user will be removed
     */
    public function set_sta_name($user_id, $name = null)
    {
        if (!$this->is_loggedin) return false;
        $return          = false;
        $json            = json_encode(array('name' => $name));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/upd/user/'.$user_id,'json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Daily site stats method
     * ------------------------
     * returns an array of daily stats objects for the current site
     * optional parameter <start> = Unix timestamp in seconds
     * optional parameter <end>   = Unix timestamp in seconds
     *
     * NOTES:
     * - defaults to the past 52*7*24 hours
     * - bytes" are no longer returned with controller version 4.9.1 and later
     */
    public function stat_daily_site($start = null, $end = null)
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $end             = is_null($end) ? ((time()-(time() % 3600))*1000) : $end;
        $start           = is_null($start) ? $end-(52*7*24*3600*1000) : $start;
        $json            = json_encode(array('attrs' => array('bytes', 'wan-tx_bytes', 'wan-rx_bytes', 'wlan_bytes', 'num_sta', 'lan-num_sta', 'wlan-num_sta', 'time'), 'start' => $start, 'end' => $end));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/report/daily.site','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $stat) {
                        $return[]= $stat;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Hourly site stats method
     * ------------------------
     * returns an array of hourly stats objects for the current site
     * optional parameter <start> = Unix timestamp in seconds
     * optional parameter <end>   = Unix timestamp in seconds
     *
     * NOTES:
     * - defaults to the past 7*24 hours
     * - "bytes" are no longer returned with controller version 4.9.1 and later
     */
    public function stat_hourly_site($start = null, $end = null)
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $end             = is_null($end) ? ((time())*1000) : $end;
        $start           = is_null($start) ? $end-(7*24*3600*1000) : $start;
        $json            = json_encode(array('attrs' => array('bytes', 'wan-tx_bytes', 'wan-rx_bytes', 'wlan_bytes', 'num_sta', 'lan-num_sta', 'wlan-num_sta', 'time'), 'start' => $start, 'end' => $end));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/report/hourly.site','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $stat) {
                        $return[]= $stat;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Hourly stats method for all access points
     * -----------------------------------------
     * returns an array of hourly stats objects
     * optional parameter <start> = Unix timestamp in seconds
     * optional parameter <end>   = Unix timestamp in seconds
     *
     * NOTES:
     * - defaults to the past 7*24 hours
     * - UniFi controller does not keep these stats longer than 5 hours with versions < 4.6.6
     */
    public function stat_hourly_aps($start = null, $end = null)
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $end             = is_null($end) ? ((time())*1000) : $end;
        $start           = is_null($start) ? $end-(7*24*3600*1000) : $start;
        $json            = json_encode(array('attrs' => array('bytes', 'num_sta', 'time'), 'start' => $start, 'end' => $end));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/report/hourly.ap','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $stat) {
                        $return[]= $stat;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Daily stats method for all access points
     * ----------------------------------------
     * returns an array of daily stats objects
     * optional parameter <start> = Unix timestamp in seconds
     * optional parameter <end>   = Unix timestamp in seconds
     *
     * NOTES:
     * - defaults to the past 7*24 hours
     * - UniFi controller does not keep these stats longer than 5 hours with versions < 4.6.6
     */
    public function stat_daily_aps($start = null, $end = null)
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $end             = is_null($end) ? ((time())*1000) : $end;
        $start           = is_null($start) ? $end-(7*24*3600*1000) : $start;
        $json            = json_encode(array('attrs' => array('bytes', 'num_sta', 'time'), 'start' => $start, 'end' => $end));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/report/daily.ap','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $stat) {
                        $return[]= $stat;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Show all login sessions
     * -----------------------
     * returns an array of login session objects for all devices
     * optional parameter <start> = Unix timestamp in seconds
     * optional parameter <end>   = Unix timestamp in seconds
     * optional parameter <mac>   = client MAC address to return sessions for (can only be used when start and end are also provided)
     *
     * NOTES:
     * - defaults to the past 7*24 hours
     */
    public function stat_sessions($start = null, $end = null, $mac = null)
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $end             = is_null($end) ? time() : $end;
        $start           = is_null($start) ? $end-(7*24*3600) : $start;
        $json            = array('type'=> 'all', 'start' => $start, 'end' => $end);
        if (!is_null($mac)) $json['mac'] = $mac;
        $json            = json_encode($json);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/session','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $session) {
                        $return[]= $session;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Show latest 'n' login sessions for a single device
     * --------------------------------------------------
     * returns an array of latest login session objects for given client device
     * required parameter <mac>   = client MAC address
     * optional parameter <limit> = maximum number of sessions to get (defaults to 5)
     */
    public function stat_sta_sessions_latest($mac, $limit = null)
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $limit           = is_null($limit) ? 5 : $limit;
        $json            = json_encode(array('mac' => $mac, '_limit' => $limit, '_sort'=> '-assoc_time'));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/session','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $session) {
                        $return[]= $session;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Show all authorizations
     * -----------------------
     * returns an array of authorization objects
     * optional parameter <start> = Unix timestamp in seconds
     * optional parameter <end>   = Unix timestamp in seconds
     *
     * NOTES:
     * - defaults to the past 7*24 hours
     */
    public function stat_auths($start = null, $end = null)
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $end             = is_null($end) ? time() : $end;
        $start           = is_null($start) ? $end-(7*24*3600) : $start;
        $json            = json_encode(array('start' => $start, 'end' => $end));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/authorization','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $auth) {
                        $return[]= $auth;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List all client devices ever connected to the site
     * --------------------------------------------------
     * returns an array of client device objects
     * optional parameter <historyhours> = hours to go back (default is 8760 hours or 1 year)
     *
     * NOTES:
     * - <historyhours> is only used to select clients that were online within that period,
     *    the returned stats per client are all-time totals, irrespective of the value of <historyhours>
     */
    public function stat_allusers($historyhours = 8760)
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $json            = json_encode(array('type' => 'all', 'conn' => 'all', 'within' => $historyhours));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/alluser','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $stats) {
                        $return[]= $stats;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List guest devices
     * ------------------
     * returns an array of guest device objects with valid access
     * optional parameter <within> = time frame in hours to go back to list guests with valid access (default = 24*365 hours)
     */
    public function list_guests($within = 8760)
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $json            = json_encode(array('within' => $within));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/guest','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $guest) {
                        $return[]= $guest;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List online client device(s)
     * ----------------------------
     * returns an array of online client device objects, or in case of a single device request, returns a single client device object
     * optional parameter <client_mac> = the MAC address of a single online client device for which the call must be made
     */
    public function list_clients($client_mac = null)
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/sta/'.$client_mac));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $client) {
                        $return[]= $client;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Get data for a single client device
     * -----------------------------------
     * returns an object with the client device information
     * required parameter <client_mac> = client device MAC address
     */
    public function stat_client($client_mac)
    {
        if (!$this->is_loggedin) return false;
        $return          = false;
	    $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/user/'.$client_mac));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $client) {
                        $return[]= $client;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List user groups
     * ----------------
     * returns an array of user group objects
     */
    public function list_usergroups()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/usergroup'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $usergroup) {
                        $return[]= $usergroup;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Assign user device to another group
     * -----------------------------------
     * return true on success
     * required parameter <user_id>  = id of the user device to be modified
     * required parameter <group_id> = id of the user group to assign user to
     */
    public function set_usergroup($user_id, $group_id)
    {
        if (!$this->is_loggedin) return false;
        $return          = false;
        $json            = json_encode(array('usergroup_id' => $group_id));
	    $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/upd/user/'.$user_id,'json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * List health metrics
     * -------------------
     * returns an array of health metric objects
     */
    public function list_health()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/health'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $health) {
                        $return[]= $health;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List dashboard metrics
     * ----------------------
     * returns an array of dashboard metric objects (available since controller version 4.9.1.alpha)
     */
    public function list_dashboard()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/dashboard'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $dashboard) {
                        $return[]= $dashboard;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List user devices
     * -----------------
     * returns an array of known user device objects
     */
    public function list_users()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/user'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $user) {
                        $return[]= $user;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List access points and other devices under management of the controller (USW and/or USG devices)
     * ------------------------------------------------------------------------------------------------
     * returns an array of known device objects (or a single device when using the <device_mac> parameter)
     * optional parameter <device_mac> = the MAC address of a single device for which the call must be made
     */
    public function list_aps($device_mac = null)
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/device/'.$device_mac));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $ap) {
                        $return[]= $ap;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List rogue access points
     * ------------------------
     * returns an array of known rogue access point objects
     * optional parameter <within> = hours to go back to list discovered "rogue" access points (default = 24 hours)
     */
    public function list_rogueaps($within = '24')
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $json            = json_encode(array('within' => $within));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/rogueap','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $rogue) {
                        $return[]= $rogue;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List sites
     * ----------
     * returns a list sites hosted on this controller with some details
     */
    public function list_sites()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/self/sites'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $site) {
                        $return[]= $site;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List sites stats
     * ----------------
     * returns statistics for all sites hosted on this controller
     * NOTE: this endpoint was introduced with controller version 5.2.9
     */
    public function stat_sites()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/stat/sites'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $site) {
                        $return[]= $site;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Add a site
     * ----------
     * returns an array containing a single object with attributes of the new site ("_id", "desc", "name") on success
     * required parameter <description> = the long name for the new site
     * NOTE: immediately after being added, the new site will be available in the output of the "list_sites" function
     */
    public function add_site($description)
    {
        if (!$this->is_loggedin) return false;
        $return          = false;
        $json            = json_encode(array('desc' => $description, 'cmd' => 'add-site'));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/sitemgr','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $site) {
                        $return[]= $site;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Delete a site
     * -------------
     * return true on success
     * required parameter <site_id> = _id (24 char string) of the site to delete
     */
    public function delete_site($site_id)
    {
        if (!$this->is_loggedin) return false;
        $return          = false;
        $json            = json_encode(array('site' => $site_id, 'cmd' => 'delete-site'));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/sitemgr','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * List admins
     * -----------
     * returns an array containing administrator objects for selected site
     */
    public function list_admins($description)
    {
        if (!$this->is_loggedin) return false;
        $return          = false;
        $json            = json_encode(array('cmd' => 'get-admins'));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/sitemgr','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $admin) {
                        $return[]= $admin;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List wlan_groups
     * ----------------
     * returns an array containing known wlan_groups
     */
    public function list_wlan_groups()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/wlangroup'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $wlan_group) {
                        $return[]= $wlan_group;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List sysinfo
     * ------------
     * returns an array of known sysinfo data
     */
    public function stat_sysinfo()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/sysinfo'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $sysinfo) {
                        $return[]= $sysinfo;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List self
     * ---------
     * returns an array of information about the logged in user
     */
    public function list_self()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/self'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $selfinfo) {
                        $return[]= $selfinfo;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List networkconf
     * ----------------
     * returns an array of network configuration data
     */
    public function list_networkconf()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/networkconf'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $networkconf) {
                        $return[]= $networkconf;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List vouchers
     * -------------
     * returns an array of hotspot voucher objects
     * optional parameter <create_time> = Unix timestamp in seconds
     */
    public function stat_voucher($create_time = null)
    {
        if (!$this->is_loggedin) return false;
        $return = array();
        if (trim($create_time) != null) {
            $json = json_encode(array('create_time' => $create_time));
        } else {
            $json = json_encode(array());
        }

        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/voucher','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $voucher) {
                        $return[]= $voucher;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List payments
     * -------------
     * returns an array of hotspot payments
     * optional parameter <within> = number of hours to go back to fetch payments
     */
    public function stat_payment($within = null)
    {
        if (!$this->is_loggedin) return false;
        $return = array();
        if ($within != null) {
          $url_suffix = '?within='.$within;
        } else {
          $url_suffix = '';
        }

        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/payment'.$url_suffix));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $payment) {
                        $return[]= $payment;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Create hotspot operator
     * -----------------------
     * return true upon success
     * required parameter <name>       = name for the hotspot operator
     * required parameter <x_password> = clear text password for the hotspot operator
     * optional parameter <note>       = note to attach to the hotspot operator
     */
    public function create_hotspotop($name, $x_password, $note = null)
    {
        if (!$this->is_loggedin) return false;
        $return = false;
        $json   = array('name' => $name, 'x_password' => $x_password);
        /**
         * if we have received a value for note, we append it to the payload array to be submitted
         */
        if (isset($note)) $json['note'] = trim($note);
        $json            = json_encode($json);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/rest/hotspotop','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * List hotspot operators
     * ----------------------
     * returns an array of hotspot operators
     */
    public function list_hotspotop()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/hotspotop'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $hotspotop) {
                        $return[]= $hotspotop;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Create voucher(s)
     * -----------------
     * returns an array of voucher codes (NOTE: without the "-" in the middle) by calling the stat_voucher method
     * required parameter <minutes> = minutes the voucher is valid after activation
     * optional parameter <count>   = number of vouchers to create, default value is 1
     * optional parameter <quota>   = single-use or multi-use vouchers, string value '0' is for multi-use, '1' is for single-use, "n" is for multi-use n times
     * optional parameter <note>    = note text to add to voucher when printing
     * optional parameter <up>      = upload speed limit in kbps
     * optional parameter <down>    = download speed limit in kbps
     * optional parameter <MBytes>  = data transfer limit in MB
     */
    public function create_voucher($minutes, $count = 1, $quota = '0', $note = null, $up = null, $down = null, $MBytes = null)
    {
        if (!$this->is_loggedin) return false;
        $return = array();
        $json   = array('cmd' => 'create-voucher', 'expire' => $minutes, 'n' => $count, 'quota' => $quota);
        /**
         * if we have received values for note/up/down/MBytes we append them to the payload array to be submitted
         */
        if (isset($note))    $json['note'] = trim($note);
        if (isset($up))      $json['up'] = $up;
        if (isset($down))    $json['down'] = $down;
        if (isset($MBytes))  $json['bytes'] = $MBytes;
        $json            = json_encode($json);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/hotspot','json='.$json));
        if ($content_decoded->meta->rc == 'ok') {
            if (is_array($content_decoded->data)) {
                $obj = $content_decoded->data[0];
                foreach ($this->stat_voucher($obj->create_time) as $voucher)  {
                    $return[]= $voucher->code;
                }
            }
        }

        return $return;
    }

    /**
     * Revoke voucher
     * --------------
     * return true on success
     * required parameter <voucher_id> = _id (24 char string) of the voucher to revoke
     */
    public function revoke_voucher($voucher_id)
    {
        if (!$this->is_loggedin) return false;
        $return          = false;
        $json            = json_encode(array('_id' => $voucher_id, 'cmd' => 'delete-voucher'));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/hotspot','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Extend guest validity
     * ---------------------
     * return true on success
     * required parameter <guest_id> = _id (24 char string) of the guest to extend validity
     */
    public function extend_guest_validity($guest_id)
    {
        if (!$this->is_loggedin) return false;
        $return          = false;
        $json            = json_encode(array('_id' => $guest_id, 'cmd' => 'extend'));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/hotspot','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * List port forwarding stats
     * --------------------------
     * returns an array of port forwarding stats
     */
    public function list_portforward_stats()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/portforward'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $portforward) {
                        $return[]= $portforward;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List port forwarding settings
     * -----------------------------
     * returns an array of port forwarding settings
     */
    public function list_portforwarding()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/portforward'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $portforward) {
                        $return[]= $portforward;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List dynamic DNS settings
     * -------------------------
     * returns an array of dynamic DNS settings
     */
    public function list_dynamicdns()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/dynamicdns'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $dynamicdns) {
                        $return[]= $dynamicdns;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List port configuration
     * -----------------------
     * returns an array of port configurations
     */
    public function list_portconf()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/portconf'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $portconf) {
                        $return[]= $portconf;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List VoIP extensions
     * --------------------
     * returns an array of VoIP extensions
     */
    public function list_extension()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/extension'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $extension) {
                        $return[]= $extension;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List site settings
     * ------------------
     * returns an array of site configuration settings
     */
    public function list_settings()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/get/setting'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $setting) {
                        $return[]= $setting;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Adopt a device
     * --------------
     * return true on success
     * required parameter <mac> = device MAC address
     */
    public function adopt_device($mac)
    {
        if (!$this->is_loggedin) return false;
        $mac             = strtolower($mac);
        $return          = false;
        $json            = json_encode(array('mac' => $mac, 'cmd' => 'adopt'));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/devmgr','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Reboot an access point
     * ----------------------
     * return true on success
     * required parameter <mac> = device MAC address
     */
    public function restart_ap($mac)
    {
        if (!$this->is_loggedin) return false;
        $mac             = strtolower($mac);
        $return          = false;
        $json            = json_encode(array('cmd' => 'restart', 'mac' => $mac));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/devmgr','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Disable/enable an access point
     * ------------------------------
     * return true on success
     * required parameter <ap_id>   = value of _id (24 char string) for the access point which can be obtained from the device list
     * required parameter <disable> = boolean; true will disable the device, false will enable the device
     *
     * NOTES:
     * - a disabled device will be excluded from the dashboard status and device count and its LED and WLAN will be turned off
     * - appears to only be supported for access points
     * - available since controller versions 5.2.X
     */
    public function disable_ap($ap_id, $disable)
    {
        if (!$this->is_loggedin) return false;
        $this->request_type = 'PUT';
        $return             = false;
        $json               = json_encode(array('disabled' => $disable));
        $content_decoded    = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/rest/device/'.$ap_id, $json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Override LED mode for a device
     * ------------------------------
     * return true on success
     * required parameter <device_id>     = value of _id (24 char string) for the device which can be obtained from the device list
     * required parameter <override_mode> = off/on/default; "off" will disable the LED of the device, "on" will enable the LED of the device,
     *                                      "default" will apply the site-wide setting for device LEDs
     *
     * NOTES:
     * - available since controller versions 5.2.X
     */
    public function led_override($device_id, $override_mode)
    {
        if (!$this->is_loggedin) return false;
        $this->request_type    = 'PUT';
        $return                = false;
        $override_mode_options = array("off", "on", "default");
        if (in_array($override_mode, $override_mode_options)) {
            $json            = json_encode(array('led_override' => $override_mode));
            $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/rest/device/'.$device_id, $json));
            if (isset($content_decoded->meta->rc)) {
                if ($content_decoded->meta->rc == 'ok') {
                    $return = true;
                }
            }
        }

        return $return;
    }

    /**
     * Start flashing LED of an access point for locating purposes
     * -----------------------------------------------------------
     * return true on success
     * required parameter <mac> = device MAC address
     */
    public function set_locate_ap($mac)
    {
        if (!$this->is_loggedin) return false;
        $mac             = strtolower($mac);
        $return          = false;
        $json            = json_encode(array('cmd' => 'set-locate', 'mac' => $mac));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/devmgr','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Stop flashing LED of an access point for locating purposes
     * ----------------------------------------------------------
     * return true on success
     * required parameter <mac> = device MAC address
     */
    public function unset_locate_ap($mac)
    {
        if (!$this->is_loggedin) return false;
        $mac             = strtolower($mac);
        $return          = false;
        $json            = json_encode(array('cmd' => 'unset-locate', 'mac' => $mac));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/devmgr','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Switch LEDs of all the access points ON
     * ---------------------------------------
     * return true on success
     */
    public function site_ledson()
    {
        if (!$this->is_loggedin) return false;
        $return          = false;
        $json            = json_encode(array('led_enabled' => true));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/set/setting/mgmt','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Switch LEDs of all the access points OFF
     * ----------------------------------------
     * return true on success
     */
    public function site_ledsoff()
    {
        if (!$this->is_loggedin) return false;
        $return          = false;
        $json            = json_encode(array('led_enabled' => false));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/set/setting/mgmt','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Set access point radio settings
     * -------------------------------
     * return true on success
     * required parameter <ap_id>
     * required parameter <radio>(default=ng)
     * required parameter <channel>
     * required parameter <ht>(default=20)
     * required parameter <tx_power_mode>
     * required parameter <tx_power>(default=0)
     */
    public function set_ap_radiosettings($ap_id, $radio, $channel, $ht, $tx_power_mode, $tx_power)
    {
        if (!$this->is_loggedin) return false;
        $return          = false;
        $jsonsettings    = json_encode(array('radio' => $radio, 'channel' => $channel, 'ht' => $ht, 'tx_power_mode' => $tx_power_mode, 'tx_power' =>$tx_power));
        $json            = '{"radio_table": ['.$jsonsettings.']}';
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/upd/device/'.$ap_id,'json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Set guest login settings
     * ------------------------
     * return true on success
     * required parameter <portal_enabled>
     * required parameter <portal_customized>
     * required parameter <redirect_enabled>
     * required parameter <redirect_url>
     * required parameter <x_password>
     * required parameter <expire_number>
     * required parameter <expire_unit>
     * required parameter <site_id>
     *
     * NOTES:
     * - both portal parameters are set to the same value!
     */
    public function set_guestlogin_settings($portal_enabled, $portal_customized, $redirect_enabled, $redirect_url, $x_password, $expire_number, $expire_unit, $site_id)
    {
        if (!$this->is_loggedin) return false;
        $return = false;
        $json   = json_encode(array('portal_enabled' => $portal_enabled, 'portal_customized' => $portal_customized,
                                    'redirect_enabled' => $redirect_enabled, 'redirect_url' => $redirect_url,
                                    'x_password' => $x_password, 'expire_number' => $expire_number,
                                    'expire_unit' => $expire_unit, 'site_id' => $site_id), JSON_UNESCAPED_SLASHES);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/set/setting/guest_access','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Rename access point
     * -------------------
     * return true on success
     * required parameter <ap_id>
     * required parameter <apname>
     */
    public function rename_ap($ap_id, $apname)
    {
        if (!$this->is_loggedin) return false;
        $return          = false;
        $json            = json_encode(array('name' => $apname));
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/upd/device/'.$ap_id,'json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Add a wlan
     * ----------
     * return true on success
     * required parameter <name>             = SSID
     * required parameter <x_passphrase>     = new pre-shared key, minimal length is 8 characters, maximum length is 63
     * required parameter <usergroup_id>     = user group id that can be found using the list_usergroups() function
     * required parameter <wlangroup_id>     = wlan group id that can be found using the list_wlan_groups() function
     * optional parameter <enabled>          = enable/disable wlan
     * optional parameter <hide_ssid>        = hide wlan SSID
     * optional parameter <is_guest>         = apply guest policies
     * optional parameter <security>         = security type
     * optional parameter <wpa_mode>         = wpa mode (wpa, wpa2, ..)
     * optional parameter <wpa_enc>          = encryption (auto, ccmp)
     * optional parameter <vlan_enabled>     = enable vlan for this wlan
     * optional parameter <vlan>             = vlan id
     * optional parameter <uapsd_enabled>    = enable Unscheduled Automatic Power Save Delivery
     * optional parameter <schedule_enabled> = enable wlan schedule
     * optional parameter <schedule>         = schedule rules
     * -----------------
     * TODO: Check parameter values
     */
    public function create_wlan($name, $x_passphrase, $usergroup_id, $wlangroup_id, $enabled = null, $hide_ssid = null, $is_guest = null, $security = null,
                                $wpa_mode = null, $wpa_enc = null, $vlan_enabled = null, $vlan = null, $uapsd_enabled = null, $schedule_enabled = null, $schedule = null)
    {
        if (!$this->is_loggedin) return false;
        $return                   = false;
        $json                     = array('name' => $name, 'x_passphrase' => $x_passphrase, 'usergroup_id' => $usergroup_id, 'wlangroup_id' => $wlangroup_id);
        $json['enabled']          = (!is_null($enabled) ? $enabled : true);
        $json['hide_ssid']        = (!is_null($hide_ssid) ? $hide_ssid : false);
        $json['is_guest']         = (!is_null($is_guest) ? $is_guest : false);
        $json['security']         = (!is_null($security) ? $security : 'open');
        $json['wpa_mode']         = (!is_null($wpa_mode) ? $wpa_mode : 'wpa2');
        $json['wpa_enc']          = (!is_null($wpa_enc) ? $wpa_enc : 'ccmp');
        $json['vlan_enabled']     = (!is_null($vlan_enabled) ? $vlan_enabled : false);
        if (!is_null($vlan)) $json['vlan'] = $vlan_enabled;
        $json['uapsd_enabled']    = (!is_null($uapsd_enabled) ? $uapsd_enabled : false);
        $json['schedule_enabled'] = (!is_null($schedule_enabled) ? $schedule_enabled : false);
        $json['schedule']         = (!is_null($schedule) ? $schedule : array());
        $json                     = json_encode($json);
        $content_decoded          = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/add/wlanconf','json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Delete a wlan
     * -------------
     * return true on success
     * required parameter <wlan_id> = _id (24 char string) of the wlan that can be found with the list_wlanconf() function
     */
    public function delete_wlan($wlan_id)
    {
        if (!$this->is_loggedin) return false;
        $return          = false;
        $json            = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/del/wlanconf/'.$wlan_id, 'json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Set wlan settings
     * -----------------
     * return true on success
     * required parameter <wlan_id>
     * required parameter <x_passphrase> = new pre-shared key, minimal length is 8 characters, maximum length is 63,
     *                                     will be ignored if set to NULL
     * optional parameter <name>
     */
    public function set_wlansettings($wlan_id, $x_passphrase, $name = null)
    {
        if (!$this->is_loggedin) return false;
        $return          = false;
        $json            = array();
        if (!is_null($x_passphrase)) $json['x_passphrase'] = $x_passphrase;
        if (!is_null($name)) $json['name'] = $name;
        $json            = json_encode($json);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/upd/wlanconf/'.$wlan_id,'json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Disable/Enable wlan
     * -------------------
     * return true on success
     * required parameter <wlan_id>
     * required parameter <disable> = true or false which determines the action to be done
     */
    public function disable_wlan($wlan_id, $disable)
    {
        if (!$this->is_loggedin) return false;
        if ($disable == 'false') {$disable = true;} else  {$disable = false;}
        $return          = false;
        $json            = array('enabled' => (bool)$disable);
        $json            = json_encode($json);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/upd/wlanconf/'.$wlan_id,'json='.$json));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * List events
     * -----------
     * returns an array of known events
     */
    public function list_events()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/event'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $event) {
                        $return[]= $event;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List wireless settings
     * ----------------------
     * returns an array of wireless networks and settings
     */
    public function list_wlanconf()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/wlanconf'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $wlan) {
                        $return[]= $wlan;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List alarms
     * -----------
     * returns an array of known alarms
     */
    public function list_alarms()
    {
        if (!$this->is_loggedin) return false;
        $return          = array();
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/alarm'));
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == 'ok') {
                if (is_array($content_decoded->data)) {
                    foreach ($content_decoded->data as $alarm) {
                        $return[]= $alarm;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Internal (private) functions from here
     */
    private function exec_curl($url, $data = '')
    {
        $ch = $this->get_curl_obj();
        curl_setopt($ch, CURLOPT_URL, $url);

        if (trim($data) != '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            if ($this->request_type == 'PUT') {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($data)));
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            } else {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            }

        } else {
            curl_setopt($ch, CURLOPT_POST, false);
        }

        if (($content = curl_exec($ch)) === false) {
            error_log('curl error: ' . curl_error($ch));
        }

        if ($this->debug === true) {
            print '<pre>';
            print PHP_EOL.'---------cURL INFO-----------'.PHP_EOL;
            print_r (curl_getinfo($ch));
            print PHP_EOL.'-------URL & PAYLOAD---------'.PHP_EOL;
            print $url.PHP_EOL;
            print $data;
            print PHP_EOL.'---------RESPONSE------------'.PHP_EOL;
            print $content;
            print PHP_EOL.'-----------------------------'.PHP_EOL;
            print '</pre>';
        }

        curl_close ($ch);
        return $content;
    }

    private function get_curl_obj()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($this->debug === true) {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
        }

        if ($this->cookies != '') {
            curl_setopt($ch, CURLOPT_COOKIESESSION, true);
            curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
        }

        return $ch;
    }
}