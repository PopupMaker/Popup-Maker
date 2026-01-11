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
 * Get upgrade link with UTM tracking.
 *
 * Generates upgrade URLs with proper UTM parameters for conversion tracking.
 *
 * @param array<string, string> $utm_args {
 *     Optional UTM parameters to override defaults.
 *
 *     @type string $utm_source   Traffic source (default: 'plugin').
 *     @type string $utm_medium   Marketing medium (default: 'dashboard').
 *     @type string $utm_campaign Campaign name (default: 'upgrade').
 *     @type string $utm_content  Optional content variant.
 * }
 * @return string Upgrade URL with UTM parameters.
 *
 * @since 1.14.0
 */
function get_upgrade_link( $utm_args = [] ) {
	$defaults = [
		'utm_source'   => 'plugin',
		'utm_medium'   => 'dashboard',
		'utm_campaign' => 'upgrade',
	];

	$utm_args = wp_parse_args( $utm_args, $defaults );

	// Remove empty values to keep URLs clean.
	$utm_args = array_filter( $utm_args );

	return add_query_arg( $utm_args, 'https://wppopupmaker.com/pricing/' );
}

/**
 * Generate upgrade URL with UTM tracking.
 *
 * Convenience function for generating contextual upgrade links.
 *
 * @param string $medium   Marketing medium (e.g., 'notice-bar', 'go-pro-tab', 'feature-preview').
 * @param string $campaign Campaign name (e.g., 'woocommerce-detected', 'exit-intent').
 * @param string $content  Optional content variant (e.g., 'variant-a', 'screenshot-viewed').
 * @return string Upgrade URL with UTM parameters.
 *
 * @since 1.21.3
 */
function generate_upgrade_url( $medium, $campaign, $content = '' ) {
	$utm_args = [
		'utm_source'   => 'plugin',
		'utm_medium'   => sanitize_key( $medium ),
		'utm_campaign' => sanitize_key( $campaign ),
	];

	if ( ! empty( $content ) ) {
		$utm_args['utm_content'] = sanitize_key( $content );
	}

	return get_upgrade_link( $utm_args );
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
