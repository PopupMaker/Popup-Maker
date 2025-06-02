<?php
/**
 * Core functions.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker;

defined( 'ABSPATH' ) || exit;

/**
 * Returns an array of the default permissions.
 *
 * @return array<string,string> Default permissions.
 *
 * @since X.X.X
 */
function get_default_permissions() {
	return [
		// Settings.
		'edit_ctas'         => 'manage_options',
		'edit_popups'       => 'manage_options',
		'edit_popup_themes' => 'manage_options',
		'manage_settings'   => 'manage_options',
	];
}

/**
 * Get global store.
 *
 * @return \PopupMaker\Services\Globals
 *
 * @since X.X.X
 */
function get_globals_store() {
	return \PopupMaker\plugin( 'globals' );
}

/**
 * Get value from global store.
 *
 * @param string $key Key.
 * @param mixed  $default_value Default value.
 *
 * @return mixed
 *
 * @since X.X.X
 */
function get_global( $key, $default_value = null ) {
	return get_globals_store()->get( $key, $default_value );
}

/**
 * Set value in global store.
 *
 * @param string $key Key.
 * @param mixed  $value Value.
 *
 * @since X.X.X
 */
function set_global( $key, $value ) {
	get_globals_store()->set( $key, $value );
}

/**
 * Get logging service.
 *
 * @since X.X.X
 *
 * @return \PopupMaker\Services\Logging
 */
function logging() {
	return \PopupMaker\plugin()->get( 'logging' );
}
