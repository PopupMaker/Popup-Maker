(function ($, document, undefined) {
    "use strict";

    var setCookie = function (settings) {
        $.pm_cookie(
            settings.name,
            true,
            settings.session ? null : settings.time,
            settings.path ? pum_vars.home_url || '/' : null
        );
        pum.hooks.doAction('popmake.setCookie', settings);
    };

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
        setCookie: setCookie,
        checkCookies: function (settings) {
            var i,
                ret = false;

            if (settings.cookie_name === undefined || settings.cookie_name === null || settings.cookie_name === '') {
                return false;
            }

            switch (typeof settings.cookie_name) {
            case 'object':
            case 'array':
                for (i = 0; settings.cookie_name.length > i; i += 1) {
                    if ($.pm_cookie(settings.cookie_name[i]) !== undefined) {
                        ret = true;
                    }
                }
                break;
            case 'string':
                if ($.pm_cookie(settings.cookie_name) !== undefined) {
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
        form_submission: function ( settings ) {
            var $popup = PUM.getPopup( this );

            settings = $.extend( {
                form: '',
                formInstanceId: '',
                only_in_popup: false,
            }, settings );

            PUM.hooks.addAction( 'pum.integration.form.success', function ( form, args ) {
                if ( ! settings.form.length ) {
                    return;
                }

                if ( PUM.integrations.checkFormKeyMatches( settings.form, settings.formInstanceId, args ) ) {
                    if (
                        ( settings.only_in_popup && PUM.getPopup( form ).length && PUM.getPopup( form ).is( $popup ) ) ||
                        ! settings.only_in_popup
                    ) {
                        $popup.popmake( 'setCookie', settings );
                    }
                }
            } );
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
        pum_sub_form_success: function (settings) {
            var $popup = PUM.getPopup(this);
            $popup.find('form.pum-sub-form').on('success', function () {
                $popup.popmake('setCookie', settings);
            });
        },
        /**
         * @deprecated 1.7.0
         *
         * @param settings
         */
        pum_sub_form_already_subscribed: function (settings) {
            var $popup = PUM.getPopup(this);
            $popup.find('form.pum-sub-form').on('success', function () {
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
            var $popup = PUM.getPopup(this),
                settings = $popup.popmake('getSettings'),
                cookies = settings.cookies || [],
                cookie = null,
                i;

            if (cookies.length) {
                for (i = 0; cookies.length > i; i += 1) {
                    cookie = cookies[i];
                    $popup.popmake('addCookie', cookie.event, cookie.settings);
                }
            }
        });

}(jQuery, document));
