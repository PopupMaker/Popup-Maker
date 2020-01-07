/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

{
	const formProvider = 'ninjaforms';
	const $ = window.jQuery;
	let pumNFController = false;

	initialize_nf_support = () => {
		/** Ninja Forms Support */
		if (typeof Marionette !== 'undefined' && typeof nfRadio !== 'undefined' && false === pumNFController) {
			pumNFController = Marionette.Object.extend({
				initialize: function () {
					this.listenTo(nfRadio.channel('forms'), 'submit:response', this.popupMaker)
				},
				popupMaker: function (response, textStatus, jqXHR, formId) {
					const form = document.getElementById('#nf-form-' + formId + '-cont'),
						$form = $(form),
						settings = {};

					if (response.errors.length) {
						return;
					}

					window.PUM.integrations.formSubmission(form, {
						formProvider,
						formId,
						formKey: formProvider + '_' + formId,
						response: response,
					});

					// Listen for older popup actions applied directly to the form.
					if ('undefined' !== typeof response.data.actions) {
						settings.openpopup = 'undefined' !== typeof response.data.actions.openpopup;
						settings.openpopup_id = settings.openpopup ? parseInt(response.data.actions.openpopup) : 0;
						settings.closepopup = 'undefined' !== typeof response.data.actions.closepopup;
						settings.closedelay = settings.closepopup ? parseInt(response.data.actions.closepopup) : 0;
						if (settings.closepopup && response.data.actions.closedelay) {
							settings.closedelay = parseInt(response.data.actions.closedelay);
						}
					}

					// Nothing should happen if older action settings not applied
					// except triggering of pumFormSuccess event.
					window.PUM.forms.success($form, settings);
				}
			});

			// Initialize it.
			new pumNFController();
		}
	};

	$(document).ready(initialize_nf_support);

}
