<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


add_filter( 'pum_settings_fields', 'pum_merge_deprecated_settings_fields' );

/**
 * Merge old deprecated settings from extensions into the new settings API.
 *
 * @since      1.7.0
 * @deprecated 1.7.0
 *
 * @param array $tabs
 *
 * @return array
 */
function pum_merge_deprecated_settings_fields( $tabs = array() ) {
	/**
	 * Apply @deprecated filters & process old fields for compatibility.
	 */
	$old_fields = popmake_get_registered_settings();

	$old_fields = array_map( 'array_filter', $old_fields );
	$old_fields = array_filter( $old_fields );

	if ( ! empty( $old_fields ) ) {
		foreach ( $old_fields as $tab_id => $fields ) {
			foreach ( $fields as $field_id => $field_args ) {
				if ( is_numeric( $field_id ) && ! empty( $field_args['id'] ) ) {
					$field_id = $field_args['id'];
					unset( $field_args['id'] );
				}

				$field_args['label'] = ! empty( $field_args['name'] ) ? $field_args['name'] : '';

				if ( $field_args['type'] == 'header' ) {
					$field_args['type'] = 'separator';
				} else if ( $field_args['type'] == 'gaeventlabel' ) {
					$field_args['type'] = 'ga_event_labels';
				} else if ( $field_args['type'] == 'hook' ) {
					$field_args['type'] = 'html';

					ob_start();

					do_action( 'popmake_' . $field_id );

					$field_args['content'] = ob_get_clean();
				}

				unset( $field_args['name'] );
				$tabs[ array_key_exists( $tab_id, $tabs ) ? $tab_id : 'general' ]['main'][ $field_id ] = $field_args;
			}
		}
	}

	return $tabs;
}
