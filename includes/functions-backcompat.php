<?php
/**
 * Functions for backward compatibility
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

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
function pum_merge_deprecated_settings_fields( $tabs = [] ) {
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

				if ( 'header' === $field_args['type'] ) {
					$field_args['type'] = 'separator';
				} elseif ( 'gaeventlabel' === $field_args['type'] ) {
					$field_args['type'] = 'ga_event_labels';
				} elseif ( 'hook' === $field_args['type'] ) {
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
