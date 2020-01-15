/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

{
	const formProvider = 'wpforms';
	const $ = window.jQuery;

	$(document).on('wpformsAjaxSubmitSuccess', '.wpforms-ajax-form', function (event, details) {
		const $form = $(this),
			formId = $form.data('formid' ),
			formInstanceId = $('form#'+$form.attr('id')).index($form) + 1;

		// All the magic happens here.
		window.PUM.integrations.formSubmission($form, {
			formProvider,
			formId,
			formInstanceId
		});
	});
}
