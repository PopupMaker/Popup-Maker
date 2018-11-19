<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

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

	$slug = $theme->post_name;

	$theme_styles = $theme->get_generated_styles();

	if ( empty( $theme_styles ) ) {
		return $styles;
	}

	foreach ( $theme_styles as $element => $rules ) {
		switch ( $element ) {

			case 'overlay':
				$rule = ".pum-theme-{$theme_id}";
				if ( $slug ) {
					$rule .= ", .pum-theme-{$slug}";
				}
				break;

			case 'container':
				$rule = ".pum-theme-{$theme_id} .pum-container";
				if ( $slug ) {
					$rule .= ", .pum-theme-{$slug} .pum-container";
				}
				break;

			case 'close':
				$rule = ".pum-theme-{$theme_id} .pum-content + .pum-close";
				if ( $slug ) {
					$rule .= ", .pum-theme-{$slug} .pum-content + .pum-close";
				}
				break;

			default:
				$rule = ".pum-theme-{$theme_id} .pum-{$element}";
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
