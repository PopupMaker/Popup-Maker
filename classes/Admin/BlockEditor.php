<?php
/**
 * Admin BlockEditor
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

/**
 * Class PUM_Admin_BlockEditor
 *
 * @since 1.10.0
 */
class PUM_Admin_BlockEditor {

	public static $version = '1.0.0';

	/**
	 * Initialize
	 */
	public static function init() {
		// Always register block categories, regardless of block playground
		// Support both WordPress 5.8+ and older versions
		add_filter( 'block_categories_all', [ __CLASS__, 'register_block_categories' ], 10, 2 );
		// add_filter( 'block_categories', [ __CLASS__, 'register_block_categories_legacy' ], 10, 2 );

		// Bail early if the Block Playground is active and ahead of core.
		if ( defined( 'PUM_BLOCK_PLAYGROUND' ) && version_compare( PUM_BLOCK_PLAYGROUND, self::$version, '>' ) ) {
			return;
		}

		// TODO Test if this is needed in core or not.
		add_action( 'enqueue_block_editor_assets', [ 'PUM_Site_Assets', 'register_styles' ] );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'register_editor_assets' ] );
		add_action( 'enqueue_block_assets', [ __CLASS__, 'register_block_assets' ] );
		add_action( 'wp_loaded', [ __CLASS__, 'add_attributes_to_registered_blocks' ], 999 );
	}

	/**
	 * Registers all block assets so that they can be enqueued through Gutenberg in
	 * the corresponding context.
	 *
	 * Passes translations to JavaScript.
	 *
	 * @since 1.10.0
	 */
	public static function register_editor_assets( $hook ) {
		if ( self::load_block_library() ) {
			wp_enqueue_script( 'popup-maker-block-library' );
		}

		wp_enqueue_script( 'popup-maker-block-editor' );
	}

	/**
	 * Register block assets.
	 *
	 * @param string $hook Current page hook.
	 */
	public static function register_block_assets( $hook ) {
		if ( self::load_block_library() ) {
			wp_enqueue_script( 'popup-maker-block-library' );
		}

		wp_enqueue_style( 'popup-maker-block-library-style' );
	}

	/**
	 * Check if the block editor is active.
	 *
	 * @param int|null $post_id Post ID.
	 * @return bool
	 */
	public static function is_block_editor_active( $post_id = null ) {
		// If no post ID is provided, attempt to get it from the global $pagenow.
		global $pagenow;

		// Check that we're on the post editing screen.
		if ( 'post.php' !== $pagenow && 'post-new.php' !== $pagenow ) {
			return false;
		}

		// Determine post type.
		$post_type = null;

		 // phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['post_type'] ) ) {
			$post_type = sanitize_key( $_GET['post_type'] );
		} elseif ( isset( $_GET['post'] ) ) {
			$post_id   = (int) $_GET['post'];
			$post_type = get_post_type( $post_id );
		}
		 // phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! $post_type ) {
			return false;
		}

		return use_block_editor_for_post_type( $post_type );
	}

	/**
	 * Check if the block library should be loaded.
	 *
	 * @return bool
	 */
	private static function load_block_library() {
		return apply_filters( 'popup_maker/block_editor/load_block_library', self::is_block_editor_active() && pum_is_popup_editor() );
	}

	/**
	 * Register custom block categories.
	 *
	 * @param array                   $categories Array of block categories.
	 * @param WP_Block_Editor_Context $editor_context Block editor context.
	 * @return array Modified block categories.
	 * @since 1.10.0
	 */
	public static function register_block_categories( $categories, $editor_context ) {
		// Always add Popup Maker category for better discoverability

		$insert_index = 3;

		// https://pm.local/wp-admin/post.php?post=821&action=edit

		// If in the popup editor insert at index 0.
		if ( isset( $_GET['post'] ) && get_post_type( sanitize_text_field( wp_unslash( $_GET['post'] ) ) ) === 'popup' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$insert_index = 0;
		}

		array_splice( $categories, $insert_index, 0, [
			[
				'slug'  => 'popup-maker',
				'title' => __( 'Popup Maker', 'popup-maker' ),
				'icon'  => pum_asset_url( 'mark.svg' ),
			],
		] );

		return $categories;
	}

	/**
	 * This is needed to resolve an issue with blocks that use the
	 * ServerSideRender component. Registering the attributes only in js
	 * can cause an error message to appear. Registering the attributes in
	 * PHP as well, seems to resolve the issue. Ideally, this bug will be
	 * fixed in the future.
	 *
	 * Reference: https://github.com/WordPress/gutenberg/issues/16850
	 *
	 * @since 1.16.0
	 */
	public static function add_attributes_to_registered_blocks() {
		global $wp_version;

		if ( version_compare( $wp_version, '5.0' ) === -1 ) {
			return;
		}

		$registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();

		foreach ( $registered_blocks as $block ) {
			$block->attributes['openPopupId'] = [
				'type'    => 'string',
				'default' => '',
			];
		}
	}
}
