/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

{
	const formProvider = 'ninjaforms';
	const $ = window.jQuery;
	let pumNFController = false;

	const initialize_nf_support = () => {
		/** Ninja Forms Support */
		if ( typeof Marionette !== 'undefined' && typeof nfRadio !== 'undefined' && false === pumNFController ) {
			pumNFController = Marionette.Object.extend( {
				initialize: function () {
					this.listenTo( nfRadio.channel( 'forms' ), 'submit:response', this.popupMaker );
				},
				popupMaker: function ( response, textStatus, jqXHR, formIdentifier ) {
					const $form = $( '#nf-form-' + formIdentifier + '-cont' ),
						[ formId, formInstanceId = null ] = formIdentifier.split( '_' ),
						settings = {};

					// Bail if submission failed.
					if ( response.errors && response.errors.length ) {
						return;
					}

					// All the magic happens here.
					window.PUM.integrations.formSubmission( $form, {
						formProvider,
						formId,
						formInstanceId,
						extras: {
							response,
						},
					} );

					/**
					 * TODO - Move this to a backward compatiblilty file, hook it into the pum.integration.form.success action.
					 *
					 * Listen for older popup actions applied directly to the form.
					 *
					 * This is here for backward compatibility with form actions prior to v1.9.
					 */
					if (response.data && response.data.actions) {
						settings.openpopup = 'undefined' !== typeof response.data.actions.openpopup;
						settings.openpopup_id = settings.openpopup ? parseInt( response.data.actions.openpopup ) : 0;
						settings.closepopup = 'undefined' !== typeof response.data.actions.closepopup;
						settings.closedelay = settings.closepopup ? parseInt( response.data.actions.closepopup ) : 0;
						if ( settings.closepopup && response.data.actions.closedelay ) {
							settings.closedelay = parseInt( response.data.actions.closedelay );
						}
					}

					// Nothing should happen if older action settings not applied
					// except triggering of pumFormSuccess event for old cookie method.
					window.PUM.forms.success( $form, settings );
				},
			} );

			// Initialize it.
			new pumNFController();
		}
	};

	// Initiate when ready.
	$( initialize_nf_support );
}
