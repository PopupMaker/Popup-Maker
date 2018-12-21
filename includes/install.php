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