<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all onboarding throughout site admin areas.
 *
 * @since 1.11.0
 */
class PUM_Admin_Onboarding {

	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'set_up_pointers' ) );
	}

	/**
	 * Sets up all guided tours for Popup Maker
	 *
	 * @since 1.11.0
	 */
	public static function set_up_pointers() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$pointers = self::get_pointers_by_screen();

		// Get dismissed pointers.
		$dismissed      = self::get_dismissed_pointers();
		$valid_pointers = array();

		// Cycles through pointers and only add valid ones.
		foreach ( $pointers as $pointer_id => $pointer ) {

			// Skip if pointer isn't an array.
			if ( ! is_array( $pointer ) ) {
				continue;
			}

			$pointer['pointer_id'] = $pointer_id;

			// Skip if pointer is not valid.
			if ( ! self::is_pointer_valid( $pointer ) ) {
				continue;
			}

			// Skip if pointer has already been dismissed.
			if ( in_array( $pointer_id, $dismissed ) )
				continue;

			// Add the pointer to $valid_pointers array
			$valid_pointers['pointers'][] =  $pointer;
		}

		// Bail out if there are no pointers to display.
		if ( empty( $valid_pointers ) ) {
			return;
		}

		// Add pointers style to queue.
		wp_enqueue_style( 'wp-pointer' );

		// Add pointers script to queue. Add custom script.
		wp_enqueue_script( 'pum-pointer', plugins_url( 'js/admin-pointer.js', POPMAKE_DIR ), array( 'wp-pointer' ) );

		// Add pointer options to script.
		wp_localize_script( 'pum-pointer', 'pumPointers', $valid_pointers );
	}

	/**
	 * Retrieves the pointers for the given screen or current screen
	 *
	 * @param bool|WP_Screen $screen Pass false for current screen.
	 * @return array
	 * @since 1.11.0
	 */
	public static function get_pointers_by_screen( $screen = false ) {
		if ( false === $screen || ! is_a( $screen, 'WP_Screen' ) ) {
			$screen = get_current_screen();
		}
		$screen_id = $screen->id;
		$pointers  = apply_filters( 'pum_admin_pointers-' . $screen_id, array() );

		if ( ! $pointers || ! is_array( $pointers ) ) {
			return array();
		}

		return $pointers;
	}

	/**
	 * Retrieves all dismissed pointers by user
	 *
	 * @param int|bool $user_id The ID of the user or false for current user.
	 * @return array The array of pointer ID's that have been dimissed.
	 * @since 1.11.0
	 */
	private static function get_dismissed_pointers( $user_id = false ) {
		if ( false === $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( 0 === intval( $user_id ) ) {
			return array();
		}
		$pointers = explode( ',', (string) get_user_meta( $user_id, 'dismissed_wp_pointers', true ) );
		if ( ! is_array( $pointers ) ) {
			return array();
		}
		return $pointers;
	}

	/**
	 * Ensures pointer is set up correctly.
	 * @param array $pointer The pointer
	 * @return bool
	 * @since 1.11.0
	 */
	private static function is_pointer_valid( $pointer ) {
		return ! empty( $pointer ) && ! empty( $pointer['pointer_id'] ) && ! empty( $pointer['target'] ) && ! empty( $pointer['options'] );
	}
}
