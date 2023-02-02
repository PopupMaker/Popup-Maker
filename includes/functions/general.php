<?php
/**
 * General functions
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
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

add_action( 'pum_save_theme', 'pum_update_all_themes_close_text_cache', 100 );

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
 * @param string $encode
 *
 * @return string
 */
function pum_get_svg_icon( $encode = false ) {
	$svg_icon_code = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid meet" viewBox="0 0 16 16" width="16" height="16"><defs><path d="M12.67 1.75L13.12 1.83L13.58 1.97L14.04 2.17L14.49 2.43L14.9 2.77L15.26 3.19L15.57 3.71L15.79 4.28L15.93 4.85L15.99 5.41L16 5.95L15.95 6.45L15.88 6.89L15.78 7.27L15.67 7.57L15.57 7.78L15.45 7.95L15.29 8.16L15.1 8.4L14.87 8.67L14.61 8.95L14.32 9.25L14 9.54L13.66 9.84L13.3 10.13L12.94 10.4L12.61 10.65L12.29 10.88L11.99 11.11L11.69 11.35L11.39 11.59L11.08 11.84L10.76 12.11L10.42 12.42L10.04 12.73L9.63 13.02L9.19 13.29L8.72 13.53L8.23 13.75L7.72 13.93L7.21 14.09L6.69 14.2L6.18 14.28L5.6 14.3L4.9 14.25L4.12 14.09L3.31 13.8L2.5 13.36L1.74 12.76L1.07 11.96L0.52 10.94L0.14 9.68L0 8.36L0.12 7.17L0.45 6.12L0.94 5.2L1.54 4.42L2.22 3.78L2.92 3.29L3.59 2.95L4.19 2.76L4.85 2.63L5.7 2.48L6.68 2.33L7.73 2.18L8.78 2.03L9.79 1.9L10.68 1.8L11.4 1.72L11.88 1.7L12.26 1.7L12.67 1.75ZM10.64 2.39L9.62 2.51L8.48 2.66L7.32 2.82L6.22 2.98L5.26 3.13L4.55 3.26L3.91 3.46L3.18 3.83L2.43 4.38L1.72 5.08L1.12 5.95L0.7 6.96L0.51 8.13L0.63 9.43L1 10.67L1.51 11.67L2.14 12.44L2.86 13.02L3.65 13.41L4.49 13.64L5.34 13.73L6.19 13.71L6.97 13.6L7.64 13.44L8.22 13.23L8.73 12.98L9.17 12.71L9.57 12.41L9.95 12.1L10.32 11.78L10.67 11.48L11 11.21L11.32 10.97L11.63 10.73L11.94 10.5L12.27 10.27L12.61 10.03L12.99 9.76L13.36 9.47L13.71 9.18L14.03 8.88L14.32 8.6L14.57 8.32L14.79 8.08L14.96 7.86L15.08 7.69L15.19 7.47L15.3 7.14L15.4 6.72L15.47 6.23L15.48 5.69L15.44 5.11L15.31 4.53L15.08 3.96L14.78 3.45L14.42 3.06L14.04 2.76L13.62 2.55L13.2 2.41L12.78 2.32L12.38 2.28L12 2.27L11.46 2.31L10.64 2.39Z" id="eLoMHfaS"></path><path d="M8.35 6.04L9.08 7.19L9.35 8.59L9.08 9.99L8.35 11.13L7.27 11.91L5.95 12.19L4.62 11.91L3.54 11.13L2.81 9.99L2.54 8.59L2.81 7.19L3.54 6.04L4.62 5.27L5.95 4.99L7.27 5.27L8.35 6.04ZM4.1 6.6L3.54 7.49L3.34 8.58L3.54 9.67L4.1 10.56L4.93 11.17L5.94 11.39L6.96 11.17L7.79 10.56L8.35 9.67L8.55 8.58L8.35 7.49L7.79 6.6L6.96 5.99L5.94 5.77L4.93 5.99L4.1 6.6Z" id="a1xlBX7eUk"></path><path d="M9.04 9.39L8.35 10.9L9.11 11.66C9.12 11.66 9.12 11.66 9.12 11.66C10.01 10.71 10.41 9.4 10.19 8.11C10.19 8.09 10.18 8.04 10.16 7.95L9.11 8.01L9.04 9.39Z" id="e1KfKU9JEu"></path><path d="M3.74 11.08L2.82 9.7L1.78 9.94C1.78 9.94 1.78 9.94 1.78 9.94C2.1 11.2 3 12.24 4.2 12.74C4.23 12.75 4.28 12.77 4.36 12.8L4.87 11.89L3.74 11.08Z" id="aswxBlyrF"></path><path d="M4.96 5.33L6.62 5.17L6.89 4.14C6.89 4.14 6.89 4.14 6.89 4.14C5.62 3.83 4.29 4.14 3.28 4.96C3.26 4.97 3.22 5.01 3.15 5.07L3.72 5.95L4.96 5.33Z" id="aFREkIqrT"></path><path d="M13.56 4.26L13.97 4.89L14.12 5.67L13.97 6.45L13.56 7.09L12.96 7.52L12.23 7.68L11.5 7.52L10.9 7.09L10.49 6.45L10.34 5.67L10.49 4.89L10.9 4.26L11.5 3.83L12.23 3.67L12.96 3.83L13.56 4.26ZM11.88 5.24L11.77 5.44L11.73 5.68L11.77 5.92L11.88 6.12L12.04 6.25L12.23 6.3L12.42 6.25L12.58 6.12L12.69 5.92L12.73 5.68L12.69 5.44L12.58 5.24L12.42 5.11L12.23 5.06L12.04 5.11L11.88 5.24Z" id="amDpzCYZN"></path><path d="M12.51 2.83L13.24 3.05L13.02 4.27L12.02 3.98L12.51 2.83Z" id="c1FHpaNCVv"></path><path d="M14.65 4.57L14.87 5.29L13.73 5.78L13.42 4.79L14.65 4.57Z" id="c59InZ73I"></path><path d="M14.34 7.35L13.77 7.85L12.85 7.02L13.62 6.33L14.34 7.35Z" id="cp1hej6U"></path><path d="M11.95 8.41L11.2 8.27L11.3 7.03L12.32 7.22L11.95 8.41Z" id="akiLoJGaJ"></path><path d="M9.69 6.69L9.58 5.95L10.78 5.62L10.95 6.64L9.69 6.69Z" id="a12HrJSLIM"></path><path d="M10.05 3.9L10.62 3.4L11.54 4.24L10.76 4.93L10.05 3.9Z" id="alSln9w1"></path></defs><g><g><g><use xlink:href="#eLoMHfaS" opacity="1" fill="black" fill-opacity="1"></use></g><g><g><use xlink:href="#a1xlBX7eUk" opacity="1" fill="black" fill-opacity="1"></use></g><g><use xlink:href="#e1KfKU9JEu" opacity="1" fill="black" fill-opacity="1"></use></g><g><use xlink:href="#aswxBlyrF" opacity="1" fill="black" fill-opacity="1"></use></g><g><use xlink:href="#aFREkIqrT" opacity="1" fill="black" fill-opacity="1"></use></g></g><g><g><use xlink:href="#amDpzCYZN" opacity="1" fill="black" fill-opacity="1"></use></g><g><use xlink:href="#c1FHpaNCVv" opacity="1" fill="black" fill-opacity="1"></use></g><g><use xlink:href="#c59InZ73I" opacity="1" fill="black" fill-opacity="1"></use></g><g><use xlink:href="#cp1hej6U" opacity="1" fill="black" fill-opacity="1"></use></g><g><use xlink:href="#akiLoJGaJ" opacity="1" fill="black" fill-opacity="1"></use></g><g><use xlink:href="#a12HrJSLIM" opacity="1" fill="black" fill-opacity="1"></use></g><g><use xlink:href="#alSln9w1" opacity="1" fill="black" fill-opacity="1"></use></g></g></g></g></svg>';
	if ( $encode ) {
		$svg_icon_code = 'data:image/svg+xml;base64,' . base64_encode( $svg_icon_code );
	}
	return $svg_icon_code;
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
 * @param string $default is the default returned if key empty or not found.
 *
 * @return mixed results of lookup
 */
function popmake_resolve( array $a, $path, $default = null ) {
	$current = $a;
	$p       = strtok( $path, '.' );
	while ( false !== $p ) {
		if ( ! isset( $current[ $p ] ) ) {
			return $default;
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
 * @param string $name is the key you are looking for. Can use dot notation for arrays such as my_meta.field1 which will resolve to $_POST['my_meta']['field1'].
 *
 * @return mixed results of lookup
 */
function popmake_post( $name, $do_stripslashes = true ) {
	$value = popmake_resolve( $_POST, $name, false );

	return $do_stripslashes ? stripslashes_deep( $value ) : $value;
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
