<?php
/**
 * Call To Action utility & helper functions.
 *
 * @author    Code Atlantic LLC
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker;

defined( 'ABSPATH' ) || exit;

use function PopupMaker\plugin;

/**
 * Get call to action by ID.
 *
 * @param int $cta_id Call to action ID.
 *
 * @return \PopupMaker\Models\CallToAction|null
 */
function get_cta_by_id( $cta_id = 0 ) {
	return plugin( 'ctas' )->get_by_id( $cta_id );
}

/**
 * Get call to action by UUID.
 *
 * @param string $cta_uuid Call to action UUID.
 *
 * @return \PopupMaker\Models\CallToAction|null
 */
function get_cta_by_uuid( $cta_uuid = '' ) {
	return plugin( 'ctas' )->get_by_uuid( $cta_uuid );
}

/**
 * Generate a unique call to action UUID.
 *
 * @param int $cta_id Call to action ID.
 *
 * @return string
 */
function generate_unique_cta_uuid( $cta_id = 0 ) {
	/**
	 * Filter for generating your own call to action UUID.
	 *
	 * @param string $uuid Call to action UUID.
	 * @param int $cta_id Call to action ID.
	 *
	 * @return string
	 */
	$override = apply_filters( 'popup_maker/generate_cta_uuid', null, $cta_id );

	if ( is_string( $override ) && '' !== $override ) {
		return $override;
	}

	global $wpdb;

	// Try up to 3 times to generate a unique UUID
	for ( $i = 0; $i < 3; $i++ ) {
		$uuid = \PopupMaker\generate_uuid();

		// Check existence efficiently with direct SQL query as it's more performant.
		$exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key = 'cta_uuid' AND meta_value = %s LIMIT 1",
				$uuid
			)
		);

		if ( ! $exists ) {
			return $uuid;
		}
	}

	// If we still don't have a unique UUID after 3 tries, make it unique by adding the post ID
	return \PopupMaker\generate_uuid( 'cta_' ) . '_' . $cta_id;
}
