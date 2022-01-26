/**
 * Initialize Popup Maker.
 * Version 1.8
 */
(function ($, document, undefined) {
    "use strict";
    // Defines the current version.
    $.fn.popmake.version = 1.8;

    // Stores the last open popup.
    $.fn.popmake.last_open_popup = null;

	// Here for backward compatibility.
	window.ajaxurl = window.pum_vars.ajaxurl;

    window.PUM.init = function () {
        console.log('init popups ✔');
        $(document).trigger('pumBeforeInit');
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

        // Initiate integrations.
        PUM.integrations.init();
    };

	// Initiate when ready.
    $(function () {
        // TODO can this be moved outside doc.ready since we are awaiting our own promises first?
        var initHandler = PUM.hooks.applyFilters('pum.initHandler', PUM.init);
        var initPromises = PUM.hooks.applyFilters('pum.initPromises', []);

        Promise.all(initPromises).then(initHandler);
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
            $forms.append('<input type="hidden" name="pum_form_popup_id" value="' + popupID + '" />');
        }
    })
    .on( 'pumAfterClose', window.PUM.actions.stopIframeVideosPlaying );


}(jQuery));
