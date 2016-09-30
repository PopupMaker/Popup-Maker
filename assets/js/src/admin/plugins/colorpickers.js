var PUMColorPickers;
(function ($, document, undefined) {
    "use strict";
    PUMColorPickers = {
        init: function () {
            $('.color-picker').filter(':not(.initialized)')
                .addClass('initialized')
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

    $(document)
        .on('click', '.iris-palette', function () {
            $(this).parents('.wp-picker-active').find('input.color-picker').trigger('change');
            setTimeout(PopMakeAdmin.update_theme, 500);
        })
        .on('colorchange', function (event, ui) {
            var $input = $(event.target),
                $opacity = $input.parents('tr').next('tr.background-opacity'),
                color = '';

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

            if ($('form#post input#post_type').val() === 'popup_theme') {
                PopMakeAdmin.update_theme();
            }
        })
        .on('pum_init', PUMColorPickers.init);
}(jQuery, document));