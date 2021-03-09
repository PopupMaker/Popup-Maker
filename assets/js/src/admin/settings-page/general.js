/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/
(function ($) {
    "use strict";

    window.PUM_Admin = window.PUM_Admin || {};

	// Initiate when ready.
    $(function () {
        var $container = $('#pum-settings-container'),
            args = pum_settings_editor.form_args || {},
            values = pum_settings_editor.current_values || {};

        if ($container.length) {
            $container.find('.pum-no-js').hide();
            PUM_Admin.forms.render(args, values, $container);
        }

    });
}(jQuery));
