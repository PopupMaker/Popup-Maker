/***************************************
 * Copyright (c) 2020, Popup Maker
 ***************************************/

{
	const formProvider = "gravityforms";
	const $ = window.jQuery;
	const gFormSettings = {};

	$(document)
		.on("gform_confirmation_loaded", function(event, formId) {
			const $form = $(
				"#gform_confirmation_wrapper_" +
					formId +
					",#gforms_confirmation_message_" +
					formId
			)[0];

			// All the magic happens here.
			window.PUM.integrations.formSubmission($form, {
				formProvider,
				formId
			});

			/**
			 * TODO - Move this to a backward compatiblilty file, hook it into the pum.integration.form.success action.
			 *
			 * Listen for older popup actions applied directly to the form.
			 *
			 * This is here for backward compatibility with form actions prior to v1.9.
			 */
			// Nothing should happen if older action settings not applied
			// except triggering of pumFormSuccess event for old cookie method.
			window.PUM.forms.success($form, gFormSettings[formId] || {});
		});


		/**
		 * TODO - Move this to a backward compatiblilty file, hook it into the pum.integration.form.success action.
		 *
		 * Listen for older popup actions applied directly to the form.
		 *
		 * This is here for backward compatibility with form actions prior to v1.9.
		 */
		$(function() {
			$(".gform_wrapper > form").each(function() {
				const $form = $(this),
					formId = $form.attr("id").replace("gform_", ""),
					$settings = $form.find("input.gforms-pum"),
					settings = $settings.length
						? JSON.parse($settings.val())
						: false;

				if (!settings || typeof settings !== "object") {
					return;
				}

				if (
					typeof settings === "object" &&
					settings.closedelay !== undefined &&
					settings.closedelay.toString().length >= 3
				) {
					settings.closedelay = settings.closedelay / 1000;
				}

				gFormSettings[formId] = settings;
			});
		});
}
