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

    PUM_Analytics = {
        beacon: function (opts) {
            var beacon = new Image();

            opts = $.extend(true, {}, {
                url: pum_vars.ajaxurl || null,
                data: {
                    action: 'pum_analytics',
                    _cache: (+(new Date()))
                },
                callback: function () {
                    console.log('test');
                }
            }, opts);

            // Create a beacon if a url is provided
            if (opts.url) {
                // Attach the event handlers to the image object
                $(beacon).on('error success load done', opts.callback);

                // Attach the src for the script call
                beacon.src = opts.url + '?' + $.param(opts.data);
            }
        }
    };

    // Only popups from the editor should fire analytics events.
    $(document)

    /**
     * Track opens for popups.
     */
        .on('pumAfterOpen.core_analytics', 'body > .pum', function () {
            var $popup = PUM.getPopup(this),
                data = {
                    pid: parseInt($popup.popmake('getSettings').id, 10) || null,
                    type: 'open'
                };

            if (data.pid > 0 && !$('body').hasClass('single-popup')) {
                PUM_Analytics.beacon({data: data});
            }
        });
}(jQuery, document));