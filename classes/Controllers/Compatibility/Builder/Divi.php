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
 * Also handles Divi 4-specific compatibility issues where block editor
 * conflicts with the popup editor loading.
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
		// This is due to Divi being both a theme & a plugin, and themes loading much later.
		return true;
	}

	/**
	 * Initialize compatibility hooks.
	 *
	 * @return void
	 */
	public function init() {
		// Delayed initialization due to Divi being a theme and loading after plugins.
		// Use after_setup_theme to ensure theme is fully loaded before settings/post types are registered.
		add_action( 'after_setup_theme', [ $this, 'setup_divi_hooks' ], 5 );
	}

	/**
	 * Set up Divi-specific hooks after Divi has loaded.
	 *
	 * @return void
	 */
	public function setup_divi_hooks() {
		if ( ! $this->is_divi_available() ) {
			return;
		}

		// Hook AFTER Divi 4's priority 100 to override its block editor filter.
		// Divi 4 uses priority 100, so we need priority > 100 to run after it.
		add_filter( 'use_block_editor_for_post_type', [ $this, 'divi4_force_classic_editor' ], 999, 2 );
		// Modify settings UI to reflect Divi 4 incompatibility.
		add_filter( 'pum_settings_fields', [ $this, 'modify_classic_editor_setting' ], 10, 1 );

		// Make popups queryable when Divi builder is loading.
		add_filter( 'popup_maker/popup_post_type_args', [ $this, 'make_popup_queryable_for_divi' ] );

		// Add popup to Divi's supported post types.
		add_filter( 'et_builder_post_types', [ $this, 'add_popup_support' ] );

		// Enable Visual Builder for popup post type.
		add_filter( 'et_fb_is_enabled', [ $this, 'enable_fb_for_popups' ], 10, 2 );
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
	 * Detect if Divi 4 is active (not 5+).
	 *
	 * Divi 4 has a bug where it conflicts with WordPress block editor filter,
	 * preventing popup editor from loading when block editor is enabled.
	 *
	 * @return bool True if Divi 4.x is active.
	 */
	private function is_divi4_active() {
		static $is_divi4 = null;

		if ( null !== $is_divi4 ) {
			return $is_divi4;
		}

		$is_divi4 = false;

		// Check ET_BUILDER_VERSION constant (used by both theme and plugin).
		if ( defined( 'ET_BUILDER_VERSION' ) ) {
			$version = ET_BUILDER_VERSION;
			// Divi 4 versions are 4.0.0 - 4.99.99.
			$is_divi4 = version_compare( $version, '4.0.0', '>=' ) && version_compare( $version, '5.0.0', '<' );
		}

		return $is_divi4;
	}

	/**
	 * Force classic editor for popups when Divi 4 is active.
	 *
	 * This runs at priority 5 (before PostTypes filter at priority 10)
	 * to ensure Divi 4's conflicting filters can't override our setting.
	 *
	 * @param bool   $use_block_editor Whether to use the block editor.
	 * @param string $post_type        The post type.
	 * @return bool Whether to use the block editor.
	 */
	public function divi4_force_classic_editor( $use_block_editor, $post_type ) {
		// Only handle our post types.
		if ( ! in_array( $post_type, [ 'popup', 'popup_theme' ], true ) ) {
			return $use_block_editor;
		}

		// Only force classic editor if Divi 4 is active.
		if ( ! $this->is_divi4_active() ) {
			return $use_block_editor;
		}

		// Force classic editor to prevent Divi 4 block editor conflict.
		// Return false to DISABLE block editor (use classic editor instead).
		return false;
	}

	/**
	 * Modify the classic editor setting for Divi 4 compatibility display.
	 *
	 * Forces the option to be enabled, disables the UI, and adds an explanation.
	 *
	 * @param array $fields Settings fields array.
	 * @return array Modified settings fields.
	 */
	public function modify_classic_editor_setting( $fields ) {
		// Only modify if Divi 4 is active.
		if ( ! $this->is_divi4_active() ) {
			return $fields;
		}

		// Navigate to the classic editor field in the general settings.
		if ( isset( $fields['general']['main']['enable_classic_editor'] ) ) {
			$field = &$fields['general']['main']['enable_classic_editor'];

			// Disable the field in UI and set to checked.
			$field['disabled'] = true;
			// Append Divi 4 note to existing description.
			$field['desc'] .= '<br/><strong style="color: #d63638;">' . esc_html__( 'Divi 4 requires the classic editor for popup editing. This setting is automatically enforced for compatibility.', 'popup-maker' ) . '</strong>';
		}

		return $fields;
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
