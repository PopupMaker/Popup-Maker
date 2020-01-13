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
			if ("undefined" !== typeof pum_vars.form_submissions) {
				var i = 0,
					count = pum_vars.form_submissions.length,
					submission;

				for (i; i < count; i++) {
					submission = pum_vars.form_submissions[i];

					// Initialize the popup var based on passed popup ID.
					submission.popup = submission.popupId > 0 ? PUM.getPopup(submission.popupId) : null;

					PUM.integrations.formSubmission(null, submission);
				}
			}
		},
		formSubmission: function (form, args) {
			args = $.extend({
				popup: PUM.getPopup(form),
				formProvider: null,
				formId: null,
				formInstanceId: null,
				formKey: null
			}, args);

			// Generate unique formKey identifier.
			args.formKey = args.formKey || [args.formProvider, args.formId, args.formInstanceId].filter(filterNull).join('_');

			if (args.popup && args.popup.length) {
				// Should this be here. It is the only thing not replicated by a new form trigger & cookie.
				// $popup.trigger('pumFormSuccess');
			}

			window.PUM.hooks.doAction('pum.integration.form.success', form, args);
		},
		checkFormKeyMatches: function (formIdentifier, formInstanceId, submittedFormArgs) {
			formInstanceId = '' === formInstanceId ? formInstanceId : false;
			// Check if the submitted form matches trigger requirements.
			var checks = [
				// Any supported form.
				formIdentifier === 'any',

				// Any provider form. ex. `ninjaforms_any`
				formIdentifier === submittedFormArgs.formProvider + '_any',

				// Specific provider form with or without instance ID. ex. `ninjaforms_1` or `ninjaforms_1_*`
				// Only run this test if not checking for a specific instanceId.
				!formInstanceId && new RegExp('^' + formIdentifier + '(_[\d]*)?').test(submittedFormArgs.formKey),

				// Specific provider form with specific instance ID. ex `ninjaforms_1_1` or `calderaforms_jbakrhwkhg_1`
				// Only run this test if we are checking for specific instanceId.
				!!formInstanceId && formIdentifier + '_' + formInstanceId === submittedFormArgs.formKey
			];

			// If any check is true, set the cookie.
			return -1 !== checks.indexOf(true);
		}
	});


}(window.jQuery));
