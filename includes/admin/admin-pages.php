<?php
/**
 * Admin Pages
 *
 * @package		POPMAKE
 * @subpackage	Admin/Pages
 * @copyright	Copyright (c) 2014, Daniel Iser
 * @license		http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Creates the admin submenu pages under the Popup Maker menu and assigns their
 * links to global variables
 *
 * @since 1.0
 * @global $popmake_popup_themes_page
 * @global $popmake_settings_page
 * @global $popmake_add_ons_page
 * @global $popmake_help_page
 * @return void
 */
function popmake_admin_submenu_pages() {
	global $popmake_settings_page, $popmake_add_ons_page, $popmake_help_page, $popmake_about_page, $popmake_changelog_page, $popmake_getting_started_page, $popmake_credits_page;

	$popmake_settings_page	= add_submenu_page(
		'edit.php?post_type=popup',
		apply_filters( 'popmake_admin_submenu_settings_page_title', __( 'Settings', 'popup-maker' ) ),
		apply_filters( 'popmake_admin_submenu_settings_menu_title', __( 'Settings', 'popup-maker' ) ),
		apply_filters( 'popmake_admin_submenu_settings_capability', 'manage_options' ),
		'settings',
		apply_filters( 'popmake_admin_submenu_settings_function', 'popmake_settings_page' )
	);

	$popmake_add_ons_page	= add_submenu_page(
		'edit.php?post_type=popup',
		apply_filters( 'popmake_admin_submenu_extensions_page_title', __( 'Extensions', 'popup-maker' ) ),
		apply_filters( 'popmake_admin_submenu_extensions_menu_title', __( 'Extensions', 'popup-maker' ) ),
		apply_filters( 'popmake_admin_submenu_extensions_capability', 'manage_options' ),
		'extensions',
		apply_filters( 'popmake_admin_submenu_extensions_function', 'popmake_extensions_page' )
	);

	$popmake_help_page	= add_submenu_page(
		'edit.php?post_type=popup',
		apply_filters( 'popmake_admin_submenu_help_page_title', __( 'Help', 'popup-maker' ) ),
		apply_filters( 'popmake_admin_submenu_help_menu_title', __( 'Help', 'popup-maker' ) ),
		apply_filters( 'popmake_admin_submenu_help_capability', 'edit_posts' ),
		'help',
		apply_filters( 'popmake_admin_submenu_help_function', 'popmake_help_page' )
	);

	// About Page
	$popmake_about_page = add_dashboard_page(
		__( 'Welcome to Popup Maker', 'popup-maker' ),
		__( 'Welcome to Popup Maker', 'popup-maker' ),
		'manage_options',
		'popmake-about',
		'popmake_about_page'
	);

	// Changelog Page
	$popmake_changelog_page = add_dashboard_page(
		__( 'Popup Maker Changelog', 'popup-maker' ),
		__( 'Popup Maker Changelog', 'popup-maker' ),
		'manage_options',
		'popmake-changelog',
		'popmake_changelog_page'
	);

	// Getting Started Page
	$popmake_getting_started_page = add_dashboard_page(
		__( 'Getting started with Popup Maker', 'popup-maker' ),
		__( 'Getting started with Popup Maker', 'popup-maker' ),
		'manage_options',
		'popmake-getting-started',
		'popmake_getting_started_page'
	);

	// Credits Page
	$popmake_credits_page = add_dashboard_page(
		__( 'The people that build Popup Maker', 'popup-maker' ),
		__( 'The people that build Popup Maker', 'popup-maker' ),
		'manage_options',
		'popmake-credits',
		'popmake_credits_page'
	);

}
add_action( 'admin_menu', 'popmake_admin_submenu_pages', 11 );


function popmake_remove_admin_subpages() {
	remove_submenu_page( 'index.php', 'popmake-about' );
	remove_submenu_page( 'index.php', 'popmake-changelog' );
	remove_submenu_page( 'index.php', 'popmake-getting-started' );
	remove_submenu_page( 'index.php', 'popmake-credits' );
}
//add_action( 'admin_head', 'popmake_remove_admin_subpages' );



/**
 * Creates the admin submenu pages for theme editor in the Popup Maker menu and assigns its
 * link to global variables
 *
 * @since 1.0
 * @global $popmake_popup_themes_page
 * @return void
 */
function popmake_admin_submenu_theme_pages() {
	global $submenu, $popmake_popup_themes_page;
	
    $popmake_popup_themes_page = admin_url( 'post.php?post='. popmake_get_default_popup_theme() .'&action=edit' );
    $submenu['edit.php?post_type=popup'][] = array(
    	apply_filters( 'popmake_admin_submenu_themes_page_title', __( 'Theme', 'popup-maker' ) ),
    	apply_filters( 'popmake_admin_submenu_themes_capability', 'edit_themes' ),
    	$popmake_popup_themes_page
    );
}
add_action( 'admin_menu', 'popmake_admin_submenu_theme_pages', 10 );

/**
 *  Determines whether the current admin page is an POPMAKE admin page.
 *  
 *  Only works after the `wp_loaded` hook, & most effective 
 *  starting on `admin_menu` hook.
 *  
 *  @since 1.0
 *  @return bool True if POPMAKE admin page.
 */
function popmake_is_admin_page() {

	if ( ! is_admin() || ! did_action( 'wp_loaded' ) ) {
		return false;
	}
	
	global $pagenow, $typenow, $popmake_popup_themes_page, $popmake_settings_page, $popmake_add_ons_page, $popmake_help_page;

	if ( 'popup' == $typenow || 'popup_theme' == $typenow ) {
		return true;
	}

	if ( 'index.php' == $pagenow && isset($_GET['page']) && in_array($_GET['page'], array('popmake-about', 'popmake-changelog', 'popmake-getting-started', 'popmake-credits')) ) {
		return true;
	}

	$popmake_admin_pages = apply_filters( 'popmake_admin_pages', array( $popmake_popup_themes_page, $popmake_settings_page, $popmake_add_ons_page, $popmake_help_page ) );
	
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
 *  @since 1.0
 *  @return bool True if POPMAKE admin popup page.
 */
function popmake_is_admin_popup_page() {

	if ( ! is_admin() || ! popmake_is_admin_page() ) {
		return false;
	}
	
	global $pagenow, $typenow;

	if ( 'popup' == $typenow && ( $pagenow == 'post-new.php' || $pagenow == 'post.php' || $pagenow == 'edit.php' )) {
		return true;
	}
	else {
		return false;
	}
}

/**
 *  Determines whether the current admin page is an POPMAKE admin theme page.
 *  
 *  
 *  @since 1.0
 *  @return bool True if POPMAKE admin theme page.
 */
function popmake_is_admin_popup_theme_page() {

	if ( ! is_admin() || ! popmake_is_admin_page() ) {
		return false;
	}
	
	global $pagenow, $typenow;

	if ( 'popup_theme' == $typenow && ( $pagenow == 'post-new.php' || $pagenow == 'post.php' || $pagenow == 'edit.php' )) {
		return true;
	}
	else {
		return false;
	}
}