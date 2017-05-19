<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function popmake_hex2rgb( $hex ) {
	if ( is_array( $hex ) ) {
		$hex = implode( '', $hex );
	}
	$hex = str_replace( "#", "", $hex );

	if ( strlen( $hex ) == 3 ) {
		$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
		$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
		$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
	} else {
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );
	}
	$rgb = array( $r, $g, $b );

	//return implode(",", $rgb); // returns the rgb values separated by commas
	return $rgb; // returns an array with the rgb values
}

function popmake_get_rgba_value( $hex, $opacity = 100 ) {
	return 'rgba( ' . implode( ', ', popmake_hex2rgb( strval( $hex ) ) ) . ', ' . number_format( intval( $opacity ) / 100, 2 ) . ' )';
}

function popmake_get_border_style( $w, $s, $c ) {
	return "{$w}px {$s} {$c}";
}

function popmake_get_box_shadow_style( $h, $v, $b, $s, $c, $o = 100, $inset = 'no' ) {
	return "{$h}px {$v}px {$b}px {$s}px " . popmake_get_rgba_value( $c, $o ) . ( $inset == 'yes' ? ' inset' : '' );
}

function popmake_get_text_shadow_style( $h, $v, $b, $c, $o = 100 ) {
	return "{$h}px {$v}px {$b}px " . popmake_get_rgba_value( $c, $o );
}

function popmake_get_font_style( $s, $w, $lh, $f, $st = null, $v = null ) {
	return str_replace( '  ', ' ', trim( "$st $v $w {$s}px/{$lh}px \"$f\"" ) );
}

function popmake_generate_theme_styles( $popup_theme_id ) {

	$styles = array(
		'overlay'   => array(),
		'container' => array(),
		'title'     => array(),
		'content'   => array(),
		'close'     => array(),
	);

	$theme = popmake_get_popup_theme_data_attr( $popup_theme_id );

	extract( $theme );

	if ( empty( $overlay ) || empty( $container ) || empty( $title ) || empty( $content ) || empty( $close ) ) {
		return array();
	}

	/*
	 * Overlay Styles
	 */
	if ( ! empty( $overlay['background_color'] ) ) {
		$styles['overlay']['background-color'] = popmake_get_rgba_value( $overlay['background_color'], $overlay['background_opacity'] );
	}

	/*
	 * Container Styles
	 */
	$styles['container'] = array(
		'padding'       => "{$container['padding']}px",
		'border-radius' => "{$container['border_radius']}px",
		'border'        => popmake_get_border_style( $container['border_width'], $container['border_style'], $container['border_color'] ),
		'box-shadow'    => popmake_get_box_shadow_style( $container['boxshadow_horizontal'], $container['boxshadow_vertical'], $container['boxshadow_blur'], $container['boxshadow_spread'], $container['boxshadow_color'], $container['boxshadow_opacity'], $container['boxshadow_inset'] ),
	);

	if ( ! empty( $container['background_color'] ) ) {
		$styles['container']['background-color'] = popmake_get_rgba_value( $container['background_color'], $container['background_opacity'] );
	}

	/*
	 * Title Styles
	 */
	$styles['title'] = array(
		'color'       => $title['font_color'],
		'text-align'  => $title['text_align'],
		'text-shadow' => popmake_get_text_shadow_style( $title['textshadow_horizontal'], $title['textshadow_vertical'], $title['textshadow_blur'], $title['textshadow_color'], $title['textshadow_opacity'] ),
		'font-family' => $title['font_family'],
		'font-weight' => $title['font_weight'],
		'font-size'   => "{$title['font_size']}px",
		'font-style'  => $title['font_style'],
		'line-height' => "{$title['line_height']}px",
	);

	/*
	 * Content Styles
	 */
	$styles['content'] = array(
		'color'       => $content['font_color'],
		'font-family' => $content['font_family'],
		'font-weight' => $content['font_weight'],
		'font-style'  => $content['font_style'],
	);

	/*
	 * Close Styles
	 */
	$styles['close'] = array(
		'height'        => empty( $close['height'] ) || $close['height'] <= 0 ? 'auto' : "{$close['height']}px",
		'width'         => empty( $close['width'] ) || $close['width'] <= 0 ? 'auto' : "{$close['width']}px",
		'left'          => 'auto',
		'right'         => 'auto',
		'bottom'        => 'auto',
		'top'           => 'auto',
		'padding'       => "{$close['padding']}px",
		'color'         => $close['font_color'],
		'font-family'   => $close['font_family'],
		'font-weight'   => $close['font_weight'],
		'font-size'     => "{$close['font_size']}px",
		'font-style'    => $close['font_style'],
		'line-height'   => "{$close['line_height']}px",
		'border'        => popmake_get_border_style( $close['border_width'], $close['border_style'], $close['border_color'] ),
		'border-radius' => "{$close['border_radius']}px",
		'box-shadow'    => popmake_get_box_shadow_style( $close['boxshadow_horizontal'], $close['boxshadow_vertical'], $close['boxshadow_blur'], $close['boxshadow_spread'], $close['boxshadow_color'], $close['boxshadow_opacity'], $close['boxshadow_inset'] ),
		'text-shadow'   => popmake_get_text_shadow_style( $close['textshadow_horizontal'], $close['textshadow_vertical'], $close['textshadow_blur'], $close['textshadow_color'], $close['textshadow_opacity'] ),
	);

	if ( ! empty( $close['background_color'] ) ) {
		$styles['close']['background-color'] = popmake_get_rgba_value( $close['background_color'], $close['background_opacity'] );
	}

	switch ( $close['location'] ) {
		case "topleft":
			$styles['close']['top']  = "{$close['position_top']}px";
			$styles['close']['left'] = "{$close['position_left']}px";
			break;
		case "topright":
			$styles['close']['top']   = "{$close['position_top']}px";
			$styles['close']['right'] = "{$close['position_right']}px";
			break;
		case "bottomleft":
			$styles['close']['bottom'] = "{$close['position_bottom']}px";
			$styles['close']['left']   = "{$close['position_left']}px";
			break;
		case "bottomright":
			$styles['close']['bottom'] = "{$close['position_bottom']}px";
			$styles['close']['right']  = "{$close['position_right']}px";
			break;
	}

	return apply_filters( 'popmake_generate_theme_styles', $styles, $popup_theme_id, $theme );
}

