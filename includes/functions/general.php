<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the current blog db_ver.
 *
 * @return mixed
 */
function pum_get_db_ver() {
	return get_option( 'pum_db_ver', false );
}

/**
 * Resets both asset cached files & transient CSS storage to be regenerated.
 *
 * @since 1.8.0
 */
function pum_reset_assets() {
	// Reset/regenerate asset cache.
	PUM_AssetCache::reset_cache();
	// Reset/regenerate stored theme CSS styles.
	delete_transient( 'popmake_theme_styles' );
}

if ( ! function_exists( 'maybe_json_attr' ) ) {
	function maybe_json_attr( $value, $encode = false ) {
		if ( is_object( $value ) || is_array( $value ) ) {
			return $encode ? htmlspecialchars( wp_json_encode( $value ) ) : wp_json_encode( $value );
		}
		return $value;
	}
}

/**
 * Returns array key from dot notated array key..
 *
 * @since 1.0
 *
 * @param string $a is the array you are searching.
 * @param string $path is the dot notated path.
 * @param string $default is the default returned if key empty or not found.
 *
 * @return mixed results of lookup
 */
function popmake_resolve( array $a, $path, $default = null ) {
	$current = $a;
	$p       = strtok( $path, '.' );
	while ( $p !== false ) {
		if ( ! isset( $current[ $p ] ) ) {
			return $default;
		}
		$current = $current[ $p ];
		$p       = strtok( '.' );
	}

	return $current;
}

/**
 * Checks whether function is disabled.
 *
 * @since 1.4
 *
 * @param string $function Name of the function.
 *
 * @return bool Whether or not function is disabled.
 */
function pum_is_func_disabled( $function ) {
	$disabled = explode( ',', ini_get( 'disable_functions' ) );

	return in_array( $function, $disabled );
}