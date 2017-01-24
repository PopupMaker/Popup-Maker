<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function popmake_init_popups() {
	global $popmake_loaded_popups, $popmake_loaded_popup_ids, $popmake_enqueued_popups;

	if ( ! $popmake_loaded_popups instanceof WP_Query ) {
		$popmake_loaded_popups        = new WP_Query();
		$popmake_loaded_popups->posts = array();
	}
	if ( ! $popmake_loaded_popup_ids || ! is_array( $popmake_loaded_popup_ids ) ) {
		$popmake_loaded_popup_ids = array();
	}
	if ( ! $popmake_enqueued_popups || ! is_array( $popmake_enqueued_popups ) ) {
		$popmake_enqueued_popups = array();
	}
}

add_action( 'init', 'popmake_init_popups' );


function popmake_load_popup( $id ) {
	global $popmake_loaded_popups, $popmake_loaded_popup_ids, $popmake_enqueued_popups;
	if ( did_action( 'wp_head' ) && ! in_array( $id, $popmake_loaded_popup_ids ) ) {
		$args1 = array(
			'post_type' => 'popup',
			'p'         => $id
		);
		$query = new WP_Query( $args1 );
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) : $query->next_post();
				do_action( 'popmake_preload_popup', $query->post->ID );
				$popmake_loaded_popups->posts[] = $query->post;
				$popmake_loaded_popups->post_count ++;
				popmake_enqueue_scripts( $query->post->ID );
			endwhile;
		}
	} elseif ( ! did_action( 'wp_head' ) && ! in_array( $id, $popmake_enqueued_popups ) ) {
		$popmake_enqueued_popups[] = $id;
	}

	return;
}


function popmake_enqueue_popup( $id ) {
	return popmake_load_popup( $id );
}


function get_enqueued_popups() {
	global $popmake_enqueued_popups;
	$popmake_enqueued_popups = apply_filters( 'popmake_get_enqueued_popups', $popmake_enqueued_popups );

	return $popmake_enqueued_popups;
}


function popmake_preload_popups() {
	global $popmake_loaded_popups, $popmake_loaded_popup_ids;

	$query = new WP_Query( array(
		'post_type'      => 'popup',
		'posts_per_page' => - 1
	) );

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) : $query->next_post();
			if ( pum_is_popup_loadable( $query->post->ID ) ) {
				$popmake_loaded_popup_ids[] = $query->post->ID;
				do_action( 'popmake_preload_popup', $query->post->ID );
				$popmake_loaded_popups->posts[] = $query->post;
				$popmake_loaded_popups->post_count ++;
			}
		endwhile;

	}
}

add_action( 'wp_enqueue_scripts', 'popmake_preload_popups', 11 );


function popmake_render_popups() {
	global $popmake_loaded_popups, $popup;

	if ( ! $popmake_loaded_popups instanceof WP_Query ) {
		$popmake_loaded_popups        = new WP_Query();
		$popmake_loaded_popups->posts = array();
	}

	if ( $popmake_loaded_popups->have_posts() ) {
		while ( $popmake_loaded_popups->have_posts() ) : $popmake_loaded_popups->next_post();
			$popup = $popmake_loaded_popups->post;
			popmake_get_template_part( 'popup' );
		endwhile;
		$popup = null;
	}
}

add_action( 'wp_footer', 'popmake_render_popups', 1 );
