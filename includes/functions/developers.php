<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
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
