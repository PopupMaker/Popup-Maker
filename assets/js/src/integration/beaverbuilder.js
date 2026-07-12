/*******************************************************************************
 * Copyright (c) 2024, WP Popup Maker
 ******************************************************************************/

{
	const formProvider = 'beaverbuilder';
	const $ = window.jQuery;

	// Hook into jQuery AJAX complete for all Beaver Builder forms.
	$( document ).on( 'ajaxComplete', function ( _event, xhr, settings ) {
		const requestData = settings.data;
		let params;

		if (
			typeof requestData === 'string' ||
			requestData instanceof URLSearchParams
		) {
			params = new URLSearchParams( requestData );
		} else if (
			window.FormData &&
			requestData instanceof window.FormData
		) {
			params = new URLSearchParams( requestData );
		} else {
			return;
		}

		const action = params.get( 'action' );

		// Check if this is a Beaver Builder form submission.
		if (
			action !== 'fl_builder_email' &&
			action !== 'fl_builder_subscribe_form_submit'
		) {
			return;
		}

		let response;
		try {
			response =
				typeof xhr.responseJSON !== 'undefined'
					? xhr.responseJSON
					: JSON.parse( xhr.responseText );
		} catch ( e ) {
			return; // Not JSON response.
		}

		// Check for success (both forms use response.data).
		const data = response.data || {};

		// Contact form: data.error === false
		// Subscribe form: !data.error
		if ( data.error !== false && data.error ) {
			return; // Form had errors.
		}

		// Extract form type and node ID from AJAX data.
		const nodeId = params.get( 'node_id' );

		if ( ! nodeId ) {
			return;
		}

		// Find the form element.
		const $module = $( '.fl-node-' + nodeId );
		const $form = $module
			.find( '.fl-contact-form, .fl-subscribe-form' )
			.first();

		if ( ! $form.length ) {
			return;
		}

		// Determine form type from action.
		let formType = 'unknown';
		if ( action === 'fl_builder_email' ) {
			formType = 'contact';
		} else if ( action === 'fl_builder_subscribe_form_submit' ) {
			formType = 'subscribe';
		}

		const formId = formType + '_' + nodeId;
		const formInstanceId = formType + '_' + nodeId;

		window.PUM.integrations.formSubmission( $form, {
			formProvider,
			formId,
			formInstanceId,
		} );
	} );

	// Login Form - Note: Login forms redirect, so conversion tracked before redirect.
	// No specific event handler needed as redirect happens immediately.
}
