<?php
/**
 * General functions
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

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
 * Gets the cache close_text of a theme from wp_options to prevent un-needed queries on the front end.
 *
 * @since 1.8.0
 *
 * @param int $theme_id
 *
 * @return string
 */
function pum_get_theme_close_text( $theme_id = 0 ) {
	$close_texts = pum_get_all_themes_close_text();

	return isset( $close_texts[ $theme_id ] ) ? $close_texts[ $theme_id ] : '';
}

/**
 * Gets the cache of theme close text from wp_options to prevent un-needed queries on the front end.
 *
 * @since 1.8.0
 *
 * @return array|mixed
 */
function pum_get_all_themes_close_text() {
	$all_themes_close_text = get_option( 'pum_all_theme_close_text_cache' );

	if ( false === $all_themes_close_text ) {
		$all_themes_close_text = pum_update_all_themes_close_text_cache();
	}

	return $all_themes_close_text;
}

/**
 * Updates the cache of theme close text to prevent un-needed queries on the front end.
 *
 * @since 1.8.0
 *
 * @return array
 */
function pum_update_all_themes_close_text_cache() {
	$all_themes_close_text = [];

	$themes = pum_get_all_themes();

	foreach ( $themes as $theme ) {
		$all_themes_close_text[ $theme->ID ] = $theme->get_setting( 'close_text', '' );
	}

	update_option( 'pum_all_theme_close_text_cache', $all_themes_close_text );

	return $all_themes_close_text;
}

/**
 * Updates the cache of theme close text to prevent un-needed queries on the front end.
 *
 * @return void
 */
function pum_update_theme_close_text_cache_on_save() {
	pum_update_all_themes_close_text_cache();
}

add_action( 'pum_save_theme', 'pum_update_theme_close_text_cache_on_save', 100 );

/**
 * @param string $path
 *
 * @return string
 */
function pum_asset_path( $path = '' ) {
	return Popup_Maker::$DIR . 'assets/' . ltrim( $path, '/' );
}

/**
 * @param string $path
 *
 * @return string
 */
function pum_asset_url( $path = '' ) {
	return Popup_Maker::$URL . 'assets/' . ltrim( $path, '/' );
}

/**
 * Get SVG.
 *
 * @param string $path SVG path within assets folder.
 * @param bool   $encode Encode SVG?
 *
 * @return string
 */
function pum_get_svg( $path, $encode = false ) {
	static $cache = [];

	if ( ! isset( $cache[ $path ] ) ) {
		$cache[ $path ] = pum_get_svg_raw( $path );
	}

	if ( empty( $cache[ $path ] ) || ! $cache[ $path ] ) {
		return '';
	}

	$svg = $cache[ $path ];

	if ( $encode ) {
		// Ignore because this is the proper way to encode SVG.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$svg = 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	return $svg;
}

/**
 * Get SVG without caching.
 *
 * @param string $path SVG path within assets folder.
 *
 * @return string
 */
function pum_get_svg_raw( $path = '' ) {
	$file = pum_asset_path( $path );

	if ( ! file_exists( $file ) ) {
		return '';
	}

	return file_get_contents( $file );
}

/**
 * Get Plugin SVG icon.
 *
 * @param bool $encode Encode SVG?
 *
 * @return string
 */
function pum_get_svg_icon( $encode = false ) {
	if ( ! is_admin() || wp_doing_ajax() ) {
		return '';
	}

	return pum_get_svg( 'images/mark.svg', $encode );
}

/**
 * Log a message to the Popup Maker log file.
 *
 * @param string $message
 *
 * @return void
 */
function pum_log_message( $message ) {
	$logger = PUM_Utils_Logging::instance();

	// Check if the logger is enabled & can write to the log file.
	if ( ! $logger->enabled() ) {
		return;
	}

	$logger->log( $message );
}

/**
 * Log a unique message (only once) to the Popup Maker log file.
 *
 * @param string $message
 *
 * @return void
 */
function pum_log_unique_message( $message ) {
	$logger = PUM_Utils_Logging::instance();

	// Check if the logger is enabled & can write to the log file.
	if ( ! $logger->enabled() ) {
		return;
	}

	$logger->log_unique( $message );
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
 * @param array  $a is the array you are searching.
 * @param string $path is the dot notated path.
 * @param string $default_value is the default returned if key empty or not found.
 *
 * @return mixed results of lookup
 */
function popmake_resolve( array $a, $path, $default_value = null ) {
	$current = $a;
	$p       = strtok( $path, '.' );
	while ( false !== $p ) {
		if ( ! isset( $current[ $p ] ) ) {
			return $default_value;
		}
		$current = $current[ $p ];
		$p       = strtok( '.' );
	}

	return $current;
}

/**
 * Returns $_POST key.
 *
 * @since 1.0
 *
 * @deprecated 1.20.0
 *
 * @param string $name is the key you are looking for. Can use dot notation for arrays such as my_meta.field1 which will resolve to $_POST['my_meta']['field1'].
 *
 * @return mixed results of lookup
 */
function popmake_post( $name, $do_stripslashes = true ) {
	// Ignored because this is a fetcher for $_POST & deprecated.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$value = popmake_resolve( $_POST, $name, false );

	return $do_stripslashes ? stripslashes_deep( $value ) : $value;
}

if ( ! function_exists( 'pum_is_func_disabled' ) ) {
	/**
	 * Checks whether function is disabled.
	 *
	 * @since 1.4
	 *
	 * @param string $function_name Name of the function.
	 *
	 * @return bool Whether or not function is disabled.
	 */
	function pum_is_func_disabled( $function_name ) {
		$disabled = explode( ',', ini_get( 'disable_functions' ) );

		return in_array( $function_name, $disabled, true );
	}
}
