<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pum_get_popup_title( $popup_id = null ) {
	$popup = new PUM_Popup( $popup_id );
	return $popup->get_title();
}


function pum_get_popup_triggers( $popup_id = null ) {
	if ( ! ( $popup = new PUM_Popup( $popup_id ) ) ) {
		return array();
	};

	return $popup->get_triggers();
}

function pum_get_popup_cookies( $popup_id = null ) {
	if ( ! ( $popup = new PUM_Popup( $popup_id ) ) ) {
		return array();
	};

	return $popup->get_cookies();
}

function pum_get_popup_conditions( $popup_id = null ) {
	if ( ! ( $popup = new PUM_Popup( $popup_id ) ) ) {
		return array();
	};

	return $popup->get_conditions();
}

function pum_is_popup_loadable( $popup_id = null ) {
	if ( ! ( $popup = new PUM_Popup( $popup_id ) ) ) {
		return false;
	};

	return $popup->is_loadable();
}
