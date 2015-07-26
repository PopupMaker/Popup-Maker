<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function popmake_optin_ajax_call() {
	// Check our nonce and make sure it's correct.
	check_ajax_referer( POPMAKE_NONCE, POPMAKE_NONCE );
	if ( isset( $_REQUEST['optin_dismiss'] ) ) {
		$optin = $_REQUEST['optin_name'];
		$type  = $_REQUEST['optin_type'];
		if ( $type == 'user' ) {
			update_user_meta( get_current_user_id(), '_popmake_dismiss_optin_' . $optin, true );
		} else {
			update_option( '_popmake_dismiss_optin_' . $optin, true );
		}
		$response['success'] = true;
	}
	$response['new_nonce'] = wp_create_nonce( POPMAKE_NONCE );
	echo json_encode( $response );
	die();
}

add_action( 'wp_ajax_popmake_optin', 'popmake_optin_ajax_call' );
add_action( 'wp_ajax_nopriv_popmake_optin', 'popmake_optin_ajax_call' );


function popmake_popup_preview_content_ajax_call() {
	// Check our nonce and make sure it's correct.
	check_ajax_referer( POPMAKE_NONCE, POPMAKE_NONCE );
	if ( isset( $_REQUEST['popup_content'] ) ) {
		remove_filter( 'the_popup_content', 'popmake_popup_content_container', 10000 );
		$response['content'] = stripslashes( apply_filters( 'the_popup_content', $_REQUEST['popup_content'], $_REQUEST['popup_id'] ) );
		$response['success'] = true;
	}
	$response['new_nonce'] = wp_create_nonce( POPMAKE_NONCE );
	header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
	header( "Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" );
	header( "Cache-Control: no-cache, must-revalidate" );
	header( "Pragma: no-cache" );
	header( "Content-type: text/x-json" );
	echo json_encode( $response );
	die();
}

add_action( 'wp_ajax_popmake_popup_preview_content', 'popmake_popup_preview_content_ajax_call' );
add_action( 'wp_ajax_nopriv_popmake_popup_preview_content', 'popmake_popup_preview_content_ajax_call' );
