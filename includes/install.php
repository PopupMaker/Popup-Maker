<?php
/**
 * Install Function
 *
 * @package    POPMAKE
 * @subpackage  Functions/Install
 * @copyright   Copyright (c) 2014, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Install
 *
 * Runs on plugin install by setting up the post types, custom taxonomies,
 * flushing rewrite rules also creates the plugin and populates the settings
 * fields for those plugin pages. After successful install, the user is
 * redirected to the POPMAKE Welcome screen.
 *
 * @since 1.0
 * @global $wpdb
 * @global $popmake_options
 * @global $wp_version
 * @return void
 */
function popmake_install() {
	global $wpdb, $popmake_options, $wp_version;

	// Setup the Popup & Theme Custom Post Type
	popmake_setup_post_types();

	// Setup the Popup Taxonomies
	popmake_setup_taxonomies();

	// Clear the permalinks
	flush_rewrite_rules();

	// Add Upgraded From Option
	$current_version = get_option( 'popmake_version' );
	if ( $current_version ) {
		update_option( 'popmake_version_upgraded_from', $current_version );
	}

	// Setup some default options
	$options = array();

	// Checks if the purchase page option exists
	if ( ! get_option( 'popmake_default_theme' ) ) {
		// Default Theme
		popmake_install_default_theme();

	}

	if ( ! isset( $popmake_options['popmake_powered_by_size'] ) ) {
		$popmake_options['popmake_powered_by_size'] = '';
	}

	update_option( 'popmake_settings', array_merge( $popmake_options, $options ) );
	update_option( 'popmake_version', POPMAKE_VERSION );

	// Add a temporary option to note that POPMAKE theme is ready for customization
	set_transient( '_popmake_installed', $options, 30 );

	// Bail if activating from network, or bulk
	if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
		return;
	}
	// Add the transient to redirect to welcome page.
	set_transient( '_popmake_activation_redirect', true, 30 );
}

register_activation_hook( POPMAKE, 'popmake_install' );


/**
 * Install Default Theme
 *
 * Installs the default theme and updates the option.
 *
 * @since 1.0
 * @return void
 */
function popmake_install_default_theme() {
	$default_theme = wp_insert_post(
		array(
			'post_title'     => __( 'Default Theme', 'popup-maker' ),
			'post_status'    => 'publish',
			'post_author'    => 1,
			'post_type'      => 'popup_theme',
			'comment_status' => 'closed'
		)
	);
	foreach ( popmake_get_popup_theme_default_meta() as $meta_key => $meta_value ) {
		update_post_meta( $default_theme, $meta_key, $meta_value );
	}
	update_post_meta( $default_theme, 'popup_theme_defaults_set', true );
	update_post_meta( $default_theme, 'popmake_default_theme', true );
	update_option( 'popmake_default_theme', $default_theme );
}


/**
 * Post-installation
 *
 * Runs just after plugin installation and exposes the
 * popmake_after_install hook.
 *
 * @since 1.0
 * @return void
 */
function popmake_after_install() {

	if ( ! is_admin() ) {
		return;
	}

	$popmake_options = get_transient( '_popmake_installed' );

	// Exit if not in admin or the transient doesn't exist
	if ( false === $popmake_options ) {
		return;
	}

	// Delete the transient
	delete_transient( '_popmake_installed' );

	do_action( 'popmake_after_install', $popmake_options );
}

add_action( 'admin_init', 'popmake_after_install' );