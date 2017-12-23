/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/
(function ($) {
    "use strict";

    window.PUM_Admin = window.PUM_Admin || {};

    window.pum_popup_settings_editor = window.pum_popup_settings_editor || {
        form_args: {},
        current_values: {}
    };

    $(document)
        .ready(function () {
            $(this).trigger('pum_init');

            $('#title').prop('required', true);

            var $container = $('#pum-popup-settings-container'),
                args = pum_popup_settings_editor.form_args || {},
                values = pum_popup_settings_editor.current_values || {};

            if ($container.length) {
                PUM_Admin.forms.render(args, values, $container);
            }

            // TODO Can't figure out why this is needed, but it looks stupid otherwise when the first condition field defaults to something other than the placeholder.
            $('#pum-first-condition, #pum-first-trigger, #pum-first-cookie')
                .val(null)
                .trigger('change');
        })
        .on('keydown', '#popup-title', function (event) {
            var keyCode = event.keyCode || event.which;
            if (9 === keyCode) {
                event.preventDefault();
                $('#title').focus();
            }
        })
        .on('keydown', '#title, #popup-title', function (event) {
            var keyCode = event.keyCode || event.which,
                target;
            if (!event.shiftKey && 9 === keyCode) {
                event.preventDefault();
                target = $(this).attr('id') === 'title' ? '#popup-title' : '#insert-media-button';
                $(target).focus();
            }
        })
        .on('keydown', '#popup-title, #insert-media-button', function (event) {
            var keyCode = event.keyCode || event.which,
                target;
            if (event.shiftKey && 9 === keyCode) {
                event.preventDefault();
                target = $(this).attr('id') === 'popup-title' ? '#title' : '#popup-title';
                $(target).focus();
            }
        });
}(jQuery));