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
 * this array defines the menu options for the various collections
 *
 * NOTE:
 * - do not modify this file, instead add a custom sub menu to the config.php file
 * - take care to use correct values for the "key" properties (reserved for later use)
 * - a valid value for params looks like this:
 *     [true, true, 'no'] (note the quotes surrounding strings)
 */
$collections = [
    [
        'label' => 'Configuration',
        'options' => [
            [
                'type' => 'collection', // or divider
                'label' => 'list sites on this controller',
                'method' => 'list_sites',
                'params' => [],
                'key' => 'configuration_0'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'sysinfo',
                'method' => 'stat_sysinfo',
                'params' => [],
                'key' => 'configuration_1'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'self',
                'method' => 'list_self',
                'params' => [],
                'key' => 'configuration_2'
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list site settings',
                'method' => 'list_settings',
                'params' => [],
                'key' => 'configuration_3'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list admins for current site',
                'method' => 'list_admins',
                'params' => [],
                'key' => 'configuration_4'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list all admins for this controller',
                'method' => 'list_all_admins',
                'params' => [],
                'key' => 'configuration_5'
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list wlan configuration',
                'method' => 'list_wlanconf',
                'params' => [],
                'key' => 'configuration_6'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list current channels',
                'method' => 'list_current_channels',
                'params' => [],
                'key' => 'configuration_7'
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list VoIP extensions',
                'method' => 'list_extension',
                'params' => [],
                'key' => 'configuration_8'
            ],
            [
                'type' => 'divider', // or collection
            ],

            [
                'type' => 'collection', // or divider
                'label' => 'list network configuration',
                'method' => 'list_networkconf',
                'params' => [],
                'key' => 'configuration_9'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list port configuration',
                'method' => 'list_portconf',
                'params' => [],
                'key' => 'configuration_10'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list port forwarding rules',
                'method' => 'list_portforwarding',
                'params' => [],
                'key' => 'configuration_11'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list firewall groups',
                'method' => 'list_firewallgroups',
                'params' => [],
                'key' => 'configuration_12'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'dynamic DNS configuration',
                'method' => 'list_dynamicdns',
                'params' => [],
                'key' => 'configuration_13'
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list country codes',
                'method' => 'list_country_codes',
                'params' => [],
                'key' => 'configuration_14'
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list auto backups',
                'method' => 'list_backups',
                'params' => [],
                'key' => 'configuration_15'
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list Radius profiles',
                'method' => 'list_radius_profiles',
                'params' => [],
                'key' => 'configuration_16'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list Radius accounts',
                'method' => 'list_radius_accounts',
                'params' => [],
                'key' => 'configuration_17'
            ],

        ],
    ],
    [
        'label' => 'Clients',
        'options' => [
            [
                'type' => 'collection', // or divider
                'label' => 'list online clients',
                'method' => 'list_clients',
                'params' => [],
                'key' => 'clients_0'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list guests',
                'method' => 'list_guests',
                'params' => [],
                'key' => 'clients_1'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list users',
                'method' => 'list_users',
                'params' => [],
                'key' => 'clients_2'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list user groups',
                'method' => 'list_usergroups',
                'params' => [],
                'key' => 'clients_3'
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'stat all users',
                'method' => 'stat_allusers',
                'params' => [],
                'key' => 'clients_4'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'stat authorisations',
                'method' => 'stat_auths',
                'params' => [],
                'key' => 'clients_5'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'stat sessions',
                'method' => 'stat_sessions',
                'params' => [],
                'key' => 'clients_6'
            ],
        ],
    ],
    [
        'label' => 'Devices',
        'options' => [
            [
                'type' => 'collection', // or divider
                'label' => 'list devices',
                'method' => 'list_devices',
                'params' => [],
                'key' => 'devices_0'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list wlan groups',
                'method' => 'list_wlan_groups',
                'params' => [],
                'key' => 'devices_1'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list rogue access points',
                'method' => 'list_rogueaps',
                'params' => [],
                'key' => 'devices_2'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list known rogue access points',
                'method' => 'list_known_rogueaps',
                'params' => [],
                'key' => 'devices_3'
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list tags', // supported from '5.5.0'
                'method' => 'list_tags',
                'params' => [],
                'key' => 'devices_4'
            ],
        ],
    ],
    [
        'label' => 'Stats',
        'options' => [
            [
                'type' => 'collection', // or divider
                'label' => '5 minutes site stats',
                'method' => 'stat_5minutes_site',
                'params' => [],
                'key' => 'stats_0'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'hourly site stats',
                'method' => 'stat_hourly_site',
                'params' => [],
                'key' => 'stats_1'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'daily site stats',
                'method' => 'stat_daily_site',
                'params' => [],
                'key' => 'stats_2'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'all sites stats',
                'method' => 'stat_sites',
                'params' => [],
                'key' => 'stats_3'
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type' => 'collection', // or divider
                'label' => '5 minutes access point stats',
                'method' => 'stat_5minutes_aps',
                'params' => [],
                'key' => 'stats_4'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'hourly access point stats',
                'method' => 'stat_hourly_aps',
                'params' => [],
                'key' => 'stats_5'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'daily access point stats',
                'method' => 'stat_daily_aps',
                'params' => [],
                'key' => 'stats_6'
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type' => 'collection', // or divider
                'label' => '5 minutes gateway stats',
                'method' => 'stat_5minutes_gateway',
                'params' => [],
                'key' => 'stats_7'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'hourly gateway stats',
                'method' => 'stat_hourly_gateway',
                'params' => [],
                'key' => 'stats_8'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'daily gateway stats',
                'method' => 'stat_daily_gateway',
                'params' => [],
                'key' => 'stats_9'
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type' => 'collection', // or divider
                'label' => '5 minutes site dashboard metrics',
                'method' => 'list_dashboard',
                'params' => [true],
                'key' => 'stats_10'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'hourly site dashboard metrics',
                'method' => 'list_dashboard',
                'params' => [],
                'key' => 'stats_11'
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'site health metrics',
                'method' => 'list_health',
                'params' => [],
                'key' => 'stats_12'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'port forwarding stats',
                'method' => 'list_portforward_stats',
                'params' => [],
                'key' => 'stats_13'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'DPI stats',
                'method' => 'list_dpi_stats',
                'params' => [],
                'key' => 'stats_14'
            ],
        ],
    ],
    [
        'label' => 'Hotspot',
        'options' => [
            [
                'type' => 'collection', // or divider
                'label' => 'stat vouchers',
                'method' => 'stat_voucher',
                'params' => [],
                'key' => 'hotspot_0'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'stat payments',
                'method' => 'stat_payment',
                'params' => [],
                'key' => 'hotspot_1'
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list hotspot operators',
                'method' => 'list_hotspotop',
                'params' => [],
                'key' => 'hotspot_2'
            ],
        ],
    ],
    [
        'label' => 'Messages',
        'options' => [
            [
                'type' => 'collection', // or divider
                'label' => 'list alarms',
                'method' => 'list_alarms',
                'params' => [],
                'key' => 'messages_0'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'count all alarms',
                'method' => 'count_alarms',
                'params' => [],
                'key' => 'messages_1'
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'count active alarms',
                'method' => 'count_alarms',
                'params' => [false],
                'key' => 'messages_2'
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list events',
                'method' => 'list_events',
                'params' => [],
                'key' => 'messages_3'
            ],
            [
                'type' => 'divider', // or collection
            ],
            [
                'type' => 'collection', // or divider
                'label' => 'list IPS/IDS events',
                'method' => 'stat_ips_events',
                'params' => [],
                'key' => 'messages_4'
            ],
        ],
    ],
];