/*******************************************************************************
 * Copyright (c) 2024, WP Popup Maker
 ******************************************************************************/
{
	const formProvider = 'fluentforms';
	const $ = window.jQuery;

	$( document ).on(
		'fluentform_submission_success',
		function ( _event, formDetails ) {
			// FluentForms fires this event twice per submission:
			// 1. First with formDetails (complete data)
			// 2. Second without formDetails (undefined)
			// We only process the first event with actual data.
			if ( ! formDetails || ! formDetails.config || ! formDetails.config.id ) {
				return;
			}

			const formId = formDetails.config.id;

			// FluentForms passes form as jQuery object in formDetails.form.
			let formEl = formDetails.form;

			// Ensure it's a valid jQuery object with elements.
			if ( ! formEl || ! formEl.length ) {
				// Fallback: try to find form by ID attribute (FluentForms uses id="fluentform_X").
				formEl = $( '#fluentform_' + formId );
			}

			if ( ! formEl || ! formEl.length ) {
				return;
			}

			const formInstanceId = formEl.data( 'form_instance' ) || null;

			// All the magic happens here.
			window.PUM.integrations.formSubmission( formEl, {
				formProvider,
				formId,
				formInstanceId,
			} );
		}
	);
}
