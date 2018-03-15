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
	 */
	public static $current;

	/**
	 * @var WP_Query|null
	 */
	public static $loaded;

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
	 * @param bool|object|null $new_popup
	 *
	 * @return null|PUM_Popup
	 */
	public static function current_popup( $new_popup = false ) {
		global $popup;

		if ( $new_popup !== false ) {
			self::$current = $new_popup;
			$popup         = $new_popup;
		}

		return self::$current;
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

		// TODO Replace this with PUM_Popup::query when available.
		$query = PUM_Popups::get_all();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) : $query->next_post();

				// Set this popup as the global $current.
				self::current_popup( $query->post );

				// If the popup is loadable (passes conditions) load it.
				if ( pum_is_popup_loadable( $query->post->ID ) ) {
					self::preload_popup( $query->post );
				}

			endwhile;

			// Clear the global $current.
			self::current_popup( null );
		}
	}

	/**
	 * @param $popup PUM_Model_Popup
	 */
	public static function preload_popup( $popup ) {
		// Add to the $loaded_ids list.
		self::$loaded_ids[] = $popup->ID;

		// Add to the $loaded query.
		self::$loaded->posts[] = $popup;
		self::$loaded->post_count ++;

		// Preprocess the content for shortcodes that need to enqueue their own assets.

		PUM_Helpers::do_shortcode( $popup->post_content );

		# TODO cache this content for later in case of double rendering causing breakage.
		# TODO Use this content during rendering as well.

		// Fire off preload action.
		do_action( 'pum_preload_popup', $popup->ID );
		// Deprecated filter.
		do_action( 'popmake_preload_popup', $popup->ID );
	}

	public static function load_popup( $id ) {
		if ( did_action( 'wp_head' ) && ! in_array( $id, self::$loaded_ids ) ) {
			$args1 = array(
				'post_type' => 'popup',
				'p'         => $id,
			);
			$query = new WP_Query( $args1 );
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) : $query->next_post();
					self::current_popup( $query->post );
					self::preload_popup( $query->post );
				endwhile;
				self::current_popup( null );
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
				self::current_popup( $loaded->post );
				popmake_get_template_part( 'popup' );
			endwhile;
			self::current_popup( null );
		}
	}

}

