/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

{
	const formProvider = 'calderaforms';
	const $ = window.jQuery;

	$(document)
		.on('cf.form.submit', function (event, data) {
			//data.$form is a jQuery object for the form that just submitted.
			const $form = data.$form;
			//get the form that is submiting's ID attribute
			const [ formId, formInstanceId ] = $form.attr('id').split('_');

			// All the magic happens here.
			window.PUM.integrations.formSubmission($form[0], {
				formProvider,
				formID: formId,
				formInstanceId,
				extras: {
					state: window.cfstate.hasOwnProperty( formId ) ? window.cfstate[formId] : null
				}
			});
		});
}
