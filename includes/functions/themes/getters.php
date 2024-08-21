<?php
/**
 * Functions for Theme Getters
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Return the theme id.
 *
 * @param int $theme_id
 *
 * @return int
 */
function pum_get_theme_id( $theme_id = 0 ) {
	if ( ! empty( $theme_id ) && is_numeric( $theme_id ) ) {
		$_theme_id = $theme_id;
	} elseif ( is_object( pum()->current_theme ) && is_numeric( pum()->current_theme->ID ) ) {
		$_theme_id = pum()->current_theme->ID;
	} else {
		$_theme_id = 0;
	}

	return (int) apply_filters( 'pum_get_theme_id', (int) $_theme_id, $theme_id );
}

/**
 * @param int $theme_id
 *
 * @return array
 */
function pum_get_theme_generated_styles( $theme_id = 0 ) {
	$theme = pum_get_theme( $theme_id );

	if ( ! pum_is_theme_object( $theme ) ) {
		return [];
	}

	return $theme->get_generated_styles();
}
