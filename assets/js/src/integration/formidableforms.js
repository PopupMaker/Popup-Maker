/***********************************
 * Copyright (c) 2020, Popup Maker
 **********************************/

{
	const formProvider = 'formidableforms';
	const $ = window.jQuery;

	$( document ).on( 'frmFormComplete', function ( event, form, response ) {
		const $form = $( form );

		// Formidable Pro renders draft saves as a completion message too. Its
		// native request marker remains on the submitted form when this event runs.
		if ( '1' === $form.find( 'input[name="frm_saving_draft"]' ).val() ) {
			return;
		}

		const formId = $form.find( 'input[name="form_id"]' ).val();
		const $popup = window.PUM.getPopup(
			$form.find( 'input[name="pum_form_popup_id"]' ).val()
		);

		// All the magic happens here.
		window.PUM.integrations.formSubmission( $form, {
			popup: $popup,
			formProvider,
			formId,
			extras: {
				response,
			},
		} );
	} );
}
