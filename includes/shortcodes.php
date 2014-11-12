<?php
add_shortcode( 'popup', 'popmake_shortcode_popup');
function popmake_shortcode_popup($atts, $content = NULL)
{
	$atts = shortcode_atts(
		apply_filters('popmake_shortcode_popup_default_atts', array(
			'id' => "",
			'theme_id' => 1,
			'title' => "",
			'overlay_disabled' => 0,
			'size' => "auto",
			'width' => "",
			'widthUnit' => "px",
			'height' => "",
			'heightUnit' => "px",
			'location' => "center top",
			'positionTop' => 100,
			'positionLeft' => 0,
			'positionBottom' => 0,
			'positionRight' => 0,
			'positionFixed' => 0,
			'animation' => "slide",
			'animationSpeed' => 350,
			'animationOrigin' => 'top',
			'overlayClose' => 0,
			'escClose' => 1,
			// Deprecated
			'theme' => NULL,
			'duration' => NULL,
			'direction' => NULL,
			'overlayEscClose' => NULL,
		)),
		apply_filters('popmake_shortcode_popup_atts', $atts)
	);

	$popup_fields = array(
		'id' => $atts['id'],
		'theme' => $atts['theme_id'],
		'meta' => array(
			'display' => array(
				'size' => $atts['size'],
				'overlay_disabled' => $atts['overlay_disabled'],
				'custom_width' => $atts['width'],
				'custom_width_unit' => $atts['widthUnit'],
				'custom_height' => $atts['height'],
				'custom_height_unit' => $atts['heightUnit'],
				'custom_height_auto' => $atts['width'] > 0 ? 0 : 1,
				'location' => $atts['location'],
				'position_top' => $atts['positionTop'],
				'position_left' => $atts['positionLeft'],
				'position_bottom' => $atts['positionBottom'],
				'position_right' => $atts['positionRight'],
				'position_fixed' => $atts['positionFixed'],
				'animation_type' => $atts['animation'],
				'animation_speed' => $atts['animationSpeed'],
				'animation_origin' => $atts['animationOrigin'],
			),
			'close' => array(
				'overlay_click' => $atts['overlayClose'],
				'esc_press' => $atts['escClose']
			),
		),
	);

	$classes = array('popmake', 'theme-'. $atts['theme_id']);
	if( in_array( $atts['size'], array('normal', 'nano', 'tiny', 'small', 'medium', 'large', 'xlarge') ) )
	{
		$classes[] = 'responsive';
		$classes[] = 'size-' . $atts['size'];
	}
	elseif($atts['size'] == 'custom')
	{
		$classes[] = 'size-custom';
	}

	$return = "<div id='popmake-". $atts['id'] ."' class='". implode(' ', $classes) ."' data-popmake='". json_encode($popup_fields) ."'>";
		if( $atts['title'] != '' ) :
			$return .= '<div class="popmake-title">'. $atts['title'] .'</div>';
		endif;
		$return .= '<div class="popmake-content">'. do_shortcode($content) . '</div>';
		$return .= '<a class="popmake-close">'. __( '&#215;', 'popup-maker') .'</a>';
	$return .= '</div>';
	return $return;
}