<?php
/**
 * Site class
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

/**
 * Class PUM_Site
 */
class PUM_Site {

	public static function init() {
		PUM_Site_Assets::init();
		PUM_Site_Popups::init();
		PUM_Analytics::init();

		self::add_core_content_filters();

		add_action( 'init', [ __CLASS__, 'actions' ] );
	}

	/**
	 * Hook core filters into `pum_popup_content`.
	 */
	public static function add_core_content_filters() {
		global $wp_version;

		/**
		 * Copied from wp-includes/class-wp-embed.php:32:40
		 *
		 * @note Hack to get the [embed] shortcode to run before wpautop().
		 *
		 * @since 1.4 hooks & filters
		 */
		add_filter( 'pum_popup_content', [ $GLOBALS['wp_embed'], 'run_shortcode' ], 8 );
		add_filter( 'pum_popup_content', [ $GLOBALS['wp_embed'], 'autoembed' ], 8 );

		/**
		 * Copied & from wp-includes/default-filters.php:141:144.
		 *
		 * Format WordPress.
		 *
		 * @since 1.10.0
		 * @sinceWP 5.4
		 */
		foreach ( [ 'pum_popup_content', 'pum_popup_title' ] as $filter ) {
			add_filter( $filter, 'capital_P_dangit', 11 );
		}

		/**
		 * Copied & from wp-includes/default-filters.php:172:178.
		 *
		 * @since 1.10.0
		 * @sinceWP 5.4
		 */
		if ( version_compare( $wp_version, '5.0.0', '>=' ) ) {
			add_filter( 'pum_popup_content', [ __CLASS__, 'do_blocks' ], 9 );
		}
		add_filter( 'pum_popup_content', 'wptexturize' );
		add_filter( 'pum_popup_content', 'convert_smilies', 20 );
		add_filter( 'pum_popup_content', 'wpautop' );
		add_filter( 'pum_popup_content', 'shortcode_unautop' );
		add_filter( 'pum_popup_content', 'prepend_attachment' );
		if ( version_compare( $wp_version, '5.5', '>=' ) ) {
			add_filter( 'pum_popup_content', 'wp_filter_content_tags' );
		} else {
			add_filter( 'pum_popup_content', 'wp_make_content_images_responsive' );
		}

		/**
		 * Copied & from wp-includes/default-filters.php:172:178.
		 *
		 * @note Shortcodes must run AFTER wpautop().
		 *
		 * @since 1.10.0
		 * @sinceWP 5.4
		 */
		$do_shortcode_handler = pum_get_option( 'disable_shortcode_compatibility_mode' ) ? 'do_shortcode' : [ 'PUM_Helpers', 'do_shortcode' ];
		add_filter( 'pum_popup_content', $do_shortcode_handler, 11 );
	}

	/**
	 * Parses dynamic blocks out of `post_content` and re-renders them.
	 *
	 * @since 1.10.0
	 * @sinceWP 5.0.0
	 *
	 * @param string $content Post content.
	 * @return string Updated post content.
	 */
	public static function do_blocks( $content ) {
		$blocks = parse_blocks( $content );
		$output = '';

		foreach ( $blocks as $block ) {
			$output .= render_block( $block );
		}

		// If there are blocks in this content, we shouldn't run wpautop() on it later.
		$priority = has_filter( 'pum_popup_content', 'wpautop' );
		if ( false !== $priority && doing_filter( 'pum_popup_content' ) && has_blocks( $content ) ) {
			remove_filter( 'pum_popup_content', 'wpautop', $priority );
			add_filter( 'pum_popup_content', [ __CLASS__, '_restore_wpautop_hook' ], $priority + 1 );
		}

		return $output;
	}

	/**
	 * If do_blocks() needs to remove wpautop() from the `pum_popup_content` filter, this re-adds it afterwards,
	 * for subsequent `pum_popup_content` usage.
	 *
	 * @access private
	 *
	 * @since 1.10.0
	 * @sinceWP 5.0.0
	 *
	 * @param string $content The post content running through this filter.
	 * @return string The unmodified content.
	 */
	public static function _restore_wpautop_hook( $content ) {
		$current_priority = has_filter( 'pum_popup_content', [ __CLASS__, '_restore_wpautop_hook' ] );

		add_filter( 'pum_popup_content', 'wpautop', $current_priority - 1 );
		remove_filter( 'pum_popup_content', [ __CLASS__, '_restore_wpautop_hook' ], $current_priority );

		return $content;
	}

	/**
	 * Hooks Popup Maker actions, when present in the $_GET superglobal. Every popmake_action
	 * present in $_GET is called using WordPress's do_action function. These
	 * functions are called on init.
	 */
	public static function actions() {
		if ( empty( $_REQUEST['pum_action'] ) ) {
			return;
		}

		$valid_actions = apply_filters(
			'pum_valid_request_actions',
			[
				'save_enabled_betas',
				'download_batch_export',
				'empty_error_log',
			]
		);

		$action = sanitize_text_field( $_REQUEST['pum_action'] );

		if ( ! in_array( $action, $valid_actions ) || ! has_action( 'pum_' . $action ) ) {
			return;
		}

		do_action( 'pum_' . $action, $_REQUEST );
	}
}
