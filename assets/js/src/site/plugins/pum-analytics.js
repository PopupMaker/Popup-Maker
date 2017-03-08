/**
 * Defines the core pum analytics methods.
 * Version 1.4
 */

var PUM_Analytics;
(function ($, document, undefined) {
    "use strict";

    $.fn.popmake.last_open_trigger = null;
    $.fn.popmake.last_close_trigger = null;
    $.fn.popmake.conversion_trigger = null;

    var rest_enabled = typeof pum_vars.restapi !== 'undefined' && pum_vars.restapi ? true : false;

    PUM_Analytics = {
        beacon: function (opts) {
            var beacon = new Image(),
                url = rest_enabled ? pum_vars.restapi : pum_vars.ajaxurl;

            opts = $.extend(true, {
                route: '/analytics/open',
                type: 'open',
                data: {
                    pid: null,
                    _cache: (+(new Date()))
                },
                callback: function () {}
            }, opts);

            if (!rest_enabled) {
                opts.data.action = 'pum_analytics';
                opts.data.type = opts.type;
            } else {
                url += opts.route;
            }

            // Create a beacon if a url is provided
            if (url) {
                // Attach the event handlers to the image object
                $(beacon).on('error success load done', opts.callback);

                // Attach the src for the script call
                beacon.src = url + '?' + $.param(opts.data);
            }
        }
    };

    if (pum_vars.disable_open_tracking === undefined || !pum_vars.disable_open_tracking) {
        // Only popups from the editor should fire analytics events.
        $(document)
        /**
         * Track opens for popups.
         */
            .on('pumAfterOpen.core_analytics', 'body > .pum', function () {
                var $popup = PUM.getPopup(this),
                    data = {
                        pid: parseInt($popup.popmake('getSettings').id, 10) || null
                    };

                if (data.pid > 0 && !$('body').hasClass('single-popup')) {
                    PUM_Analytics.beacon({data: data});
                }
            });
    }
}(jQuery, document));