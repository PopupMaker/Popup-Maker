<?php
/**
 * General Functions
 *
 * @package      PUM
 * @subpackage   Functions/General
 * @copyright    Copyright (c) 2016, Daniel Iser
 * @license      http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since        1.4.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns true if is a multisite install.
 *
 * @since 1.4.0
 *
 * @return bool
 */
function pum_is_network() {
	return function_exists( 'is_multisite' ) && is_multisite();
}

/**
 * Returns true if page is in network admin.
 *
 * @since 1.4.0
 *
 * @return bool
 */
function pum_is_network_admin() {
	return pum_is_network() && is_network_admin();
}

/**
 * Returns the current pum dv version or false.
 *
 * If network parameter is true and is multisite then returns network db version.
 *
 * @since 1.4.0
 *
 * @param bool $network
 *
 * @return mixed|void
 */
function pum_get_db_ver( $network = false ) {
	if ( pum_is_network() && $network ) {
		return get_site_option( 'pum_db_ver', false );
	}

	return get_option( 'pum_db_ver', false );
}

function pum_force_theme_css_refresh() {
	delete_transient( 'popmake_theme_styles' );
}