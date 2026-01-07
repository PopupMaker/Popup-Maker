/*******************************************************************************
 * Copyright (c) 2024, WP Popup Maker
 ******************************************************************************/

{
	const formProvider = 'bitform';
	const $ = window.jQuery;

	$( function () {
		document
			.querySelectorAll( '[id^="form-bitforms"]' )
			.forEach( function ( form ) {
				form.addEventListener(
					'bf-form-submit-success',
					function ( event ) {
						if ( ! event.detail || ! event.detail.formId ) {
							return;
						}

						// Form identifier pattern: bitforms_{formId}_{postId}_{instanceCounter}.
						// Where:
						//   - formId: Database form ID (e.g., "1")
						//   - postId: WordPress post/page ID where form is displayed (e.g., "995")
						//   - instanceCounter: 1-indexed instance if multiple forms on same page (e.g., "1")
						// PHP receives identifier minus 'bitforms_' prefix and extracts formId.
						// Example: bitforms_1_995_1 -> PHP gets "1_995_1" -> extracts "1" for DB lookup.
						const formId = event.detail.formId.replace(
							/^bitforms_/,
							''
						);

						// All the magic happens here.
						window.PUM.integrations.formSubmission( $( form ), {
							formProvider,
							formId,
						} );
					}
				);
			} );
	} );
}
