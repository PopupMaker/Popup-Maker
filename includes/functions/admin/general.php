<?php
/**
 * Functions for General Admin
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Gets the current admin screen post type.
 *
 * @return bool|string
 */
function pum_typenow() {
	if ( ! empty( $GLOBALS['typenow'] ) ) {
		return $GLOBALS['typenow'];
	}

	// Ignored because these are used for current page detection only.
	// phpcs:disable WordPress.Security.NonceVerification

	// when editing pages, $typenow isn't set until later!
	// try to pick it up from the query string
	if ( ! empty( $_GET['post_type'] ) ) {
		return sanitize_key( wp_unslash( $_GET['post_type'] ) );
	} elseif ( ! empty( $_GET['post'] ) && absint( $_GET['post'] ) > 0 ) {
		$post = get_post( absint( $_GET['post'] ) );
	} elseif ( ! empty( $_POST['post_ID'] ) && absint( $_POST['post_ID'] ) > 0 ) {
		$post = get_post( absint( $_POST['post_ID'] ) );
	}

	// phpcs:enable WordPress.Security.NonceVerification

	return isset( $post ) && is_object( $post ) && $post->ID > 0 ? $post->post_type : false;
}

/**
 * Generates an Popup Maker admin URL based on the given type.
 *
 * @since 1.7.0
 *
 * @param string $type       Optional. Type of admin URL. Accepts 'tools', 'settings'. Default empty
 * @param array  $query_args Optional. Query arguments to append to the admin URL. Default empty array.
 *
 * @return string Constructed admin URL.
 */
function pum_admin_url( $type = '', $query_args = [] ) {
	$page = '';

	$whitelist = PUM_Admin_Pages::$pages;

	if ( in_array( $type, $whitelist, true ) ) {
		$page = "pum-{$type}";
	}

	$admin_query_args = array_merge( [ 'page' => $page ], $query_args );

	$url = add_query_arg( $admin_query_args, admin_url( 'edit.php?post_type=popup' ) );

	/**
	 * Filters the Popup Maker admin URL.
	 *
	 * @param string $url        Admin URL.
	 * @param string $type       Admin URL type.
	 * @param array  $query_args Query arguments originally passed to pum_admin_url().
	 */
	return apply_filters( 'pum_admin_url', $url, $type, $query_args );
}

/**
 * @return array
 */
function pum_support_assist_args() {
	return [
		// Forces the dashboard to force logout any users.
		'nouser' => true,
		'fname'  => wp_get_current_user()->first_name,
		'lname'  => wp_get_current_user()->last_name,
		'email'  => wp_get_current_user()->user_email,
		'url'    => home_url(),
	];
}
