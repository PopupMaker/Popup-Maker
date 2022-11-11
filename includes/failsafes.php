<?php
/**
 * Failsafes
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */

if ( ! function_exists( 'popmake_get_option' ) ) {
	/**
	 * Gets option
	 *
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
	 * Returns false for popmake admin page
	 *
	 * @return bool
	 */
	function popmake_is_admin_page() {
		return false;
	}
}

if ( ! function_exists( 'pum_is_admin_page' ) ) {
	/**
	 * Returns false for pum admin page
	 *
	 * @return bool
	 */
	function pum_is_admin_page() {
		return false;
	}
}

if ( ! function_exists( 'popmake_is_admin_popup_page' ) ) {
	/**
	 * Returns false for admin popup page
	 *
	 * @return bool
	 */
	function popmake_is_admin_popup_page() {
		return false;
	}
}

if ( ! function_exists( 'pum_is_popup_editor' ) ) {
	/**
	 * Returns false for popup editor
	 *
	 * @return bool
	 */
	function pum_is_popup_editor() {
		return false;
	}
}

if ( ! function_exists( 'pum_is_settings_page' ) ) {
	/**
	 * Returns false for settings page
	 *
	 * @return bool
	 */
	function pum_is_settings_page() {
		return false;
	}
}

if ( ! function_exists( 'popmake_get_template_part' ) ) {
	/**
	 * Gets template part
	 *
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
