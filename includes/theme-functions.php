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


/**
 * Returns the meta group of a theme or value if key is set.
 *
 * @since 1.0
 *
 * @param int $popup_theme_id ID number of the popup to retrieve a overlay meta for
 *
 * @return mixed array|string of the popup overlay meta
 */
function popmake_get_popup_theme_meta_group( $group, $popup_theme_id = null, $key = null, $default = null ) {
	if ( ! $popup_theme_id ) {
		$popup_theme_id = get_the_ID();
	}

	$post_meta    = get_post_custom( $popup_theme_id );

	if ( ! is_array( $post_meta ) ) {
		$post_meta = array();
	}

	$default_check_key = 'popup_theme_defaults_set';
	if ( ! in_array( $group, array( 'overlay', 'close', 'display', 'targeting_condition' ) ) ) {
		$default_check_key = "popup_{$group}_defaults_set";
	}

	$group_values = array_key_exists( $default_check_key, $post_meta ) ? array() : apply_filters( "popmake_popup_theme_{$group}_defaults", array() );
	foreach ( $post_meta as $meta_key => $value ) {
		if ( strpos( $meta_key, "popup_theme_{$group}_" ) !== false ) {
			$new_key = str_replace( "popup_theme_{$group}_", '', $meta_key );
			if ( count( $value ) == 1 ) {
				$group_values[ $new_key ] = $value[0];
			} else {
				$group_values[ $new_key ] = $value;
			}
		}
	}
	if ( $key ) {
		$key = str_replace( '.', '_', $key );
		if ( ! isset( $group_values[ $key ] ) ) {
			$value = $default;
		} else {
			$value = $group_values[ $key ];
		}

		return apply_filters( "popmake_get_popup_theme_{$group}_$key", $value, $popup_theme_id );
	} else {
		return apply_filters( "popmake_get_popup_theme_{$group}", $group_values, $popup_theme_id );
	}
}

