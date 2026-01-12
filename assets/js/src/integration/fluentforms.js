/*******************************************************************************
 * Copyright (c) 2024, WP Popup Maker
 ******************************************************************************/
{
	const formProvider = 'fluentforms';
	const $ = window.jQuery;

	$( document ).on(
		'fluentform_submission_success',
		function ( event, formDetails ) {
			// Extract necessary form details from the event.
			const formEl = formDetails.form; // The form element
			const formConfig = formDetails.config; // The form configuration (contains formId, etc.)
			const formId = formConfig.id;
			const formInstanceId = formEl.data( 'form_instance' );

			// All the magic happens here.
			window.PUM.integrations.formSubmission( $( formEl ), {
				formProvider,
				formId,
				formInstanceId,
			} );
		}
	);
}
