/**
 * Initialize Popup Maker.
 * Version 1.7
 */
(function ($, document, undefined) {
    "use strict";
    // Defines the current version.
    $.fn.popmake.version = 1.7;

    // Stores the last open popup.
    $.fn.popmake.last_open_popup = null;

    $(document).ready(function () {
        $('.pum').popmake();
        $(document).trigger('pumInitialized');

        /**
         * Process php based form submissions when the form_success args are passed.
         */
        if (typeof pum_vars.form_success === 'object') {
            pum_vars.form_success = $.extend({
                popup_id: null,
                settings: {}
            });

            PUM.forms.success(pum_vars.form_success.popup_id, pum_vars.form_success.settings);
        }
    });

    /**
     * Add hidden field to all popup forms.
     */
    $('.pum').on('pumInit', function () {
        var $popup = PUM.getPopup(this),
            popupID = PUM.getSetting($popup, 'id'),
            $forms = $popup.find('form');

        /**
         * If there are forms in the popup add a hidden field for use in retriggering the popup on reload.
         */
        if ($forms.length) {
            $forms.prepend('<input type="hidden" name="pum_form_popup_id" value="' + popupID + '" />');
        }
    });


}(jQuery));