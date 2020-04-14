/**
 * Copyright (c) 2019, Art of WiFi
 * www.artofwifi.net
 *
 * This file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.md
 *
 */

/**
 * initialize some vars for later use
 */
var theme                  = 'bootstrap',
    controller             = {
        idx:             '',
        full_name:       $('#navbar_controller_dropdown_link').text(),
        config_complete: false
    },
    unifi_sites            = [],
    selected_site          = {},
    selected_collection    = {},
    selected_output_method = 'json';

/**
 * check whether user has stored a custom theme, if yes we switch to the stored value
 */
if (localStorage.getItem('api_browser_tool_theme') === null || localStorage.getItem('api_browser_tool_theme') === 'bootstrap') {
    $('#bootstrap').addClass('active').find('a').append(' <i class="fas fa-check"></i>');
} else {
    var stored_theme = localStorage.getItem('api_browser_tool_theme');
    switchCSS(stored_theme);
}

/**
 * catch a Bootswatch CSS stylesheet change request
 */
$('.theme_option').on('click', function(){
    if (!$(this).hasClass('active')) {
        switchCSS($(this).data('theme_name'));
    }
});

/**
 * catch and process the selection of a UniFi controller
 */
$('.controller_idx').on('click', function(){
    var new_controller_idx = $(this).data('idx');
    if (!$(this).hasClass('active')) {
        /**
         * update the current controller idx
         */
        controller.idx       = new_controller_idx;
        controller.full_name = $(this).data('value');

        /**
         * clear the selected collection and the active options in the menu
         */
        selected_collection = {};
        $('.collection_idx').removeClass('active').children('i').remove();
        $('.dropdown-toggle').removeClass('active');

        /**
         * change the label in the controller dropdown "button"
         */
        $('#navbar_controller_dropdown_link').html(controller.full_name);

        /**
         * toggle the active class together with check marks
         */
        $('.controller_idx').removeClass('active').children('i').remove();
        $($(this)).addClass('active').append(' <i class="fas fa-check"></i>');

        /**
         * in #collection_dropdown menu we also clear any active elements
         */
        $('#collection_dropdown .dropdown-item').parent().find('.dropdown-item').removeClass('active');

        /**
         * if not yet hidden, we hide the alert
         */
        $('#select_controller_alert_wrapper').hide();

        /**
         * hide the output card
         */
        $('#output_container_outer_div').addClass('d-none');
        $('#output_buttons_container_outer_div').addClass('d-none');

        /**
         * hide all alerts
         */
        $('.alert_wrapper').addClass('d-none');

        /**
         * restore the label for the site dropdown "button"
         */
        $('#navbar_site_dropdown_link').html('Sites');

        /**
         * we also update the PHP $_SESSION variable with the new theme name using AJAX POST
         */
        $.ajax({
            type:     'POST',
            url:      'ajax/update_controller.php',
            dataType: 'json',
            data: {
                new_controller_idx: controller.idx
            },
            success:  function (json) {
                /**
                 * only if we are on the "select controller" page do we reload it
                 */
                if ($('#select_controller_alert_wrapper').length > 0) {
                    location.reload(true);
                } else {
                    fetchSites();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(jqXHR);
                controller.idx       = '';
                controller.full_name = '';
            }
        });
    }
});

/**
 * catch a site selection request
 */
$('#site_dropdown').on('click', '.site_idx', function(){
    if (!$(this).hasClass('active')) {
        /**
         * toggle the active class together with check marks
         */
        $('.site_idx').removeClass('active').children('i').remove();
        $($(this)).addClass('active').append(' <i class="fas fa-check"></i>');

        selected_site.name = $(this).text();
        selected_site.id   = $(this).data('site_id');

        $('#navbar_site_dropdown_link').html(selected_site.name);
        $('.alert_wrapper').addClass('d-none');

        $('#collection_dropdown').removeClass('d-none');

        /**
         * only if a collection has not already been selected do we show the alert,
         * otherwise we need to issue the request again to fetch the output
         */
        if (selected_collection.method === undefined || selected_collection.method === '') {
            $('#select_collection_alert_wrapper').removeClass('d-none');
        } else {
            fetchCollection();
        }
    }
});

/**
 * catch a collection selection request
 */
$('#collection_dropdown').on('click', '.collection_idx', function(){
    if (!$(this).hasClass('active')) {
        /**
         * toggle the active class together with check marks
         */
        $('.collection_idx').removeClass('active').children('i').remove();
        $($(this)).addClass('active').append(' <i class="fas fa-check"></i>');

        $('[data-toggle="dropdown"]').removeClass('active');
        $(this).closest('.dropdown-submenu').find('[data-toggle="dropdown"]').eq(0).addClass('active');

        selected_collection.method = $(this).data('method');
        selected_collection.label  = $(this).text();
        selected_collection.key    = $(this).data('key');
        selected_collection.params = $(this).data('params');
        selected_collection.group  = $(this).closest('.dropdown-submenu').find('[data-toggle="dropdown"]').eq(0).text();

        fetchCollection();
    }
});

/**
 * catch a output selection request
 */
$('.output_radio_button').click(function() {
    var button_value = $(this).find('input').attr('value');
    if (button_value !== '') {
        selected_output_method = button_value;
        fetchCollection();
    }
});

/**
 * function to process the CSS switch
 */
function switchCSS(new_theme) {
    console.log('switching to new Bootswatch theme: ' + new_theme);
    if (new_theme == 'bootstrap') {
        $('#bootswatch_theme_stylesheet').attr('href', '');
    } else {
        $('#bootswatch_theme_stylesheet').attr('href', 'https://stackpath.bootstrapcdn.com/bootswatch/4.3.1/' + new_theme + '/bootstrap.min.css');
    }

    $('#' + theme).removeClass('active').children('i').remove();
    $('#' + new_theme).addClass('active').append(' <i class="fas fa-check"></i>');
    theme = new_theme;
    localStorage.setItem('api_browser_tool_theme', theme);

    /**
     * we also update the PHP $_SESSION variable with the new theme name using AJAX POST
     */
    $.ajax({
        type:     'POST',
        url:      'ajax/update_theme.php',
        dataType: 'json',
        data: {
            new_theme: theme
        },
        success: function (json) {
            //
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR);
        }
    });
}

