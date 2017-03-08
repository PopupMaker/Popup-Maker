<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param null $popup_id
 *
 * @return PUM_Popup|false
 */
function pum_popup( $popup_id = null ) {
	if ( ! $popup_id && isset( $GLOBALS['popup'] ) && is_a( $GLOBALS['popup'], 'PUM_Popup' ) ) {
		$popup = $GLOBALS['popup'];
	} else {
		$popup = new PUM_Popup( $popup_id );
	}
	return apply_filters( 'pum_popup', $popup, $popup_id );
}

function pum_get_popup_title( $popup_id = null ) {
	return pum_popup( $popup_id )->get_title();
}

function pum_get_popup_triggers( $popup_id = null ) {
	return pum_popup( $popup_id )->get_triggers();
}

function pum_get_popup_cookies( $popup_id = null ) {
	return pum_popup( $popup_id )->get_cookies();
}

function pum_get_popup_conditions( $popup_id = null ) {
	return pum_popup( $popup_id )->get_conditions();
}

function pum_is_popup_loadable( $popup_id = null ) {
	return pum_popup( $popup_id )->is_loadable();
}
