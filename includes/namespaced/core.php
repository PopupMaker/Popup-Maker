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
 * @since 1.21.0
 */
function get_default_permissions() {
	$permissions = [
		// Allow editors to manage popups and themes.
		'edit_ctas'         => 'edit_others_posts',
		'edit_popups'       => 'edit_others_posts',
		'edit_popup_themes' => 'edit_others_posts',
		// Keep admin-only for plugin settings.
		'manage_settings'   => 'manage_options',
	];

	/**
	 * Filter: popup_maker/permissions
	 *
	 * Allows customization of user permissions for Popup Maker functionality.
	 *
	 * @param array<string,string> $permissions Permission mappings.
	 *
	 * @since 1.21.1
	 */
	return apply_filters( 'popup_maker/permissions', $permissions );
}

/**
 * Get global store.
 *
 * @return \PopupMaker\Services\Globals
 *
 * @since 1.21.0
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
 * @since 1.21.0
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
 * @since 1.21.0
 */
function set_global( $key, $value ) {
	get_globals_store()->set( $key, $value );
}

/**
 * Check if pro is active.
 *
 * @return boolean
 */
function is_pro_active() {
	return plugin()->is_pro_active();
}

/**
 * Get upgrade link.
 *
 * @return string
 */
function get_upgrade_link( $utm_args = [] ) {
	$utm_args = array_merge( [
		'utm_source'   => 'plugin',
		'utm_medium'   => 'dashboard',
		'utm_campaign' => 'upgrade',
	], $utm_args );

	return 'https://wppopupmaker.com/pricing/?' . http_build_query( $utm_args );
}

/**
 * Get logging service.
 *
 * @since 1.21.0
 *
 * @return \PopupMaker\Services\Logging
 */
function logging() {
	return \PopupMaker\plugin()->get( 'logging' );
}
