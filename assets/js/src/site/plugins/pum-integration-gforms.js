/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/
(function ($) {
    "use strict";

    $.fn.popmake.cookies = $.fn.popmake.cookies || {};

    $.extend($.fn.popmake.cookies, {
        gforms_form_success: function (settings) {
            var $popup = PUM.getPopup(this);
            $popup.on('pum_gforms.success', function () {
                $popup.popmake('setCookie', settings);
            });
        }
    });

    var defaults = {
        openpopup: false,
        openpopup_id: 0,
        closepopup: false,
        closedelay: 0
    },
    formSettings = {};

    $(document).ready(function () {
        $('.gform_wrapper > form').each(function () {
            var $form     = $(this),
                form_id   = $form.attr('id').replace('gform_', ''),
                $settings = $form.find('meta[name="gforms-pum"]'),
                settings  = $settings.length ? JSON.parse($settings.attr('content')) : false;

            if (!settings) {
                return;
            }

            formSettings[ form_id ] = $.extend({}, defaults, settings);
        });
    });

    $(document).on('gform_confirmation_loaded', function (event, form_id) {
        var $form = $('#gform_' + form_id),
            settings  = formSettings[ form_id ] || false,
            $parentPopup    = $form.parents('.pum'),
            thankYouPopup = function () {
                if (settings.openpopup && PUM.getPopup(settings.openpopup_id).length) {
                    PUM.open(settings.openpopup_id);
                }
            };

        if (!settings) {
            return;
        }

        if ($parentPopup.length) {
            $parentPopup.trigger('pum_gforms.success');
        }

        if ($parentPopup.length && settings.closepopup) {
            setTimeout(function () {
                $parentPopup.popmake('close', thankYouPopup);
            }, parseInt(settings.closedelay));
        } else if (settings.openpopup) {
            // Trigger another if set up.
            thankYouPopup();
        }

    });
}(jQuery));