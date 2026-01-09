/*******************************************************************************
 * Copyright (c) 2024, WP Popup Maker
 ******************************************************************************/
{
	const formProvider = 'elementor';
	const $ = window.jQuery;

	// Elementor Forms success event.
	$( document ).on(
		'submit_success',
		'.elementor-form',
		function ( event, response ) {
			const $form = $( this )[ 0 ];

			// Get form_id from hidden input field.
			const formIdInput = $form.querySelector( 'input[name="form_id"]' );
			const formId = formIdInput
				? formIdInput.value
				: $form.getAttribute( 'name' ) || 'unknown';

			window.PUM.integrations.formSubmission( $form, {
				formProvider,
				formId,
			} );
		}
	);
}
