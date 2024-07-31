<?php
/**
 * Copyright (c) 2024, Art of WiFi
 * www.artofwifi.net
 *
 * @license This file is subject to the MIT license bundled with this package in the file LICENSE.md
 */

/**
 * this array defines the menu options for the various collections
 *
 * NOTES:
 * - do not modify this file, instead add a custom sub menu to the config.php file as explained in the README.md file
 * - a valid value for params looks like this:
 *     [true, true, 'no'] (note the quotes surrounding strings)
 */
$collections = [
    [
        'label'   => 'Configuration',
        'options' => [
            [
                'type'   => 'collection', // or divider
                'label'  => 'list sites on this controller',
                'method' => 'list_sites',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'sysinfo',
                'method' => 'stat_sysinfo',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'self',
                'method' => 'list_self',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'controller status',
                'method' => 'stat_full_status',
                'params' => [],
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list site settings',
                'method' => 'list_settings',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list admins for current site',
                'method' => 'list_admins',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list all admins for this controller',
                'method' => 'list_all_admins',
                'params' => [],
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list wlan configuration',
                'method' => 'list_wlanconf',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list current channels',
                'method' => 'list_current_channels',
                'params' => [],
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list VoIP extensions',
                'method' => 'list_extension',
                'params' => [],
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list network configuration',
                'method' => 'list_networkconf',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list port configuration',
                'method' => 'list_portconf',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list port forwarding rules',
                'method' => 'list_portforwarding',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list firewall rules',
                'method' => 'list_firewallrules',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list firewall groups',
                'method' => 'list_firewallgroups',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list static routes',
                'method' => 'list_routing',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'dynamic DNS configuration',
                'method' => 'list_dynamicdns',
                'params' => [],
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list country codes',
                'method' => 'list_country_codes',
                'params' => [],
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list auto backups',
                'method' => 'list_backups',
                'params' => [],
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list Radius profiles',
                'method' => 'list_radius_profiles',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list Radius accounts',
                'method' => 'list_radius_accounts',
                'params' => [],
            ],

        ],
    ],
    [
        'label'   => 'Clients',
        'options' => [
            [
                'type'   => 'collection', // or divider
                'label'  => 'list online clients',
                'method' => 'list_clients',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list guests',
                'method' => 'list_guests',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list users',
                'method' => 'list_users',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list user groups',
                'method' => 'list_usergroups',
                'params' => [],
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'stat all users',
                'method' => 'stat_allusers',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'stat authorisations',
                'method' => 'stat_auths',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'stat sessions',
                'method' => 'stat_sessions',
                'params' => [],
            ],
        ],
    ],
    [
        'label'   => 'Devices',
        'options' => [
            [
                'type'   => 'collection', // or divider
                'label'  => 'list devices',
                'method' => 'list_devices',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list wlan groups',
                'method' => 'list_wlan_groups',
                'params' => [],
            ],
            [
                'type'   => 'collection',     // or divider
                'label'  => 'list AP groups', // supported from '6.0.23'
                'method' => 'list_apgroups',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list rogue access points',
                'method' => 'list_rogueaps',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list known rogue access points',
                'method' => 'list_known_rogueaps',
                'params' => [],
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list available firmware',
                'method' => 'list_firmware',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list cached firmware',
                'method' => 'list_firmware',
                'params' => ['cached'],
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list tags',  // supported from '5.5.0'
                'method' => 'list_tags',
                'params' => [],
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type'   => 'collection',                // or divider
                'label'  => 'list device name mappings', // supported from '5.5.0'
                'method' => 'list_device_name_mappings',
                'params' => [],
            ],
        ],
    ],
    [
        'label'   => 'Stats',
        'options' => [
            [
                'type'   => 'collection', // or divider
                'label'  => 'all sites stats',
                'method' => 'stat_sites',
                'params' => [],
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => '5 minutes site stats',
                'method' => 'stat_5minutes_site',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'hourly site stats',
                'method' => 'stat_hourly_site',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'daily site stats',
                'method' => 'stat_daily_site',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'monthly site stats',
                'method' => 'stat_monthly_site',
                'params' => [],
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => '5 minutes access point stats',
                'method' => 'stat_5minutes_aps',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'hourly access point stats',
                'method' => 'stat_hourly_aps',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'daily access point stats',
                'method' => 'stat_daily_aps',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'monthly access point stats',
                'method' => 'stat_monthly_aps',
                'params' => [],
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => '5 minutes gateway stats',
                'method' => 'stat_5minutes_gateway',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'hourly gateway stats',
                'method' => 'stat_hourly_gateway',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'daily gateway stats',
                'method' => 'stat_daily_gateway',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'monthly gateway stats',
                'method' => 'stat_monthly_gateway',
                'params' => [],
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => '5 minutes site dashboard metrics',
                'method' => 'list_dashboard',
                'params' => [true],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'hourly site dashboard metrics',
                'method' => 'list_dashboard',
                'params' => [],
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'speedtest results',
                'method' => 'stat_speedtest_results',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'site health metrics',
                'method' => 'list_health',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'port forwarding stats',
                'method' => 'list_portforward_stats',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'DPI stats',
                'method' => 'list_dpi_stats',
                'params' => [],
            ],
        ],
    ],
    [
        'label'   => 'Hotspot',
        'options' => [
            [
                'type'   => 'collection', // or divider
                'label'  => 'stat vouchers',
                'method' => 'stat_voucher',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'stat payments',
                'method' => 'stat_payment',
                'params' => [],
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list hotspot operators',
                'method' => 'list_hotspotop',
                'params' => [],
            ],
        ],
    ],
    [
        'label'   => 'Messages',
        'options' => [
            [
                'type'   => 'collection', // or divider
                'label'  => 'list alarms',
                'method' => 'list_alarms',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'count all alarms',
                'method' => 'count_alarms',
                'params' => [],
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'count archived alarms',
                'method' => 'count_alarms',
                'params' => [true],
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list events',
                'method' => 'list_events',
                'params' => [],
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type'   => 'collection', // or divider
                'label'  => 'list IPS/IDS events',
                'method' => 'stat_ips_events',
                'params' => [],
            ],
        ],
    ],
];
