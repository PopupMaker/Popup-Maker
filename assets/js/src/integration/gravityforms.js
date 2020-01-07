/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

{
	const formProvider = 'gravityforms';
	const $ = window.jQuery;
	const gFormSettings = {};


	$(document)
		.on('gform_confirmation_loaded', function (event, formId) {
			const form = $('#gform_confirmation_wrapper_' + formId + ',#gforms_confirmation_message_' + formId)[0];

			// All the magic happens here.
			window.PUM.integrations.formSubmission(form, {
				formProvider,
				formId,
				formKey: formProvider + '_' + formId
			});

			/**
			 * Listen for older popup actions applied directly to the form.
			 *
			 * @deprecated 1.9.0
			 */
			const settings = gFormSettings[formId] || {};
			window.PUM.forms.success(form, gFormSettings[formId] || {});
		})
		/**
		 * This is still needed for backward compatibility.
		 *
		 * @deprecated 1.9.0
		 */
		.ready(function () {
			$('.gform_wrapper > form').each(function () {
				const $form = $(this),
					formId = $form.attr('id').replace('gform_', ''),
					$settings = $form.find('input.gforms-pum'),
					settings = $settings.length ? JSON.parse($settings.val()) : false;

				if (!settings || typeof settings !== 'object') {
					return;
				}

				if (typeof settings === 'object' && settings.closedelay !== undefined && settings.closedelay.toString().length >= 3) {
					settings['closedelay'] = settings.closedelay / 1000;
				}

				gFormSettings[formId] = settings;
			});
		});
}
