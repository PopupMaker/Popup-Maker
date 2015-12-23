<?php

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
	if ( ! $popup_id ) {
		$popup_id = popmake_get_the_popup_ID();
	}
	$cookies = get_post_meta( $popup_id, 'popup_cookies', true );

	return apply_filters( 'pum_get_popup_cookies', $cookies, $popup_id );
}

