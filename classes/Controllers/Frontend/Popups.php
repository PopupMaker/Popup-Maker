<?php
/**
 * Plugin assets controller.
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Controllers\Frontend;

use PopupMaker\Plugin\Controller;
use PUM_Model_Popup as Popup;
use function PopupMaker\set_current_popup;

defined( 'ABSPATH' ) || exit;

/**
 * Assets controller.
 *
 * @since 1.21.0
 */
class Popups extends Controller {

	/**
	 * Popups.
	 *
	 * @var array<int,Popup>
	 */
	private $popups;

	/**
	 * Enqueued popup ids.
	 *
	 * @var int[]
	 */
	private $enqueued = [];

	/**
	 * Cached popup content.
	 *
	 * @var array<int,string>
	 */
	private $content_cache = [];

	/**
	 * Initialize the assets controller.
	 */
	public function init() {
		if ( is_admin() ) {
			return;
		}

		/**
		 * Preload & enqueue popups once WP conditionals are available.
		 *
		 * CRITICAL TIMING REQUIREMENTS (Do NOT change without extensive testing):
		 *
		 * Historical Context:
		 * - v1.20.6 & below: Used wp_enqueue_scripts:11 for 10+ years with ZERO page builder conflicts
		 * - v1.21.0: Changed to wp_head:0 for performance - BROKE page builders
		 * - v1.21.3: Reverted to wp_enqueue_scripts:11 after extensive compatibility testing
		 *
		 * Page Builder Compatibility Analysis:
		 * ❌ wp_head:0        - TOO EARLY: Breaks Beaver Builder, Elementor, Divi (CSS isolation not ready)
		 * ⚠️  wp_head:1        - RISKY: Minimum viable but race conditions possible
		 * ⚠️  wp_enqueue_scripts:10 - RISKY: Same priority as page builders (race conditions)
		 * ✅ wp_enqueue_scripts:11 - SAFE: 10+ years proven, after page builder initialization
		 *
		 * Why wp_enqueue_scripts:11 is the Sweet Spot:
		 * - Page builders initialize at priority 10 (Beaver Builder, Elementor core)
		 * - Priority 11 runs AFTER page builders set up CSS isolation frameworks
		 * - Prevents CSS leakage when popups contain page builder templates
		 * - Battle-tested with thousands of plugin combinations over a decade
		 *
		 * Specific Issues with Earlier Timing:
		 * - Beaver Builder: CSS from popup templates leaks to main page
		 * - Elementor: Asset loading conflicts, DOM manipulation too early
		 * - Sliders: Content not ready for popup processing
		 * - General: Page builder shortcodes execute before isolation context exists
		 *
		 * NEVER change this to earlier than priority 11 without:
		 * 1. Testing with Beaver Builder templates in popups
		 * 2. Testing with Elementor Pro popups enabled
		 * 3. Testing with slider plugins (Revolution, Layer, etc.)
		 *
		 * - No earlier than `wp` suggested, `pre_get_posts:1` at earliest due to missing WP conditionals such as `is_home`.
		 * - No later than `wp_enqueue_scripts:15` as some content processing may have already occurred.
		 */
		add_action( 'wp_enqueue_scripts', [ $this, 'preload_popups' ], 11 );

		// Check content for popup triggers used, enqueue if enabled.
		add_filter( 'the_content', [ $this, 'check_content_for_popups' ] );

		// Render popups in the footer.
		add_action( 'wp_footer', [ $this, 'render_popups' ] );
	}

	/**
	 * Step 1. Enqueue popups once WP conditionals are availble. `wp` suggested, `pre_get_posts` at earliest.
	 *
	 * @return void
	 */
	public function preload_popups() {
		$popups = $this->container->get( 'popups' )->query( [
			'post_status' => [ 'publish', 'private' ],
		] );

		foreach ( $popups as $popup ) {
			set_current_popup( $popup );

			if ( pum_is_popup_loadable( $popup->ID ) ) {
				$this->preload_popup( $popup );
			}
		}

		set_current_popup( null );
	}

	/**
	 * Get array of all loaded popups.
	 *
	 * Calls `preload_popups` if needed.
	 *
	 * @return Popup[]
	 */
	public function get_loaded_popups() {
		if ( ! isset( $this->popups ) ) {
			$this->preload_popups();
		}

		return $this->popups ?? [];
	}

	/**
	 * Preloads popup, if enabled.
	 *
	 * @param int|Popup $popup_id The popup's ID.
	 */
	public function maybe_preload_popup( $popup_id ) {
		$popup = is_object( $popup_id ) ? $popup_id : pum_get_popup( $popup_id );

		if ( $popup && $popup->is_enabled() ) {
			$this->preload_popup( $popup );
		}
	}

	/**
	 * Enqueues popup
	 *
	 * @param Popup $popup
	 *
	 * @return void
	 */
	public function preload_popup( $popup ) {
		// Bail early if the popup is preloaded already.
		if ( in_array( $popup->ID, $this->enqueued, true ) ) {
			return;
		}

		$this->popups[ $popup->ID ]        = $popup;
		$this->enqueued[]                  = $popup->ID;
		$this->content_cache[ $popup->ID ] = $popup->get_content();

		// Fire off preload action.
		do_action( 'pum_preload_popup', $popup->ID );
		// Deprecated filters
		do_action( 'popmake_preload_popup', $popup->ID );
	}

	/**
	 * Preload popup content, only useful for compatibility with 3rd party
	 * plugins that conditionally enqueue scripts based on being rendered.
	 *
	 * @return void
	 */
	public function preload_content() {
		$popups = $this->get_loaded_popups();

		foreach ( $popups as $popup ) {
			// We could try to cache it here like we did before, but it might take memory and cause issues with rendering.
			$this->content_cache[ $popup->ID ] = $popup->get_content();
			// $popup->get_content();
		}
	}

	/**
	 * Step 3. Checks post content to see if there are popups we need to automagically load
	 *
	 * @param string $content The content from the filter.
	 *
	 * @return string The content.
	 */
	public function check_content_for_popups( $content ) {
		// Only search for popups in the main query of a singular page.
		if ( is_singular() && in_the_loop() && is_main_query() ) {
			// Look for popmake-### within class attributes, supporting both single and double quotes.
			preg_match_all( '/class=[\'"][^"\']*?popmake-(\d+)[^"\']*?[\'"]/', $content, $matches, PREG_SET_ORDER );

			foreach ( $matches as $match ) {
				$popup_id = absint( $match[1] );
				if ( $popup_id > 0 ) {
					$this->maybe_preload_popup( $popup_id );
				}
			}
		}

		return $content;
	}

	/**
	 * Get cached popup content if exists.
	 *
	 * @param int $popup_id
	 *
	 * @return null|string
	 */
	public function get_content_cache( $popup_id ) {
		return isset( $this->content_cache[ $popup_id ] ) ? $this->content_cache[ $popup_id ] : null;
	}

	/**
	 * Render the popups in the footer.
	 *
	 * @return void
	 */
	public function render_popups() {
		$popups = $this->get_loaded_popups();

		foreach ( $popups as $popup ) {
			set_current_popup( $popup );
			pum_template_part( 'popup' );
		}

		set_current_popup( null );
	}
}
