<?php


function popmake_get_popup_triggers( $popup_id = null ) {
	if ( ! $popup_id ) {
		$popup_id = popmake_get_the_popup_ID();
	}
	$triggers = get_post_meta( $popup_id, 'popup_triggers', true );
	return apply_filters( 'popmake_get_popup_triggers', $triggers, $popup_id );
}

function popmake_get_popup_cookies( $popup_id = null ) {
	if ( ! $popup_id ) {
		$popup_id = popmake_get_the_popup_ID();
	}
	$cookies = get_post_meta( $popup_id, 'popup_cookies', true );
	return apply_filters( 'popmake_get_popup_cookies', $cookies, $popup_id );
}

