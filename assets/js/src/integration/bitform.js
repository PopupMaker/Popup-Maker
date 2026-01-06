/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

{
	const formProvider = 'bitform';
	const $ = window.jQuery;

	// Bit Form uses custom events.
	$( document ).on( 'bitforms_ajax_success', function ( event, response, formId ) {
		if ( response.success && response.data ) {
			const $form = $( `#bitforms-${ formId }` )[ 0 ];

			if ( $form ) {
				window.PUM.integrations.formSubmission( $form, {
					formProvider,
					formId: formId.toString(),
				} );
			}
		}
	} );
}
