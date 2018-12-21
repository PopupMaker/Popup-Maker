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


