<?php
/**
 * Functions for Theme Migrations
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

/**
 * Checks if passive migration for popups should be enabled.
 *
 * This determines if the query load may be potentially too high to run passive migrations on live servers.
 *
 * @return bool
 */
function pum_passive_theme_upgrades_enabled() {
	/** @var int $theme_count */
	static $theme_count;

	if ( defined( 'PUM_DISABLE_PASSIVE_UPGRADES' ) && PUM_DISABLE_PASSIVE_UPGRADES ) {
		return false;
	}

	if ( ! $theme_count ) {
		$theme_count = get_transient( 'pum_theme_count' );

		if ( false === $theme_count ) {
			$theme_count = pum_count_themes(
				[
					'post_status' => [ 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ],
				]
			);

			set_transient( 'pum_theme_count', $theme_count, MINUTE_IN_SECONDS );
		}
	}

	return pum_is_popup_theme_editor() || $theme_count <= apply_filters( 'pum_passive_themes_enabled_max_count', 10 );
}

/**
 * Upgrade popup data to model v2.
 *
 * @since 1.8.0
 *
 * @param PUM_Model_Theme $theme
 */
function pum_theme_migration_1( &$theme ) {

	$delete_meta = [ 'popup_theme_defaults_set' ];

	// Used to merge with existing values to ensure data integrity.
	$meta_defaults = pum_get_theme_v2_meta_defaults();

	foreach ( array_keys( $meta_defaults ) as $group ) {
		// Get old data.
		$v1_meta_values = pum_get_theme_v1_meta( $group, $theme->ID );

		// Loop over all fields which were merged and mark their meta keys for deletion.
		foreach ( $v1_meta_values as $old_meta_key => $old_meta_value ) {
			$delete_meta[] = "popup_theme_{$group}_{$old_meta_key}";
		}

		$existing_v2_meta = $theme->get_meta( "popup_theme_{$group}" );

		if ( ! empty( $existing_v2_meta ) ) {
			continue;
		}

		// Merge defaults.
		$values = wp_parse_args( $v1_meta_values, $meta_defaults[ $group ] );

		// Update meta storage.
		$theme->update_meta( "popup_theme_{$group}", $values );
	}

	/**
	 * Clean up automatically.
	 */
	pum_cleanup_post_meta_keys( $theme->ID, $delete_meta );
}

add_action( 'pum_theme_passive_migration_1', 'pum_theme_migration_1' );

/**
 * Upgrade popup data to model v3.
 *
 * @since 1.8.0
 *
 * @param PUM_Model_Theme $theme
 */
function pum_theme_migration_2( &$theme ) {

	$changed     = false;
	$delete_meta = [];

	$settings = $theme->get_settings();

	$old_meta_elements = [
		'overlay',
		'container',
		'title',
		'content',
		'close',
	];

	foreach ( $old_meta_elements as $element ) {
		$meta_key = 'popup_theme_' . $element;

		/**
		 * Migrate popup_theme_overlay meta data.
		 */
		$element_data = $theme->get_meta( $meta_key );
		if ( ! empty( $element_data ) && is_array( $element_data ) ) {
			$keys = $theme->remapped_meta_settings_keys( $element );

			// Foreach old key, save the value under popup settings for the new key.
			foreach ( $keys as $old_key => $new_key ) {
				if ( isset( $element_data[ $old_key ] ) ) {
					$settings[ $new_key ] = $element_data[ $old_key ];
					$changed              = true;
					unset( $element_data[ $old_key ] );
				}
			}

			if ( empty( $element_data ) ) {
				$delete_meta[] = $meta_key;
			} else {
				// Update the saved popup display data with any remaining keys from extensions.
				$theme->update_meta( $meta_key, $element_data );
			}
		}
	}

	/**
	 * Save only if something changed.
	 */
	if ( $changed ) {
		$theme->update_meta( 'popup_theme_settings', $settings );
	}

	/**
	 * Clean up automatically.
	 */
	pum_cleanup_post_meta_keys( $theme->ID, $delete_meta );
}

add_action( 'pum_theme_passive_migration_2', 'pum_theme_migration_2' );
