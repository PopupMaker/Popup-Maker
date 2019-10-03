<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds an upgrade action to the completed upgrades array
 *
 * @since 1.7.0
 *
 * @see PUM_Utils_Upgrades::set_upgrade_complete
 *
 * @param  string $upgrade_id The action to add to the competed upgrades array
 *
 * @return bool If the function was successfully added
 */
function pum_set_upgrade_complete( $upgrade_id = '' ) {
	return PUM_Utils_Upgrades::instance()->set_upgrade_complete( $upgrade_id );
}

/**
 * Get's the array of completed upgrade actions
 *
 * @since 1.7.0
 *
 * @return array The array of completed upgrades
 */
function pum_get_completed_upgrades() {
	return PUM_Utils_Upgrades::instance()->get_completed_upgrades();
}

/**
 * Check if the upgrade routine has been run for a specific action
 *
 * @since 1.7.0
 *
 * @param  string $upgrade_id The upgrade action to check completion for
 *
 * @return bool  If the action has been added to the completed actions array
 */
function pum_has_completed_upgrade( $upgrade_id = '' ) {
	return PUM_Utils_Upgrades::instance()->has_completed_upgrade( $upgrade_id );
}

/**
 * Clean up postmeta by removing all keys from the given post_id.
 *
 * @param int   $post_id
 * @param array $keys_to_delete
 */
function pum_cleanup_post_meta_keys( $post_id = 0, $keys_to_delete = array() ) {
	/**
	 * Clean up automatically.
	 */
	if ( ! empty( $keys_to_delete ) ) {
		global $wpdb;

		$keys_to_delete = array_map( 'esc_sql', (array) $keys_to_delete );
		$meta_keys = implode( "','", $keys_to_delete );

		$query = $wpdb->prepare( "DELETE FROM `$wpdb->postmeta` WHERE `post_id` = %d AND `meta_key` IN ('{$meta_keys}')", $post_id );

		$wpdb->query( $query );

		wp_cache_delete( $post_id, 'post_meta' );
	}

}
