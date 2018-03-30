<?php
/**
 * General Functions
 *
 * @package      PUM
 * @subpackage   Functions/General
 * @copyright    Copyright (c) 2016, Daniel Iser
 * @license      http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since        1.4
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
 * Checks if the db_ver is v1.4 compatible.
 *
 * v1.4 compatibility is db_ver 6 or higher.
 *
 * @uses pum_get_db_ver()
 *
 * @return bool
 */
function pum_is_v1_4_compatible() {
	return true;
}

/**
 * Deletes the theme css transient forcing it to refresh.
 */
function pum_force_theme_css_refresh() {
	delete_transient( 'popmake_theme_styles' );
}