/*******************************************************************************
 * Copyright (c) 2024, WP Popup Maker
 ******************************************************************************/
{
	const formProvider = 'elementor';
	const $ = window.jQuery;

	// Elementor Forms success event.
	$( document ).on(
		'submit_success',
		'.elementor-form',
		function ( event, response ) {
			const $form = $( this )[ 0 ];

			// Get element_id from the widget container.
			// Elementor form widgets are inside a .elementor-element-{id} container.
			const $widget = $( this ).closest( '[data-id]' );
			const elementId = $widget.length
				? $widget.attr( 'data-id' )
				: 'unknown';

			window.PUM.integrations.formSubmission( $form, {
				formProvider,
				formId: elementId,
			} );
		}
	);
}
