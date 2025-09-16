<?php
/**
 * Functions for Upgrades Utility
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds an upgrade action to the completed upgrades array
 *
 * @see PUM_Utils_Upgrades::set_upgrade_complete
 *
 * @param  string $upgrade_id The action to add to the competed upgrades array
 *
 * @return void If the function was successfully added
 *
 * @since 1.7.0
 * @deprecated 1.21.0 Use \PopupMaker\mark_upgrade_complete() instead.
 */
function pum_set_upgrade_complete( $upgrade_id = '' ) {
	\PopupMaker\mark_upgrade_complete( $upgrade_id );
}

/**
 * Get's the array of completed upgrade actions
 *
 * @return array The array of completed upgrades
 *
 * @since 1.7.0
 * @deprecated 1.21.0 Use \PopupMaker\get_completed_upgrades() instead.
 */
function pum_get_completed_upgrades() {
	return \PopupMaker\get_completed_upgrades();
}

/**
 * Check if the upgrade routine has been run for a specific action
 *
 * @param  string $upgrade_id The upgrade action to check completion for
 *
 * @return bool  If the action has been added to the completed actions array
 *
 * @since 1.7.0
 * @deprecated 1.21.0 Use \PopupMaker\is_upgrade_complete() instead.
 */
function pum_has_completed_upgrade( $upgrade_id = '' ) {
	return \PopupMaker\is_upgrade_complete( $upgrade_id );
}

/**
 * Clean up postmeta by removing all keys from the given post_id.
 *
 * @param int   $post_id
 * @param array $keys_to_delete
 */
function pum_cleanup_post_meta_keys( $post_id = 0, $keys_to_delete = [] ) {
	/**
	 * Clean up automatically.
	 */
	if ( ! empty( $keys_to_delete ) ) {
		global $wpdb;

		$keys_to_delete = array_map( 'esc_sql', (array) $keys_to_delete );
		$meta_keys      = implode( "','", $keys_to_delete );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM `$wpdb->postmeta` WHERE `post_id` = %d AND `meta_key` IN ('{$meta_keys}')",
				$post_id
			)
		);

		wp_cache_delete( $post_id, 'post_meta' );
	}
}
