<?php
/**
 * Functions for Themes Template
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * @param $theme_id
 *
 * @return string
 */
function pum_get_rendered_theme_styles( $theme_id ) {
	$styles = '';

	$theme = pum_get_theme( $theme_id );

	if ( ! pum_is_theme( $theme ) ) {
		return '';
	}

	$slug = $theme->post_name;

	$theme_styles = $theme->get_generated_styles();

	if ( empty( $theme_styles ) ) {
		return $styles;
	}

	foreach ( $theme_styles as $element => $element_rules ) {
		switch ( $element ) {

			case 'overlay':
				$css_selector = ".pum-theme-{$theme_id}";
				if ( $slug ) {
					$css_selector .= ", .pum-theme-{$slug}";
				}
				break;

			case 'container':
				$css_selector = ".pum-theme-{$theme_id} .pum-container";
				if ( $slug ) {
					$css_selector .= ", .pum-theme-{$slug} .pum-container";
				}
				break;

			case 'close':
				$css_selector       = ".pum-theme-{$theme_id} .pum-content + .pum-close";
				$admin_bar_selector = "body.admin-bar .pum-theme-{$theme_id} .pum-content + .pum-close";
				if ( $slug ) {
					$css_selector       .= ", .pum-theme-{$slug} .pum-content + .pum-close";
					$admin_bar_selector .= ", body.admin-bar .pum-theme-{$slug} .pum-content + .pum-close";
				}
				break;

			default:
				$css_selector = ".pum-theme-{$theme_id} .pum-{$element}";
				if ( $slug ) {
					$css_selector .= ", .pum-theme-{$slug} .pum-{$element}";
				}
				break;

		}

		$rule_set = $sep = '';
		foreach ( $element_rules as $property => $value ) {
			if ( ! empty( $value ) ) {
				$rule_set .= $sep . $property . ': ' . $value;
				$sep       = '; ';
			}
		}

		$styles .= "$css_selector { $rule_set } \r\n";

		if ( 'close' === $element && ! empty( $admin_bar_selector ) && $theme->get_setting( 'close_position_outside' ) && strpos( $theme->get_setting( 'close_location' ), 'top' ) !== false ) {
			$top = ! empty( $element_rules['top'] ) ? (int) str_replace( 'px', '', $element_rules['top'] ) : 0;
			// Move it down to compensate for admin bar height.
			$top    += 32;
			$styles .= "$admin_bar_selector { top: {$top}px }";
		}
	}

	return $styles;
}
