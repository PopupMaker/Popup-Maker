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
		// Bail early if the Block Playground is active and ahead of core.
		if ( defined( 'PUM_BLOCK_PLAYGROUND' ) && version_compare( PUM_BLOCK_PLAYGROUND, self::$version, '>' ) ) {
			return;
		}

		// TODO Test if this is needed in core or not.
		add_action( 'enqueue_block_editor_assets', [ 'PUM_Site_Assets', 'register_styles' ] );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'register_editor_assets' ] );
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
		wp_enqueue_script( 'popup-maker-block-editor' );
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
