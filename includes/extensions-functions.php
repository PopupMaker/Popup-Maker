<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pum_enabled_extensions() {
	return apply_filters( 'pum_enabled_extensions', array() );
}

function pum_extension_enabled( $extension = '' ) {
	$enabled_extensions = pum_enabled_extensions();

	return ! empty( $extension ) && array_key_exists( $extension, $enabled_extensions );
}

function popmake_available_extensions() {
	$json_data = file_get_contents( POPMAKE_DIR . 'includes/extension-list.json' );

	return json_decode( $json_data, true );
	/*
	if(($extensions = get_site_transient('popup-maker-extension-list')) === false) {

		// data to send in our API request
		$api_params = array( 
			'edd_action'	=> 'extension_list',
			'url'       => home_url()
		);
		// Call the custom API.
		$response = wp_remote_get( add_query_arg( $api_params, POPMAKE_API_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return array();

		$extensions = json_decode( wp_remote_retrieve_body( $response ) );
		set_site_transient( 'popup-maker-extension-list', $extensions, 86400 );
	}
	return $extensions;
	*/
}

add_filter( 'popmake_existing_extension_images', 'popmake_core_extension_images', 10 );
function popmake_core_extension_images( $array ) {
	return array_merge( $array, array(
		'core-extensions-bundle',
		'aweber-integration',
		'mailchimp-integration',
		'remote-content',
		'scroll-triggered-popups',
		'popup-analytics',
		'forced-interaction',
		'age-verification-modals',
		'advanced-theme-builder',
		'exit-intent-popups',
		'ajax-login-modals',
		'advanced-targeting-conditions',
		'secure-idle-user-logout',
		'terms-conditions-popups',
	) );
}
