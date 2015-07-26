<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function popmake_enable_popup_classes_in_post_editor_styles_dropdown() {
	// Ultimate MCE Compatibility Check
	$ultmce = get_option( 'jwl_options_group1' );
	$row    = isset( $ultmce['jwl_styleselect_field_id'] ) ? intval( $ultmce['jwl_styleselect_dropdown'] ) : 2;
	add_filter( "mce_buttons_$row", 'popmake_tiny_mce_buttons', 999 );
	add_filter( 'tiny_mce_before_init', 'popmake_tiny_mce_styles_dropdown_options', 999 );
}

add_action( 'admin_init', 'popmake_enable_popup_classes_in_post_editor_styles_dropdown' );


function popmake_tiny_mce_buttons( $buttons ) {
	if ( ! in_array( 'styleselect', $buttons ) ) {
		$buttons[] = 'styleselect';
	}

	return $buttons;
}

function popmake_tiny_mce_styles_dropdown_options( $initArray ) {
	// Add Popup styles to styles dropdown
	$styles = ! empty( $initArray['style_formats'] ) && is_array( json_decode( $initArray['style_formats'] ) ) ? json_decode( $initArray['style_formats'] ) : array();
	foreach ( get_all_popups()->posts as $popup ) {
		$styles[] = array(
			'title'   => "Open Popup - {$popup->post_title}",
			'inline'  => 'span',
			'classes' => "popmake-{$popup->ID}"
		);
	}
	$initArray['style_formats'] = json_encode( $styles );

	return $initArray;
}
