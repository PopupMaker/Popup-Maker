<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Get a popup model instance.
 *
 * @since 1.8.0
 *
 * @param int $popup_id
 *
 * @return PUM_Model_Popup
 */
function pum_get_popup( $popup_id = 0 ) {
	if ( ! $popup_id ) {
		$popup_id = pum_get_popup_id();
	}

	try {
		return PUM_Repository_Popups::instance()->get_item( $popup_id );
	} catch ( InvalidArgumentException $e ) {
		// Return empty object
		return new PUM_Model_Popup( $popup_id );
	}
}

/**
 * Queries popups and returns them in a specific format.
 *
 * @param array $args
 *
 * @return PUM_Model_Popup[]
 */
function pum_get_popups( $args = array() ) {
	return PUM_Repository_Popups::instance()->get_items( $args );
}

/**
 * Gets a count popups with specified args.
 *
 * @param array $args
 *
 * @return int
 */
function pum_count_popups( $args = array() ) {
	$args = wp_parse_args( $args, array(
		'post_status' => 'publish',
	) );

	return PUM_Repository_Popups::instance()->count_items( $args );
}
