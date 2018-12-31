<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Checks if the $popup is valid.
 *
 * @param mixed|PUM_Model_Popup $popup
 *
 * @return bool
 */
function pum_is_popup( $popup ) {
	return is_object( $popup ) && is_numeric( $popup->ID ) && $popup->is_valid();
}

/**
 * Tests a given value to see if its a valid Forum model.
 *
 * @param PUM_Model_Popup|mixed $popup
 *
 * @return bool
 */
function pum_is_popup_object( $popup ) {
	return is_a( $popup, 'PUM_Model_Popup' );
}

/**
 * @param int $popup_id
 *
 * @return bool
 */
function pum_is_popup_loadable( $popup_id = 0 ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup_object( $popup ) ) {
		return false;
	}

	return $popup->is_loadable();
}