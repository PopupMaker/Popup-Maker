<?php
/**
 * Functions for Theme Queries
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Get a theme model instance.
 *
 * @param int $theme_id
 *
 * @return PUM_Model_Theme
 */
function pum_get_theme( $theme_id = 0 ) {
	if ( ! $theme_id ) {
		$theme_id = pum_get_theme_id();
	}

	try {
		return pum()->themes->get_item( $theme_id );
	} catch ( InvalidArgumentException $e ) {
		// Return empty object
		return new PUM_Model_Theme( $theme_id );
	}
}

/**
 * Queries themes and returns them in a specific format.
 *
 * @param array $args
 *
 * @return PUM_Model_Theme[]
 */
function pum_get_themes( $args = [] ) {
	return pum()->themes->get_items( $args );
}

/**
 * Queries themes and returns them in a specific format.
 *
 * @param array $args
 *
 * @return PUM_Model_Theme[]
 */
function pum_get_all_themes( $args = [] ) {
	$args['posts_per_page'] = -1;

	return pum_get_themes( $args );
}

/**
 * Gets a count themes with specified args.
 *
 * @param array $args
 *
 * @return int
 */
function pum_count_themes( $args = [] ) {
	$args = wp_parse_args(
		$args,
		[
			'post_status' => 'publish',
		]
	);

	return pum()->themes->count_items( $args );
}
