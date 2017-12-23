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
