/*******************************************************************************
 * Copyright (c) 2024, WP Popup Maker
 ******************************************************************************/
{
	const formProvider = 'happyforms';
	const $ = window.jQuery;

	$( document ).on(
		'happyforms.submitted',
		function ( event, response ) {
			// Only process successful submissions.
			if ( ! response || ! response.success || ! response.data ) {
				return;
			}

			// Extract form element from event target.
			const $form = $( event.target );

			if ( ! $form.length ) {
				return;
			}

			// Extract form ID from hidden input.
			const formId = $form.find( '[name="happyforms_form_id"]' ).val();

			// Generate instance ID from form element index (for multiple instances of same form).
			const $sameIdForms = $( 'form.happyforms-form' ).filter( function () {
				return $( this ).find( '[name="happyforms_form_id"]' ).val() === formId;
			} );
			const formInstanceId = $sameIdForms.index( $form ) + 1;

			// All the magic happens here.
			window.PUM.integrations.formSubmission( $form, {
				formProvider,
				formId,
				formInstanceId,
			} );
		}
	);
}
