var PUMColorPickers;
(function ($, document, undefined) {
    "use strict";
    PUMColorPickers = {
        init: function () {
            $('.color-picker').filter(':not(.initialized)')
                .addClass('initialized')
                .wpColorPicker({
                    change: function (e) {
                        var $input = $(e.currentTarget);
                        if ($input.hasClass('background-color')) {
                            $input.parents('table').find('.background-opacity').show();
                        }

                        $(this).trigger('change.update');

                        PopMakeAdmin.update_theme();
                    },
                    clear: function (e) {
                        var $input = $(e.currentTarget).prev();
                        if ($input.hasClass('background-color')) {
                            $input.parents('table').find('.background-opacity').hide();
                        }

                        $(this).prev('input').trigger('change.clear').wpColorPicker('close');

                        PopMakeAdmin.update_theme();
                    }
                });
        }
    };

    $(document)
        .on('click', '.iris-palette', function () {
            console.log(this);
            $(this).parents('.wp-picker-active').find('input.color-picker').trigger('change');
            setTimeout(PopMakeAdmin.update_theme, 500);
        })
        .on('pum_init', PUMColorPickers.init);
}(jQuery, document));