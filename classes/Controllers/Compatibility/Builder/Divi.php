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
		// Intercept requests for specific popups during Divi builder.
		add_action( 'pre_get_posts', [ $this, 'allow_popup_query_for_divi' ] );

		// Prevent other popups from loading during Divi builder sessions.
		add_filter( 'pum_popup_is_loadable', [ $this, 'prevent_other_popups_during_builder' ], 10, 2 );

		// Use custom template for popup rendering during Divi builder.
		// add_filter( 'template_include', [ $this, 'use_popup_template_for_divi' ] );

		// Add popup to Divi's supported post types.
		add_filter( 'et_builder_post_types', [ $this, 'add_popup_support' ] );

		// Add popup to Divi's post type options if available.
		add_filter( 'et_builder_enabled_builder_post_types', [ $this, 'add_popup_support' ] );

		// Force admin_debug trigger for popup being edited.
		add_filter( 'pum_popup_triggers', [ $this, 'force_admin_debug_trigger' ], 10, 2 );

		// Disable close functionality during builder sessions.
		add_filter( 'pum_popup_close_disabled', [ $this, 'disable_close_during_builder' ], 10, 2 );

		// Add Divi-specific classes to popup content for builder compatibility.
		add_filter( 'pum_popup_content_classes', [ $this, 'add_divi_popup_classes' ], 10, 2 );
		add_action( 'wp_head', [ $this, 'add_divi_builder_styles' ] );

		// Override popup content to use the_content() for proper builder integration.
		add_filter( 'pum_popup_content', [ $this, 'use_the_content_for_builder' ], 10, 2 );

		// Disable footer popup rendering entirely during builder sessions.
		add_action( 'init', [ $this, 'disable_footer_popup_rendering' ], 20 );
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
	 * Allow querying specific popup during Divi builder requests.
	 *
	 * @param WP_Query $query The WordPress query object.
	 * @return void
	 */
	public function allow_popup_query_for_divi( $query ) {
		if ( ! $this->is_divi_available() ) {
			return;
		}

		// Only affect main query on frontend.
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// Check for Divi builder parameters.
		if ( ! $this->is_divi_builder_request() ) {
			return;
		}

		// Verify user has permission.
		if ( ! is_user_logged_in() || ! current_user_can( $this->container->get_permission( 'edit_popups' ) ) ) {
			return;
		}

		// Get post ID from various possible query string parameters.
		$post_id = $this->get_popup_id_from_request();
		if ( ! $post_id ) {
			return;
		}

		// Verify it's a popup and user can edit it.
		if ( 'popup' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if this query is trying to access our specific popup.
		$queried_id = $query->get( 'p' ) ?: $query->get( 'page_id' ) ?: $query->get( 'post_id' );
		if ( $post_id === (int) $queried_id ) {
			// Temporarily make this specific popup queryable by modifying the query.
			$query->set( 'post_type', [ 'popup' ] );
			$query->set( 'post_status', [ 'publish', 'private', 'draft' ] );
		}
	}

	/**
	 * Control popup loading during Divi builder sessions.
	 *
	 * During Divi builder sessions, we disable other popups but force the popup
	 * being edited to load with admin_debug trigger for visibility.
	 *
	 * @param bool $is_loadable Whether the popup is loadable.
	 * @param int  $popup_id    The popup ID.
	 * @return bool Modified loadable status.
	 */
	public function prevent_other_popups_during_builder( $is_loadable, $popup_id ) {
		if ( ! $this->is_divi_available() ) {
			return $is_loadable;
		}

		// Check if we're in a Divi builder request.
		if ( ! $this->is_divi_builder_request() ) {
			return $is_loadable;
		}

		// Get the popup being edited.
		$edited_popup_id = $this->get_popup_id_from_request();

		// Force the popup being edited to be loadable, disable others.
		if ( $edited_popup_id && $popup_id === $edited_popup_id ) {
			return true;
		}

		// Disable all other popups during Divi builder sessions.
		return false;
	}

	/**
	 * Use appropriate template for Divi builder rendering.
	 *
	 * @param string $template The template file to load.
	 * @return string Modified template path.
	 */
	public function use_popup_template_for_divi( $template ) {
		if ( ! $this->is_divi_available() ) {
			return $template;
		}

		// Check if we're in a Divi builder request.
		if ( ! $this->is_divi_builder_request() ) {
			return $template;
		}

		// Check if this is a popup being edited.
		if ( ! is_singular( 'popup' ) ) {
			return $template;
		}

		// Verify user has permission.
		if ( ! is_user_logged_in() || ! current_user_can( $this->container->get_permission( 'edit_popups' ) ) ) {
			return $template;
		}

		// Get popup ID and verify user can edit it.
		$popup_id = get_the_ID();
		if ( ! $popup_id || ! current_user_can( 'edit_post', $popup_id ) ) {
			return $template;
		}

		// Use the popup template for proper rendering within popup container.
		$popup_template = \PUM_Utils_Template::locate( 'single-popup.php' );
		if ( $popup_template ) {
			return $popup_template;
		}

		return $template;
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

		// Only on frontend, never on admin pages.
		if ( is_admin() ) {
			return false;
		}

		// Don't apply during AJAX requests to prevent recursion.
		if ( wp_doing_ajax() ) {
			return false;
		}

		// Don't apply if we're inside an iframe to prevent recursion.
		// Use JavaScript to detect iframe context client-side.
		if ( $this->is_iframe_request() ) {
			return false;
		}

		// Check for Visual Builder parameters.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['et_fb'] ) || isset( $_GET['et_bfb'] );
	}

	/**
	 * Check if current request is inside an iframe.
	 *
	 * @return bool
	 */
	private function is_iframe_request() {
		// Add JavaScript to detect iframe context and set a flag.
		// This needs to be done client-side since PHP can't detect iframe context.
		if ( ! isset( $_COOKIE['et_fb_iframe_context'] ) ) {
			// Set a cookie to detect iframe context on next request.
			add_action( 'wp_footer', [ $this, 'add_iframe_detection_script' ], 1 );
			return false;
		}

		// Check if we're in iframe context based on cookie.
		return $_COOKIE['et_fb_iframe_context'] === '1';
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

	/**
	 * Force admin_debug trigger for popup being edited during Divi builder.
	 *
	 * @param array $triggers Current popup triggers.
	 * @param int   $popup_id The popup ID.
	 * @return array Modified triggers.
	 */
	public function force_admin_debug_trigger( $triggers, $popup_id ) {
		if ( ! $this->is_divi_available() ) {
			return $triggers;
		}

		// Check if we're in a Divi builder request.
		if ( ! $this->is_divi_builder_request() ) {
			return $triggers;
		}

		// Get the popup being edited.
		$edited_popup_id = $this->get_popup_id_from_request();

		// Force admin_debug trigger only for the popup being edited.
		if ( $edited_popup_id && $popup_id === $edited_popup_id ) {
			return [
				[
					'type' => 'admin_debug',
				],
			];
		}

		return $triggers;
	}

	/**
	 * Disable close functionality during Divi builder sessions.
	 *
	 * @param bool $disabled Whether close is disabled.
	 * @param int  $popup_id The popup ID.
	 * @return bool Modified disabled status.
	 */
	public function disable_close_during_builder( $disabled, $popup_id ) {
		if ( ! $this->is_divi_available() ) {
			return $disabled;
		}

		// Check if we're in a Divi builder request.
		if ( ! $this->is_divi_builder_request() ) {
			return $disabled;
		}

		// Get the popup being edited.
		$edited_popup_id = $this->get_popup_id_from_request();

		// Disable close only for the popup being edited.
		if ( $edited_popup_id && $popup_id === $edited_popup_id ) {
			return true;
		}

		return $disabled;
	}

	/**
	 * Add Divi-specific classes to popup during builder sessions.
	 *
	 * @param array  $classes   Current popup classes.
	 * @param int    $popup_id  The popup ID.
	 * @param string $context   The class context (popup, container, content, etc.).
	 * @return array Modified popup classes.
	 */
	public function add_divi_popup_classes( $classes, $popup_id ) {
		if ( ! $this->is_divi_available() ) {
			return $classes;
		}

		// Check if we're in a Divi builder request.
		if ( ! $this->is_divi_builder_request() ) {
			return $classes;
		}

		// Get the popup being edited.
		$edited_popup_id = $this->get_popup_id_from_request();

		// Only add classes to the popup being edited.
		if ( ! $edited_popup_id || $popup_id !== $edited_popup_id ) {
			return $classes;
		}

		// Add Divi classes based on context.
		$classes[] = 'et_pb_pagebuilder_layout';
		$classes[] = 'et_pb_post';

		return $classes;
	}

	/**
	 * Add Divi builder styles for popup rendering.
	 *
	 * @return void
	 */
	public function add_divi_builder_styles() {
		if ( ! $this->is_divi_available() ) {
			return;
		}

		// Check if we're in a Divi builder request.
		if ( ! $this->is_divi_builder_request() ) {
			return;
		}

		// Add styles for proper popup display in Divi builder.
		?>
		<script>
			// After dom ready remove class .et-fb-root-ancestor et-fb-iframe-ancestor from pum-overlay, pum-container, and pum-content
			document.addEventListener('DOMContentLoaded', function() {
				setTimeout(function() {
				document.querySelector('.pum-overlay').classList.remove('et-fb-root-ancestor');
				document.querySelector('.pum-overlay').classList.remove('et-fb-iframe-ancestor');
				document.querySelector('.pum-container').classList.remove('et-fb-root-ancestor');
				document.querySelector('.pum-container').classList.remove('et-fb-iframe-ancestor');
				document.querySelector('.pum-content').classList.remove('et-fb-root-ancestor');
				document.querySelector('.pum-content').classList.remove('et-fb-iframe-ancestor');
				}, 3000);
			});
		</script>
		<style>
		/* Override Divi's iframe ancestor styles that break popup overlay */
		.pum-overlay,
		.pum-container,
		.pum-popup {
			/* width: auto !important;
			max-width: none !important;
			height: auto !important;
			margin: revert !important;
			padding: revert !important;
			display: revert !important;
			border: revert !important;
			box-shadow: revert !important;
			transform: revert !important;
			filter: revert !important;
			position: revert !important; */
		}

		/* Restore proper popup positioning and display */
		.pum-overlay {
			/* position: fixed !important;
			top: 0 !important;
			left: 0 !important;
			right: 0 !important;
			bottom: 0 !important;
			z-index: 1999999999 !important;
			display: none !important; */
		}

		.pum-overlay.pum-active {
			/* display: flex !important;
			align-items: center !important;
			justify-content: center !important; */
		}

		.pum-container {
			/* position: relative !important;
			max-width: 90vw !important;
			max-height: 90vh !important;
			margin: auto !important;
			overflow: visible !important; */
		}

		.pum-popup {
			/* position: relative !important;
			display: block !important;
			background: white !important;
			padding: 20px !important;
			border: 2px dashed #ccc !important;
			box-shadow: 0 4px 20px rgba(0,0,0,0.1) !important;
			overflow: auto !important;
			max-height: 80vh !important; */
		}

		.pum-popup::before {
			content: "Popup: <?php echo esc_js( get_the_title() ); ?>" !important;
			display: block !important;
			margin-bottom: 10px !important;
			padding-bottom: 10px !important;
			border-bottom: 1px solid #eee !important;
			color: #666 !important;
			font-weight: bold !important;
			font-size: 14px !important;
		}
		</style>
		<?php
	}

	/**
	 * Use the_content() instead of raw post content for Divi builder.
	 *
	 * @param string $content  The popup content.
	 * @param int    $popup_id The popup ID.
	 * @return string Modified content.
	 */
	public function use_the_content_for_builder( $content, $popup_id ) {
		if ( ! $this->is_divi_available() ) {
			return $content;
		}

		// Check if we're in a Divi builder request.
		if ( ! $this->is_divi_builder_request() ) {
			return $content;
		}

		// Get the popup being edited.
		$edited_popup_id = $this->get_popup_id_from_request();

		// Only override content for the popup being edited.
		if ( $edited_popup_id && $popup_id === $edited_popup_id ) {
			// Set up the post data for the_content() to work properly.
			global $post;
			$original_post = $post;
			$post          = get_post( $popup_id );

			if ( $post ) {
				setup_postdata( $post );
				$content = get_the_content();
				$content = apply_filters( 'the_content', $content );
				wp_reset_postdata();
			}

			$post = $original_post;
		}

		return $content;
	}

	/**
	 * Add iframe detection script to determine if we're in an iframe.
	 *
	 * @return void
	 */
	public function add_iframe_detection_script() {
		?>
		<script>
		(function() {
			// Detect if we're in an iframe and set a cookie accordingly
			var inIframe = window.location !== window.parent.location;
			var cookieName = 'et_fb_iframe_context';
			var cookieValue = inIframe ? '1' : '0';

			// Set the cookie
			document.cookie = cookieName + '=' + cookieValue + '; path=/; SameSite=Lax';
		})();
		</script>
		<?php
	}

	/**
	 * Disable footer popup rendering during Divi builder sessions.
	 *
	 * @return void
	 */
	public function disable_footer_popup_rendering() {
		if ( ! $this->is_divi_available() ) {
			return;
		}

		// Check if we're in a Divi builder request.
		if ( ! $this->is_divi_builder_request() ) {
			return;
		}

		// Remove popup rendering actions to prevent footer loading.
		remove_action( 'wp_footer', 'pum_print_popups_to_footer' );
		remove_action( 'pum_print_popup_to_footer', 'pum_print_popup_to_footer' );

		// Also remove any legacy hooks that might render popups.
		remove_action( 'wp_footer', 'popmake_print_popups_to_footer' );
	}
}
