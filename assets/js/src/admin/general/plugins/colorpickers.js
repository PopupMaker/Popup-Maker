/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/
(function ($) {
    "use strict";

    var colorpicker = {
        init: function () {
            $('.pum-color-picker').filter(':not(.pum-color-picker-initialized)')
                .addClass('pum-color-picker-initialized')
                .wpColorPicker({
                    change: function (event, ui) {
                        $(event.target).trigger('colorchange', ui);
                    },
                    clear: function (event) {
                        $(event.target).prev().trigger('colorchange').wpColorPicker('close');
                    }
                });
        }
    };

    // Import this module.
    window.PUM_Admin = window.PUM_Admin || {};
    window.PUM_Admin.colorpicker = colorpicker;

    $(document)
        .on('click', '.iris-palette', function () {
            $(this).parents('.wp-picker-active').find('input.pum-color-picker').trigger('change');

            // TODO Remove this.
            setTimeout(PopMakeAdmin.update_theme, 500);
        })
        .on('colorchange', function (event, ui) {
            var $input   = $(event.target),
                $opacity = $input.parents('tr').next('tr.background-opacity'),
                color    = '';

            if (ui !== undefined && ui.color !== undefined) {
                color = ui.color.toString();
            }

            if ($input.hasClass('background-color')) {
                if (typeof color === 'string' && color.length) {
                    $opacity.show();
                } else {
                    $opacity.hide();
                }
            }

            $input.val(color);

            // TODO Remove this.
            if ($('form#post input#post_type').val() === 'popup_theme') {
                PopMakeAdmin.update_theme();
            }
        })
        .on('pum_init', colorpicker.init);
}(jQuery));