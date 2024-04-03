<?php
/**
 * Functions for Deprecated Themes
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * @deprecated 1.8.0 use pum_get_theme_generated_styles
 *
 * @param int $popup_theme_id
 *
 * @return array
 */
function popmake_generate_theme_styles( $popup_theme_id = 0 ) {
	return pum_get_theme_generated_styles( $popup_theme_id );
}

/**
 * Get theme meta defaults from data model 1.
 *
 * @since 1.8.0
 *
 * @param null|string $group
 *
 * @return array|bool|mixed
 */
function pum_get_theme_v1_meta_defaults() {

}

// TODO LEFT OFF HERE
// REFACTOR v1 meta getter & defaults.
// CONTINUE PURGING CODE.

/**
 * Fetches theme meta group data from v1 data format.
 *
 * @deprecated 1.1.0
 * @since      1.8.0
 *
 * @param      $group
 * @param null  $popup_theme_id
 * @param null  $key
 * @param null  $default
 *
 * @return mixed
 */
function pum_get_theme_v1_meta( $group, $popup_theme_id = null, $key = null, $default = null ) {
	if ( ! $popup_theme_id ) {
		$popup_theme_id = get_the_ID();
	}

	$post_meta = get_post_custom( $popup_theme_id );

	if ( ! is_array( $post_meta ) ) {
		$post_meta = [];
	}

	$default_check_key = 'popup_theme_defaults_set';
	if ( ! in_array( $group, [ 'overlay', 'close', 'display', 'targeting_condition' ] ) ) {
		$default_check_key = "popup_{$group}_defaults_set";
	}

	$group_values = array_key_exists( $default_check_key, $post_meta ) ? [] : apply_filters( "popmake_popup_theme_{$group}_defaults", [] );
	foreach ( $post_meta as $meta_key => $value ) {
		if ( strpos( $meta_key, "popup_theme_{$group}_" ) !== false ) {
			$new_key = str_replace( "popup_theme_{$group}_", '', $meta_key );
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
			$value = $default;
		} else {
			$value = $group_values[ $key ];
		}

		return apply_filters( "popmake_get_popup_theme_{$group}_$key", $value, $popup_theme_id );
	} else {
		return apply_filters( "popmake_get_popup_theme_{$group}", $group_values, $popup_theme_id );
	}
}

/**
 * Get theme meta defaults from data model 2.
 *
 * @since 1.8.0
 *
 * @param null|string $group
 *
 * @return array|bool|mixed
 */
function pum_get_theme_v2_meta_defaults( $group = null ) {
	$defaults = [
		'overlay'   => [
			'background_color'   => '#ffffff',
			'background_opacity' => 100,
		],
		'container' => [
			'padding'              => 18,
			'background_color'     => '#f9f9f9',
			'background_opacity'   => 100,
			'border_style'         => 'none',
			'border_color'         => '#000000',
			'border_width'         => 1,
			'border_radius'        => 0,
			'boxshadow_inset'      => 'no',
			'boxshadow_horizontal' => 1,
			'boxshadow_vertical'   => 1,
			'boxshadow_blur'       => 3,
			'boxshadow_spread'     => 0,
			'boxshadow_color'      => '#020202',
			'boxshadow_opacity'    => 23,
		],
		'title'     => [
			'font_color'            => '#000000',
			'line_height'           => 36,
			'font_size'             => 32,
			'font_family'           => 'inherit',
			'font_weight'           => 'inherit',
			'font_style'            => 'normal',
			'text_align'            => 'left',
			'textshadow_horizontal' => 0,
			'textshadow_vertical'   => 0,
			'textshadow_blur'       => 0,
			'textshadow_color'      => '#020202',
			'textshadow_opacity'    => 23,
		],
		'content'   => [
			'font_color'  => '#8c8c8c',
			'font_family' => 'inherit',
			'font_weight' => 'inherit',
			'font_style'  => 'normal',
		],
		'close'     => [
			'text'                  => __( 'CLOSE', 'popup-maker' ),
			'location'              => 'topright',
			'position_top'          => 0,
			'position_left'         => 0,
			'position_bottom'       => 0,
			'position_right'        => 0,
			'padding'               => 8,
			'height'                => 0,
			'width'                 => 0,
			'background_color'      => '#00b7cd',
			'background_opacity'    => 100,
			'font_color'            => '#ffffff',
			'line_height'           => 14,
			'font_size'             => 12,
			'font_family'           => 'inherit',
			'font_weight'           => 'inherit',
			'font_style'            => 'normal',
			'border_style'          => 'none',
			'border_color'          => '#ffffff',
			'border_width'          => 1,
			'border_radius'         => 0,
			'boxshadow_inset'       => 'no',
			'boxshadow_horizontal'  => 0,
			'boxshadow_vertical'    => 0,
			'boxshadow_blur'        => 0,
			'boxshadow_spread'      => 0,
			'boxshadow_color'       => '#020202',
			'boxshadow_opacity'     => 23,
			'textshadow_horizontal' => 0,
			'textshadow_vertical'   => 0,
			'textshadow_blur'       => 0,
			'textshadow_color'      => '#000000',
			'textshadow_opacity'    => 23,
		],
	];

	// Here for backward compatibility with extensions.
	foreach ( $defaults as $key => $values ) {
		$defaults[ $key ] = apply_filters( "popmake_popup_theme_{$key}_defaults", $values );
	}

	return isset( $group ) ? ( isset( $defaults[ $group ] ) ? $defaults[ $group ] : false ) : $defaults;

}

