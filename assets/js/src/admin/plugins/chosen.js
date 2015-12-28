var PUMChosenFields;
(function ($) {
    "use strict";
    PUMChosenFields = {
        init: function () {
            $('.pum-chosen select').filter(':not(.initialized)')
                .addClass('initialized')
                .chosen({
                    allow_single_deselect: true,
                    width: '100%'
                })
                .next()
                .css({});
        }
    };

    $(document).on('pum_init', PUMChosenFields.init);
}(jQuery));