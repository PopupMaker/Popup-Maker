<?php
/**
 * Integrations for cf7
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_CF7_Integration
 */
class PUM_CF7_Integration {

	/**
	 * Initialize if CF7 is active.
	 */
	public static function init() {
		add_filter( 'pum_get_cookies', [ __CLASS__, 'register_cookies' ] );
		add_action( 'popmake_preload_popup', [ __CLASS__, 'preload' ] );
	}

	/**
	 * Check if the popups use CF7 Forms and force enqueue their assets.
	 *
	 * @param $popup_id
	 */
	public static function preload( $popup_id ) {
		$popup = pum_get_popup( $popup_id );

		if ( has_shortcode( $popup->post_content, 'contact-form-7' ) ) {
			if ( defined( 'WPCF7_LOAD_JS' ) && ! WPCF7_LOAD_JS ) {
				return;
			}

			if ( function_exists( 'wpcf7_enqueue_scripts' ) ) {
				wpcf7_enqueue_scripts();
			}

			if ( function_exists( 'wpcf7_enqueue_styles' ) ) {
				wpcf7_enqueue_styles();
			}
		}
	}

	/**
	 * Registers new cookie events.
	 *
	 * @param array $cookies
	 *
	 * @return array
	 */
	public static function register_cookies( $cookies = [] ) {
		$cookies['cf7_form_success'] = [
			'labels' => [
				'name' => __( 'Contact Form 7 Success (deprecated. Use Form Submission instead.)', 'popup-maker' ),
			],
			'fields' => pum_get_cookie_fields(),
		];

		return $cookies;
	}
}