function pum_render_theme_styles( $popup_theme_id ) {
	$styles = '';

	$theme_data = get_post( $popup_theme_id );
	$slug       = $theme_data->post_name != $popup_theme_id ? $theme_data->post_name : false;


	$theme_styles = popmake_generate_theme_styles( $popup_theme_id );

	if ( empty( $theme_styles ) ) {
		return '';
	}

	foreach ( $theme_styles as $element => $rules ) {
		switch ( $element ) {
			case 'overlay':
				$rule = ".pum-theme-{$popup_theme_id}";
				if ( $slug ) {
					$rule .= ", .pum-theme-{$slug}";
				}
				break;
			case 'container':
				$rule = ".pum-theme-{$popup_theme_id} .pum-container";
				if ( $slug ) {
					$rule .= ", .pum-theme-{$slug} .pum-container";
				}
				break;
			case 'close':
				$rule = ".pum-theme-{$popup_theme_id} .pum-content + .pum-close";
				if ( $slug ) {
					$rule .= ", .pum-theme-{$slug} .pum-content + .pum-close";
				}
				break;
			default:
				$rule = ".pum-theme-{$popup_theme_id} .pum-{$element}";
				if ( $slug ) {
					$rule .= ", .pum-theme-{$slug} .pum-{$element}";
				}
				break;
		}

		$rule_set = $sep = '';
		foreach ( $rules as $key => $value ) {
			if ( ! empty( $value ) ) {
				$rule_set .= $sep . $key . ': ' . $value;
				$sep      = '; ';
			}
		}

		$styles .= "$rule { $rule_set } \r\n";
	}

	return $styles;
}