/**
 * function to fetch sites for the current controller
 */
function fetchSites() {
    /**
     * inform the user
     */
    $('#site_dropdown > li > div').html('');
    $('#site_dropdown > li > div').append('<h5 class="dropdown-header">Fetching sites <i class="fas fa-sync fa-spin"></i></h5>');

    $('.alert_wrapper').addClass('d-none');
    $('#fetching_sites_alert_wrapper').removeClass('d-none');

    /**
     * reset the selected_site var and hide the collections dropdown
     */
    selected_site = {};
    $('#collection_dropdown').addClass('d-none');

    /**
     * but before we fetch the sites we need to check if we have a complete config for the current controller,
     * we use the controller.config_complete property to cache the config state
     */
    if (!controller.config_complete) {
        /**
         * TODO: determine correct action when a controller config is incomplete
         */
    }

    fetchDebugDetails();

    /**
     * we fetch the sites for the current controller
     */
    $.ajax({
        type:     'POST',
        url:      'ajax/fetch_sites.php',
        dataType: 'json',
        success:  function (json) {
            if (json.state === 'success') {
                unifi_sites = json.data;

                $('.alert_wrapper').addClass('d-none');
                $('#select_site_alert_wrapper').removeClass('d-none');

                $('#site_dropdown > li > div').html('');

                if (unifi_sites.length < 1) {
                    $('#site_dropdown > li > div').html('<h5 class="dropdown-header">No sites available</h5>');

                    $('.alert_wrapper').addClass('d-none');
                    $('#site_load_error_alert_wrapper').removeClass('d-none');
                } else {
                    $('#site_dropdown > li > div').html('<h5 class="dropdown-header">Select a site</h5>');
                    $.each(unifi_sites, function( index, value ) {
                        $('#site_dropdown > li > div').append('<a class="site_idx dropdown-item" id="' + value.site_id + '" data-site_id="' + value.site_id + '" href="#">' + value.site_full_name + '</a>');
                    });
                }

                /**
                 * we now update the About modal with the dynamic metrics
                 */
                updateAboutModal();
            } else {
                console.log(json.message);
                $('#site_dropdown > li > div').html('<h5 class="dropdown-header">Error loading sites</h5>');
                renderGeneralErrorAlert(json.message);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR);

            unifi_sites = [];

            $('#site_dropdown > li > div').html('');
            $('#site_dropdown > li > div').html('<h5 class="dropdown-header">Error loading sites</h5>');

            $('.alert_wrapper').addClass('d-none');
            $('#site_load_error_alert_wrapper').removeClass('d-none');
        }
    });
}

