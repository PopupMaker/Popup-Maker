/*******************************************************************************
 * Copyright (c) 2024, WP Popup Maker
 ******************************************************************************/
{
	const formProvider = 'newsletter';
	const $ = window.jQuery;

	// Newsletter triggers custom event on AJAX subscription success.
	$( document ).on( 'tnp-subscription', '.tnp-subscription', function ( event, data ) {
		if ( data && data.status === 'subscribed' ) {
			const $form = $( this );

			window.PUM.integrations.formSubmission( $form, {
				formProvider,
				formId: null,
				formInstanceId: null,
			} );
		}
	} );
}

