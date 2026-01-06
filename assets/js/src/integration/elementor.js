/*******************************************************************************
 * Copyright (c) 2024, WP Popup Maker
 ******************************************************************************/
{
	const formProvider = 'elementor';
	const $ = window.jQuery;

	// Elementor Forms success event.
	$( document ).on( 'submit_success', '.elementor-form', function ( event, response ) {
		const $form = $( this )[ 0 ];
		const formId = $form.getAttribute( 'data-form_name' ) || $form.getAttribute( 'data-settings' )?.form_name || 'unknown';

		window.PUM.integrations.formSubmission( $form, {
			formProvider,
			formId,
		} );
	} );
}
