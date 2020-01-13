<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Call this with a popup ID and it will trigger the
 * JS based forms.success function with your settings
 * on the next page load.
 *
 * @since 1.7.0
 *
 * @param int $popup_id
 * @param array $settings
 */
function pum_trigger_popup_form_success( $popup_id = null, $settings = array() ) {
	if ( ! isset( $popup_id )  ) {
		$popup_id = isset( $_REQUEST['pum_form_popup_id'] ) && absint( $_REQUEST['pum_form_popup_id'] ) > 0 ? absint( $_REQUEST['pum_form_popup_id'] ) : false;
	}

	if ( $popup_id ) {
		PUM_Integrations::$form_success = array(
			'popup_id' => $popup_id,
			'settings'=> $settings
		);
	}
}

/**
 * @param array $args {
 *     	An array of parameters that customize the way the parser works.
 *		@var string $form_provider
 * 		@type string|int $form_id
 *	 	@type string|int $form_instance_id
 * 		@type string|int $popup_id
 * }
 */
function pum_integrated_form_submission( $args = [] ) {
	PUM_Integrations::$form_submissions[] = wp_parse_args( $args, [
		'popup_id'         => null,
		'form_provider'    => null,
		'form_id'          => null,
		'form_instance_id' => null,
	] );
}
