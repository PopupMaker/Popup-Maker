var PUMColorPickers;
(function ($) {
    "use strict";
    PUMColorPickers = {
        init: function () {
            $('.color-picker').wpColorPicker({
                change: function (e) {
                    var $input = $(e.currentTarget);
                    if ($input.hasClass('background-color')) {
                        $input.parents('table').find('.background-opacity').show();
                    }
                    PUMUtils.throttle(function () {
                        PopMakeAdmin.update_theme();
                    }, 50);
                },
                clear: function (e) {
                    var $input = $(e.currentTarget).prev();
                    if ($input.hasClass('background-color')) {
                        $input.parents('table').find('.background-opacity').hide();
                    }
                    PopMakeAdmin.update_theme();
                }
            });

        }
    };

    $(document).on('pum_init', PUMColorPickers.init);
}(jQuery));