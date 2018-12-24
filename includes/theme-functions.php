<?php
/**
 * Popup Theme Functions
 *
 * @package        POPMAKE
 * @subpackage  Functions
 * @copyright   Copyright (c) 2014, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
			if ( ! function_exists( 'popmake_install_default_theme' ) ) {
				include_once POPMAKE_DIR . 'includes/install.php';
			}

			$default_theme_id = pum_install_default_theme();
			pum_update_option( 'default_theme_id', $default_theme_id );
		}
	}

	$theme = absint( $default_theme_id ) ? pum_get_theme( $default_theme_id ) : false;

	return $theme && pum_is_theme( $theme ) ? $theme->ID : false;
}
