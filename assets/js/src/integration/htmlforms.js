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
				// Get form ID from data-id attribute.
				const formId =
					formElement.getAttribute( 'data-id' ) ||
					formElement.id?.replace( 'hf-form-', '' ) ||
					'unknown';

				// Generate instance ID from form element index (for multiple instances of same form).
				const $sameIdForms = $( '.hf-form' ).filter( function () {
					return (
						$( this ).attr( 'data-id' ) === formId ||
						$( this ).attr( 'id' )?.replace( 'hf-form-', '' ) ===
							formId
					);
				} );

				const formInstanceId = $sameIdForms.index( formElement ) + 1;

				// Trigger Popup Maker tracking.
				window.PUM.integrations.formSubmission( $( formElement ), {
					formProvider,
					formId,
					formInstanceId,
				} );
			} );
		}
	} );
}
