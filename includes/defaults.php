<?php
/**
 * Default Settings, Popup Settings, Theme Settings.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'popmake_popup_display_defaults', 'popmake_popup_display_defaults', 0 );
add_filter( 'pum_popup_display_defaults', 'popmake_popup_display_defaults', 0 );

/**
 * Returns default display settings for popups.
 *
 * @param  array $defaults
 *
 * @return array
 */
function popmake_popup_display_defaults( $defaults = array() ) {
	return array_merge( $defaults, array(
		'stackable'                 => false,
		'overlay_disabled'          => false,
		'scrollable_content'        => false,
		'disable_reposition'        => false,
		'size'                      => 'medium',
		'responsive_min_width'      => '',
		'responsive_min_width_unit' => 'px',
		'responsive_max_width'      => '',
		'responsive_max_width_unit' => 'px',
		'custom_width'              => 640,
		'custom_width_unit'         => 'px',
		'custom_height'             => 380,
		'custom_height_unit'        => 'px',
		'custom_height_auto'        => false,
		'location'                  => 'center top',
		'position_from_trigger'     => false,
		'position_top'              => 100,
		'position_left'             => 0,
		'position_bottom'           => 0,
		'position_right'            => 0,
		'position_fixed'            => false,
		'animation_type'            => 'fade',
		'animation_speed'           => 350,
		'animation_origin'          => 'center top',
		'overlay_zindex'            => 1999999998,
		'zindex'                    => 1999999999,
	) );
}


add_filter( 'popmake_popup_close_defaults', 'popmake_popup_close_defaults', 0 );
add_filter( 'pum_popup_close_defaults', 'popmake_popup_close_defaults', 0 );
function popmake_popup_close_defaults( $defaults = array() ) {
	return array_merge( $defaults, array(
		'text'          => '',
		'button_delay'  => '0',
		'overlay_click' => false,
		'esc_press'     => false,
		'f4_press'      => false,
	) );
}



add_filter( 'popmake_popup_theme_overlay_defaults', 'popmake_popup_theme_overlay_defaults', 0 );
function popmake_popup_theme_overlay_defaults( $defaults = array() ) {
	return array_merge( $defaults, array(
		'background_color'   => '#ffffff',
		'background_opacity' => 100,
	) );
}


add_filter( 'popmake_popup_theme_container_defaults', 'popmake_popup_theme_container_defaults', 0 );
function popmake_popup_theme_container_defaults( $defaults = array() ) {
	return array_merge( $defaults, array(
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
	) );
}


add_filter( 'popmake_popup_theme_title_defaults', 'popmake_popup_theme_title_defaults', 0 );
function popmake_popup_theme_title_defaults( $defaults = array() ) {
	return array_merge( $defaults, array(
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
	) );
}


add_filter( 'popmake_popup_theme_content_defaults', 'popmake_popup_theme_content_defaults', 0 );
function popmake_popup_theme_content_defaults( $defaults = array() ) {
	return array_merge( $defaults, array(
		'font_color'  => '#8c8c8c',
		'font_family' => 'inherit',
		'font_weight' => 'inherit',
		'font_style'  => 'normal',
	) );
}


add_filter( 'popmake_popup_theme_close_defaults', 'popmake_popup_theme_close_defaults', 0 );
function popmake_popup_theme_close_defaults( $defaults = array() ) {
	return array_merge( $defaults, array(
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
	) );
}