/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

{
	const formProvider = 'wsforms';
	const $ = window.jQuery;

	$(document).on(
		'wsf-submit-success wsf-save-success',
		function (
			event,
			formObject,
			formId,
			formInstanceId,
			formEl,
			formCanvasEl
		) {
			// All the magic happens here.
			window.PUM.integrations.formSubmission($(formEl), {
				formProvider,
				formId,
				formInstanceId,
			});
		}
	);
}
