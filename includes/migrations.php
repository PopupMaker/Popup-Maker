<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/


/**
 * Upgrade popup data to model v3.
 *
 * @param $popup PUM_Model_Popup
 */
function pum_popup_passive_migration_2 ( &$popup ) {

	$changed     = false;
	$delete_meta = array();

	// v1.7 Migrations
	$triggers = $popup->get_meta( 'popup_triggers' );
	if ( ! empty( $triggers ) ) {
		if ( ! empty( $triggers ) ) {
			$triggers = ! empty( $popup->settings['triggers'] ) && is_array( $popup->settings['triggers'] ) ? array_merge( $popup->settings['triggers'], $triggers ) : $triggers;

			foreach ( $triggers as $key => $trigger ) {
				if ( ! empty( $trigger['settings']['cookie']['name'] ) ) {
					$triggers[ $key ]['settings']['cookie_name'] = $trigger['settings']['cookie']['name'];
					unset( $triggers[ $key ]['settings']['cookie'] );
				}
			}

			$popup->settings['triggers'] = $triggers;
			$changed                    = true;
		}

		$delete_meta[] = 'popup_triggers';
	}

	$conditions = $popup->get_meta( 'popup_conditions' );
	if ( ! empty( $conditions ) ) {
		if ( ! empty( $conditions ) ) {
			$conditions = ! empty( $popup->settings['conditions'] ) && is_array( $popup->settings['conditions'] ) ? array_merge( $popup->settings['conditions'], $conditions ) : $conditions;

			foreach ( $conditions as $cg_key => $group ) {
				if ( ! empty( $group ) ) {
					foreach ( $group as $c_key => $condition ) {
						// Clean empty conditions.
						if ( empty( $condition['target'] ) ) {
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
			$changed                      = true;
		}

		$delete_meta[] = 'popup_conditions';
	}

	if ( $changed ) {
		$popup->update_meta( 'popup_settings', $popup->settings );
	}

	if ( ! empty( $delete_meta ) ) {
		foreach ( $delete_meta as $key ) {
			$popup->delete_meta( $key );
		}
	}
}
add_action( 'pum_popup_passive_migration_2', 'pum_popup_passive_migration_2' );