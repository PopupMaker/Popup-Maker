<?php
/**
 * Failsafes
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! function_exists( 'popmake_get_option' ) ) {
	/**
	 * @param string $key
	 * @param bool   $default
	 *
	 * @return bool
	 */
	function popmake_get_option( $key = '', $default = false ) {
		return $default;
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
