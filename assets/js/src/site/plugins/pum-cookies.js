(function ($, document, undefined) {
    "use strict";

    $.extend($.fn.popmake.methods, {
        addCookie: function (type) {
            // Method calling logic

            pum.hooks.doAction('popmake.addCookie', arguments);

            if ($.fn.popmake.cookies[type]) {
                return $.fn.popmake.cookies[type].apply(this, Array.prototype.slice.call(arguments, 1));
            }
            if (window.console) {
                console.warn('Cookie type ' + type + ' does not exist.');
            }
            return this;
        },
        setCookie: function (settings) {
            $.pm_cookie(
                settings.name,
                true,
                settings.session ? null : settings.time,
                settings.path ? '/' : null
            );
            pum.hooks.doAction('popmake.setCookie', settings);
        },
        checkCookies: function (settings) {
            var i,
                ret = false;

            if (settings.cookie === undefined || settings.cookie.name === undefined || settings.cookie.name === null) {
                return false;
            }

            switch (typeof settings.cookie.name) {
            case 'object':
            case 'array':
                for (i = 0; settings.cookie.name.length > i; i += 1) {
                    if ($.pm_cookie(settings.cookie.name[i]) !== undefined) {
                        ret = true;
                    }
                }
                break;
            case 'string':
                if ($.pm_cookie(settings.cookie.name) !== undefined) {
                    ret = true;
                }
                break;
            }

            pum.hooks.doAction('popmake.checkCookies', settings, ret);

            return ret;
        }
    });

    $.fn.popmake.cookies = $.fn.popmake.cookies || {};

    $.extend($.fn.popmake.cookies, {
        on_popup_open: function (settings) {
            var $popup = PUM.getPopup(this);
            $popup.on('pumAfterOpen', function () {
                $popup.popmake('setCookie', settings);
            });
        },
        on_popup_close: function (settings) {
            var $popup = PUM.getPopup(this);
            $popup.on('pumBeforeClose', function () {
                $popup.popmake('setCookie', settings);
            });
        },
        manual: function (settings) {
            var $popup = PUM.getPopup(this);
            $popup.on('pumSetCookie', function () {
                $popup.popmake('setCookie', settings);
            });
        },
        form_success: function (settings) {
            var $popup = PUM.getPopup(this);
            $popup.on('pumFormSuccess', function () {
                $popup.popmake('setCookie', settings);
            });
        },
        ninja_form_success: function (settings) {
            return $.fn.popmake.cookies.form_success.apply(this, arguments);
        },
        cf7_form_success: function (settings) {
            return $.fn.popmake.cookies.form_success.apply(this, arguments);
        },
        gforms_form_success: function (settings) {
            return $.fn.popmake.cookies.form_success.apply(this, arguments);
        }
    });

    // Register All Cookies for a Popup
    $(document)
        .on('pumInit', '.pum', function () {
            var $popup   = PUM.getPopup(this),
                settings = $popup.popmake('getSettings'),
                cookies  = settings.cookies,
                cookie   = null,
                i;

            if (cookies !== undefined && cookies.length) {
                for (i = 0; cookies.length > i; i += 1) {
                    cookie = cookies[i];
                    $popup.popmake('addCookie', cookie.event, cookie.settings);
                }
            }
        });

}(jQuery, document));