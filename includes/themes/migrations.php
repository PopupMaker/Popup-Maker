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
				'post_status'    => array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ),
			) );

			set_transient( 'pum_theme_count', $theme_count, HOUR_IN_SECONDS * 24 );
		}
	}

	return $theme_count > apply_filters( 'pum_passive_themes_enabled_max_count', 5 );
}

/**
 * Upgrade popup data to model v3.
 *
 * @since 1.8.0
 *
 * @param $theme PUM_Model_Popup
 */
function pum_theme_migration_2( &$theme ) {

	$changed     = false;
	$delete_meta = array();

	$settings = $theme->get_settings();

	/**
	 * Migrate popup_display meta data.
	 */
	$display = $theme->get_meta( 'popup_display' );
	if ( ! empty( $display ) && is_array( $display ) ) {
		$keys = $theme->remapped_meta_settings_keys( 'display' );

		// Foreach old key, save the value under popup settings for the new key.
		foreach ( $keys as $old_key => $new_key ) {
			if ( isset( $display[ $old_key ] ) && ! empty( $display[ $old_key ] ) ) {
				$settings[ $new_key ] = $display[ $old_key ];
				$changed                     = true;
				unset( $display[ $old_key ] );

				if ( in_array( $old_key, array(
						'responsive_min_width',
						'responsive_max_width',
						'custom_width',
						'custom_height',
					) ) && isset( $display[ $old_key . '_unit' ] ) ) {
					$settings[ $new_key ] .= $display[ $old_key . '_unit' ];
					unset( $display[ $old_key . '_unit' ] );
				}
			}
		}

		if ( empty( $display ) ) {
			$delete_meta[] = 'popup_display';
		} else {
			// Update the saved popup display data with any remaining keys from extensions.
			$theme->update_meta( 'popup_display', $display );
		}
	}

	/**
	 * Migrate popup_close meta data
	 */
	$close = $theme->get_meta( 'popup_close' );
	if ( ! empty( $close ) && is_array( $close ) ) {
		$keys = $theme->remapped_meta_settings_keys( 'close' );

		// Foreach old key, save the value under popup settings for the new key.
		foreach ( $keys as $old_key => $new_key ) {
			if ( isset( $close[ $old_key ] ) ) {
				$settings[ $new_key ] = $close[ $old_key ];
				$changed                     = true;
				unset( $close[ $old_key ] );
			}
		}

		if ( empty( $close ) ) {
			$delete_meta[] = 'popup_close';
		} else {
			// Update the saved popup close data with any remaining keys from extensions.
			$theme->update_meta( 'popup_close', $close );
		}
	}

	/**
	 * Migrate triggers.
	 */
	$triggers = $theme->get_meta( 'popup_triggers' );
	if ( ! empty( $triggers ) && is_array( $triggers ) ) {
		$triggers = ! empty( $settings['triggers'] ) && is_array( $settings['triggers'] ) ? array_merge( $settings['triggers'], $triggers ) : $triggers;

		foreach ( $triggers as $key => $trigger ) {
			if ( isset( $trigger['settings']['cookie']['name'] ) ) {
				$triggers[ $key ]['settings']['cookie_name'] = $trigger['settings']['cookie']['name'];
				unset( $triggers[ $key ]['settings']['cookie'] );
			}
		}

		$settings['triggers'] = $triggers;
		$changed                     = true;

		$delete_meta[] = 'popup_triggers';
	}

	/**
	 * Migrate cookies.
	 */
	$cookies = $theme->get_meta( 'popup_cookies' );
	if ( ! empty( $cookies ) && is_array( $cookies ) ) {
		$cookies                    = ! empty( $settings['cookies'] ) && is_array( $settings['cookies'] ) ? array_merge( $settings['cookies'], $cookies ) : $cookies;
		$settings['cookies'] = $cookies;
		$changed                    = true;
		$delete_meta[]              = 'popup_cookies';
	}

	/**
	 * Migrate conditions.
	 */
	$conditions = $theme->get_meta( 'popup_conditions' );
	if ( ! empty( $conditions ) && is_array( $conditions ) ) {
		$conditions = ! empty( $settings['conditions'] ) && is_array( $settings['conditions'] ) ? array_merge( $settings['conditions'], $conditions ) : $conditions;

		foreach ( $conditions as $cg_key => $group ) {
			if ( ! empty( $group ) ) {
				foreach ( $group as $c_key => $condition ) {

					// Clean empty conditions.
					if ( ! empty( $condition['target'] ) ) {
						$fixed_condition = array(
							'target'      => $condition['target'],
							'not_operand' => isset( $condition['not_operand'] ) ? (bool) $condition['not_operand'] : false,
							'settings'    => isset( $condition['settings'] ) ? $condition['settings'] : array(),
						);

						foreach ( $condition as $key => $val ) {
							if ( ! in_array( $key, array( 'target', 'not_operand', 'settings' ) ) ) {
								$fixed_condition['settings'][ $key ] = $val;
							}
						}

						$conditions[ $cg_key ][ $c_key ] = $fixed_condition;
					} else {
						unset( $conditions[ $cg_key ][ $c_key ] );
					}
				}

				// Clean empty groups.
				if ( empty( $conditions[ $cg_key ] ) ) {
					unset( $conditions[ $cg_key ] );
				}
			}
		}

		$settings['conditions'] = $conditions;
		$changed                       = true;
		$delete_meta[]                 = 'popup_conditions';
	}

	/**
	 * Migrate popup_mobile_disabled.
	 */
	$mobile_disabled = $theme->get_meta( 'popup_mobile_disabled' );
	if ( ! empty( $mobile_disabled ) ) {
		$settings['disable_on_mobile'] = (bool) ( $mobile_disabled );
		$changed                              = true;
		$delete_meta[]                        = 'popup_mobile_disabled';
	}

	/**
	 * Migrate popup_tablet_disabled.
	 */
	$tablet_disabled = $theme->get_meta( 'popup_tablet_disabled' );
	if ( ! empty( $tablet_disabled ) ) {
		$settings['disable_on_tablet'] = (bool) ( $tablet_disabled );
		$changed                              = true;
		$delete_meta[]                        = 'popup_tablet_disabled';
	}

	/**
	 * Migrate analytics reset keys.
	 */
	$open_count_reset = $theme->get_meta( 'popup_open_count_reset', false );
	if ( ! empty( $open_count_reset ) && is_array( $open_count_reset ) ) {
		foreach ( $open_count_reset as $key => $reset ) {
			if ( is_array( $reset ) ) {
				add_post_meta( $theme->ID, 'popup_count_reset', array(
					'timestamp'   => ! empty( $reset['timestamp'] ) ? $reset['timestamp'] : '',
					'opens'       => ! empty( $reset['count'] ) ? absint( $reset['count'] ) : 0,
					'conversions' => 0,
				) );
			}
		}

		$delete_meta[] = 'popup_open_count_reset';
	}

	/**
	 * Save only if something changed.
	 */
	if ( $changed ) {
		$theme->update_meta( 'popup_settings', $settings );
	}

	/**
	 * Clean up automatically.
	 */
	if ( ! empty( $delete_meta ) ) {
		foreach ( $delete_meta as $key ) {
			$theme->delete_meta( $key );
		}
	}
}

add_action( 'pum_theme_passive_migration_2', 'pum_theme_migration_2' );
