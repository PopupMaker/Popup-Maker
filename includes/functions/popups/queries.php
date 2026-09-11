<?php
/**
 * Functions for Popup Queries
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Get a popup model instance.
 *
 * @param int $popup_id
 *
 * @return PUM_Model_Popup
 */
function pum_get_popup( $popup_id = null ) {
	if ( ( is_null( $popup_id ) || 0 === $popup_id ) && pum_is_popup( pum()->current_popup ) ) {
		return pum()->current_popup;
	}

	/** @var int $popup_id filtered $popup_id */
	$popup_id         = pum_get_popup_id( $popup_id );
	$popup_controller = null;

	if ( ! is_admin() ) {
		$popup_controller = \PopupMaker\plugin()->get_controller( 'Frontend\\Popups' );
		$queried_popup    = $popup_controller ? $popup_controller->get_queried_popup( $popup_id ) : null;

		if ( pum_is_popup( $queried_popup ) ) {
			return $queried_popup;
		}
	}

	try {
		$popup = pum()->popups->get_item( $popup_id );
	} catch ( InvalidArgumentException $e ) {
		// Return empty object
		$popup = new PUM_Model_Popup( $popup_id );
	}

	if ( ! is_admin() && $popup_controller && pum_is_popup( $popup ) ) {
		$popup_controller->cache_queried_popup( $popup );
	}

	return $popup;
}

/**
 * Queries popups and returns them in a specific format.
 *
 * @param array $args
 *
 * @return PUM_Model_Popup[]
 */
function pum_get_popups( $args = [] ) {
	return pum()->popups->get_items( $args );
}

/**
 * Queries popups and returns them in a specific format.
 *
 * @param array $args
 *
 * @return PUM_Model_Popup[]
 */
function pum_get_all_popups( $args = [] ) {
	$args['posts_per_page'] = -1;

	return pum_get_popups( $args );
}

/**
 * Gets a count popups with specified args.
 *
 * @param array $args
 *
 * @return int
 */
function pum_count_popups( $args = [] ) {
	$args = wp_parse_args(
		$args,
		[
			'post_status' => 'publish',
		]
	);

	return pum()->popups->count_items( $args );
}
