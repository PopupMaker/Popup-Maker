<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pum_initialize_deprecated_v6() {

	// Disable the old meta storage methods.
	if ( pum_get_db_ver() >= 6 ) {

	} else {
		add_filter( 'popmake_popup_meta_field_groups', 'pum_deprecated_v6_popup_meta_field_groups' );
		add_filter( 'popmake_popup_meta_field_group_display', 'popmake_popup_meta_field_group_display', 0 );
		add_filter( 'popmake_popup_meta_field_group_close', 'popmake_popup_meta_field_group_close', 0 );
	}

}

add_action( 'pum_initialize_deprecated', 'pum_initialize_deprecated_v6' );

function pum_deprecated_v6_popup_meta_field_groups( $groups ) {
	return array_merge( $groups, array( 'display', 'close' ) );
}

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

function popmake_popup_meta_field_group_close() {
	return array(
		'text',
		'button_delay',
		'overlay_click',
		'esc_press',
		'f4_press',
	);
}
