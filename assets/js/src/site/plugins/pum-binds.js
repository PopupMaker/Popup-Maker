/**
 * Defines the core $.popmake binds.
 * Version 1.4
 */
(function ($, document, undefined) {
    "use strict";

    $(document)
    // Backward Compatibility
    // TODO: Add check for compatibility mode once available.
        .on('pumInit', '.pum', function () {
            $(this).popmake('getContainer').trigger('popmakeInit');
        })


        /**
         * Fires the deprecated popmakeBeforeOpen event
         */
        .on('pumBeforeOpen', '.pum', function () {
            $(this).popmake('getContainer')
                .addClass('active')
                .trigger('popmakeBeforeOpen');
        })
        /**
         * Fires the deprecated popmakeAfterOpen event
         */
        .on('pumAfterOpen', '.pum', function () {
            $(this).popmake('getContainer').trigger('popmakeAfterOpen');
        })


        /**
         * Fires the deprecated popmakeBeforeClose event
         */
        .on('pumBeforeClose', '.pum', function () {
            $(this).popmake('getContainer').trigger('popmakeBeforeClose');
        })
        /**
         * Fires the deprecated popmakeAfterClose event
         */
        .on('pumAfterClose', '.pum', function () {
            $(this).popmake('getContainer')
                .removeClass('active')
                .trigger('popmakeAfterClose');
        })


        /**
         * Fires the deprecated popmakeSetupClose event
         */
        .on('pumSetupClose', '.pum', function () {
            $(this).popmake('getContainer').trigger('popmakeSetupClose');
        })


        /**
         * Removes the prevent open classes if they exist.
         */
        .on('pumOpenPrevented', '.pum', function () {
            $(this).popmake('getContainer')
                .removeClass('preventOpen')
                .removeClass('active');
        })
        /**
         * Removes the prevent close classes if they exist.
         */
        .on('pumClosePrevented', '.pum', function () {
            $(this).popmake('getContainer')
                .removeClass('preventClose');
        })


        /**
         * Fires the deprecated popmakeBeforeReposition event
         */
        .on('pumBeforeReposition', '.pum', function () {
            $(this).popmake('getContainer').trigger('popmakeBeforeReposition');
        });


}(jQuery, document));