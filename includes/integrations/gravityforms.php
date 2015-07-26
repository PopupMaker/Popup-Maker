<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function popmake_enqueue_gravityforms_during_preload( $popup_id ) {
	if ( function_exists( 'gravity_form_enqueue_scripts' ) ) {
		$regex = "/\[gravityform.*id=[\'\"]?([0-9]*)[\'\"]?.*/";
		$popup = get_post( $popup_id );
		preg_match_all( $regex, $popup->post_content, $matches );
		foreach ( $matches[1] as $form_id ) {
			add_filter( "gform_confirmation_anchor_{$form_id}", create_function( "", "return false;" ) );
			gravity_form_enqueue_scripts( $form_id, true );
		}
	}
}

add_action( 'popmake_preload_popup', 'popmake_enqueue_gravityforms_during_preload' );

function popmake_force_gforms_ajax( $out ) {
	$out['ajax'] = 'true';

	return $out;
}

function popmake_gforms_force_ajax() {
	if ( current_action() == 'popmake_popup_before_inner' ) {
		add_filter( 'shortcode_atts_gravityforms', 'popmake_force_gforms_ajax' );
	}
	if ( current_action() == 'popmake_popup_after_inner' ) {
		remove_filter( 'shortcode_atts_gravityforms', 'popmake_force_gforms_ajax' );
	}
}

add_action( 'popmake_popup_before_inner', 'popmake_gforms_force_ajax' );
add_action( 'popmake_popup_after_inner', 'popmake_gforms_force_ajax' );
