<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'popup', 'pum_popup_shortcode' );
function pum_popup_shortcode( $atts, $content = '' ) {
	global $popup;

	$atts = shortcode_atts(
		apply_filters( 'pum_popup_shortcode_default_atts', array(
			'id'               => "",
			'theme_id'         => null,
			'theme'            => null,
			'title'            => "",
			'overlay_disabled' => 0,#
			'size'             => "small",#
			'width'            => "",#
			'width_unit'       => "px",#
			'height'           => "",#
			'height_unit'      => "px",#
			'location'         => "center top",#
			'position_top'     => 100,#
			'position_left'    => 0,#
			'position_bottom'  => 0,#
			'position_right'   => 0,#
			'position_fixed'   => 0,#
			'animation_type'   => "slide",#
			'animation_speed'  => 350,#
			'animation_origin' => 'top',#
			'overlay_click'    => 0,#
			'esc_press'        => 1,#
		) ),
		apply_filters( 'popmake_shortcode_popup_atts', $atts ),
		'popup'
	);

	$popup = new PUM_Popup;

	$popup->ID = $atts['id'];
	$popup->title = $atts['title'];
	$popup->post_content = $content;

	// Get Theme ID
	if ( ! $atts['theme_id'] ) {
		$atts['theme_id'] = $atts['theme'] ? $atts['theme'] : popmake_get_default_popup_theme();
	}

	// Theme ID
	$popup->theme_id = $atts['theme_id'];

	// Display Meta
	$popup->display = array(
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
	);

	// Close Meta
	$popup->close = array(
		'overlay_click' => $atts['overlay_click'],
		'esc_press'     => $atts['esc_press']
	);


	ob_start();
	popmake_get_template_part( 'popup' );
	return ob_get_clean();
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

add_shortcode( 'popup_close', 'popmake_shortcode_popup_close' );
function popmake_shortcode_popup_close( $atts, $content = null ) {
	$atts = shortcode_atts(
		apply_filters( 'popmake_shortcode_popup_close_default_atts', array(
			'id'    => "",
			'tag'   => 'span',
			'class' => '',
		) ),
		apply_filters( 'popmake_shortcode_popup_close_atts', $atts ),
		'popup_trigger'
	);

	$return = '<' . $atts['tag'] . ' class="popmake-close' . ' ' . $atts['class'] . '">';
	$return .= do_shortcode( $content );
	$return .= '</' . $atts['tag'] . '>';

	return $return;
}