## UniFi controller API client class

This directory contains a PHP class, which is based off the work of @domwo and @fbagnol and the API shell client as published by UBNT.

The class currently supports the following functions/methods to get/set data through the UniFi controller API:
- login()
- logout()
- get_last_results_raw()
- add_site()
- adopt_device()
- authorize_guest()
- unauthorize_guest()
- block_sta()
- unblock_sta()
- create_hotspotop()
- create_voucher()
- delete_site()
- disable_ap()
- led_override()
- list_admins()
- list_alarms()
- list_aps()
- list_clients()
- list_dashboard()
- list_dynamicdns()
- list_events()
- list_extension()
- list_guests()
- list_health()
- list_hotspotop()
- list_networkconf()
- list_portconf()
- list_portforward_stats()
- list_portforwarding()
- list_rogueaps()
- list_self()
- list_settings()
- list_sites()
- list_usergroups()
- list_users()
- list_wlan_groups()
- list_wlanconf()
- reconnect_sta()
- rename_ap()
- restart_ap()
- revoke_voucher()
- extend_guest_validity()
- set_ap_radiosettings()
- set_guestlogin_settings()
- locate_ap()
- set_locate_ap() (deprecated but still available as alias)
- unset_locate_ap() (deprecated but still available as alias)
- set_sta_name()
- set_sta_note()
- set_usergroup()
- set_wlansettings()
- create_wlan()
- delete_wlan()
- site_leds()
- site_ledsoff() (deprecated but still available as alias)
- site_ledson() (deprecated but still available as alias)
- stat_allusers()
- stat_auths()
- stat_client()
- stat_daily_site()
- stat_daily_aps()
- stat_hourly_aps()
- stat_hourly_site()
- stat_payment()
- stat_sessions()
- stat_sites()
- stat_sta_sessions_latest()
- stat_sysinfo()
- stat_voucher()

Please refer to the source code for more details on each function/method and it's parameters.

### Example usage
A basic example how to use the class:

```php
/**
 * load the Unifi API connection class, log in to the controller and request the alarms collection
 * (this example assumes you have already assigned the correct values to the variables used)
 */
require_once('../phpapi/class.unifi.php');
$unifidata = new unifiapi($controller_user, $controller_password, $controller_url, $site_id, $controller_version);
$login     = $unifidata->login();
$results   = $unifidata->list_alarms(); // returns the alarms in a PHP array
```

Have a look at the files in the `examples` directory for more examples how to use this class.

## Important Disclaimer
Many of these functions are not officially supported by UBNT and as such, may not be supported in future versions of the UniFi controller API.
