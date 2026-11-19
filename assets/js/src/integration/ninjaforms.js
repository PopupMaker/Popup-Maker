/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

{
	const formProvider = 'ninjaforms';
	const $ = window.jQuery;
	let pumNFController = false;

	const initialize_nf_support = () => {
		/** Ninja Forms Support */
		if (
			typeof Marionette !== 'undefined' &&
			typeof nfRadio !== 'undefined' &&
			false === pumNFController
		) {
			pumNFController = Marionette.Object.extend( {
				initialize: function () {
					this.listenTo(
						nfRadio.channel( 'forms' ),
						'submit:response',
						this.popupMaker
					);
				},
				popupMaker: function (
					response,
					textStatus,
					jqXHR,
					formIdentifier
				) {
					// Bail if submission failed.
					if ( response.errors && response.errors.length ) {
						return;
					}

					const $form = $( '#nf-form-' + formIdentifier + '-cont' );
					const [ formId, formInstanceId = null ] =
						formIdentifier.split( '_' );

					// All the magic happens here.
					window.PUM.integrations.formSubmission( $form, {
						formProvider,
						formId,
						formInstanceId,
						extras: {
							response,
						},
					} );

					// Nothing else to apply, just trigger the pumFormSuccess event for the old cookie method.
					window.PUM.forms.success( $form, false );
				},
			} );

			// Initialize it.
			new pumNFController();
		}
	};

	// Initiate when ready.
	$( initialize_nf_support );
}