/**
 * function to fetch controller debug details using AJAX when needed
 */
function fetchDebugDetails() {
    if (debug) {
        $.ajax({
            type:     'POST',
            url:      'ajax/show_api_debug.php',
            dataType: 'html',
            success:  function (data) {
                if (data != 'ignore') {
                    console.log('debug messages as returned by the cURL request to the UniFi controller API:');
                    console.log(data);
                }
            }
        });
    }
}

/**
 * function to fetch a collection
 */
function fetchCollection() {
    /**
     * finally, we show the output container
     */
    $('.alert_wrapper').addClass('d-none');
    $('#output_container_outer_div').removeClass('d-none');
    $('#output_buttons_container_outer_div').removeClass('d-none');

    $('#output_pre').html('<div class="d-flex align-items-center justify-content-center h-100 m-2"><div class="d-flex flex-column m-2"><i class="fas fa-sync fa-spin fa-2x"></i></div></div>');

    var params_string = JSON.stringify(selected_collection.params).slice(1,-1);

    $('#results_summary_placeholder').html(
        controller.full_name + ' <i class="fas fa-sm fa-chevron-right"></i> ' + selected_site.name + ' <i class="fas fa-sm fa-chevron-right"></i> ' +
        selected_collection.group + ' <i class="fas fa-sm fa-chevron-right"></i> ' + selected_collection.label +
        ' / API function: <code>' + selected_collection.method + '(' + params_string + ')</code> / '
    );

    $('#objects_count').html('<i class="fas fa-sync fa-spin"></i>');
    $('#results_stats_placeholder').html('');
    $('.js-copy-trigger').show();

    /**
     * then we fetch the collection using AJAX
     */
    $.ajax({
        type:     'POST',
        url:      'ajax/fetch_collection.php',
        dataType: 'json',
        data: {
            selected_collection_method: selected_collection.method,
            selected_collection_label:  selected_collection.label,
            selected_collection_key:    selected_collection.key,
            selected_collection_params: JSON.stringify(selected_collection.params),
            selected_collection_group:  selected_collection.group,
            selected_site_id:           selected_site.id,
            selected_output_method:     selected_output_method
        },
        success:  function (json) {
            if (json.state === 'success') {
                /**
                 * push results to various elements
                 */
                if (selected_output_method === 'json' || selected_output_method === 'json_highlighted') {
                    var output = JSON.stringify(json.data, undefined, 4);
                    $('#output_pre').html('<code id="copy_container" class="json js-copy-target">' + output + '</code>');

                    if (selected_output_method === 'json_highlighted') {
                        $('#output_pre > code').each(function() {
                             hljs.highlightBlock(this);
                        });
                    }
                } else if (selected_output_method === 'kint' || selected_output_method === 'kint_plain') {
                    $('#output_pre').html('<div class="p-3">' + json.data + '</div>');
                    $('.js-copy-trigger').hide();
                }

                $('#results_stats_placeholder').html(
                    'Total time: ' + (json.timings.load + json.timings.login) + ' seconds' +
                    '<div class="progress">' +
                        '<div class="progress-bar bg-warning" role="progressbar" style="width: ' + json.timings.login_perc +
                            '%" aria-valuenow="' + json.timings.login_perc + ' aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="API login and connect took ' + json.timings.login + ' seconds">API login</div>' +
                        '<div class="progress-bar bg-success" role="progressbar" style="width: ' + json.timings.load_perc +
                            '%" aria-valuenow="'  + json.timings.load_perc + '" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="Data transfer took ' + json.timings.load + ' seconds">data transfer</div>' +
                    '</div>'
                )

                /**
                 * to ensure the tooltips on the freshly rendered progress bar are available
                 */
                $('[data-toggle="tooltip"]').tooltip();

                /**
                 * update the objects count
                 */
                $('#objects_count').html(json.count);

                /**
                 * we now update the About modal with the dynamic metrics
                 */
                updateAboutModal();
            } else {
                console.log(json.message);
                renderGeneralErrorAlert(json.message);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR);
        }
    });
}

