(function ($) {
    "use strict";

    $.fn.popmake.methods.addCookie = function (type, settings) {
        // Method calling logic
        if ($.fn.popmake.cookies[type]) {
            return $.fn.popmake.cookies[type].apply(this, Array.prototype.slice.call(arguments, 1));
        }
        $.error('Cookie type ' + $.fn.popmake.cookies + ' does not exist.');
        return this;
    };

    $.fn.popmake.methods.setCookie = function (settings) {
        $.pm_cookie(
            settings.name,
            true,
            settings.session ? null : settings.time,
            settings.path ? '/' : null
        );
    };

    $.fn.popmake.cookies = {
        on_popup_open: function (settings) {
            var $popup = $(this);
            $popup.on('pumAfterOpen', function () {
                $popup.popmake('setCookie', settings);
            });
        },
        on_popup_close: function (settings) {
            var $popup = $(this);
            console.log($popup, settings);
            $popup.on('pumBeforeClose', function () {
                $popup.popmake('setCookie', settings);
            });
        },
        manual: function (settings) {
            var $popup = $(this);
            $popup.on('pumSetCookie', function () {
                $popup.popmake('setCookie', settings);
            });
        }
    };

    // Register All Cookies for a Popup
    $(document)
        .on('pumInit', '.popmake', function (e) {
            var $popup = $(this),
                settings = $popup.data('popmake'),
                cookies = settings.cookies,
                cookie = null;

            if (typeof cookies !== 'undefined' && cookies.length) {
                for (var i = 0; cookies.length > i; i++) {
                    cookie = cookies[i];
                    console.log(cookie);
                    $popup.popmake('addCookie', cookie.event, cookie.settings);
                }
            }
        });

}(jQuery));