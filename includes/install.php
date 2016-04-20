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
 * Install Default Theme
 *
 * Installs the default theme and updates the option.
 *
 * @since 1.0
 * @return void
 */
function popmake_install_default_theme() {
	$default_theme = @wp_insert_post(
		array(
			'post_title'     => __( 'Default Theme', 'popup-maker' ),
			'post_status'    => 'publish',
			'post_author'    => 1,
			'post_type'      => 'popup_theme',
			'comment_status' => 'closed',
			'meta_input' => array(
				'_pum_built_in' => 'default-theme',
				'_pum_default_theme' => true
			),
		)
	);
	foreach ( popmake_get_popup_theme_default_meta() as $meta_key => $meta_value ) {
		update_post_meta( $default_theme, $meta_key, $meta_value );
	}
	update_option( 'popmake_default_theme', $default_theme );
	pum_force_theme_css_refresh();
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

	// Exit if not in admin or the transient doesn't exist
	if ( false === get_transient( '_popmake_installed' ) ) {
		return;
	}

	// Delete the transient
	delete_transient( '_popmake_installed' );

	do_action( 'popmake_after_install' );
}
add_action( 'admin_init', 'popmake_after_install' );