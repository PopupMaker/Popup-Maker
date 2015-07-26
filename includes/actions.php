<?php
/**
 * Front-end Actions
 *
 * @package     POPMAKE
 * @subpackage  Functions
 * @copyright   Copyright (c) 2015, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.3.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hooks Popup Maker actions, when present in the $_GET superglobal. Every popmake_action
 * present in $_GET is called using WordPress's do_action function. These
 * functions are called on init.
 *
 * @since 1.0
 * @return void
 */
function popmake_get_actions() {
	if ( isset( $_GET['popmake_action'] ) ) {
		do_action( 'popmake_' . $_GET['popmake_action'], $_GET );
	}
}

add_action( 'init', 'popmake_get_actions' );

/**
 * Hooks Popup Maker actions, when present in the $_POST superglobal. Every popmake_action
 * present in $_POST is called using WordPress's do_action function. These
 * functions are called on init.
 *
 * @since 1.0
 * @return void
 */
function popmake_post_actions() {
	if ( isset( $_POST['popmake_action'] ) ) {
		do_action( 'popmake_' . $_POST['popmake_action'], $_POST );
	}
}

add_action( 'init', 'popmake_post_actions' );