/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

{
	const formProvider = 'wsforms';
	const $ = window.jQuery;

	$( document ).on(
		'wsf-submit-success',
		function ( event, formObject, formId, formInstanceId, formEl ) {
			// All the magic happens here.
			window.PUM.integrations.formSubmission( $( formEl ), {
				formProvider,
				formId,
				formInstanceId,
			} );
		}
	);
}
