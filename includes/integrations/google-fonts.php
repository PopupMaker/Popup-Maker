<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function popmake_get_popup_theme_google_fonts( $popup_theme_id ) {

	$fonts_found = array();
	$theme = popmake_get_popup_theme_data_attr( $popup_theme_id );

	$google_fonts = popmake_get_google_webfonts_list();

	if ( ! empty( $theme['title']['font_family'] ) && is_string( $theme['title']['font_family'] ) && array_key_exists( $theme['title']['font_family'], $google_fonts ) ) {
		$variant = $theme['title']['font_weight'] != 'normal' ? $theme['title']['font_weight'] : '';
		if ( $theme['title']['font_style'] == 'italic' ) {
			$variant .= 'italic';
		}
		$fonts_found[ $theme['title']['font_family'] ][ $variant ] = $variant;
	}
	if ( ! empty( $theme['content']['font_family'] ) && is_string( $theme['content']['font_family'] ) && array_key_exists( $theme['content']['font_family'], $google_fonts ) ) {
		$variant = $theme['content']['font_weight'] != 'normal' ? $theme['content']['font_weight'] : '';
		if ( $theme['content']['font_style'] == 'italic' ) {
			$variant .= 'italic';
		}
		$fonts_found[ $theme['content']['font_family'] ][ $variant ] = $variant;
	}
	if ( ! empty( $theme['close']['font_family'] ) && is_string( $theme['close']['font_family'] ) && array_key_exists( $theme['close']['font_family'], $google_fonts ) ) {
		$variant = $theme['close']['font_weight'] != 'normal' ? $theme['close']['font_weight'] : '';
		if ( $theme['close']['font_style'] == 'italic' ) {
			$variant .= 'italic';
		}
		$fonts_found[ $theme['close']['font_family'] ][ $variant ] = $variant;
	}

	return $fonts_found;
}

