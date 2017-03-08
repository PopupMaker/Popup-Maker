<?php
/**
 * Admin Pages
 *
 * @package        POPMAKE
 * @subpackage    Admin/Pages
 * @copyright    Copyright (c) 2014, Daniel Iser
 * @license        http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates the admin submenu pages under the Popup Maker menu and assigns their
 * links to global variables
 *
 * @since 1.0
 * @global $popmake_popup_themes_page
 * @global $popmake_settings_page
 * @global $popmake_extensions_page
 * @return void
 */
function popmake_admin_submenu_pages() {
	global $popmake_settings_page, $popmake_tools_page, $popmake_extensions_page;

	$popmake_settings_page = add_submenu_page( 'edit.php?post_type=popup', __( 'Settings', 'popup-maker' ), __( 'Settings', 'popup-maker' ), apply_filters( 'popmake_admin_submenu_settings_capability', 'manage_options' ), 'pum-settings', 'popmake_settings_page' );

	$popmake_tools_page = add_submenu_page( 'edit.php?post_type=popup', __( 'Tools', 'popup-maker' ), __( 'Tools', 'popup-maker' ), apply_filters( 'popmake_admin_submenu_tools_capability', 'manage_options' ), 'pum-tools', 'popmake_tools_page' );

	$popmake_extensions_page = add_submenu_page( 'edit.php?post_type=popup', __( 'Extend', 'popup-maker' ), __( 'Extend', 'popup-maker' ), apply_filters( 'popmake_admin_submenu_extensions_capability', 'edit_posts' ), 'pum-extensions', 'popmake_extensions_page' );

	$popmake_support_page = add_submenu_page( 'edit.php?post_type=popup', __( 'Help & Support', 'popup-maker' ), __( 'Help & Support', 'popup-maker' ), apply_filters( 'popmake_admin_submenu_extensions_capability', 'edit_posts' ), 'pum-support', 'pum_settings_page' );

	$popmake_appearance_themes_page = add_theme_page( __( 'Popup Themes', 'popup-maker' ), __( 'Popup Themes', 'popup-maker' ), 'edit_posts', 'edit.php?post_type=popup_theme' );
}

add_action( 'admin_menu', 'popmake_admin_submenu_pages' );

/**
 * Submenu filter function. Tested with Wordpress 4.1.1
 * Sort and order submenu positions to match our custom order.
 *
 * @since 1.4
 */
function pum_reorder_admin_submenu() {
	global $submenu;

	if ( isset( $submenu['edit.php?post_type=popup'] ) ) {
		// Sort the menu according to your preferences
		usort( $submenu['edit.php?post_type=popup'], 'pum_reorder_submenu_array' );
	}
}

add_action( 'admin_head', 'pum_reorder_admin_submenu' );


/**
 * Reorders the submenu by title.
 *
 * Forces $first_pages to load in order at the beginning of the menu
 * and $last_pages to load in order at the end. All remaining menu items will
 * go out in generic order.
 *
 * @since 1.4
 *
 * @param $a
 * @param $b
 *
 * @return int
 */
