<?php
/**
 * Functions for Admin Conditionals
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 *  Determines whether the current page is an popup maker admin page.
 *
 * @since 1.7.0
 *
 * @return bool
 */
function pum_is_admin_page() {
	if ( ! is_admin() || ! did_action( 'wp_loaded' ) ) {
		return false;
	}

	$typenow = pum_typenow();

	$tests = [
		'popup' === $typenow,
		'popup_theme' === $typenow,
		! empty( $GLOBALS['hook_suffix'] ) && in_array( $GLOBALS['hook_suffix'], PUM_Admin_Pages::$pages, true ),
	];

	return in_array( true, $tests, true );
}


/**
 * Determines whether the current admin page is the All Popups page.
 *
 * @since 1.12
 * @return bool True if current page is All Popups page.
 */
function pum_is_all_popups_page() {
	$screen = get_current_screen();

	$tests = [
		pum_is_admin_page(),
		'edit-popup' === $screen->id,
		pum_typenow() === 'popup',
	];

	return ! in_array( false, $tests, true );
}

/**
 * Determines whether the current admin page is the popup editor.
 *
 * @since 1.7.0
 *
 * @return bool
 */
function pum_is_popup_editor() {
	global $pagenow;

	$tests = [
		is_admin(),
		pum_is_admin_page(),
		'popup' === pum_typenow(),
		in_array( $pagenow, [ 'post-new.php', 'post.php' ], true ),
	];

	return ! in_array( false, $tests, true );
}

/**
 * Determines whether the current admin page is the popup theme editor.
 *
 * @since 1.7.0
 *
 * @return bool
 */
function pum_is_popup_theme_editor() {
	global $pagenow;

	$tests = [
		is_admin(),
		pum_is_admin_page(),
		'popup_theme' === pum_typenow(),
		in_array( $pagenow, [ 'post-new.php', 'post.php' ], true ),
	];

	return ! in_array( false, $tests, true );
}

/**
 * Determines whether the current admin page is the extensions page.
 *
 * @since 1.7.0
 *
 * @param null|string $key
 *
 * @return bool
 */
function pum_is_submenu_page( $key = null ) {
	$tests = [
		is_admin(),
		pum_is_admin_page(),
		! pum_is_popup_editor(),
		! pum_is_popup_theme_editor(),
		$key && ! empty( $GLOBALS['hook_suffix'] ) ? PUM_Admin_Pages::get_page( $key ) === $GLOBALS['hook_suffix'] : true,
		! isset( $key ) && ! empty( $GLOBALS['hook_suffix'] ) ? in_array( $GLOBALS['hook_suffix'], PUM_Admin_Pages::$pages, true ) : true,
	];

	return ! in_array( false, $tests, true );
}

/**
 * Determines whether the current admin page is the subscriptions page.
 *
 * @since 1.7.0
 *
 * @return bool
 */
function pum_is_subscriptions_page() {
	return pum_is_submenu_page( 'subscriptions' );
}

/**
 * Determines whether the current admin page is the extensions page.
 *
 * @since 1.7.0
 *
 * @return bool
 */
function pum_is_extensions_page() {
	return pum_is_submenu_page( 'extensions' );
}

/**
 * Determines whether the current admin page is the settings page.
 *
 * @since 1.7.0
 *
 * @return bool
 */
function pum_is_settings_page() {
	return pum_is_submenu_page( 'settings' );
}

/**
 * Determines whether the current admin page is the tools page.
 *
 * @since 1.7.0
 *
 * @return bool
 */
function pum_is_tools_page() {
	return pum_is_submenu_page( 'tools' );
}

/**
 * Determines whether the current admin page is the support page.
 *
 * @since 1.7.0
 *
 * @return bool
 */
function pum_is_support_page() {
	return pum_is_submenu_page( 'support' );
}
