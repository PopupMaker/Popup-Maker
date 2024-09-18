<?php
/**
 * Site Popups
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

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
	public static $cached_content = [];

	/**
	 * @var array
	 */
	public static $loaded_ids = [];

	/**
	 * Hook the initialize method to the WP init action.
	 */
	public static function init() {

		// Preload the $loaded query.
		add_action( 'init', [ __CLASS__, 'init_state' ] );

		// Check content for popups.
		add_filter( 'the_content', [ __CLASS__, 'check_content_for_popups' ] );

		// TODO determine if the late priority is needed.
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'load_popups' ], 11 );

		add_action( 'wp_footer', [ __CLASS__, 'render_popups' ] );
	}

	/**
	 * Initializes this modules variables.
	 *
	 * @return void
	 */
	public static function init_state() {
		self::get_loaded_popups();
	}

	/**
	 * Returns the current popup.
	 *
	 * @param bool|object|null $new_popup
	 *
	 * @return null|PUM_Model_Popup
	 *
	 * @deprecated 1.8.0
	 */
	public static function current_popup( $new_popup = false ) {
		global $popup;

		if ( false !== $new_popup ) {
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
			self::$loaded->posts = [];
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

		$popups = pum_get_all_popups( [ 'post_status' => [ 'publish', 'private' ] ] );

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
	 * Checks post content to see if there are popups we need to automagically load
	 *
	 * @param string $content The content from the filter.
	 * @return string The content.
	 * @since 1.15
	 */
	public static function check_content_for_popups( $content ) {

		// Only search for popups in the main query of a singular page.
		if ( is_singular() && in_the_loop() && is_main_query() ) {
			/**
			 * We want to detect instances of popmake-### but only within classes and not in the actual text.
			 * So, we check to make sure it is wrapped by quotes to make sure it's in the class="" attribute
			 * but also allow for whitespace and characters in case there are classes before or after it.
			 */
			preg_match_all( '/[\'\"][\s\w\-\_]*?popmake-(\d+)[\s\w\-\_]*?[\'\"]/', $content, $matches );

			// Then, if we find any popups, let's preload it.
			foreach ( $matches[1] as $popup_id ) {
				self::preload_popup_by_id_if_enabled( $popup_id );
			}
		}

		return $content;
	}

	/**
	 * Preloads popup, if enabled
	 *
	 * @param int $popup_id The popup's ID.
	 * @since 1.15
	 */
	public static function preload_popup_by_id_if_enabled( $popup_id ) {
		if ( ! in_array( (int) $popup_id, self::$loaded_ids, true ) ) {
			$popup = pum_get_popup( $popup_id );
			if ( $popup->is_enabled() ) {
				self::preload_popup( $popup );
			}
		}
	}

	/**
	 * @param PUM_Model_Popup $popup
	 */
	public static function preload_popup( $popup ) {
		// Bail early if the popup is preloaded already.
		if ( in_array( $popup->ID, self::$loaded_ids, true ) ) {
			return;
		}

		// Add to the $loaded_ids list.
		self::$loaded_ids[] = $popup->ID;

		// Ensure the loaded query is up to date.
		self::get_loaded_popups();

		// Add to the $loaded query.
		self::$loaded->posts[] = $popup;
		++self::$loaded->post_count;

		// Preprocess the content for shortcodes that need to enqueue their own assets.
		self::$cached_content[ $popup->ID ] = $popup->get_content();

		// Fire off preload action.
		do_action( 'pum_preload_popup', $popup->ID );
		// Deprecated filter.
		do_action( 'popmake_preload_popup', $popup->ID );
	}

	/**
	 * REWRITE THIS
	 */
	public static function load_popup( $id ) {
		if ( did_action( 'wp_head' ) && ! in_array( (int) $id, self::$loaded_ids, true ) ) {
			$args1 = [
				'post_type' => 'popup',
				'p'         => $id,
			];
			$query = new WP_Query( $args1 );
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) :
					$query->next_post();
					pum()->current_popup = $query->post;
					self::preload_popup( $query->post );
				endwhile;
				pum()->current_popup = null;
			}
		}
	}


	/**
	 * Render the popups in the footer.
	 */
	public static function render_popups() {
		$loaded = self::get_loaded_popups();

		if ( $loaded->have_posts() ) {
			while ( $loaded->have_posts() ) :
				$loaded->next_post();
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
