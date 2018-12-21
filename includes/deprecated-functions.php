<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @deprecated 1.8.0
 *
 * @param $hex
 *
 * @return array|string
 */
function popmake_hex2rgb( $hex ) {
	return PUM_Utils_CSS::hex2rgb( $hex, 'array' );
}

/**
 * @deprecated 1.8.0
 *
 * @param     $hex
 * @param int $opacity
 *
 * @return string
 */
function popmake_get_rgba_value( $hex, $opacity = 100 ) {
	return PUM_Utils_CSS::hex2rgba( $hex, $opacity );
}

/**
 * @deprecated 1.8.0
 *
 * @param int    $thickness
 * @param string $style
 * @param string $color
 *
 * @return string
 */
function popmake_get_border_style( $thickness = 1, $style = 'solid', $color = '#cccccc' ) {
	return PUM_Utils_CSS::border_style( $thickness, $style, $color );
}

/**
 * @deprecated 1.8.0
 *
 * @param int    $horizontal
 * @param int    $vertical
 * @param int    $blur
 * @param int    $spread
 * @param string $hex
 * @param int    $opacity
 * @param string $inset
 *
 * @return string
 */
function popmake_get_box_shadow_style( $horizontal = 0, $vertical = 0, $blur = 0, $spread = 0, $hex = '#000000', $opacity = 50, $inset = 'no' ) {
	return PUM_Utils_CSS::box_shadow_style( $horizontal, $vertical, $blur, $spread, $hex, $opacity, $inset );
}

/**
 * @deprecated 1.8.0
 *
 * @param int    $horizontal
 * @param int    $vertical
 * @param int    $blur
 * @param string $hex
 * @param int    $opacity
 *
 * @return string
 */
function popmake_get_text_shadow_style( $horizontal = 0, $vertical = 0, $blur = 0, $hex = '#000000', $opacity = 50 ) {
	return PUM_Utils_CSS::text_shadow_style( $horizontal, $vertical, $blur, $hex, $opacity );
}
