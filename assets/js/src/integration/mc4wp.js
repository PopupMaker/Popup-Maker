/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

{
	const formProvider = 'mc4wp';
	const $ = window.jQuery;

	$( document ).ready( () => {
		if ( mc4wp !== undefined ) {
			mc4wp.forms.on( 'success', function ( form, data ) {
				const $form = $( form.element ),
					formId = form.id,
					formInstanceId = $( '.mc4wp-form-' + form.id ).index( $form ) + 1;

				// All the magic happens here.
				window.PUM.integrations.formSubmission( $form, {
					formProvider,
					formId,
					formInstanceId,
					extras: {
						form,
						data,
					},
				} );
			} );
		}
	} );
}
