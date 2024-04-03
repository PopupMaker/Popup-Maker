<?php
/**
 * Selectbox options,and other array based data sets used for options.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// TODO LEFT OFF HERE!! MOVE THESE INTO  <-- Themes <--

add_filter( 'popmake_size_unit_options', 'popmake_core_size_unit_options', 10 );
function popmake_core_size_unit_options( $options ) {
	return array_merge(
		$options,
		[
			// option => value
			'px'  => 'px',
			'%'   => '%',
			'em'  => 'em',
			'rem' => 'rem',
		]
	);
}



add_filter( 'popmake_font_style_options', 'popmake_core_font_style_options', 10 );
function popmake_core_font_style_options( $options ) {
	return array_merge(
		$options,
		[
			__( 'Normal', 'popup-maker' ) => '',
			__( 'Italic', 'popup-maker' ) => 'italic',
		]
	);
}


add_filter( 'popmake_text_align_options', 'popmake_core_text_align_options', 10 );
function popmake_core_text_align_options( $options ) {
	return array_merge(
		$options,
		[
			// option => value
			__( 'Left', 'popup-maker' )   => 'left',
			__( 'Center', 'popup-maker' ) => 'center',
			__( 'Right', 'popup-maker' )  => 'right',
		]
	);
}

add_filter( 'popmake_popup_display_size_options', 'popmake_popup_display_size_options_responsive', 10 );
function popmake_popup_display_size_options_responsive( $options ) {
	return array_merge(
		$options,
		[
			// option => value
			__( 'Responsive Sizes&#10549;', 'popup-maker' ) => '',
			__( 'Nano - 10%', 'popup-maker' )    => 'nano',
			__( 'Micro - 20%', 'popup-maker' )   => 'micro',
			__( 'Tiny - 30%', 'popup-maker' )    => 'tiny',
			__( 'Small - 40%', 'popup-maker' )   => 'small',
			__( 'Medium - 60%', 'popup-maker' )  => 'medium',
			__( 'Normal - 70%', 'popup-maker' )  => 'normal',
			__( 'Large - 80%', 'popup-maker' )   => 'large',
			__( 'X Large - 95%', 'popup-maker' ) => 'xlarge',
			__( 'Non Responsive Sizes&#10549;', 'popup-maker' ) => '',
			__( 'Auto', 'popup-maker' )          => 'auto',
			__( 'Custom', 'popup-maker' )        => 'custom',
		]
	);
}


add_filter( 'popmake_popup_display_animation_type_options', 'popmake_core_popup_display_animation_type_options', 10 );
function popmake_core_popup_display_animation_type_options( $options ) {
	return array_merge(
		$options,
		[
			// option => value
			__( 'None', 'popup-maker' )           => 'none',
			__( 'Slide', 'popup-maker' )          => 'slide',
			__( 'Fade', 'popup-maker' )           => 'fade',
			__( 'Fade and Slide', 'popup-maker' ) => 'fadeAndSlide',
			__( 'Grow', 'popup-maker' )           => 'grow',
			__( 'Grow and Slide', 'popup-maker' ) => 'growAndSlide',
		]
	);
}


add_filter( 'popmake_popup_display_animation_origin_options', 'popmake_core_popup_display_animation_origins_options', 10 );
function popmake_core_popup_display_animation_origins_options( $options ) {
	return array_merge(
		$options,
		[
			// option => value
			__( 'Top', 'popup-maker' )           => 'top',
			__( 'Left', 'popup-maker' )          => 'left',
			__( 'Bottom', 'popup-maker' )        => 'bottom',
			__( 'Right', 'popup-maker' )         => 'right',
			__( 'Top Left', 'popup-maker' )      => 'left top',
			__( 'Top Center', 'popup-maker' )    => 'center top',
			__( 'Top Right', 'popup-maker' )     => 'right top',
			__( 'Middle Left', 'popup-maker' )   => 'left center',
			__( 'Middle Center', 'popup-maker' ) => 'center center',
			__( 'Middle Right', 'popup-maker' )  => 'right center',
			__( 'Bottom Left', 'popup-maker' )   => 'left bottom',
			__( 'Bottom Center', 'popup-maker' ) => 'center bottom',
			__( 'Bottom Right', 'popup-maker' )  => 'right bottom',
		// __( 'Mouse', 'popup-maker' )          => 'mouse',
		]
	);
}

add_filter( 'popmake_popup_display_location_options', 'popmake_core_popup_display_location_options', 10 );
function popmake_core_popup_display_location_options( $options ) {
	return array_merge(
		$options,
		[
			// option => value
			__( 'Top Left', 'popup-maker' )      => 'left top',
			__( 'Top Center', 'popup-maker' )    => 'center top',
			__( 'Top Right', 'popup-maker' )     => 'right top',
			__( 'Middle Left', 'popup-maker' )   => 'left center',
			__( 'Middle Center', 'popup-maker' ) => 'center',
			__( 'Middle Right', 'popup-maker' )  => 'right center',
			__( 'Bottom Left', 'popup-maker' )   => 'left bottom',
			__( 'Bottom Center', 'popup-maker' ) => 'center bottom',
			__( 'Bottom Right', 'popup-maker' )  => 'right bottom',
		]
	);
}


add_filter( 'popmake_theme_close_location_options', 'popmake_core_theme_close_location_options', 10 );
function popmake_core_theme_close_location_options( $options ) {
	return array_merge(
		$options,
		[
			// option => value
			__( 'Top Left', 'popup-maker' )     => 'topleft',
			__( 'Top Right', 'popup-maker' )    => 'topright',
			__( 'Bottom Left', 'popup-maker' )  => 'bottomleft',
			__( 'Bottom Right', 'popup-maker' ) => 'bottomright',
		]
	);
}


add_filter( 'popmake_cookie_trigger_options', 'popmake_cookie_trigger_options', 10 );
function popmake_cookie_trigger_options( $options ) {
	return array_merge(
		$options,
		[
			// option => value
			__( 'Disabled', 'popup-maker' ) => 'disabled',
			__( 'On Open', 'popup-maker' )  => 'open',
			__( 'On Close', 'popup-maker' ) => 'close',
			__( 'Manual', 'popup-maker' )   => 'manual',
		]
	);
}
