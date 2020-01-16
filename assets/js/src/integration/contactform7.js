/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

{
	const formProvider = 'contactform7';
	const $ = window.jQuery;

	$(document).on('wpcf7:mailsent', '.wpcf7', function (event, details) {
		const $form = $(this),
			formId = $form.find('input[name="_wpcf7"]').val(),
			// Converts string like wpcf7-f190-p2-o11 and reduces it to simply 11, the last o11 is the instance ID.
			// More accurate way of doing it in case things change in the future, this version filters out all but the o param.
			// formInstanceId = .split('-').filter((string) => string.indexOf('o') === 0)[0].replace('o','');
			// Simpler version that simply splits and pops the last item in the array. This requires it always be the last.
			formInstanceId = $form.attr('id').split('-').pop().replace('o', '');

		// All the magic happens here.
		window.PUM.integrations.formSubmission($form, {
			formProvider,
			formId,
			formInstanceId,
			extras: {
				details
			}
		});

		/**
		 * TODO - Move this to a backward compatiblilty file, hook it into the pum.integration.form.success action.
		 *
		 * Listen for older popup actions applied directly to the form.
		 *
		 * This is here for backward compatibility with form actions prior to v1.9.
		 */
		const $settings = $form.find('input.wpcf7-pum'),
			settings = $settings.length ? JSON.parse($settings.val()) : false;

		if (typeof settings === 'object' && settings.closedelay !== undefined && settings.closedelay.toString().length >= 3) {
			settings['closedelay'] = settings.closedelay / 1000;
		}

		// Nothing should happen if older action settings not applied
		// except triggering of pumFormSuccess event for old cookie method.
		window.PUM.forms.success($form, settings);
	});
}