/**
 * Fetch themes v2 meta as a single array.
 *
 * @param null|int $theme_id
 *
 * @return array|bool
 */
function pum_get_theme_v2_meta( $theme_id = null ) {
	$theme = pum_get_theme( $theme_id );

	if ( ! pum_is_theme( $theme ) ) {
		return false;
	}

	$defaults = pum_get_theme_v2_meta_defaults();

	$values = [
		'overlay'   => $theme->get_meta( 'popup_theme_overlay' ),
		'container' => $theme->get_meta( 'popup_theme_container' ),
		'title'     => $theme->get_meta( 'popup_theme_title' ),
		'content'   => $theme->get_meta( 'popup_theme_content' ),
		'close'     => $theme->get_meta( 'popup_theme_close' ),
	];

	foreach ( array_keys( $values ) as $array_key ) {
		$values[ $array_key ] = wp_parse_args( $values[ $array_key ], $defaults[ $array_key ] );
	}

	return $values;
}

/**
 * Fetches theme meta group data from v2 data format.
 *
 * @deprecated 1.3.0
 * @since      1.8.0
 *
 * @param string      $meta_group
 * @param null|int    $theme_id
 * @param null|string $option_key
 * @param null|mixed  $default
 *
 * @return mixed
 */
function pum_get_theme_v2_meta_group( $meta_group, $theme_id = null, $option_key = null, $default = null ) {
	$theme_meta = pum_get_theme_v2_meta( $theme_id );

	if ( ! $theme_meta ) {
		return false;
	}

	$group_meta = ! empty( $theme_meta[ $meta_group ] ) ? $theme_meta[ $meta_group ] : false;

	if ( ! $group_meta ) {
		return $default;
	}

	if ( isset( $option_key ) ) {
		$value = isset( $group_meta[ $option_key ] ) ? $group_meta[ $option_key ] : $default;

		return apply_filters( "popmake_get_popup_theme_{$meta_group}_$option_key", $value, $theme_id );
	} else {
		return apply_filters( "popmake_get_popup_theme_{$meta_group}", $group_meta, $theme_id );
	}
}

/**
 * Returns the overlay meta of a theme.
 *
 * @since      1.0
 * @deprecated 1.8.0
 * @remove     2.0.0
 *
 * @param int  $popup_theme_id ID number of the popup to retrieve a overlay meta for
 *
 * @param null $key
 * @param null $default
 *
 * @return mixed array|string of the popup overlay meta
 */
function popmake_get_popup_theme_overlay( $popup_theme_id = null, $key = null, $default = null ) {
	return pum_get_theme_v2_meta_group( 'overlay', $popup_theme_id, $key, $default );
}

/**
 * Returns the container meta of a theme.
 *
 * @since      1.0
 * @deprecated 1.8.0
 * @remove     2.0.0
 *
 * @param int  $popup_theme_id ID number of the popup to retrieve a container meta for
 *
 * @param null $key
 * @param null $default
 *
 * @return mixed array|string of the popup container meta
 */
function popmake_get_popup_theme_container( $popup_theme_id = null, $key = null, $default = null ) {
	return pum_get_theme_v2_meta_group( 'container', $popup_theme_id, $key, $default );
}

/**
 * Returns the title meta of a theme.
 *
 * @since      1.0
 * @deprecated 1.8.0
 * @remove     2.0.0
 *
 * @param int  $popup_theme_id ID number of the popup to retrieve a title meta for
 * @param null $key
 * @param null $default
 *
 * @return mixed array|string of the popup title meta
 */
function popmake_get_popup_theme_title( $popup_theme_id = null, $key = null, $default = null ) {
	return pum_get_theme_v2_meta_group( 'title', $popup_theme_id, $key, $default );
}

