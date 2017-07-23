/*
 * @copyright   Copyright (c) 2017, Jungle Plugins
 * @author      Daniel Iser
 */
(function ($) {
    "use strict";

    var popup_settings = {
        autosave: function () {
            var values = $('#pum-popup-settings-container').serializeObject().popup_settings;

            wp.ajax.send("pum_popup_settings_autosave", {
                success: function (data) {
                },
                error: function (error) {
                    console.log(error);
                },
                data: {
                    nonce: pum_vars.nonce,
                    key: 'popup_settings',
                    value: values
                }
            });
        }
    };

    $(document)
        .ready(function () {
            var $container = $('#pum-popup-settings-container'),
                args       = pum_popup_settings_editor.form_args || {},
                values     = window.pum_popup_settings_editor.current_values || {};


            if ($container.length) {
                PUM_Admin.forms.render($container, args, values);
            }
        });

}(jQuery));