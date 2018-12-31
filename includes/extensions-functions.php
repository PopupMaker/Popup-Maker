<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return mixed
 */
function pum_enabled_extensions() {
	return apply_filters( 'pum_enabled_extensions', array() );
}

/**
 * @param string $extension
 *
 * @return bool
 */
function pum_extension_enabled( $extension = '' ) {
	$enabled_extensions = pum_enabled_extensions();

	return ! empty( $extension ) && array_key_exists( $extension, $enabled_extensions );
}

/**
 * @return array
 */
function popmake_available_extensions() {
	$json_data = file_get_contents( POPMAKE_DIR . 'includes/extension-list.json' );

	return json_decode( $json_data, true );
}

/**
 * @return array
 */
function pum_extensions_with_local_image() {
	return apply_filters( 'pum_extensions_with_local_image', array(
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
