/**
 * Defines the core pum analytics methods.
 * Version 1.4.0
 */

var PUM_Analytics;
(function ($, document, undefined) {
    "use strict";

    $.fn.popmake.last_open_trigger = null;
    $.fn.popmake.last_close_trigger = null;
    $.fn.popmake.conversion_trigger = null;

    PUM_Analytics = {
        send: function (data, callback) {
            var img = (new Image());

            data = $.extend({}, {
                'action': 'pum_analytics'
            }, data);

            // Add Cache busting.
            data._cache = (+(new Date()));

            // Method 1
            if (callback !== undefined) {
                img.addEventListener('load', function () {
                    callback(data);
                });
            }
            img.src = pum_vars.ajaxurl + '?' + $.param(data);

            return;
            /*
             Method 2 - True AJAX
             $.get({
             type: 'POST',
             dataType: 'json',
             url: pum_vars.ajaxurl,
             data: data,
             success: function (data) {
             if (callback !== undefined) {
             callback(data);
             }
             }
             });
             */
        }

    };

    // Only popups from the editor should fire analytics events.
    $('body > .pum')

    /**
     * Track opens for popups.
     */
        .on('pumAfterOpen.core_analytics', function () {
            var $popup = PUM.getPopup(this),
                data = {
                    pid: parseInt($popup.popmake('getSettings').id, 10) || null,
                    type: 'open'
                };

            if (data.pid > 0 && !$('body').hasClass('single-popup')) {
                PUM_Analytics.send(data);
            }
        });

}(jQuery, document));