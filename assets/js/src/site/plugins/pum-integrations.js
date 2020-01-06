/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/
(function ($) {
    "use strict";

	window.PUM = window.PUM || {};
	window.PUM.integrations = window.PUM.integrations || {};

	$.extend(window.PUM.integrations, {
		formSubmission: function (form, args) {
			var $popup = PUM.getPopup(form);

			args = $.extend({
				popup: $popup,
				formProvider: null,
				formID: null,
				formKey: null
			}, args);

			if ($popup.length) {
				// Should this be here. It is the only thing not replicated by a new form trigger & cookie.
				// $popup.trigger('pumFormSuccess');
			}

			window.PUM.hooks.doAction('pum.integration.form.success', form, args );
		}
	});

    $(document)
        /** Contact Form 7 Support */
        .on('wpcf7:mailsent', '.wpcf7', function (event) {
            var $form = $(event.target),
                $settings = $form.find('input.wpcf7-pum'),
                settings = $settings.length ? JSON.parse($settings.val()) : false;

            if (typeof settings === 'object' && settings.closedelay !== undefined && settings.closedelay.toString().length >= 3) {
                settings['closedelay'] = settings.closedelay / 1000;
            }

            window.PUM.forms.success($form, settings);
        });

}(jQuery));
