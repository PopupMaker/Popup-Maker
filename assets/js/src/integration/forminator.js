/*******************************************************************************
 * Copyright (c) 2025, WP Popup Maker
 ******************************************************************************/
{
	const formProvider = 'forminator';
	const $ = window.jQuery;

	// Listen for Forminator's success event.
	$( document ).on(
		'forminator:form:submit:success',
		function ( event, formData ) {
			// The form element that triggered the event.
			const $form = $( event.target );

			// Extract form ID from data-form-id attribute.
			const formId = $form.attr( 'data-form-id' );

			// Extract form instance ID from data-render-id attribute.
			const formInstanceId = $form.attr( 'data-render-id' );

			// All the magic happens here.
			window.PUM.integrations.formSubmission( $form, {
				formProvider,
				formId,
				formInstanceId,
				extras: {
					formData,
				},
			} );
		}
	);
}
