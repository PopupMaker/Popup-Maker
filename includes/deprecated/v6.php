<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize changes involving deprecated code in pum_db_ver: 6.
 */
function pum_initialize_deprecated_v6() {

	// Disable the old meta storage methods.
	if ( pum_get_db_ver() < 6 ) {
		// Popup Meta Storage
		add_filter( 'popmake_popup_meta_field_groups', 'pum_deprecated_v6_popup_meta_field_groups' );
		add_filter( 'popmake_popup_meta_field_group_display', 'popmake_popup_meta_field_group_display', 0 );
		add_filter( 'popmake_popup_meta_field_group_close', 'popmake_popup_meta_field_group_close', 0 );

		// Theme Meta Storage
		add_filter( 'popmake_popup_theme_meta_field_groups', 'pum_deprecated_v6_popup_theme_meta_field_groups' );
		add_filter( 'popmake_popup_theme_meta_field_group_overlay', 'popmake_popup_theme_meta_field_group_overlay', 0 );
		add_filter( 'popmake_popup_theme_meta_field_group_container', 'popmake_popup_theme_meta_field_group_container', 0 );
		add_filter( 'popmake_popup_theme_meta_field_group_title', 'popmake_popup_theme_meta_field_group_title', 0 );
		add_filter( 'popmake_popup_theme_meta_field_group_content', 'popmake_popup_theme_meta_field_group_content', 0 );
		add_filter( 'popmake_popup_theme_meta_field_group_close', 'popmake_popup_theme_meta_field_group_close', 0 );
	}

}

add_action( 'pum_initialize_deprecated', 'pum_initialize_deprecated_v6' );


#region Popup Meta Field Groups

/**
 * @param $groups
 *
 * @return array
 */
function pum_deprecated_v6_popup_meta_field_groups( $groups ) {
	return array_merge( $groups, array( 'display', 'close' ) );
}

/**
 * @return array
 */
function popmake_popup_meta_field_group_display() {
	return array(
		'stackable',
		'scrollable_content',
		'overlay_disabled',
		'size',
		'responsive_min_width',
		'responsive_min_width_unit',
		'responsive_max_width',
		'responsive_max_width_unit',
		'custom_width',
		'custom_width_unit',
		'custom_height',
		'custom_height_unit',
		'custom_height_auto',
		'location',
		'position_top',
		'position_left',
		'position_bottom',
		'position_right',
		'position_fixed',
		'animation_type',
		'animation_speed',
		'animation_origin',
		'overlay_zindex',
		'zindex',
	);
}

/**
 * @return array
 */
function popmake_popup_meta_field_group_close() {
	return array(
		'text',
		'button_delay',
		'overlay_click',
		'esc_press',
		'f4_press',
	);
}

#endregion Popup Meta Field Groups

#region Popup Theme Meta Field Groups

/**
 * @param $groups
 *
 * @return array
 */
function pum_deprecated_v6_popup_theme_meta_field_groups( $groups ) {
	return array_merge( $groups, array( 'overlay', 'container', 'title', 'content', 'close' ) );
}

/**
 * @return array
 */
function popmake_popup_theme_meta_field_group_overlay() {
	return array(
		'background_color',
		'background_opacity'
	);
}

/**
 * @return array
 */
function popmake_popup_theme_meta_field_group_container() {
	return array(
		'padding',
		'background_color',
		'background_opacity',
		'border_radius',
		'border_style',
		'border_color',
		'border_width',
		'boxshadow_inset',
		'boxshadow_horizontal',
		'boxshadow_vertical',
		'boxshadow_blur',
		'boxshadow_spread',
		'boxshadow_color',
		'boxshadow_opacity',
	);
}

/**
 * @return array
 */
function popmake_popup_theme_meta_field_group_title() {
	return array(
		'font_color',
		'line_height',
		'font_size',
		'font_family',
		'font_weight',
		'font_style',
		'text_align',
		'textshadow_horizontal',
		'textshadow_vertical',
		'textshadow_blur',
		'textshadow_color',
		'textshadow_opacity',
	);
}

/**
 * @return array
 */
function popmake_popup_theme_meta_field_group_content() {
	return array(
		'font_color',
		'font_family',
		'font_weight',
		'font_style',
	);
}

/**
 * @return array
 */
function popmake_popup_theme_meta_field_group_close() {
	return array(
		'text',
		'padding',
		'height',
		'width',
		'location',
		'position_top',
		'position_left',
		'position_bottom',
		'position_right',
		'line_height',
		'font_color',
		'font_size',
		'font_family',
		'font_weight',
		'font_style',
		'background_color',
		'background_opacity',
		'border_radius',
		'border_style',
		'border_color',
		'border_width',
		'boxshadow_inset',
		'boxshadow_horizontal',
		'boxshadow_vertical',
		'boxshadow_blur',
		'boxshadow_spread',
		'boxshadow_color',
		'boxshadow_opacity',
		'textshadow_horizontal',
		'textshadow_vertical',
		'textshadow_blur',
		'textshadow_color',
		'textshadow_opacity',
	);
}

#endregion Popup Theme Meta Field Groups

function popmake_render_theme_styles( $popup_theme_id ) {
	$styles = '';

	$theme_data = get_post($popup_theme_id);
	$slug = $theme_data->post_name != $popup_theme_id ? $theme_data->post_name : false;

	$theme_styles = popmake_generate_theme_styles( $popup_theme_id );

	if ( empty( $theme_styles ) ) {
		return '';
	}

	foreach ( $theme_styles as $element => $rules ) {
		switch ( $element ) {
			case 'overlay':
				$rule = ".popmake-overlay.theme-{$popup_theme_id}";
				if ( $slug ) {
					$rule .= ", .popmake-overlay.theme-{$slug}";
				}
				break;
			case 'container':
				$rule = ".popmake.theme-{$popup_theme_id}";
				if ( $slug ) {
					$rule .= ", .popmake.theme-{$slug}";
				}
				break;
			case 'close':
				$rule = ".popmake.theme-{$popup_theme_id} > .popmake-close";
				if ( $slug ) {
					$rule .= ", .popmake.theme-{$slug} > .popmake-close";
				}
				break;
			default:
				$rule = ".popmake.theme-{$popup_theme_id} .popmake-{$element}";
				if ( $slug ) {
					$rule .= ", .popmake.theme-{$slug} .popmake-{$element}";
				}
				break;
		}

		$rule_set = $sep = '';
		foreach ( $rules as $key => $value ) {
			if ( ! empty( $value ) ) {
				$rule_set .= $sep . $key . ': ' . $value;
				$sep = '; ';
			}
		}

		$styles .= "$rule { $rule_set } \r\n";
	}

	return $styles;
}