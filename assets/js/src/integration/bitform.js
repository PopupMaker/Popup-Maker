/*******************************************************************************
 * Copyright (c) 2024, WP Popup Maker
 ******************************************************************************/

/**
 * Bit Form integration for Popup Maker.
 *
 * Tracks form submissions and triggers popup conversions.
 */
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
						// Extract formId and formInstanceId from full pattern.
						// Example: bitforms_1_995_1 -> formId="1", formInstanceId="1"
						const fullIdentifier = event.detail.formId.replace(
							/^bitforms_/,
							''
						);
						const parts = fullIdentifier.split( '_' );
						const formId = parts[ 0 ];
						const formInstanceId = parts[ 2 ] || null;

						// All the magic happens here.
						window.PUM.integrations.formSubmission( $( form ), {
							formProvider,
							formId,
							formInstanceId,
						} );
					}
				);
			} );
	} );
}