function pum_reorder_submenu_array( $a, $b ) {
	$first_pages = apply_filters( 'pum_admin_submenu_first_pages', array(
		__( 'All Popups', 'popup-maker' ),
		__( 'Add New', 'popup-maker' ),
		__( 'All Themes', 'popup-maker' ),
		__( 'Categories', 'popup-maker' ),
		__( 'Tags', 'popup-maker' ),
	) );
	$last_pages  = apply_filters( 'pum_admin_submenu_last_pages', array(
		__( 'Extend', 'popup-maker' ),
		__( 'Settings', 'popup-maker' ),
		__( 'Tools', 'popup-maker' ),
		__( 'Support Forum', 'freemius' ),
		__( 'Account', 'freemius' ),
		__( 'Contact Us', 'freemius' ),
		__( 'Help & Support', 'popup-maker' ),
	) );

	$a_val = strip_tags( $a[0], false );
	$b_val = strip_tags( $b[0], false );

	// Sort First Page Keys.
	if ( in_array( $a_val, $first_pages ) && ! in_array( $b_val, $first_pages ) ) {
		return - 1;
	} elseif ( ! in_array( $a_val, $first_pages ) && in_array( $b_val, $first_pages ) ) {
		return 1;
	} elseif ( in_array( $a_val, $first_pages ) && in_array( $b_val, $first_pages ) ) {
		$a_key = array_search( $a_val, $first_pages );
		$b_key = array_search( $b_val, $first_pages );

		return ( $a_key < $b_key ) ? - 1 : 1;
	}

	// Sort Last Page Keys.
	if ( in_array( $a_val, $last_pages ) && ! in_array( $b_val, $last_pages ) ) {
		return 1;
	} elseif ( ! in_array( $a_val, $last_pages ) && in_array( $b_val, $last_pages ) ) {
		return - 1;
	} elseif ( in_array( $a_val, $last_pages ) && in_array( $b_val, $last_pages ) ) {
		$a_key = array_search( $a_val, $last_pages );
		$b_key = array_search( $b_val, $last_pages );

		return ( $a_key < $b_key ) ? - 1 : 1;
	}

	// Sort remaining keys
	return $a > $b ? 1 : - 1;
}


/**
 *  Determines whether the current admin page is an POPMAKE admin page.
 *
 *  Only works after the `wp_loaded` hook, & most effective
 *  starting on `admin_menu` hook.
 *
 * @since 1.0
 * @return bool True if POPMAKE admin page.
 */
function popmake_is_admin_page() {
	global $pagenow, $typenow, $popmake_popup_themes_page, $popmake_settings_page, $popmake_tools_page, $popmake_extensions_page;

	if ( ! is_admin() || ! did_action( 'wp_loaded' ) ) {
		return false;
	}

	// when editing pages, $typenow isn't set until later!
	if ( empty( $typenow ) ) {
		// try to pick it up from the query string
		if ( ! empty( $_GET['post'] ) ) {
			$post    = get_post( $_GET['post'] );
			$typenow = $post->post_type;
		} // try to pick it up from the quick edit AJAX post
		elseif ( ! empty( $_POST['post_ID'] ) ) {
			$post    = get_post( $_POST['post_ID'] );
			$typenow = $post->post_type;
		}
	}

	if ( 'popup' == $typenow || 'popup_theme' == $typenow ) {
		return true;
	}

	$popmake_admin_pages = apply_filters( 'popmake_admin_pages', array(
		$popmake_popup_themes_page,
		$popmake_settings_page,
		$popmake_tools_page,
		$popmake_extensions_page,
	) );

	// TODO Replace this whole function using the global $hook_suffix which is what add_submenu_page returns.
	if ( in_array( $pagenow, $popmake_admin_pages ) ) {
		return true;
	} else {
		return false;
	}
}


/**
 *  Determines whether the current admin page is an POPMAKE admin popup page.
 *
 *
 * @since 1.0
 * @return bool True if POPMAKE admin popup page.
 */
function popmake_is_admin_popup_page() {
	global $pagenow, $typenow;

	if ( ! is_admin() || ! popmake_is_admin_page() ) {
		return false;
	}

	if ( 'popup' == $typenow && in_array( $pagenow, array( 'post-new.php', 'post.php' ) ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 *  Determines whether the current admin page is an POPMAKE admin theme page.
 *
 *
 * @since 1.0
 * @return bool True if POPMAKE admin theme page.
 */
function popmake_is_admin_popup_theme_page() {
	global $pagenow, $typenow;

	if ( ! is_admin() || ! popmake_is_admin_page() ) {
		return false;
	}

	if ( 'popup_theme' == $typenow && in_array( $pagenow, array( 'post-new.php', 'post.php' ) ) ) {
		return true;
	} else {
		return false;
	}
}