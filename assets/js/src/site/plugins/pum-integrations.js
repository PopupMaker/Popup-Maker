/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/
(function ($) {
	"use strict";

	window.PUM = window.PUM || {};
	window.PUM.integrations = window.PUM.integrations || {};

	function filterNull(x) {
		return x;
	}

	$.extend(window.PUM.integrations, {
		init: function () {
			if ("undefined" !== typeof pum_vars.form_submission) {
				var submission = pum_vars.form_submission;

				// Declare these are not AJAX submissions.
				submission.ajax = false;

				// Initialize the popup var based on passed popup ID.
				submission.popup = submission.popupId > 0 ? PUM.getPopup(submission.popupId) : null;

				PUM.integrations.formSubmission(null, submission);
			}
		},
		/**
		 * This hook fires after any integrated form is submitted successfully.
		 *
		 * It does not matter if the form is in a popup or not.
		 *
		 * @since 1.9.0
		 *
		 * @param {Object} form JavaScript DOM node or jQuery object for the form submitted
		 * @param {Object} args {
		 *     @type {string} formProvider Such as gravityforms or ninjaforms
		 *     @type {string|int} formId Usually an integer ID number such as 1
		 *     @type {int} formInstanceId Not all form plugins support this.
		 * }
		 */
		formSubmission: function (form, args) {
			args = $.extend({
				popup: PUM.getPopup(form),
				formProvider: null,
				formId: null,
				formInstanceId: null,
				formKey: null,
				ajax: true, // Allows detecting submissions that may have already been counted.
				tracked: false
			}, args);

			// Generate unique formKey identifier.
			args.formKey = args.formKey || [args.formProvider, args.formId, args.formInstanceId].filter(filterNull).join('_');

			if (args.popup && args.popup.length) {
				args.popupId = PUM.getSetting(args.popup, 'id');
				// Should this be here. It is the only thing not replicated by a new form trigger & cookie.
				// $popup.trigger('pumFormSuccess');
			}

			/**
			 * This hook fires after any integrated form is submitted successfully.
			 *
			 * It does not matter if the form is in a popup or not.
			 *
			 * @since 1.9.0
			 *
			 * @param {Object} form JavaScript DOM node or jQuery object for the form submitted
			 * @param {Object} args {
			 *     @type {string} formProvider Such as gravityforms or ninjaforms
			 *     @type {string|int} formId Usually an integer ID number such as 1
			 *     @type {int} formInstanceId Not all form plugins support this.
			 *     @type {string} formKey Concatenation of provider, ID & Instance ID.
			 *     @type {int} popupId The ID of the popup the form was in.
			 *     @type {Object} popup Usable jQuery object for the popup.
			 * }
			 */
			window.PUM.hooks.doAction('pum.integration.form.success', form, args);
		},
		checkFormKeyMatches: function (formIdentifier, formInstanceId, submittedFormArgs) {
			formInstanceId = '' === formInstanceId ? formInstanceId : false;
			// Check if the submitted form matches trigger requirements.
			var checks = [
				// Any supported form.
				formIdentifier === 'any',

				// Checks for PM core sub form submissions.
				'pumsubform' === formIdentifier && 'pumsubform' === submittedFormArgs.formProvider,

				// Any provider form. ex. `ninjaforms_any`
				formIdentifier === submittedFormArgs.formProvider + '_any',

				// Specific provider form with or without instance ID. ex. `ninjaforms_1` or `ninjaforms_1_*`
				// Only run this test if not checking for a specific instanceId.
				!formInstanceId && new RegExp('^' + formIdentifier + '(_[\d]*)?').test(submittedFormArgs.formKey),

				// Specific provider form with specific instance ID. ex `ninjaforms_1_1` or `calderaforms_jbakrhwkhg_1`
				// Only run this test if we are checking for specific instanceId.
				!!formInstanceId && formIdentifier + '_' + formInstanceId === submittedFormArgs.formKey
			],
			// If any check is true, set the cookie.
			matchFound = -1 !== checks.indexOf(true);

			/**
			 * This filter is applied when checking if a form match was found.
			 *
			 * It is used for comparing user selected form identifiers with submitted forms.
			 *
			 * @since 1.9.0
			 *
			 * @param {boolean} matchFound A boolean determining whether a match was found.
			 * @param {Object} args {
			 *		@type {string} formIdentifier gravityforms_any or ninjaforms_1
			 *		@type {int} formInstanceId Not all form plugins support this.
			 *		@type {Object} submittedFormArgs{
			 *			@type {string} formProvider Such as gravityforms or ninjaforms
			 * 			@type {string|int} formId Usually an integer ID number such as 1
			 *			@type {int} formInstanceId Not all form plugins support this.
			 *			@type {string} formKey Concatenation of provider, ID & Instance ID.
			 *			@type {int} popupId The ID of the popup the form was in.
			 *			@type {Object} popup Usable jQuery object for the popup.
			 *		}
			 * }
			 *
			 * @returns {boolean}
			 */
			return window.PUM.hooks.applyFilters('pum.integration.checkFormKeyMatches', matchFound, {
				formIdentifier: formIdentifier,
				formInstanceId: formInstanceId,
				submittedFormArgs: submittedFormArgs
			} );
		}
	});


}(window.jQuery));
