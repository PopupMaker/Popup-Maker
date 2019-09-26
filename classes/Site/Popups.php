<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Post_Types
 */
class PUM_Site_Popups {

	/**
	 * @var PUM_Popup|null
	 *
	 * @deprecated 1.8.0
	 */
	public static $current;

	/**
	 * @var WP_Query|null
	 */
	public static $loaded;

	/**
	 * @var array
	 */
	public static $cached_content = array();

	/**
	 * @var array
	 */
	public static $loaded_ids = array();

	/**
	 * Hook the initialize method to the WP init action.
	 */
	public static function init() {

		// Preload the $loaded query.
		add_action( 'init', array( __CLASS__, 'get_loaded_popups' ) );

		// TODO determine if the late priority is needed.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'load_popups' ), 11 );

		add_action( 'wp_footer', array( __CLASS__, 'render_popups' ) );
	}

	/**
	 * Returns the current popup.
	 *
	 * @deprecated 1.8.0
	 *
	 * @param bool|object|null $new_popup
	 *
	 * @return null|PUM_Popup
	 */
	public static function current_popup( $new_popup = false ) {
		global $popup;

		if ( $new_popup !== false ) {
			pum()->current_popup = $new_popup;
			$popup               = $new_popup;
		}

		return pum()->current_popup;
	}

	/**
	 * Gets the loaded popup query.
	 *
	 * @return null|WP_Query
	 */
	public static function get_loaded_popups() {
		if ( ! self::$loaded instanceof WP_Query ) {
			self::$loaded        = new WP_Query();
			self::$loaded->posts = array();
		}

		return self::$loaded;
	}

	/**
	 * Preload popups in the head and determine if they will be rendered or not.
	 *
	 * @uses `pum_preload_popup` filter
	 * @uses `popmake_preload_popup` filter
	 */
	public static function load_popups() {
		if ( is_admin() ) {
			return;
		}

		$popups = pum_get_all_popups();

		if ( ! empty( $popups ) ) {

			foreach ( $popups as $popup ) {
				// Set this popup as the global $current.
				pum()->current_popup = $popup;

				// If the popup is loadable (passes conditions) load it.
				if ( pum_is_popup_loadable( $popup->ID ) ) {
					self::preload_popup( $popup );
				}
			}

			// Clear the global $current.
			pum()->current_popup = null;
		}

	}

	/**
	 * @param PUM_Model_Popup $popup
	 */
	public static function preload_popup( $popup ) {
		// Add to the $loaded_ids list.
		self::$loaded_ids[] = $popup->ID;

		// Add to the $loaded query.
		self::$loaded->posts[] = $popup;
		self::$loaded->post_count ++;

		// Preprocess the content for shortcodes that need to enqueue their own assets.
		self::$cached_content[ $popup->ID ] = $popup->get_content();

		// Fire off preload action.
		do_action( 'pum_preload_popup', $popup->ID );
		// Deprecated filter.
		do_action( 'popmake_preload_popup', $popup->ID );
	}

	// REWRITE THIS
	public static function load_popup( $id ) {
		if ( did_action( 'wp_head' ) && ! in_array( $id, self::$loaded_ids ) ) {
			$args1 = array(
				'post_type' => 'popup',
				'p'         => $id,
			);
			$query = new WP_Query( $args1 );
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) : $query->next_post();
					pum()->current_popup = $query->post;
					self::preload_popup( $query->post );
				endwhile;
				pum()->current_popup = null;
			}
		}

		return;
	}


	/**
	 * Render the popups in the footer.
	 */
	public static function render_popups() {
		$loaded = self::get_loaded_popups();

		if ( $loaded->have_posts() ) {
			while ( $loaded->have_posts() ) : $loaded->next_post();
				pum()->current_popup = $loaded->post;
				pum_template_part( 'popup' );
			endwhile;
			pum()->current_popup = null;
		}
	}

	/**
	 * @param $popup_id
	 *
	 * @return string|bool
	 */
	public static function get_cache_content( $popup_id ) {
		return isset( self::$cached_content[ $popup_id ] ) ? self::$cached_content[ $popup_id ] : false;
	}

}

