/*******************************************************************************
 * Copyright (c) 2024, WP Popup Maker
 ******************************************************************************/

{
	const formProvider = 'beaverbuilder';
	const $ = window.jQuery;

	// Contact Form success event.
	$( document ).on( 'fl_module_contact_form_ajax_complete', function ( event, form, response ) {
		if ( response.message_type === 'success' ) {
			const $form = $( form )[ 0 ];
			const formId = $form.getAttribute( 'data-node' ) || 'contact';

			window.PUM.integrations.formSubmission( $form, {
				formProvider,
				formId: 'contact_' + formId,
			} );
		}
	} );

	// Subscribe Form success.
	$( document ).on( 'fl_builder_subscribe_form_submit', function ( event, response ) {
		if ( response.error === false ) {
			const $form = $( event.target ).closest( 'form' )[ 0 ];
			const formId = $form.getAttribute( 'data-node' ) || 'subscribe';

			window.PUM.integrations.formSubmission( $form, {
				formProvider,
				formId: 'subscribe_' + formId,
			} );
		}
	} );

	// Login Form - Note: Login forms redirect, so conversion tracked before redirect.
	// No specific event handler needed as redirect happens immediately.
}
