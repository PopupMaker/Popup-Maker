<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

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

		if ( $theme_count === false ) {
			$theme_count = pum_count_themes( array(
				'post_status' => array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ),
			) );

			set_transient( 'pum_theme_count', $theme_count, HOUR_IN_SECONDS * 24 );
		}
	}

	return pum_is_popup_theme_editor() || $theme_count <= apply_filters( 'pum_passive_themes_enabled_max_count', 5 );
}

/**
 * Upgrade popup data to model v3.
 *
 * @since 1.8.0
 *
 * @param PUM_Model_Theme $theme
 */
function pum_theme_migration_2( &$theme ) {

	$changed     = false;
	$delete_meta = array();

	$settings = $theme->get_settings();

	$old_meta_elements = array(
		'overlay',
		'container',
		'title',
		'content',
		'close',
	);

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
				if ( isset( $element_data[ $old_key ] ) && ! empty( $element_data[ $old_key ] ) ) {
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
	if ( ! empty( $delete_meta ) ) {
		foreach ( $delete_meta as $key ) {
			//$theme->delete_meta( $key );
		}
	}
}

add_action( 'pum_theme_passive_migration_2', 'pum_theme_migration_2' );