/**
 * Returns the content meta of a theme.
 *
 * @since      1.0
 * @deprecated 1.8.0
 * @remove     2.0.0
 *
 * @param int  $popup_theme_id ID number of the popup to retrieve a content meta for
 *
 * @param null $key
 * @param null $default
 *
 * @return mixed array|string of the popup content meta
 */
function popmake_get_popup_theme_content( $popup_theme_id = null, $key = null, $default = null ) {
	return pum_get_theme_v2_meta_group( 'content', $popup_theme_id, $key, $default );
}

/**
 * Returns the close meta of a theme.
 *
 * @since      1.0
 * @deprecated 1.8.0
 * @remove     2.0.0
 *
 * @param int  $popup_theme_id ID number of the popup to retrieve a close meta for
 *
 * @param null $key
 * @param null $default
 *
 * @return mixed array|string of the popup close meta
 */
function popmake_get_popup_theme_close( $popup_theme_id = null, $key = null, $default = null ) {
	return pum_get_theme_v2_meta_group( 'close', $popup_theme_id, $key, $default );
}

/**\
 *
 * @deprecated 1.8.0
 *
 * @param int $theme_id
 *
 * @return mixed
 */
function popmake_get_popup_theme_data_attr( $theme_id = 0 ) {
	$data_attr = pum_get_theme_v2_meta( $theme_id );

	return apply_filters( 'popmake_get_popup_theme_data_attr', $data_attr, $theme_id );
}

/**
 * @deprecated 1.8.0 Do not use!
 * @remove     1.9.0
 *
 * @return mixed
 */
function popmake_get_popup_themes_data() {
	$themes = pum_get_all_themes();

	$popmake_themes = [];

	foreach ( $themes as $theme ) {
		$popmake_themes[ $theme->ID ] = popmake_get_popup_theme_data_attr( $theme->ID );
	}

	wp_reset_postdata();

	return apply_filters( 'popmake_get_popup_themes_data', $popmake_themes );
}

/**
 * Returns the meta group of a theme or value if key is set.
 *
 * @since      1.0
 * @deprecated 1.3.0
 * @remove     2.0.0
 *
 * @param      $group
 * @param int   $popup_theme_id ID number of the popup to retrieve a overlay meta for
 * @param null  $key
 * @param null  $default
 *
 * @return mixed array|string of the popup overlay meta
 */
function popmake_get_popup_theme_meta_group( $group, $popup_theme_id = null, $key = null, $default = null ) {
	return pum_get_theme_v1_meta( $group, $popup_theme_id, $key, $default );
}

/**
 * Fetches theme meta group data from v2 data format.
 *
 * @since      1.3.0
 * @deprecated 1.7.0
 * @remove     2.0.0
 *
 * @param      $group
 * @param null  $popup_theme_id
 * @param null  $key
 * @param null  $default
 *
 * @return mixed
 */
function popmake_get_popup_theme_meta( $group, $popup_theme_id = null, $key = null, $default = null ) {
	return pum_get_theme_v2_meta_group( $group, $popup_theme_id, $key, $default );
}

/**
 * @deprecated 1.3.0
 * @remove     2.0.0
 *
 * @return array|bool|mixed
 */
function popmake_popup_theme_overlay_defaults() {
	return pum_get_theme_v2_meta_defaults( 'overlay' );
}

/**
 * @deprecated 1.3.0
 * @remove     2.0.0
 *
 * @return array|bool|mixed
 */
function popmake_popup_theme_container_defaults() {
	return pum_get_theme_v2_meta_defaults( 'container' );
}

/**
 * @deprecated 1.3.0
 * @remove     2.0.0
 *
 * @return array|bool|mixed
 */
function popmake_popup_theme_title_defaults() {
	return pum_get_theme_v2_meta_defaults( 'title' );
}

/**
 * @deprecated 1.3.0
 * @remove     2.0.0
 *
 * @return array|bool|mixed
 */
function popmake_popup_theme_content_defaults() {
	return pum_get_theme_v2_meta_defaults( 'content' );
}

/**
 * @deprecated 1.3.0
 * @remove     2.0.0
 *
 * @return array|bool|mixed
 */
function popmake_popup_theme_close_defaults() {
	return pum_get_theme_v2_meta_defaults( 'close' );
}

/**
 * @deprecated 1.8.0
 *
 * @return \PUM_Model_Theme[]
 */
function popmake_get_all_popup_themes() {
	return pum_get_all_themes();
}

/**
 * @deprecated 1.8.0
 *
 * @return false|int
 */
function popmake_get_default_popup_theme() {
	return pum_get_default_theme_id();
}
