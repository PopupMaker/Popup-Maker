<?php
/**
 * Functions for Popup Conditionals
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

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
	return pum_is_popup_object( $popup ) && $popup->is_valid();
}

/**
 * Tests a given value to see if its a valid Popup model.
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
function pum_is_popup_loadable( $popup_id = null ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup_object( $popup ) ) {
		return false;
	}

	return $popup->is_loadable();
}

/**
 * Returns true if the close button should be shown.
 *
 * @param null|int $popup_id
 *
 * @return bool
 */
function pum_show_close_button( $popup_id = null ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return true;
	}

	return $popup->show_close_button();
}

/**
 * Whether the popup title should be displayed on the frontend.
 *
 * Returns true when display_title setting is enabled (defaults true)
 * AND the popup has a non-empty title.
 *
 * @param null|int $popup_id Popup ID or null for current popup.
 *
 * @return bool
 */
function pum_show_popup_title( $popup_id = null ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return false;
	}

	$has_title = '' !== pum_get_popup_title( $popup_id );

	return $has_title && $popup->get_setting( 'display_title', true );
}
