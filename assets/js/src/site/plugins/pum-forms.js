/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/
(function ($) {
    "use strict";

    var defaults = {
        openpopup: false,
        openpopup_id: 0,
        closepopup: false,
        closedelay: 0
    };

    window.PUM = window.PUM || {};
    window.PUM.forms = window.PUM.forms || {};

    window.PUM.forms.success = function ($form, settings) {
        settings = $.extend({}, defaults, settings);

        if (!settings) {
            return;
        }

        var $parentPopup  = $form.parents('.pum'),
            thankYouPopup = function () {
                if (settings.openpopup && PUM.getPopup(settings.openpopup_id).length) {
                    PUM.open(settings.openpopup_id);
                }
            };

        if ($parentPopup.length) {
            $parentPopup.trigger('pumFormSuccess');
        }

        if ($parentPopup.length && settings.closepopup) {
            setTimeout(function () {
                $parentPopup.popmake('close', thankYouPopup);
            }, parseInt(settings.closedelay));
        } else {
            thankYouPopup();
        }
    };

}(jQuery));