/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

{
	const formProvider = 'contactform7';
	const $ = window.jQuery;

	$(document).on('wpcf7:mailsent', '.wpcf7', function (event) {
		const form = event.target,
			$form = $(form),
			formId = $(form).find('input[name="_wpcf7"]').val();

		// All the magic happens here.
		window.PUM.integrations.formSubmission(form, {
			formProvider,
			formId,
			formKey: formProvider + '_' + formId
		});

		/**
		 * Listen for older popup actions applied directly to the form.
		 *
		 * This is here for backward compatibility with form actions prior to v1.9.
		 */
		const $settings = $form.find('input.wpcf7-pum'),
			settings = $settings.length ? JSON.parse($settings.val()) : false;

		if (typeof settings === 'object' && settings.closedelay !== undefined && settings.closedelay.toString().length >= 3) {
			settings['closedelay'] = settings.closedelay / 1000;
		}

		window.PUM.forms.success($form, settings);
	});
}
