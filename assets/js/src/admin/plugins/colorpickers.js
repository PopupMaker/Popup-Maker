var PUMColorPickers;
(function ($, document, undefined) {
    "use strict";
    PUMColorPickers = {
        init: function () {
            $('.color-picker').filter(':not(.initialized)')
                .addClass('initialized')
                .wpColorPicker({
                    change: function (e) {
                        var $this = $(this),
                            $input = $(e.currentTarget);
                        if ($input.hasClass('background-color')) {
                            $input.parents('table').find('.background-opacity').show();
                        }

                        $this.trigger('change.update');

                        if ($('form#post input#post_type').val() === 'popup_theme') {
                            PopMakeAdmin.update_theme();
                        }
                    },
                    clear: function (e) {
                        var $input = $(e.currentTarget).prev();
                        if ($input.hasClass('background-color')) {
                            $input.parents('table').find('.background-opacity').hide();
                        }

                        $(this).prev('input').trigger('change.clear').wpColorPicker('close');

                        if ($('form#post input#post_type').val() === 'popup_theme') {
                            PopMakeAdmin.update_theme();
                        }
                    }
                });
        }
    };

    $(document)
        .on('click', '.iris-palette', function () {
            $(this).parents('.wp-picker-active').find('input.color-picker').trigger('change');
            setTimeout(PopMakeAdmin.update_theme, 500);
        })
        .on('pum_init', PUMColorPickers.init);
}(jQuery, document));