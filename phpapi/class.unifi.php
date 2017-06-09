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
 * VERSION: 1.1.5
 *
 * NOTES:
 * - this class will only work with UniFi Controller versions 4.x and 5.x. There are no checks to prevent
 *   you from trying to use it with other versions of the UniFi Controller.
 *
 * IMPORTANT CHANGES:
 * - function/method get_vouchers() has been removed and has been replaced by stat_voucher()
 * as of version 1.1.2:
 * - functions/methods unset_locate_ap() and set_locate_ap() have deprecated and replaced
 *   by locate_ap(), but are still available as alias.
 * - functions/methods site_ledson() and site_ledsoff() have deprecated and replaced
 *   by site_leds(), but are still available as alias.
 * as of version 1.1.5:
 * - list_devices() function/method replaces list_aps which is still available as alias
 * - changed class name from unifiapi to UnifiApi (StudlyCaps)
 *
 * ------------------------------------------------------------------------------------
 *
 * Copyright (c) 2016, Slooffmaster
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.md
 *
 */
define('API_CLASS_VERSION', '1.1.5');

class UnifiApi
{
    /**
     * public properties
     */
    public $user          = '';
    public $password      = '';
    public $site          = 'default';
    public $baseurl       = 'https://127.0.0.1:8443';
    public $version       = '5.4.16';

    /**
     * private properties
     */
    private $debug        = false;
    private $is_loggedin  = false;
    private $cookies      = '';
    private $request_type = 'POST';
    private $last_results_raw;
    private $last_error_message;

