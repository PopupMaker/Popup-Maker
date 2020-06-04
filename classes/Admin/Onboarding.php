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
		add_filter( 'pum_admin_pointers-popup', array( __CLASS__, 'popup_editor_main_tour' ) );
		add_filter( 'pum_admin_pointers-edit-popup', array( __CLASS__, 'all_popups_main_tour' ) );
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
		wp_enqueue_script( 'pum-pointer', Popup_Maker::$URL . 'assets/js/admin-pointer.js', array( 'wp-pointer' ) );

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
	 * Appends our main tour for the popup editor to pointers.
	 *
	 * @param array $pointers
	 * @return array $pointers
	 * @since 1.11.0
	 */
	public static function popup_editor_main_tour( $pointers ) {
		/**
		 * For the position, the 'edge' is used as the second parameter
		 * in jQuery's "at" with the opposite in jQuery's "my".
		 * The optional align is used as the first parameter in both "at" and "my".
		 *
		 * @see https://github.com/WordPress/WordPress/blob/master/wp-includes/js/wp-pointer.js#L295
		 * @see https://jqueryui.com/position/
		 */

		$pointers['popup-editor-1'] = array(
			'target' => '#title',
			'options' => array(
				'content' => sprintf( '<h3> %s </h3> <p> %s </p>',
					__( 'Popup Name' ,'popup-maker'),
					__( 'Name your popup so you can find it later. Site visitors will not see this.','popup-maker')
				),
				'position' => array( 'edge' => 'top', 'align' => 'center' )
			)
		);
		$pointers['popup-editor-2'] = array(
			'target' => '#wp-content-editor-container',
			'options' => array(
				'content' => sprintf( '<h3> %s </h3> <p> %s </p>',
					__( 'Popup Content' ,'popup-maker'),
					__( 'Add content for your popup here.','popup-maker')
				),
				'position' => array( 'edge' => 'bottom', 'align' => 'center' )
			)
		);
		$pointers['popup-editor-3'] = array(
			'target' => 'a[href="#pum-popup-settings_triggers"]',
			'options' => array(
				'content' => sprintf( '<h3> %s </h3> <p> %s </p>',
					__( 'Popup Triggers' ,'popup-maker'),
					__( 'Use triggers to choose  what causes the popup to open.','popup-maker')
				),
				'position' => array( 'edge' => 'left', 'align' => 'center' )
			)
		);
		$pointers['popup-editor-4'] = array(
			'target' => 'a[href="#pum-popup-settings_targeting"]',
			'options' => array(
				'content' => sprintf( '<h3> %s </h3> <p> %s </p>',
					__( 'Popup Targeting' ,'popup-maker'),
					__( 'Use targeting to choose where on your site the popup should load and who to show the popup to.','popup-maker')
				),
				'position' => array( 'edge' => 'left', 'align' => 'center'  )
			)
		);
		$pointers['popup-editor-5'] = array(
			'target' => 'a[href="#pum-popup-settings_display"]',
			'options' => array(
				'content' => sprintf( '<h3> %s </h3> <p> %s </p>',
					__( 'Popup Display' ,'popup-maker'),
					__( 'Use display settings to choose where on the screen the popup appears and what it looks like.','popup-maker')
				),
				'position' => array( 'edge' => 'left', 'align' => 'center'  )
			)
		);
		$pointers['popup-editor-6'] = array(
			'target' => 'select#theme_id',
			'options' => array(
				'content' => sprintf( '<h3> %s </h3> <p> %s </p>',
					__( 'Popup Theme' ,'popup-maker'),
					__( 'Choose the popup theme which controls the visual appearance of your popup including; colors, spacing, and fonts.','popup-maker')
				),
				'position' => array( 'edge' => 'bottom', 'align' => 'left'  )
			),
			'pre' => array(
				'clicks' => array(
					'a[href="#pum-popup-settings_display"]',
					'a[href="#pum-popup-settings-display-subtabs_main"]',
				),
			),
		);
		return $pointers;
	}

	/**
	 * Appends our main tour for the All Popups page.
	 *
	 * @param array $pointers
	 * @return array $pointers
	 * @since 1.11.0
	 */
	public static function all_popups_main_tour( $pointers ) {
		$pointers['all-popups-1'] = array(
			'target'  => 'h2.nav-tab-wrapper a:nth-child(4)',
			'options' => array(
				'content'  => sprintf( '<h3> %s </h3> <p> %s </p>',
					__( 'Welcome to Popup Maker!', 'popup-maker' ),
					__( 'Click the "Add New Popup" button to create your first popup.', 'popup-maker' )
				),
				'position' => array( 'edge' => 'top' )
			)
		);

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
