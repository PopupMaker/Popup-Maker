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


function popmake_get_default_popup_theme() {
	static $default_theme = null;

	if ( $default_theme === null ) {
		$default_theme = get_option( 'popmake_default_theme' );
	}

	if ( ! $default_theme ) {
		if ( ! function_exists( 'popmake_install_default_theme' ) ) {
			include_once POPMAKE_DIR . 'includes/install.php';
		}
		popmake_install_default_theme();
		$default_theme = get_option( 'popmake_default_theme' );
		pum_force_theme_css_refresh();
	}

	return $default_theme;
}


function popmake_get_all_popup_themes() {
	static $themes;

	if ( ! $themes ) {
		$query = new WP_Query( array(
			'post_type'              => 'popup_theme',
			'post_status'            => 'publish',
			'posts_per_page'         => - 1,
			// Performance Optimization.
			'update_post_term_cache' => false,
			'no_found_rows'          => true,
		) );

		$themes = $query->posts;
	}

	return $themes;
}

