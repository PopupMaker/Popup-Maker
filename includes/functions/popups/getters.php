<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Return the popup id.
 *
 * @param int $popup_id
 *
 * @return int
 */
function pum_get_popup_id( $popup_id = 0 ) {
	if ( ! empty( $popup_id ) && is_numeric( $popup_id ) ) {
		$_popup_id = $popup_id;
	} elseif ( is_object( pum()->current_popup ) && is_numeric( pum()->current_popup->ID ) ) {
		$_popup_id = pum()->current_popup->ID;
	} else {
		$_popup_id = 0;
	}

	return (int) apply_filters( 'pum_get_popup_id', (int) $_popup_id, $popup_id );
}

/**
 * @param int $popup_id
 *
 * @return string
 */
function pum_get_popup_title( $popup_id = 0 ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup_object( $popup ) ) {
		return "";
	}

	return $popup->get_title();
}

/**
 * @deprecated 1.8.0
 *
 * @param int $popup_id
 *
 * @return array
 */
function pum_get_popup_triggers( $popup_id = 0 ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup_object( $popup ) ) {
		return array();
	}

	return $popup->get_triggers();
}
