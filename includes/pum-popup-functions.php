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
	return pum_popup( $popup_id )->get_title();
}

function pum_get_popup_triggers( $popup_id = null ) {
	return pum_popup( $popup_id )->get_triggers();
}

function pum_get_popup_cookies( $popup_id = null ) {
	return pum_popup( $popup_id )->get_cookies();
}

function pum_is_popup_loadable( $popup_id = null ) {
	return pum_popup( $popup_id )->is_loadable();
}
