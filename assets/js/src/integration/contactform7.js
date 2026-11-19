/**************************************
 * Copyright (c) 2020, Popup Maker
 *************************************/

{
	const formProvider = 'contactform7';
	const $ = window.jQuery;

	$( document ).on( 'wpcf7mailsent', function ( event, details ) {
		const formId = event.detail.contactFormId,
			$form = $( event.target ),
			identifier = event.detail.id || event.detail.unitTag,
			// Converts string like wpcf7-f190-p2-o11 and reduces it to simply 11, the last o11 is the instance ID.
			// More accurate way of doing it in case things change in the future, this version filters out all but the o param.
			// formInstanceId = .split('-').filter((string) => string.indexOf('o') === 0)[0].replace('o','');
			// Simpler version that simply splits and pops the last item in the array. This requires it always be the last.
			formInstanceId = identifier.split( '-' ).pop().replace( 'o', '' );

		// All the magic happens here.
		window.PUM.integrations.formSubmission( $form, {
			formProvider,
			formId,
			formInstanceId,
			extras: {
				details,
			},
		} );

		// Nothing else to apply, just trigger the pumFormSuccess event for the old cookie method.
		window.PUM.forms.success( $form, false );
	} );
}
