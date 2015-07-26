<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function popmake_enqueue_google_fonts_during_preload( $data_attr, $popup_theme_id ) {
	global $popmake_needed_google_fonts;
	if ( ! is_array( $popmake_needed_google_fonts ) ) {
		$popmake_needed_google_fonts = array();
	}

	$google_fonts = popmake_get_google_webfonts_list();

	if ( ! empty( $data_attr['title']['font_family'] ) && array_key_exists( $data_attr['title']['font_family'], $google_fonts ) ) {
		$variant = $data_attr['title']['font_weight'] != 'normal' ? $data_attr['title']['font_family'] : '';
		if ( $data_attr['title']['font_style'] == 'italic' ) {
			$variant .= 'italic';
		}
		$popmake_needed_google_fonts[ $data_attr['title']['font_family'] ][ $variant ] = $variant;
	}
	if ( ! empty( $data_attr['content']['font_family'] ) && array_key_exists( $data_attr['content']['font_family'], $google_fonts ) ) {
		$variant = $data_attr['content']['font_weight'] != 'normal' ? $data_attr['content']['font_family'] : '';
		if ( $data_attr['content']['font_style'] == 'italic' ) {
			$variant .= 'italic';
		}
		$popmake_needed_google_fonts[ $data_attr['content']['font_family'] ][ $variant ] = $variant;
	}
	if ( ! empty( $data_attr['close']['font_family'] ) && array_key_exists( $data_attr['close']['font_family'], $google_fonts ) ) {
		$variant = $data_attr['close']['font_weight'] != 'normal' ? $data_attr['close']['font_family'] : '';
		if ( $data_attr['close']['font_style'] == 'italic' ) {
			$variant .= 'italic';
		}
		$popmake_needed_google_fonts[ $data_attr['close']['font_family'] ][ $variant ] = $variant;
	}

	return $data_attr;
}

add_action( 'popmake_get_popup_theme_data_attr', 'popmake_enqueue_google_fonts_during_preload', 10, 2 );
