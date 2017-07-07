/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/
(function ($) {
    "use strict";

    $.fn.popmake.cookies = $.fn.popmake.cookies || {};

    $.extend($.fn.popmake.cookies, {
        cf7_form_success: function (settings) {
            var $popup = PUM.getPopup(this);
            $popup.on('pum_cf7.success', function () {
                $popup.popmake('setCookie', settings);
            });
        }
    });

    $(document).on('wpcf7:mailsent', '.wpcf7', function (event) {
        var $form     = $(event.target),
            $settings = $form.find('meta[name="wpcf7-pum"]'),
            settings  = $settings.length ? JSON.parse($settings.attr('content')) : false,
            $popup    = $form.parents('.pum');

        if (!settings) {
            return;
        }

        settings = $.extend({
            openpopup: false,
            openpopup_id: 0,
            closepopup: false,
            closedelay: 0
        }, settings);

        if ($popup.length) {
            $popup.trigger('pum_cf7.success');

        }

        if ($popup.length && settings.closepopup) {
            setTimeout(function () {
                $popup.popmake('close');

                // Trigger another if set up.
                if (settings.openpopup && PUM.getPopup(settings.openpopup_id).length) {
                    PUM.open(settings.openpopup_id);
                }
            }, parseInt(settings.closedelay));
        } else if (settings.openpopup) {
            $popup = PUM.getPopup(settings.openpopup_id);

            if ($popup.length) {
                $popup.popmake('open');
            }
        }

    });
}(jQuery));