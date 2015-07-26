<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'popup', 'popmake_shortcode_popup' );
function popmake_shortcode_popup( $atts, $content = null ) {
	$atts = shortcode_atts(
		apply_filters( 'popmake_shortcode_popup_default_atts', array(
			'id'               => "",
			'theme_id'         => 1,
			'title'            => "",
			'overlay_disabled' => 0,
			'size'             => "small",
			'width'            => "",
			'width_unit'       => "px",
			'height'           => "",
			'height_unit'      => "px",
			'location'         => "center top",
			'position_top'     => 100,
			'position_left'    => 0,
			'position_bottom'  => 0,
			'position_right'   => 0,
			'position_fixed'   => 0,
			'animation_type'   => "slide",
			'animation_speed'  => 350,
			'animation_origin' => 'top',
			'overlay_click'    => 0,
			'esc_press'        => 1,
		) ),
		apply_filters( 'popmake_shortcode_popup_atts', $atts ),
		'popup'
	);

	$popup_fields = apply_filters( 'popmake_shortcode_data_attr', array(
		'id'    => $atts['id'],
		'theme' => $atts['theme_id'],
		'meta'  => array(
			'display' => array(
				'size'               => $atts['size'],
				'overlay_disabled'   => $atts['overlay_disabled'],
				'custom_width'       => $atts['width'],
				'custom_width_unit'  => $atts['width_unit'],
				'custom_height'      => $atts['height'],
				'custom_height_unit' => $atts['height_unit'],
				'custom_height_auto' => $atts['width'] > 0 ? 0 : 1,
				'location'           => $atts['location'],
				'position_top'       => $atts['position_top'],
				'position_left'      => $atts['position_left'],
				'position_bottom'    => $atts['position_bottom'],
				'position_right'     => $atts['position_right'],
				'position_fixed'     => $atts['position_fixed'],
				'animation_type'     => $atts['animation_type'],
				'animation_speed'    => $atts['animation_speed'],
				'animation_origin'   => $atts['animation_origin'],
			),
			'close'   => array(
				'overlay_click' => $atts['overlay_click'],
				'esc_press'     => $atts['esc_press']
			),
		),
	), $atts );

	$classes = array( 'popmake', 'theme-' . $atts['theme_id'] );
	if ( in_array( $atts['size'], array( 'normal', 'nano', 'tiny', 'small', 'medium', 'large', 'xlarge' ) ) ) {
		$classes[] = 'responsive';
		$classes[] = 'size-' . $atts['size'];
	} elseif ( $atts['size'] == 'custom' ) {
		$classes[] = 'size-custom';
	}

	$return = "<div id='popmake-" . $atts['id'] . "' class='" . implode( ' ', $classes ) . "' data-popmake='" . json_encode( $popup_fields ) . "'>";
	if ( $atts['title'] != '' ) :
		$return .= '<div class="popmake-title">' . $atts['title'] . '</div>';
	endif;
	$return .= '<div class="popmake-content">' . do_shortcode( $content ) . '</div>';
	$return .= '<a class="popmake-close">' . __( '&#215;', 'popup-maker' ) . '</a>';
	$return .= '</div>';

	return $return;
}

add_shortcode( 'popup_trigger', 'popmake_shortcode_popup_trigger' );
function popmake_shortcode_popup_trigger( $atts, $content = null ) {
	$atts = shortcode_atts(
		apply_filters( 'popmake_shortcode_popup_trigger_default_atts', array(
			'id'    => "",
			'tag'   => 'span',
			'class' => '',
		) ),
		apply_filters( 'popmake_shortcode_popup_trigger_atts', $atts ),
		'popup_trigger'
	);

	$return = '<' . $atts['tag'] . ' class="popmake-' . $atts['id'] . ' ' . $atts['class'] . '">';
	$return .= do_shortcode( $content );
	$return .= '</' . $atts['tag'] . '>';

	return $return;
}