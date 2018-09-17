<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



#region Deprecated & Soon to Be Deprecated Functions

/**
 * @return int
 */
function popmake_get_the_popup_ID() {
	global $popup;

	return $popup ? $popup->ID : 0;
}

/**
 * Returns the meta group of a popup or value if key is set.
 *
 * @since 1.3.0
 * @deprecated 1.4
 *
 * @param $group
 * @param int $popup_id ID number of the popup to retrieve a overlay meta for
 * @param null $key
 * @param null $default
 *
 * @return mixed array|string
 */
function popmake_get_popup_meta( $group, $popup_id = null, $key = null, $default = null ) {
	if ( ! $popup_id ) {
		$popup_id = popmake_get_the_popup_ID();
	}

	$values = get_post_meta( $popup_id, "popup_{$group}", true );

	if ( ! $values ) {
		$defaults = apply_filters( "popmake_popup_{$group}_defaults", array() );
		$values = array_merge( $defaults, popmake_get_popup_meta_group( $group, $popup_id ) );
	} else {
		$values = array_merge( popmake_get_popup_meta_group( $group, $popup_id ), $values );
	}

	if ( $key ) {

		// Check for dot notation key value.
		$test  = uniqid();
		$value = popmake_resolve( $values, $key, $test );
		if ( $value == $test ) {

			$key = str_replace( '.', '_', $key );

			if ( ! isset( $values[ $key ] ) ) {
				$value = $default;
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
 * @param int $popup_id ID number of the popup to retrieve a overlay meta for
 *
 * @return mixed array|string
 */
function popmake_get_popup_meta_group( $group, $popup_id = null, $key = null, $default = null ) {
	if ( ! $popup_id || $group === 'secure_logout') {
		$popup_id = popmake_get_the_popup_ID();
	}

	$post_meta         = get_post_custom( $popup_id );

	if ( ! is_array( $post_meta ) ) {
		$post_meta = array();
	}

	$default_check_key = 'popup_defaults_set';
	if ( ! in_array( $group, array( 'auto_open', 'close', 'display', 'targeting_condition' ) ) ) {
		$default_check_key = "popup_{$group}_defaults_set";
	}

	$group_values = array_key_exists( $default_check_key, $post_meta ) ? array() : apply_filters( "popmake_popup_{$group}_defaults", array() );
	foreach ( $post_meta as $meta_key => $value ) {
		if ( strpos( $meta_key, "popup_{$group}_" ) !== false ) {
			$new_key = str_replace( "popup_{$group}_", '', $meta_key );
			if ( count( $value ) == 1 ) {
				$group_values[ $new_key ] = $value[0];
			} else {
				$group_values[ $new_key ] = $value;
			}
		}
	}
	if ( $key ) {
		$key = str_replace( '.', '_', $key );
		if ( ! isset( $group_values[ $key ] ) ) {
			$value = $default;
		} else {
			$value = $group_values[ $key ];
		}

		return apply_filters( "popmake_get_popup_{$group}_$key", $value, $popup_id );
	} else {
		return apply_filters( "popmake_get_popup_{$group}", $group_values, $popup_id );
	}
}

/**
 * Returns the load settings meta of a popup.
 *
 * @since 1.0
 * @deprecated 1.4
 *
 * @param int $popup_id ID number of the popup to retrieve a overlay meta for
 *
 * @return mixed array|string of the popup load settings meta
 */
function popmake_get_popup_targeting_condition( $popup_id = null, $key = null ) {
	return popmake_get_popup_meta_group( 'targeting_condition', $popup_id, $key );
}

/**
 * Returns the click_open meta of a popup.
 *
 * @since 1.0
 * @deprecated 1.4
 *
 * @param int $popup_id ID number of the popup to retrieve a click_open meta for
 * @param null $key
 * @param null $default
 *
 * @return mixed array|string of the popup click_open meta
 */
function popmake_get_popup_click_open( $popup_id = null, $key = null, $default = null ) {
	return popmake_get_popup_meta( 'click_open', $popup_id, $key, $default );
}

/**
 * Returns the auto open meta of a popup.
 *
 * @since 1.1.0
 * @deprecated 1.4
 *
 * @param int $popup_id ID number of the popup to retrieve a auto open meta for
 *
 * @return mixed array|string of the popup auto open meta
 */
function popmake_get_popup_auto_open( $popup_id = null, $key = null, $default = null ) {
	return popmake_get_popup_meta( 'auto_open', $popup_id, $key, $default );
}


#endregion