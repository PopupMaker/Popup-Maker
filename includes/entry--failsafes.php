<?php
/**
 * Failsafes
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! function_exists( 'popmake_get_option' ) ) {
	/**
	 * @param string $key
	 * @param bool   $default_value
	 *
	 * @return bool
	 */
	function popmake_get_option( $key = '', $default_value = false ) {
		return $default_value;
	}
}

if ( ! function_exists( 'popmake_is_admin_page' ) ) {
	/**
	 * @return bool
	 */
	function popmake_is_admin_page() {
		return false;
	}
}

if ( ! function_exists( 'pum_is_admin_page' ) ) {
	/**
	 * @return bool
	 */
	function pum_is_admin_page() {
		return false;
	}
}

if ( ! function_exists( 'popmake_is_admin_popup_page' ) ) {
	/**
	 * @return bool
	 */
	function popmake_is_admin_popup_page() {
		return false;
	}
}

if ( ! function_exists( 'pum_is_popup_editor' ) ) {
	/**
	 * @return bool
	 */
	function pum_is_popup_editor() {
		return false;
	}
}

if ( ! function_exists( 'pum_is_settings_page' ) ) {
	/**
	 * @return bool
	 */
	function pum_is_settings_page() {
		return false;
	}
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

if ( ! function_exists( 'popmake_get_template_part' ) ) {
	/**
	 * @param $slug
	 * @param null $name
	 * @param bool $load
	 *
	 * @return string
	 */
	function popmake_get_template_part( $slug, $name = null, $load = true ) {
		if ( $load ) {
			return;
		}

		return __DIR__ . '/index.php';
	}
}