    function __construct($user = '', $password = '', $baseurl = '', $site = '', $version = '')
    {
        if (!empty($user)) $this->user         = trim($user);
        if (!empty($password)) $this->password = trim($password);
        if (!empty($baseurl)) $this->baseurl   = trim($baseurl);
        if (!empty($site)) $this->site         = trim($site);
        if (!empty($version)) $this->version   = trim($version);
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
        $ch = $this->get_curl_obj();

        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_REFERER, $this->baseurl.'/login');
        curl_setopt($ch, CURLOPT_URL, $this->baseurl.'/api/login');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => $this->user, 'password' => $this->password]));

        if ($this->debug) {
            curl_setopt($ch, CURLOPT_VERBOSE, true);

            print '<pre>';
            print PHP_EOL.'-----------LOGIN-------------'.PHP_EOL;
            print_r (curl_getinfo($ch));
            print PHP_EOL.'----------RESPONSE-----------'.PHP_EOL;
            print $content;
            print PHP_EOL.'-----------------------------'.PHP_EOL;
            print '</pre>';
        }

        if (($content = curl_exec($ch)) === false) {
            error_log('cURL error: '.curl_error($ch));
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
                    if (strpos($this->cookies, 'unifises') !== false) {
                        $this->is_loggedin = true;
                    }
                }

                if ($code === 400) {
                     error_log('We have received an HTTP response status: 400. Probably a controller login failure');
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
        $this->exec_curl($this->baseurl.'/logout');
        $this->is_loggedin = false;
        $this->cookies     = '';
        return true;
    }

    /****************************************************************
     * setter/getter functions from here:
     ****************************************************************/

    /**
     * Set debug mode
     * --------------
     * sets debug mode to true or false, returns false if a non-boolean parameter was passed
     * required parameter <enable> = boolean; true will enable debug mode, false will disable it
     */
    public function set_debug($enable)
    {
        if ($enable) {
            $this->debug = true;
        } elseif ($enable === false) {
            $this->debug = false;
        }

        return false;
    }

    /**
     * Get last raw results
     * --------------------
     * returns the raw results of the last method called in PHP stdClass Object format by default, returns false if not set
     * optional parameter <return_json> = boolean; true will return the results in "pretty printed" json format
     *
     * NOTE:
     * this method can be used to get the full error as returned by the controller
     */
    public function get_last_results_raw($return_json = false)
    {
        if ($this->last_results_raw != null) {
            if ($return_json) {
                return json_encode($this->last_results_raw, JSON_PRETTY_PRINT);
            }

            return $this->last_results_raw;
        }

        return false;
    }

    /**
     * Get last error message
     * ----------------------
     * returns the error message of the last method called in PHP stdClass Object format, returns false if not set
     */
    public function get_last_error_message()
    {
        if (isset($this->last_error_message)) {
            return $this->last_error_message;
        }

        return false;
    }

    /****************************************************************
     * Functions to access UniFi controller API routes from here:
     ****************************************************************/

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
        $mac  = strtolower($mac);
        $json = ['cmd' => 'authorize-guest', 'mac' => $mac, 'minutes' => $minutes];

        /**
         * if we have received values for up/down/MBytes we append them to the payload array to be submitted
         */
        if (isset($up))     $json['up']     = $up;
        if (isset($down))   $json['down']   = $down;
        if (isset($MBytes)) $json['bytes']  = $MBytes;
        if (isset($ap_mac)) $json['ap_mac'] = $ap_mac;
        $json            = json_encode($json);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/stamgr', 'json='.$json));
        return $this->process_response_boolean($content_decoded);
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
        $mac             = strtolower($mac);
        $json            = json_encode(['cmd' => 'unauthorize-guest', 'mac' => $mac]);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/stamgr', 'json='.$json));
        return $this->process_response_boolean($content_decoded);
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
        $mac             = strtolower($mac);
        $json            = json_encode(['cmd' => 'kick-sta', 'mac' => $mac]);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/stamgr', 'json='.$json));
        return $this->process_response_boolean($content_decoded);
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
        $mac             = strtolower($mac);
        $json            = json_encode(['cmd' => 'block-sta', 'mac' => $mac]);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/stamgr', 'json='.$json));
        return $this->process_response_boolean($content_decoded);
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
        $mac             = strtolower($mac);
        $json            = json_encode(['cmd' => 'unblock-sta', 'mac' => $mac]);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/stamgr', 'json='.$json));
        return $this->process_response_boolean($content_decoded);
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
        $noted           = (is_null($note)) || (empty($note)) ? false : true;
        $json            = json_encode(['note' => $note, 'noted' => $noted]);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/upd/user/'.trim($user_id), 'json='.$json));
        return $this->process_response_boolean($content_decoded);
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
        $json            = json_encode(['name' => $name]);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/upd/user/'.trim($user_id), 'json='.$json));
        return $this->process_response_boolean($content_decoded);
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
        $end             = is_null($end) ? ((time()-(time() % 3600))*1000) : $end;
        $start           = is_null($start) ? $end-(52*7*24*3600*1000) : $start;
        $attributes      = ['bytes', 'wan-tx_bytes', 'wan-rx_bytes', 'wlan_bytes', 'num_sta', 'lan-num_sta', 'wlan-num_sta', 'time'];
        $json            = json_encode(['attrs' => $attributes, 'start' => $start, 'end' => $end]);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/report/daily.site', 'json='.$json));
        return $this->process_response($content_decoded);
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
        $end             = is_null($end) ? ((time())*1000) : $end;
        $start           = is_null($start) ? $end-(7*24*3600*1000) : $start;
        $attributes      = ['bytes', 'wan-tx_bytes', 'wan-rx_bytes', 'wlan_bytes', 'num_sta', 'lan-num_sta', 'wlan-num_sta', 'time'];
        $json            = json_encode(['attrs' => $attributes, 'start' => $start, 'end' => $end]);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/report/hourly.site', 'json='.$json));
        return $this->process_response($content_decoded);
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
        $end             = is_null($end) ? ((time())*1000) : $end;
        $start           = is_null($start) ? $end-(7*24*3600*1000) : $start;
        $json            = json_encode(['attrs' => ['bytes', 'num_sta', 'time'], 'start' => $start, 'end' => $end]);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/report/hourly.ap', 'json='.$json));
        return $this->process_response($content_decoded);
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
        $end             = is_null($end) ? ((time())*1000) : $end;
        $start           = is_null($start) ? $end-(7*24*3600*1000) : $start;
        $json            = json_encode(['attrs' => ['bytes', 'num_sta', 'time'], 'start' => $start, 'end' => $end]);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/report/daily.ap', 'json='.$json));
        return $this->process_response($content_decoded);
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
        $end             = is_null($end) ? time() : $end;
        $start           = is_null($start) ? $end-(7*24*3600) : $start;
        $json            = ['type'=> 'all', 'start' => $start, 'end' => $end];
        if (!is_null($mac)) $json['mac'] = $mac;
        $json            = json_encode($json);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/session', 'json='.$json));
        return $this->process_response($content_decoded);
    }

    /**
     * Show latest 'n' login sessions for a single client device
     * ---------------------------------------------------------
     * returns an array of latest login session objects for given client device
     * required parameter <mac>   = client MAC address
     * optional parameter <limit> = maximum number of sessions to get (defaults to 5)
     */
    public function stat_sta_sessions_latest($mac, $limit = null)
    {
        if (!$this->is_loggedin) return false;
        $limit           = is_null($limit) ? 5 : $limit;
        $json            = json_encode(['mac' => $mac, '_limit' => $limit, '_sort'=> '-assoc_time']);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/session', 'json='.$json));
        return $this->process_response($content_decoded);
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
        $end             = is_null($end) ? time() : $end;
        $start           = is_null($start) ? $end-(7*24*3600) : $start;
        $json            = json_encode(['start' => $start, 'end' => $end]);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/authorization', 'json='.$json));
        return $this->process_response($content_decoded);
    }

    /**
     * List all client devices ever connected to the site
     * --------------------------------------------------
     * returns an array of client device objects
     * optional parameter <historyhours> = hours to go back (default is 8760 hours or 1 year)
     *
     * NOTES:
     * - <historyhours> is only used to select clients that were online within that period,
     *   the returned stats per client are all-time totals, irrespective of the value of <historyhours>
     */
    public function stat_allusers($historyhours = 8760)
    {
        if (!$this->is_loggedin) return false;
        $json            = json_encode(['type' => 'all', 'conn' => 'all', 'within' => $historyhours]);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/alluser', 'json='.$json));
        return $this->process_response($content_decoded);
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
        $json            = json_encode(['within' => $within]);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/guest', 'json='.$json));
        return $this->process_response($content_decoded);
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
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/sta/'.trim($client_mac)));
        return $this->process_response($content_decoded);
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
	    $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/user/'.trim($client_mac)));
        return $this->process_response($content_decoded);
    }

    /**
     * List user groups
     * ----------------
     * returns an array of user group objects
     */
    public function list_usergroups()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/usergroup'));
        return $this->process_response($content_decoded);
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
        $json            = json_encode(['usergroup_id' => $group_id]);
	    $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/upd/user/'.trim($user_id), 'json='.$json));
        return $this->process_response_boolean($content_decoded);
    }


    /**
     * Edit user group
     * ---------------
     * 
     * required parameter <group_id> = id of the user group
     * required parameter <site_id> = id of the site
     * required parameter <group_name> = name of the user group
     * optional parameter <group_dn> = limit download bandwidth in Kbps (default = -1)
     * optional parameter <group_up> = limit upload bandwidth in Kbps (default = -1)
     * 
     */
    public function edit_usergroup($group_id, $site_id, $group_name, $group_dn = -1, $group_up = -1)
    {
        if (!$this->is_loggedin) return false;
        $this->request_type = 'PUT';
        $json               = json_encode(array('_id' => $group_id, 'name' => $group_name, 'qos_rate_max_down' => $group_dn, 'qos_rate_max_up' => $group_up, 'site_id' => $site_id));
        $content_decoded    = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/rest/usergroup/'.trim($group_id), $json));
        return $this->process_response($content_decoded);
    }

    /**
     * Add user group
     * --------------
     * returns an array containing a single object with attributes of the new usergroup ("_id", "name", "qos_rate_max_down", "qos_rate_max_up", "site_id") on success
     * required parameter <group_name> = name of the user group
     * optional parameter <group_dn> = limit download bandwidth in Kbps (default = -1)
     * optional parameter <group_up> = limit upload bandwidth in Kbps (default = -1)
     */
    public function add_usergroup($group_name, $group_dn = -1, $group_up = -1)
    {
        if (!$this->is_loggedin) return false;
        $json               = json_encode(['name' => $group_name, 'qos_rate_max_down' => $group_dn, 'qos_rate_max_up' => $group_up]);
        $content_decoded    = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/rest/usergroup', $json));
        return $this->process_response($content_decoded);
    }

    /**
     * Delete user group
     * --------------
     * returns true on success 
     * required parameter <group_id> = id of the user group
     */
    public function delete_usergroup($group_id)
    {
        if (!$this->is_loggedin) return false;
        $this->request_type = 'DELETE';
        $content_decoded    = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/rest/usergroup/'.trim($group_id)));
        return $this->process_response_boolean($content_decoded);
    }

    /**
     * List health metrics
     * -------------------
     * returns an array of health metric objects
     */
    public function list_health()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/health'));
        return $this->process_response($content_decoded);
    }

    /**
     * List dashboard metrics
     * ----------------------
     * returns an array of dashboard metric objects (available since controller version 4.9.1.alpha)
     */
    public function list_dashboard()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/dashboard'));
        return $this->process_response($content_decoded);
    }

    /**
     * List user devices
     * -----------------
     * returns an array of known user device objects
     */
    public function list_users()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/user'));
        return $this->process_response($content_decoded);
    }

    /**
     * List access points and other devices under management of the controller (USW and/or USG devices)
     * ------------------------------------------------------------------------------------------------
     * returns an array of known device objects (or a single device when using the <device_mac> parameter)
     * optional parameter <device_mac> = the MAC address of a single device for which the call must be made
     */
    public function list_devices($device_mac = null)
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/device/'.$device_mac));
        return $this->process_response($content_decoded);
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
        $json            = json_encode(['within' => $within]);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/rogueap', 'json='.$json));
        return $this->process_response($content_decoded);
    }

    /**
     * List sites
     * ----------
     * returns a list sites hosted on this controller with some details
     */
    public function list_sites()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/self/sites'));
        return $this->process_response($content_decoded);
    }

    /**
     * List sites stats
     * ----------------
     * returns statistics for all sites hosted on this controller
     *
     * NOTES: this endpoint was introduced with controller version 5.2.9
     */
    public function stat_sites()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/stat/sites'));
        return $this->process_response($content_decoded);
    }

    /**
     * Add a site
     * ----------
     * returns an array containing a single object with attributes of the new site ("_id", "desc", "name") on success
     * required parameter <description> = the long name for the new site
     *
     * NOTES: immediately after being added, the new site will be available in the output of the "list_sites" function
     */
    public function add_site($description)
    {
        if (!$this->is_loggedin) return false;
        $json            = json_encode(['desc' => $description, 'cmd' => 'add-site']);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/sitemgr', 'json='.$json));
        return $this->process_response($content_decoded);
    }

    /**
     * Delete a site
     * -------------
     * return true on success
     * required parameter <site_id> = 24 char string; _id of the site to delete
     */
    public function delete_site($site_id)
    {
        if (!$this->is_loggedin) return false;
        $json            = json_encode(['site' => $site_id, 'cmd' => 'delete-site']);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/sitemgr', 'json='.$json));
        return $this->process_response_boolean($content_decoded);
    }

    /**
     * List admins
     * -----------
     * returns an array containing administrator objects for selected site
     */
    public function list_admins()
    {
        if (!$this->is_loggedin) return false;
        $json            = json_encode(['cmd' => 'get-admins']);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/sitemgr', 'json='.$json));
        return $this->process_response($content_decoded);
    }

    /**
     * List wlan_groups
     * ----------------
     * returns an array containing known wlan_groups
     */
    public function list_wlan_groups()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/wlangroup'));
        return $this->process_response($content_decoded);
    }

    /**
     * List sysinfo
     * ------------
     * returns an array of known sysinfo data
     */
    public function stat_sysinfo()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/sysinfo'));
        return $this->process_response($content_decoded);
    }

    /**
     * List self
     * ---------
     * returns an array of information about the logged in user
     */
    public function list_self()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/self'));
        return $this->process_response($content_decoded);
    }

    /**
     * List networkconf
     * ----------------
     * returns an array of network configuration data
     */
    public function list_networkconf()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/networkconf'));
        return $this->process_response($content_decoded);
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
        if (trim($create_time) != null) {
            $json = json_encode(['create_time' => $create_time]);
        } else {
            $json = json_encode([]);
        }

        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/voucher', 'json='.$json));
        return $this->process_response($content_decoded);
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
        if ($within != null) {
            $url_suffix = '?within='.$within;
        } else {
            $url_suffix = '';
        }

        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/payment'.$url_suffix));
        return $this->process_response($content_decoded);
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
        $json = ['name' => $name, 'x_password' => $x_password];

        /**
         * if we have received a value for note, we append it to the payload array to be submitted
         */
        if (isset($note)) $json['note'] = trim($note);
        $json            = json_encode($json);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/rest/hotspotop', 'json='.$json));
        return $this->process_response_boolean($content_decoded);
    }

    /**
     * List hotspot operators
     * ----------------------
     * returns an array of hotspot operators
     */
    public function list_hotspotop()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/hotspotop'));
        return $this->process_response($content_decoded);
    }

    /**
     * Create voucher(s)
     * -----------------
     * returns an array of voucher codes (without the dash "-" in the middle) by calling the stat_voucher method
     * required parameter <minutes> = minutes the voucher is valid after activation
     * optional parameter <count>   = number of vouchers to create, default value is 1
     * optional parameter <quota>   = single-use or multi-use vouchers, string value '0' is for multi-use, '1' is for single-use,
     *                                "n" is for multi-use n times
     * optional parameter <note>    = note text to add to voucher when printing
     * optional parameter <up>      = upload speed limit in kbps
     * optional parameter <down>    = download speed limit in kbps
     * optional parameter <MBytes>  = data transfer limit in MB
     */
    public function create_voucher($minutes, $count = 1, $quota = '0', $note = null, $up = null, $down = null, $MBytes = null)
    {
        if (!$this->is_loggedin) return false;
        $json = ['cmd' => 'create-voucher', 'expire' => $minutes, 'n' => $count, 'quota' => $quota];

        /**
         * if we have received values for note/up/down/MBytes we append them to the payload array to be submitted
         */
        if (isset($note))   $json['note'] = trim($note);
        if (isset($up))     $json['up'] = $up;
        if (isset($down))   $json['down'] = $down;
        if (isset($MBytes)) $json['bytes'] = $MBytes;
        $json            = json_encode($json);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/hotspot', 'json='.$json));
        return $this->process_response($content_decoded);
    }

    /**
     * Revoke voucher
     * --------------
     * return true on success
     * required parameter <voucher_id> = 24 char string; _id of the voucher to revoke
     */
    public function revoke_voucher($voucher_id)
    {
        if (!$this->is_loggedin) return false;
        $json            = json_encode(['_id' => $voucher_id, 'cmd' => 'delete-voucher']);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/hotspot', 'json='.$json));
        return $this->process_response_boolean($content_decoded);
    }

    /**
     * Extend guest validity
     * ---------------------
     * return true on success
     * required parameter <guest_id> = 24 char string; _id of the guest to extend validity
     */
    public function extend_guest_validity($guest_id)
    {
        if (!$this->is_loggedin) return false;
        $json            = json_encode(['_id' => $guest_id, 'cmd' => 'extend']);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/hotspot', 'json='.$json));
        return $this->process_response_boolean($content_decoded);
    }

    /**
     * List port forwarding stats
     * --------------------------
     * returns an array of port forwarding stats
     */
    public function list_portforward_stats()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/portforward'));
        return $this->process_response($content_decoded);
    }

    /**
     * List DPI stats
     * --------------
     * returns an array of DPI stats
     */
    public function list_dpi_stats()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/dpi'));
        return $this->process_response($content_decoded);
    }

    /**
     * List current channels
     * ---------------------
     * returns an array of currently allowed channels
     */
    public function list_current_channels()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/current-channel'));
        return $this->process_response($content_decoded);
    }

    /**
     * List port forwarding settings
     * -----------------------------
     * returns an array of port forwarding settings
     */
    public function list_portforwarding()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/portforward'));
        return $this->process_response($content_decoded);
    }

    /**
     * List dynamic DNS settings
     * -------------------------
     * returns an array of dynamic DNS settings
     */
    public function list_dynamicdns()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/dynamicdns'));
        return $this->process_response($content_decoded);
    }

    /**
     * List port configuration
     * -----------------------
     * returns an array of port configurations
     */
    public function list_portconf()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/portconf'));
        return $this->process_response($content_decoded);
    }

    /**
     * List VoIP extensions
     * --------------------
     * returns an array of VoIP extensions
     */
    public function list_extension()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/extension'));
        return $this->process_response($content_decoded);
    }

    /**
     * List site settings
     * ------------------
     * returns an array of site configuration settings
     */
    public function list_settings()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/get/setting'));
        return $this->process_response($content_decoded);
    }

    /**
     * Adopt a device to the selected site
     * -----------------------------------
     * return true on success
     * required parameter <mac> = device MAC address
     */
    public function adopt_device($mac)
    {
        if (!$this->is_loggedin) return false;
        $mac             = strtolower($mac);
        $json            = json_encode(['mac' => $mac, 'cmd' => 'adopt']);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/devmgr', 'json='.$json));
        return $this->process_response_boolean($content_decoded);
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
        $json            = json_encode(['cmd' => 'restart', 'mac' => $mac]);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/devmgr', 'json='.$json));
        return $this->process_response_boolean($content_decoded);
    }

    /**
     * Disable/enable an access point
     * ------------------------------
     * return true on success
     * required parameter <ap_id>   = 24 char string; value of _id for the access point which can be obtained from the device list
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
        $json               = json_encode(['disabled' => (bool)$disable]);
        $content_decoded    = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/rest/device/'.trim($ap_id), $json));
        return $this->process_response_boolean($content_decoded);
    }

    /**
     * Override LED mode for a device
     * ------------------------------
     * return true on success
     * required parameter <device_id>     = 24 char string; value of _id for the device which can be obtained from the device list
     * required parameter <override_mode> = string, off/on/default; "off" will disable the LED of the device,
     *                                      "on" will enable the LED of the device,
     *                                      "default" will apply the site-wide setting for device LEDs
     *
     * NOTES:
     * - available since controller versions 5.2.X
     */
    public function led_override($device_id, $override_mode)
    {
        if (!$this->is_loggedin) return false;
        $this->request_type    = 'PUT';
        $override_mode_options = ['off', 'on', 'default'];
        if (in_array($override_mode, $override_mode_options)) {
            $json            = json_encode(['led_override' => $override_mode]);
            $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/rest/device/'.trim($device_id), $json));
            return $this->process_response_boolean($content_decoded);
        }

        return false;
    }

    /**
     * Toggle flashing LED of an access point for locating purposes
     * ------------------------------------------------------------
     * return true on success
     * required parameter <mac>    = device MAC address
     * required parameter <enable> = boolean; true will enable flashing LED, false will disable
     *
     * NOTES:
     * replaces the old set_locate_ap() and unset_locate_ap() methods/functions
     */
    public function locate_ap($mac, $enable)
    {
        if (!$this->is_loggedin) return false;
        $mac             = strtolower($mac);
        $cmd             = (($enable) ? 'set-locate' : 'unset-locate');
        $json            = json_encode(['cmd' => $cmd, 'mac' => $mac]);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/devmgr', 'json='.$json));
        return $this->process_response_boolean($content_decoded);
    }

    /**
     * Toggle LEDs of all the access points ON or OFF
     * ----------------------------------------------
     * return true on success
     * required parameter <enable> = boolean; true will switch LEDs of all the access points ON, false will switch them OFF
     */
    public function site_leds($enable)
    {
        if (!$this->is_loggedin) return false;
        $json            = json_encode(['led_enabled' => (bool)$enable]);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/set/setting/mgmt', 'json='.$json));
        return $this->process_response_boolean($content_decoded);
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
        $json            = json_encode(['radio_table' => ['radio' => $radio, 'channel' => $channel, 'ht' => $ht, 'tx_power_mode' => $tx_power_mode, 'tx_power' =>$tx_power]]);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/upd/device/'.trim($ap_id), 'json='.$json));
        return $this->process_response_boolean($content_decoded);
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
    public function set_guestlogin_settings(
        $portal_enabled,
        $portal_customized,
        $redirect_enabled,
        $redirect_url,
        $x_password,
        $expire_number,
        $expire_unit,
        $site_id
    ) {
        if (!$this->is_loggedin) return false;
        $json = [
            'portal_enabled'    => $portal_enabled,
            'portal_customized' => $portal_customized,
            'redirect_enabled'  => $redirect_enabled,
            'redirect_url'      => $redirect_url,
            'x_password'        => $x_password,
            'expire_number'     => $expire_number,
            'expire_unit'       => $expire_unit,
            'site_id'           => $site_id
        ];
        $json            = json_encode($json, JSON_UNESCAPED_SLASHES);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/set/setting/guest_access', 'json='.$json));
        return $this->process_response_boolean($content_decoded);
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
        $json            = json_encode(['name' => $apname]);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/upd/device/'.trim($ap_id), 'json='.$json));
        return $this->process_response_boolean($content_decoded);
    }

    /**
     * Add a wlan
     * ----------
     * return true on success
     * required parameter <name>             = string; SSID
     * required parameter <x_passphrase>     = string; new pre-shared key, minimal length is 8 characters, maximum length is 63
     * required parameter <usergroup_id>     = string; user group id that can be found using the list_usergroups() function
     * required parameter <wlangroup_id>     = string; wlan group id that can be found using the list_wlan_groups() function
     * optional parameter <enabled>          = boolean; enable/disable wlan
     * optional parameter <hide_ssid>        = boolean; hide/unhide wlan SSID
     * optional parameter <is_guest>         = boolean; apply guest policies or not
     * optional parameter <security>         = string; security type
     * optional parameter <wpa_mode>         = string; wpa mode (wpa, wpa2, ..)
     * optional parameter <wpa_enc>          = string; encryption (auto, ccmp)
     * optional parameter <vlan_enabled>     = boolean; enable/disable vlan for this wlan
     * optional parameter <vlan>             = string; vlan id
     * optional parameter <uapsd_enabled>    = boolean; enable/disable Unscheduled Automatic Power Save Delivery
     * optional parameter <schedule_enabled> = boolean; enable/disable wlan schedule
     * optional parameter <schedule>         = string; schedule rules
     * -----------------
     * TODO: Check parameter values
     */
    public function create_wlan(
        $name,
        $x_passphrase,
        $usergroup_id,
        $wlangroup_id,
        $enabled = true,
        $hide_ssid = false,
        $is_guest = false,
        $security = 'open',
        $wpa_mode = 'wpa2',
        $wpa_enc = 'ccmp',
        $vlan_enabled = false,
        $vlan = null,
        $uapsd_enabled = false,
        $schedule_enabled = false,
        $schedule = []
    ) {
        if (!$this->is_loggedin) return false;
        $json = [
            'name'             => $name,
            'x_passphrase'     => $x_passphrase,
            'usergroup_id'     => $usergroup_id,
            'wlangroup_id'     => $wlangroup_id,
            'enabled'          => $enabled,
            'hide_ssid'        => $hide_ssid,
            'is_guest'         => $is_guest,
            'security'         => $security,
            'wpa_mode'         => $wpa_mode,
            'wpa_enc'          => $wpa_enc,
            'vlan_enabled'     => $vlan_enabled,
            'uapsd_enabled'    => $uapsd_enabled,
            'schedule_enabled' => $schedule_enabled,
            'schedule'         => $schedule,
        ];
        if (!is_null($vlan) && $vlan_enabled) $json['vlan'] = $vlan;
        $json            = json_encode($json);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/add/wlanconf', 'json='.$json));
        return $this->process_response_boolean($content_decoded);
    }

    /**
     * Delete a wlan
     * -------------
     * return true on success
     * required parameter <wlan_id> = 24 char string; _id of the wlan that can be found with the list_wlanconf() function
     */
    public function delete_wlan($wlan_id)
    {
        if (!$this->is_loggedin) return false;
        $json            = [];
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/del/wlanconf/'.trim($wlan_id), 'json='.$json));
        return $this->process_response_boolean($content_decoded);
    }

    /**
     * Set wlan settings
     * -----------------
     * return true on success
     * required parameter <wlan_id>
     * required parameter <x_passphrase> = new pre-shared key, minimal length is 8 characters, maximum length is 63,
     *                                     will be ignored if set to null
     * optional parameter <name>
     */
    public function set_wlansettings($wlan_id, $x_passphrase, $name = null)
    {
        if (!$this->is_loggedin) return false;
        $json            = [];
        if (!is_null($x_passphrase)) $json['x_passphrase'] = trim($x_passphrase);
        if (!is_null($name)) $json['name'] = trim($name);
        $json            = json_encode($json);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/upd/wlanconf/'.trim($wlan_id), 'json='.$json));
        return $this->process_response_boolean($content_decoded);
    }

    /**
     * Disable/Enable wlan
     * -------------------
     * return true on success
     * required parameter <wlan_id>
     * required parameter <disable> = boolean; true disables the wlan, false enables it
     */
    public function disable_wlan($wlan_id, $disable)
    {
        if (!$this->is_loggedin) return false;
        $action          = ($disable) ? false : true;
        $json            = ['enabled' => (bool)$action];
        $json            = json_encode($json);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/upd/wlanconf/'.trim($wlan_id), 'json='.$json));
        return $this->process_response_boolean($content_decoded);
    }

    /**
     * List events
     * -----------
     * returns an array of known events
     * optional parameter <historyhours> = hours to go back, default value is 720 hours
     * optional parameter <start>        = which event number to start with (useful for paging of results), default value is 0
     * optional parameter <limit>        = number of events to return, default value is 3000
     */
    public function list_events($historyhours = 720, $start = 0, $limit = 3000)
    {
        if (!$this->is_loggedin) return false;
        $json            = ['_sort' => '-time', 'within' => $historyhours, 'type' => null, '_start' => $start, '_limit' => $limit];
        $json            = json_encode($json);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/event', 'json='.$json));
        return $this->process_response($content_decoded);
    }

    /**
     * List wireless settings
     * ----------------------
     * returns an array of wireless networks and settings
     */
    public function list_wlanconf()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/wlanconf'));
        return $this->process_response($content_decoded);
    }

    /**
     * List alarms
     * -----------
     * returns an array of known alarms
     */
    public function list_alarms()
    {
        if (!$this->is_loggedin) return false;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/list/alarm'));
        return $this->process_response($content_decoded);
    }

    /**
     * Count alarms
     * ------------
     * returns an array containing the alarm count
     * optional parameter <archived> = boolean; if true all alarms will be counted, if false only non-archived (active) alarms will be counted
     */
    public function count_alarms($archived = null)
    {
        if (!$this->is_loggedin) return false;
        $url_suffix      = ($archived === false) ? '?archived=false' : null;
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cnt/alarm'.$url_suffix));
        return $this->process_response($content_decoded);
    }

    /**
     * Upgrade a device to the latest firmware
     * ---------------------------------------
     * return true on success
     * required parameter <device_mac> = MAC address of the device to upgrade
     *
     * NOTES:
     * - updates the device to the latest firmware known to the controller
     */
    public function upgrade_device($device_mac)
    {
        if (!$this->is_loggedin) return false;
        $json            = ['mac' => $device_mac];
        $json            = json_encode($json);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/devmgr/upgrade', 'json='.$json));
        return $this->process_response_boolean($content_decoded);
    }

    /**
     * Upgrade a device to a specific firmware file
     * --------------------------------------------
     * return true on success
     * required parameter <firmware_url> = URL for the firmware file to upgrade the device to
     * required parameter <device_mac>   = MAC address of the device to upgrade
     *
     * NOTES:
     * - updates the device to the firmware file at the given URL
     * - please take great care to select a valid firmware file for the device!
     */
    public function upgrade_device_external($firmware_url, $device_mac)
    {
        if (!$this->is_loggedin) return false;
        $json            = ['url' => $firmware_url, 'mac' => $device_mac];
        $json            = json_encode($json, JSON_UNESCAPED_SLASHES);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/devmgr/upgrade-external', 'json='.$json));
        return $this->process_response_boolean($content_decoded);
    }

    /**
     * Trigger an RF scan by an AP
     * ---------------------------
     * return true on success
     * required parameter <ap_mac> = MAC address of the AP
     */
    public function spectrum_scan($ap_mac)
    {
        if (!$this->is_loggedin) return false;
        $json            = ['cmd' => 'spectrum-scan', 'mac' => $ap_mac];
        $json            = json_encode($json);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/cmd/devmgr', 'json='.$json));
        return $this->process_response_boolean($content_decoded);
    }

    /**
     * Check the RF scanning state of an AP
     * ------------------------------------
     * returns an object with relevant information (results if available) regarding the RF scanning state of the AP
     * required parameter <ap_mac> = MAC address of the AP
     */
    public function spectrum_scan_state($ap_mac)
    {
        if (!$this->is_loggedin) return false;
        $json            = json_encode($json);
        $content_decoded = json_decode($this->exec_curl($this->baseurl.'/api/s/'.$this->site.'/stat/spectrum-scan/'.trim($ap_mac)));
        return $this->process_response($content_decoded);
    }

    /****************************************************************
     * "Aliases" for deprecated functions from here, to support
     * backward compatibility:
     ****************************************************************/

    /**
     * List access points and other devices under management of the controller (USW and/or USG devices)
     * ------------------------------------------------------------------------------------------------
     * returns an array of known device objects (or a single device when using the <device_mac> parameter)
     * optional parameter <device_mac> = the MAC address of a single device for which the call must be made
     *
     * NOTE:
     * changed function/method name to fit it's purpose
     */
    public function list_aps($device_mac = null)
    {
        return $this->list_devices($device_mac);
    }

    /**
     * Start flashing LED of an access point for locating purposes
     * -----------------------------------------------------------
     * return true on success
     * required parameter <mac> = device MAC address
     */
    public function set_locate_ap($mac)
    {
        trigger_error(
            'Function set_locate_ap() has been deprecated, use locate_ap() instead.',
            E_USER_DEPRECATED
        );
        return $this->locate_ap($mac, true);
    }

    /**
     * Stop flashing LED of an access point for locating purposes
     * ----------------------------------------------------------
     * return true on success
     * required parameter <mac> = device MAC address
     */
    public function unset_locate_ap($mac)
    {
        trigger_error(
            'Function unset_locate_ap() has been deprecated, use locate_ap() instead.',
            E_USER_DEPRECATED
        );
        return $this->locate_ap($mac, false);
    }

    /**
     * Switch LEDs of all the access points ON
     * ---------------------------------------
     * return true on success
     */
    public function site_ledson()
    {
        trigger_error(
            'Function site_ledson() has been deprecated, use site_leds() instead.',
            E_USER_DEPRECATED
        );
        return $this->site_leds(true);
    }

    /**
     * Switch LEDs of all the access points OFF
     * ----------------------------------------
     * return true on success
     */
    public function site_ledsoff()
    {
        trigger_error(
            'Function site_ledsoff() has been deprecated, use site_leds() instead.',
            E_USER_DEPRECATED
        );
        return $this->site_leds(false);
    }

    /****************************************************************
     * Internal (private) functions from here:
     ****************************************************************/

    /**
     * Process regular responses where output is the content of the data array
     */
    private function process_response($response)
    {
        $this->last_results_raw = $response;
        if (isset($response->meta->rc)) {
            if ($response->meta->rc == 'ok') {
                $this->last_error_message = null;
                if (is_array($response->data)) {
                    return $response->data;
                }

                return true;
            } elseif ($response->meta->rc == 'error') {
                /**
                 * we have an error; set latest_error_message if we have a message
                 */
                if (isset($response->meta->msg)) {
                    $this->last_error_message = $response->meta->msg;
                }

                if ($this->debug) {
                    error_log('Last error message: ' . $this->last_error_message);
                }

                return false;
            }

            return false;
        }

        return false;
    }

    /**
     * Process responses where output should be boolean (true/false)
     */
    private function process_response_boolean($response)
    {
        $this->last_results_raw = $response;
        if (isset($response->meta->rc)) {
            if ($response->meta->rc == 'ok') {
                $this->last_error_message = null;
                return true;
            } elseif ($response->meta->rc == 'error') {
                /**
                 * we have an error:
                 * set latest_error_message if the returned error message is available
                 */
                if (isset($response->meta->msg)) {
                    $this->last_error_message = $response->meta->msg;
                }

                if ($this->debug) {
                    error_log('Last error message: ' . $this->last_error_message);
                }

                return false;
            }

            return false;
        }

        return false;
    }

    /**
     * Execute the cURL request
     */
    private function exec_curl($url, $data = '')
    {
        $ch = $this->get_curl_obj();
        curl_setopt($ch, CURLOPT_URL, $url);

        if (trim($data) != '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            if ($this->request_type == 'PUT') {
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json','Content-Length: '.strlen($data)]);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            } else {
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            }

        } else {
            curl_setopt($ch, CURLOPT_POST, false);
            if ($this->request_type == 'DELETE') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            }
        }

        if (($content = curl_exec($ch)) === false) {
            error_log('cURL error: '.curl_error($ch));
        }

        if ($this->debug) {
            print '<pre>';
            print PHP_EOL.'---------cURL INFO-----------'.PHP_EOL;
            print_r (curl_getinfo($ch));
            print PHP_EOL.'-------URL & PAYLOAD---------'.PHP_EOL;
            print $url.PHP_EOL;
            print $data;
            print PHP_EOL.'----------RESPONSE-----------'.PHP_EOL;
            print $content;
            print PHP_EOL.'-----------------------------'.PHP_EOL;
            print '</pre>';
        }

        curl_close ($ch);
        return $content;
    }

    /**
     * get the cURL object
     */
    private function get_curl_obj()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($this->debug) {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
        }

        if ($this->cookies != '') {
            curl_setopt($ch, CURLOPT_COOKIESESSION, true);
            curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
        }

        return $ch;
    }
}
