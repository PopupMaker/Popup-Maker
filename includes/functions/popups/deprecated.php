<?php
/**
 * Functions for Deprecated Popups
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

/**
 * Returns a popup object.
 *
 * @deprecated 1.7
 *
 * @param null $popup_id
 *
 * @return false|PUM_Model_Popup
 */
function pum_popup( $popup_id = null ) {
	return pum_get_popup( $popup_id );
}

/**
 * Returns the meta group of a popup or value if key is set.
 *
 * @since 1.3.0
 * @deprecated 1.4
 *
 * @param $group
 * @param int   $popup_id ID number of the popup to retrieve a overlay meta for
 * @param null  $key
 * @param null  $default_value
 *
 * @return mixed array|string
 */
function popmake_get_popup_meta( $group, $popup_id = null, $key = null, $default_value = null ) {
	if ( ! $popup_id ) {
		$popup_id = pum_get_popup_id();
	}

	$values = get_post_meta( $popup_id, "popup_{$group}", true );

	if ( ! $values ) {
		$defaults = apply_filters( "popmake_popup_{$group}_defaults", [] );
		$values   = array_merge( $defaults, popmake_get_popup_meta_group( $group, $popup_id ) );
	} else {
		$values = array_merge( popmake_get_popup_meta_group( $group, $popup_id ), $values );
	}

	if ( $key ) {

		// Check for dot notation key value.
		$test  = uniqid();
		$value = popmake_resolve( $values, $key, $test );
		if ( $value === $test ) {
			$key = str_replace( '.', '_', $key );

			if ( ! isset( $values[ $key ] ) ) {
				$value = $default_value;
			} else {
				$value = $values[ $key ];
			}
		}

		return apply_filters( "popmake_get_popup_{$group}_$key", $value, $popup_id );
	} else {
		return apply_filters( "popmake_get_popup_{$group}", $values, $popup_id );
	}
}

/**
 * Returns the meta group of a popup or value if key is set.
 *
 * @since 1.0
 * @deprecated 1.3.0
 *
 * @param int $group ID number of the popup to retrieve a overlay meta for
 *
 * @return mixed array|string
 */
function popmake_get_popup_meta_group( $group, $popup_id = null, $key = null, $default_value = null ) {
	if ( ! $popup_id || 'secure_logout' === $group ) {
		$popup_id = pum_get_popup_id();
	}

	$post_meta = get_post_custom( $popup_id );

	if ( ! is_array( $post_meta ) ) {
		$post_meta = [];
	}

	$default_check_key = 'popup_defaults_set';
	if ( ! in_array( $group, [ 'auto_open', 'close', 'display', 'targeting_condition' ], true ) ) {
		$default_check_key = "popup_{$group}_defaults_set";
	}

	$group_values = array_key_exists( $default_check_key, $post_meta ) ? [] : apply_filters( "popmake_popup_{$group}_defaults", [] );
	foreach ( $post_meta as $meta_key => $value ) {
		if ( strpos( $meta_key, "popup_{$group}_" ) !== false ) {
			$new_key = str_replace( "popup_{$group}_", '', $meta_key );
			if ( count( $value ) === 1 ) {
				$group_values[ $new_key ] = $value[0];
			} else {
				$group_values[ $new_key ] = $value;
			}
		}
	}
	if ( $key ) {
		$key = str_replace( '.', '_', $key );
		if ( ! isset( $group_values[ $key ] ) ) {
			$value = $default_value;
		} else {
			$value = $group_values[ $key ];
		}

		return apply_filters( "popmake_get_popup_{$group}_$key", $value, $popup_id );
	} else {
		return apply_filters( "popmake_get_popup_{$group}", $group_values, $popup_id );
	}
}
