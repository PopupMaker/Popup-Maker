/*******************************************************************************
 * Copyright (c) 2024, WP Popup Maker
 ******************************************************************************/

{
	const formProvider = 'bricksbuilder';
	const $ = window.jQuery;

	$( document ).on( 'bricks/form/success', function ( event ) {
		// Extract form details from Bricks event
		const elementId = event.detail.elementId;
		const formData = event.detail.formData;

		// Find the form element using Bricks' element ID pattern
		const $form = $( '[data-element-id="' + elementId + '"]' );

		if ( ! $form.length ) {
			return;
		}

		// Generate form identifiers
		const formId = elementId; // Bricks uses unique element IDs
		const formInstanceId =
			$form.index(
				'[data-element-id^="' + elementId.replace( /\d+$/, '' ) + '"]'
			) + 1;

		// All the magic happens here.
		window.PUM.integrations.formSubmission( $form, {
			formProvider,
			formId,
			formInstanceId,
			extras: {
				formData: formData,
				elementId: elementId,
				bricksEvent: event.detail,
			},
		} );
	} );
}
