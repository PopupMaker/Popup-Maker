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
						// Extract numeric formId from full pattern.
						// Example: bitforms_1_995_1 -> "1"
						const fullIdentifier = event.detail.formId.replace(
							/^bitforms_/,
							''
						);
						const formId = fullIdentifier.split( '_' )[ 0 ];

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
