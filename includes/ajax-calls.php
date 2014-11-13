<?php


function popmake_optin_ajax_call() { 
	// Check our nonce and make sure it's correct.
	check_ajax_referer(POPMAKE_NONCE, POPMAKE_NONCE);
	if ( isset( $_REQUEST['optin_dismiss'] ) ) {
		$optin = $_REQUEST['optin_name'];
		$type = $_REQUEST['optin_type'];
		if($type == 'user') {
			update_user_meta( get_current_user_id(), '_popmake_dismiss_optin_' . $optin, true );
		}
		else {
			update_option( '_popmake_dismiss_optin_' . $optin, true );
		}
		$response['success'] = true;
	}
	$response['new_nonce'] = wp_create_nonce(POPMAKE_NONCE);
	echo json_encode($response);
	die();
}

add_action('wp_ajax_popmake_optin', 'popmake_optin_ajax_call');
add_action('wp_ajax_nopriv_popmake_optin', 'popmake_optin_ajax_call');
