<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Returns a popup object.
 *
 * @deprecated 1.7
 *
 * @param null $popup_id
 *
 * @return false|PUM_Model_Popup
 */
function pum_popup( $popup_id = null ) {
	return pum_get_popup( $popup_id );
}

/**
 * @param null $popup_id
 *
 * @return string
 */
function pum_get_popup_title( $popup_id = null ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return "";
	}

	return $popup->get_title();
}

/**
 * @deprecated 1.7.0
 *
 * @param null $popup_id
 *
 * @return array
 */
function pum_get_popup_triggers( $popup_id = null ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return array();
	}

	return $popup->get_triggers();
}

/**
 * @deprecated 1.7.0
 *
 * @param null $popup_id
 *
 * @return array
 */
function pum_get_popup_cookies( $popup_id = null ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return array();
	}

	return $popup->get_cookies();
}

/**
 * @param null $popup_id
 *
 * @return bool
 */
function pum_is_popup_loadable( $popup_id = null ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return false;
	}

	return $popup->is_loadable();
}
