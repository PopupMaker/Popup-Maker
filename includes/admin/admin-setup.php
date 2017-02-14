<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function popmake_prevent_default_theme_deletion( $allcaps, $caps, $args ) {
	global $wpdb;
	if ( isset( $args[0] ) && isset( $args[2] ) && $args[2] == get_option( 'popmake_default_theme' ) && $args[0] == 'delete_post' ) {
		$allcaps[ $caps[0] ] = false;
	}

	return $allcaps;
}

add_filter( 'user_has_cap', 'popmake_prevent_default_theme_deletion', 10, 3 );

function popmake_plugin_action_links( $links, $file ) {

	if ( $file == plugin_basename( POPMAKE ) ) {
		$plugin_action_links = apply_filters( 'popmake_action_links', array(
			'extensions' => '<a href="'. admin_url( 'edit.php?post_type=popup&page=pum-extensions' ) .'">'.__( 'Extensions', 'popup-maker' ).'</a>',
			'settings' => '<a href="'. admin_url( 'edit.php?post_type=popup&page=pum-settings' ) .'">'.__( 'Settings', 'popup-maker' ).'</a>',
		) );

		foreach ( $plugin_action_links as $link ) {
			array_unshift( $links, $link );
		}
	}

	return $links;
}

add_filter( 'plugin_action_links', 'popmake_plugin_action_links', 10, 2 );


function popmake_admin_header() {
	if ( popmake_is_admin_page() ) {
		do_action( 'popmake_admin_header' );
	}
}

add_action( 'admin_header', 'popmake_admin_header' );


function popmake_admin_footer() {
	if ( popmake_is_admin_page() ) {
		do_action( 'popmake_admin_footer' );
	}
}

add_action( 'admin_print_footer_scripts', 'popmake_admin_footer', 1000 );


function popmake_admin_popup_preview() {
	echo do_shortcode( '[popup id="preview" title="' . __( 'A Popup Preview', 'popup-maker' ) . '"]' . popmake_get_default_example_popup_content() . '[/popup]' );
	echo '<div id="popmake-overlay" class="popmake-overlay"></div>';
}


function popmake_post_submitbox_misc_actions() {
	global $post;
	if ( $post && in_array( $post->post_type, array( 'popup', 'popup_theme' ) ) ) : ?>
		<a href="#" id="trigger-popmake-preview" class="popmake-preview button button-large"><span class="dashicons dashicons-visibility"></span></a><?php
	endif;
}

//add_action( 'post_submitbox_start', 'popmake_post_submitbox_misc_actions', 100 );
