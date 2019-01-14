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
 * Returns the default theme_id from global settings.
 *
 * Returns false if none set.
 *
 * @since 1.8.0
 *
 * @return int|false
 */
function pum_get_default_theme_id() {
	$default_theme_id = pum_get_option( 'default_theme_id' );

	if ( false === $default_theme_id ) {
		$default_theme_id = get_option( 'popmake_default_theme' );

		if ( false === $default_theme_id ) {
			$default_theme_id = pum_install_default_theme();
			if ( pum_update_option( 'default_theme_id', $default_theme_id ) ) {
				// Self cleanup old version.
				delete_option( 'popmake_default_theme' );
			}
		}
	}

	return absint( $default_theme_id );
}

/**
 * @param string $path
 *
 * @return string
 */
function pum_asset_path( $path = '' ) {
	return Popup_Maker::$DIR . 'assets/' . ltrim( $path,'/' );
}

/**
 * @param string $path
 *
 * @return string
 */
function pum_asset_url( $path = '' ) {
	return Popup_Maker::$URL . 'assets/' . ltrim( $path,'/' );
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

/**
 * Returns array key from dot notated array key..
 *
 * @since 1.0
 *
 * @deprecated 1.8.0
 *
 * @param array $a is the array you are searching.
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