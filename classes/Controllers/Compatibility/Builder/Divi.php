<?php
/**
 * Divi Builder Compatibility Controller.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Controllers\Compatibility\Builder;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Divi Builder Compatibility Controller.
 *
 * Enables Divi's Visual Builder for popup post types by temporarily
 * making them queryable when the builder is active.
 *
 * @since 1.21.0
 */
class Divi extends Controller {

	/**
	 * Check if controller should be enabled.
	 *
	 * @return bool
	 */
	public function controller_enabled() {
		// Always return true so controller gets initialized regardless of timing.
		// We handle Divi availability checks in individual methods.
		return true;
	}

	/**
	 * Initialize compatibility hooks.
	 *
	 * @return void
	 */
	public function init() {
		// Make popups queryable when Divi builder is loading.
		add_filter( 'popup_maker/popup_post_type_args', [ $this, 'make_popup_queryable_for_divi' ] );

		// Add popup to Divi's supported post types.
		add_filter( 'et_builder_post_types', [ $this, 'add_popup_support' ] );

		// Enable Visual Builder for popup post type.
		add_filter( 'et_fb_is_enabled', [ $this, 'enable_fb_for_popups' ], 10, 2 );

		// Add popup to Divi's post type options if available.
		add_filter( 'et_builder_enabled_builder_post_types', [ $this, 'add_popup_support' ] );
	}

	/**
	 * Check if Divi is available (theme or plugin).
	 *
	 * Uses the same detection logic as Divi itself to detect both theme and plugin.
	 *
	 * @return bool
	 */
	private function is_divi_available() {
		static $is_divi_available = null;

		if ( null !== $is_divi_available ) {
			return $is_divi_available;
		}

		// Divi Theme detection: ET_BUILDER_THEME constant or et_divi_fonts_url function.
		$theme_available = ( defined( 'ET_BUILDER_THEME' ) && ET_BUILDER_THEME ) || function_exists( 'et_divi_fonts_url' );

		// Divi Plugin detection: Plugin version constant or main plugin class.
		$plugin_available = defined( 'ET_BUILDER_PLUGIN_VERSION' ) || class_exists( 'ET_Builder_Plugin' );

		// Also check for general ET_BUILDER_VERSION which both use.
		$general_available = defined( 'ET_BUILDER_VERSION' ) || function_exists( 'et_setup_builder' );

		$is_divi_available = $theme_available || $plugin_available || $general_available;
		return $is_divi_available;
	}

	/**
	 * Add popup post type to Divi's builder post types.
	 *
	 * @param array $post_types Existing post types.
	 *
	 * @return array Modified post types.
	 */
	public function add_popup_support( $post_types ) {
		if ( ! $this->is_divi_available() ) {
			return $post_types;
		}

		if ( ! in_array( 'popup', $post_types, true ) ) {
			$post_types[] = 'popup';
		}
		return $post_types;
	}

	/**
	 * Make popups queryable when Divi builder is active.
	 *
	 * @param array $popup_args Popup post type arguments.
	 * @return array Modified popup post type arguments.
	 */
	public function make_popup_queryable_for_divi( $popup_args ) {
		if ( ! $this->is_divi_available() ) {
			return $popup_args;
		}

		// Check for Divi builder parameters.
		if ( ! $this->is_divi_builder_request() ) {
			return $popup_args;
		}

		// Verify user has permission.
		if ( ! is_user_logged_in() || ! current_user_can( $this->container->get_permission( 'edit_popups' ) ) ) {
			return $popup_args;
		}

		// Get post ID from various possible query string parameters.
		$post_id = $this->get_popup_id_from_request();
		if ( ! $post_id ) {
			return $popup_args;
		}

		// Verify it's a popup (this might be redundant but good for safety).
		if ( $post_id && 'popup' !== get_post_type( $post_id ) ) {
			return $popup_args;
		}

		// Verify user can edit this specific popup.
		if ( $post_id && ! current_user_can( 'edit_post', $post_id ) ) {
			return $popup_args;
		}

		// Make popups publicly queryable for this request.
		$popup_args['publicly_queryable'] = true;

		return $popup_args;
	}

	/**
	 * Enable Visual Builder for popup posts.
	 *
	 * @param bool|null $enabled Current enabled status.
	 * @param int       $post_id Post ID being checked.
	 *
	 * @return bool|null Whether Visual Builder is enabled.
	 */
	public function enable_fb_for_popups( $enabled, $post_id ) {
		if ( ! $this->is_divi_available() ) {
			return $enabled;
		}

		if ( 'popup' === get_post_type( $post_id ) ) {
			return current_user_can( 'edit_post', $post_id );
		}
		return $enabled;
	}

	/**
	 * Check if current request is for Divi builder.
	 *
	 * @return bool
	 */
	private function is_divi_builder_request() {
		if ( ! $this->is_divi_available() ) {
			return false;
		}

		// Check for Visual Builder parameters.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['et_fb'] ) || isset( $_GET['et_bfb'] );
	}

	/**
	 * Get popup ID from request parameters.
	 *
	 * @return int Post ID or 0 if not found.
	 */
	private function get_popup_id_from_request() {
		// Try different parameter names that Divi might use.
		$possible_params = [ 'p', 'post', 'post_id', 'et_post_id' ];

		foreach ( $possible_params as $param ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET[ $param ] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$post_id = absint( $_GET[ $param ] );
				if ( $post_id > 0 ) {
					return $post_id;
				}
			}
		}

		// Try to get from current page if we're on a singular popup.
		if ( is_singular( 'popup' ) ) {
			return get_the_ID();
		}

		return 0;
	}
}
