/***********************************
 * Copyright (c) 2020, Popup Maker
 **********************************/

{
	const formProvider = "formidableforms";
	const $ = window.jQuery;

	$(document).on("frmFormComplete", function(event, form, response) {
		const $form = $(form);
		const formId = $form.find('input[name="form_id"]').val();
		const $popup = PUM.getPopup(
			$form.find('input[name="pum_form_popup_id "]').val()
		);

		// All the magic happens here.
		window.PUM.integrations.formSubmission($form, {
			popup: $popup,
			formProvider,
			formId,
			extras: {
				response
			}
		});
	});
}
