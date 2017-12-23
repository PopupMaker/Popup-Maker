<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/


/**
 * Upgrade popup data to model v3.
 *
 * @since 1.7.0
 *
 * @param $popup PUM_Model_Popup
 */
function pum_popup_passive_migration_2( &$popup ) {

	$changed     = false;
	$delete_meta = array();

	/**
	 * Migrate popup theme selection.
	 */
	$theme = $popup->get_meta( 'popup_theme' );
	if ( ! empty( $theme ) && is_numeric( $theme ) ) {
		$popup->settings['theme_id'] = absint( $theme );
		$changed                     = true;
		$delete_meta[]               = 'popup_theme';
	}

	/**
	 * Migrate popup_display meta data.
	 */
	$display = $popup->get_meta( 'popup_display' );
	if ( ! empty( $display ) && is_array( $display ) ) {
		$keys = $popup->remapped_meta_settings_keys( 'display' );

		// Foreach old key, save the value under popup settings for the new key.
		foreach ( $keys as $old_key => $new_key ) {
			if ( isset( $display[ $old_key ] ) ) {
				$popup->settings[ $new_key ] = $display[ $old_key ];
				$changed                     = true;
				unset( $display[ $old_key ] );
			}
		}

		if ( empty( $display ) ) {
			$delete_meta[] = 'popup_display';
		} else {
			// Update the saved popup display data with any remaining keys from extensions.
			$popup->update_meta( 'popup_display', $display );
		}
	}

	/**
	 * Migrate popup_close meta data
	 */
	$close = $popup->get_meta( 'popup_close' );
	if ( ! empty( $close ) && is_array( $close ) ) {
		$keys = $popup->remapped_meta_settings_keys( 'close' );

		// Foreach old key, save the value under popup settings for the new key.
		foreach ( $keys as $old_key => $new_key ) {
			if ( isset( $close[ $old_key ] ) ) {
				$popup->settings[ $new_key ] = $close[ $old_key ];
				$changed                     = true;
				unset( $close[ $old_key ] );
			}
		}

		if ( empty( $close ) ) {
			$delete_meta[] = 'popup_close';
		} else {
			// Update the saved popup close data with any remaining keys from extensions.
			$popup->update_meta( 'popup_close', $close );
		}
	}

	/**
	 * Migrate triggers.
	 */
	$triggers = $popup->get_meta( 'popup_triggers' );
	if ( ! empty( $triggers ) && is_array( $triggers ) ) {
		$triggers = ! empty( $popup->settings['triggers'] ) && is_array( $popup->settings['triggers'] ) ? array_merge( $popup->settings['triggers'], $triggers ) : $triggers;

		foreach ( $triggers as $key => $trigger ) {
			if ( isset( $trigger['settings']['cookie']['name'] ) ) {
				$triggers[ $key ]['settings']['cookie_name'] = $trigger['settings']['cookie']['name'];
				unset( $triggers[ $key ]['settings']['cookie'] );
			}
		}

		$popup->settings['triggers'] = $triggers;
		$changed                     = true;

		$delete_meta[] = 'popup_triggers';
	}

	/**
	 * Migrate cookies.
	 */
	$cookies = $popup->get_meta( 'popup_cookies' );
	if ( ! empty( $cookies ) && is_array( $cookies ) ) {
		$cookies                    = ! empty( $popup->settings['cookies'] ) && is_array( $popup->settings['cookies'] ) ? array_merge( $popup->settings['cookies'], $cookies ) : $cookies;
		$popup->settings['cookies'] = $cookies;
		$changed                    = true;
		$delete_meta[]              = 'popup_cookies';
	}

	/**
	 * Migrate conditions.
	 */
	$conditions = $popup->get_meta( 'popup_conditions' );
	if ( ! empty( $conditions ) && is_array( $conditions ) ) {
		$conditions = ! empty( $popup->settings['conditions'] ) && is_array( $popup->settings['conditions'] ) ? array_merge( $popup->settings['conditions'], $conditions ) : $conditions;

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

		$popup->settings['conditions'] = $conditions;
		$changed                       = true;
		$delete_meta[]                 = 'popup_conditions';
	}

	/**
	 * Migrate popup_mobile_disabled.
	 */
	$mobile_disabled = $popup->get_meta( 'popup_mobile_disabled' );
	if ( ! empty( $mobile_disabled ) ) {
		$popup->settings['disable_on_mobile'] = (bool) ( $mobile_disabled );
		$changed                              = true;
		$delete_meta[]                        = 'popup_mobile_disabled';
	}

	/**
	 * Migrate popup_tablet_disabled.
	 */
	$tablet_disabled = $popup->get_meta( 'popup_tablet_disabled' );
	if ( ! empty( $tablet_disabled ) ) {
		$popup->settings['disable_on_tablet'] = (bool) ( $tablet_disabled );
		$changed                              = true;
		$delete_meta[]                        = 'popup_tablet_disabled';
	}

	/**
	 * Migrate analytics reset keys.
	 */
	$open_count_reset = $popup->get_meta( 'popup_open_count_reset' );
	if ( ! empty( $open_count_reset ) && is_array( $open_count_reset ) ) {
		add_post_meta( $this->ID, 'popup_count_reset', array(
			'timestamp'   => ! empty( $open_count_reset['timestamp'] ) ? $open_count_reset['timestamp'] : '',
			'opens'       => ! empty( $open_count_reset['count'] ) ? absint( $open_count_reset['count'] ) : 0,
			'conversions' => 0,
		) );

		$delete_meta[] = 'popup_open_count_reset';
	}

	/**
	 * Save only if something changed.
	 */
	if ( $changed ) {
		$popup->update_meta( 'popup_settings', $popup->settings );
	}

	/**
	 * Clean up automatically.
	 */
	if ( ! empty( $delete_meta ) ) {
		foreach ( $delete_meta as $key ) {
			$popup->delete_meta( $key );
		}
	}
}

add_action( 'pum_popup_passive_migration_2', 'pum_popup_passive_migration_2' );