/**
 * function to render an alert containing a customized message
 */
function renderGeneralErrorAlert(error_message) {
    /**
     * hide any existings alerts
     */
    $('.alert_wrapper').addClass('d-none');

    /**
     * render the alert
     */
    $('#general_error_alert_wrapper').removeClass('d-none');
    $('#general_error').html('We encountered the following error: ' + error_message);
}

/**
 * function to fetch controller details and PHP memory usage and push to the About modal
 */
function updateAboutModal() {
    /**
     * we fetch the metrics
     */
    $.ajax({
        type:     'GET',
        url:      'ajax/fetch_about_modal_metrics.php',
        dataType: 'json',
        success: function (json) {
            $('#span_controller_url').html(json.controller_url);
            $('#span_controller_user').html(json.controller_user);
            $('#span_controller_version').html(json.controller_version);
            $('#span_memory_used').html(json.memory_used);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR);
        }
    });
}

/**
 * initialize Bootstrap 4 tooltips and popovers
 *
 * NOTE:
 * boundary setting prevents tooltips/popovers from flickering in certain cases
 */
$('[data-toggle="tooltip"]').tooltip({
    trigger:   'hover',
    container: 'body',
    boundary:  'window'
});

$('[data-toggle="popover"]').popover({
    trigger:   'hover',
    container: 'body',
    boundary:  'window'
});

if (selected_site.length > 1) {
    $('#select_site_alert_wrapper').removeClass('d-none');
}

if ($('.controller_idx.dropdown-item.active')) {
    fetchSites();
}

/**
 * manage display of the "back to top" button element
 */
$(window).scroll(function () {
    if ($(this).scrollTop() > 50) {
        $('#back-to-top').fadeIn();
    } else {
        $('#back-to-top').fadeOut();
    }
});

/**
 * scroll body to 0px (top) on click on the "back to top" button
 */
$('#back-to-top').click(function () {
    $('#back-to-top').tooltip('hide');
    $('body,html').animate({
        scrollTop: 0
    }, 500);

    return false;
});

$(function() {
    /**
     * handle multi Level dropdowns for the collections menu
     */
    $("ul.dropdown-menu [data-toggle='dropdown']").on('click', function(event) {
        event.preventDefault();
        event.stopPropagation();

        $(this).siblings().toggleClass('show');
        $(this).closest('.dropdown-submenu').siblings().children().removeClass('show');

        if (!$(this).next().hasClass('show')) {
            $(this).parents('.dropdown-menu').first().find('.show').removeClass('show');
        }

        $(this).parents('li.nav-item.dropdown.show').on('hidden.bs.dropdown', function(e) {
            $('.dropdown-submenu .show').removeClass('show');
        });
    });
});

/**
 * upon opening the "About" modal we check latest version of API browser tool using AJAX and inform user when it's
 * more recent than the current
 */
$('#about_modal').on('shown.bs.modal', function (e) {
    $.ajax({
        type:     'GET',
        url:      'https://api.github.com/repos/Art-of-WiFi/UniFi-API-browser/releases/latest',
        dataType: 'json',
        success:  function (json) {
            if (api_browser_version != '' && typeof(json.tag_name) !== 'undefined') {
                if (api_browser_version < json.tag_name.substring(1)) {
                    $('#span_api_browser_update').html('an update is available: ' + json.tag_name.substring(1));
                    $('#span_api_browser_update').removeClass('badge-success').addClass('badge-warning');
                } else if (api_browser_version == json.tag_name.substring(1)) {
                    $('#span_api_browser_update').html('up to date');
                    $('#span_api_browser_update').removeClass('badge-danger').addClass('badge-success');
                } else {
                    $('#span_api_browser_update').html('bleeding edge!');
                    $('#span_api_browser_update').removeClass('badge-success').addClass('badge-danger');
                }
            }
        },
        error:    function(jqXHR, textStatus, errorThrown) {
            $('#span_api_browser_update').html('error checking updates');
            $('#span_api_browser_update').removeClass('badge-success').addClass('badge-danger');
            console.log(jqXHR);
        }
    });
})
