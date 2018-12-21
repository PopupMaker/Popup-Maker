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
