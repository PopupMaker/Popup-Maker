/***************************************
 * HTML Forms Integration
 * Copyright (c) 2024, Popup Maker
 ***************************************/

{
	const formProvider = 'htmlforms';
	const $ = window.jQuery;

	/**
	 * Listen for HTML Forms success event
	 *
	 * Uses global html_forms.on('success') event that fires after successful submission
	 * Event fires with formElement as parameter
	 */
	$( () => {
		if ( typeof window.html_forms !== 'undefined' ) {
			window.html_forms.on( 'success', function ( formElement ) {
				// Get form ID from data-id attribute
				const formId =
					formElement.getAttribute( 'data-id' ) ||
					formElement.id?.replace( 'hf-form-', '' ) ||
					'unknown';

				// Trigger Popup Maker tracking
				window.PUM.integrations.formSubmission( formElement, {
					formProvider,
					formId,
				} );
			} );
		}
	} );
}
