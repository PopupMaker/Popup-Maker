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

		add_action( 'enqueue_block_editor_assets', [ 'PUM_Site_Assets', 'register_styles' ] );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'register_editor_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'register_block_assets' ] );
		add_action( 'enqueue_block_assets', [ __CLASS__, 'register_block_assets' ] );
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
		global $wp_version;
		$build_path = 'dist/block-editor/';

		$script_path       = $build_path . 'block-editor.js';
		$script_asset_path = $build_path . 'block-editor.asset.php';
		$script_asset      = file_exists( Popup_Maker::$DIR . $script_asset_path ) ? require Popup_Maker::$DIR . $script_asset_path : [
			'dependencies' => [],
			'version'      => Popup_Maker::$VER,
		];
		$script_url        = plugins_url( $script_path, Popup_Maker::$FILE );
		wp_enqueue_script( 'popup-maker-block-editor', $script_url, array_merge( $script_asset['dependencies'], [ 'wp-edit-post' ] ), $script_asset['version'] );

		self::register_block_assets();

		wp_localize_script(
			'popup-maker-block-editor',
			'pum_block_editor_vars',
			apply_filters(
				'pum_block_editor_vars',
				[
					'compat56'                      => version_compare( $wp_version, '5.6' ),
					'popups'                        => pum_get_all_popups(),
					'ctas'                          => PUM_CallToActions::instance()->get_as_array(),
					'popup_trigger_excluded_blocks' => apply_filters(
						'pum_block_editor_popup_trigger_excluded_blocks',
						[
							'core/nextpage',
							'pum/call-to-action',
							'pum/call-to-actions',
						]
					),
				]
			)
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
}
