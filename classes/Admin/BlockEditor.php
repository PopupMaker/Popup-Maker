<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

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

		// Here for future use.
		// add_action( 'enqueue_block_assets', [ __CLASS__, 'register_block_assets' ] );
	}

	/**
	 * Registers all block assets so that they can be enqueued through Gutenberg in
	 * the corresponding context.
	 *
	 * Passes translations to JavaScript.
	 *
	 * @since 1.10.0
	 */
	public static function register_editor_assets() {
		$build_path = 'dist/block-editor/';

		$script_path       = $build_path . 'block-editor.js';
		$script_asset_path = $build_path . 'block-editor.asset.php';
		$script_asset      = file_exists( Popup_Maker::$DIR . $script_asset_path ) ? require Popup_Maker::$DIR . $script_asset_path : [
			'dependencies' => [],
			'version'      => Popup_Maker::$VER,
		];
		$script_url        = plugins_url( $script_path, Popup_Maker::$FILE );
		wp_enqueue_script( 'popup-maker-block-editor', $script_url, array_merge( $script_asset['dependencies'], [ 'wp-edit-post' ] ), $script_asset['version'] );

		wp_localize_script(
			'popup-maker-block-editor',
			'pum_block_editor_vars',
			[
				'popups'                        => pum_get_all_popups(),
				'popup_trigger_excluded_blocks' => apply_filters(
					'pum_block_editor_popup_trigger_excluded_blocks',
					[
						'core/nextpage',
					]
				),
			]
		);

		$editor_styles_path       = $build_path . 'block-editor-styles.css';
		$editor_styles_asset_path = $build_path . 'block-editor-styles.asset.php';
		$editor_styles_asset      = file_exists( Popup_Maker::$DIR . $editor_styles_asset_path ) ? require Popup_Maker::$DIR . $editor_styles_asset_path : [
			'dependencies' => [],
			'version'      => Popup_Maker::$VER,
		];
		wp_enqueue_style( 'popup-maker-block-editor', plugins_url( $editor_styles_path, Popup_Maker::$FILE ), [], $editor_styles_asset['version'] );

		if ( function_exists( 'wp_set_script_translations' ) ) {
			/**
			 * May be extended to wp_set_script_translations( 'my-handle', 'my-domain',
			 * plugin_dir_path( MY_PLUGIN ) . 'languages' ) ). For details see
			 * https://make.wordpress.org/core/2018/11/09/new-javascript-i18n-support-in-wordpress/
			 */
			wp_set_script_translations( 'popup-maker-block-editor', 'popup-maker' );
		}
	}

	/**
	 * Register assets for individual block styles
	 */
	public static function register_block_assets() {
		$build_path              = 'dist/block-editor/';
		$block_styles_path       = $build_path . 'block-styles.css';
		$block_styles_asset_path = $build_path . 'block-styles.asset.php';
		$block_styles_asset      = file_exists( Popup_Maker::$DIR . $block_styles_asset_path ) ? require Popup_Maker::$DIR . $block_styles_asset_path : [
			'dependencies' => [],
			'version'      => Popup_Maker::$VER,
		];
		wp_enqueue_style( 'popup-maker-block-styles', plugins_url( $block_styles_path, Popup_Maker::$FILE ), [], $block_styles_asset['version'] );
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
