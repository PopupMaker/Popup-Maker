<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Integrations {

	public static $integrations = array();

	public static $preload_posts = false;

	public static function init() {
		self::$integrations = array(
			'nf'           => class_exists( 'Ninja_Forms' ) && ! ( version_compare( get_option( 'ninja_forms_version', '0.0.0' ), '3.0', '<' ) || get_option( 'ninja_forms_load_deprecated', false ) ),
			'gf'           => class_exists( 'RGForms' ),
			'cf7'          => class_exists( 'WPCF7' ) || ( defined( 'WPCF7_VERSION' ) && WPCF7_VERSION ),
			'kingcomposer' => class_exists( 'KingComposer' ) || defined( 'KC_VERSION' ),
		);

		self::$preload_posts = isset( $_GET['page'] ) && $_GET['page'] == 'pum-settings';

		add_filter( 'pum_settings_fields', array( __CLASS__, 'settings_fields' ) );
		add_action( 'pum_preload_popup', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'pum_registered_conditions', array( __CLASS__, 'register_conditions' ) );

		add_action( 'init', array( __CLASS__, 'wp_init_late' ), 99 );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_filter( 'pum_popup_post_type_args', array( __CLASS__, 'popup_post_type_args' ) );
	}

	/**
	 * Adds additional settings to help better integrate with 3rd party plugins.
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public static function settings_fields( $fields = array() ) {

		foreach ( self::$integrations as $key => $enabled ) {
			if ( ! $enabled ) {
				continue;
			}

			switch ( $key ) {

			}
		}

		return $fields;
	}

	public static function enqueue_assets( $popup_id = 0 ) {

		$popup = pum_get_popup( $popup_id );

		if ( ! pum_is_popup( $popup ) ) {
			return;
		}

		// Do stuff here.

	}

	public static function register_conditions( $conditions = array() ) {

		foreach ( self::$integrations as $key => $enabled ) {
			if ( ! $enabled ) {
				continue;
			}

			switch ( $key ) {


			}
		}

		return $conditions;
	}

	/**
	 * Runs during init
	 */
	public static function wp_init_late() {

		/**
		 * Force KingComposer support for popups.
		 */
		if ( self::enabled( 'kingcomposer' ) ) {
			global $kc;
			$kc->add_content_type( 'popup' );
		}
	}

	/**
	 * Runs during admin_init
	 */
	public static function admin_init() {

	}

	/**
	 * Checks if a 3rd party integration should be enabled.
	 *
	 * @param $tag
	 *
	 * @return bool
	 */
	public static function enabled( $tag ) {
		return (bool) isset( self::$integrations[ $tag ] ) && self::$integrations[ $tag ];
	}

	public static function popup_post_type_args( $args = array() ) {

		if ( self::enabled( 'kingcomposer' ) && ( ( is_admin() && isset( $_GET['page'] ) && $_GET['page'] == 'kingcomposer' ) || pum_is_popup_editor() ) ) {
			$args = array_merge( $args, array(
				'public'              => true,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'show_in_nav_menus'   => false,
			) );
		}

		return $args;
	}
}