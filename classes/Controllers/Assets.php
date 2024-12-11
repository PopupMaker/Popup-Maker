<?php
/**
 * Plugin assets controller.
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Controllers;

use PopupMaker\Base\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Admin assets controller.
 */
class Assets extends Controller {

	/**
	 * Initialize the assets controller.
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ], 1 );
		add_action( 'wp_print_scripts', [ $this, 'autoload_styles_for_scripts' ], 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_scripts' ], 1 );
		add_action( 'admin_print_scripts', [ $this, 'autoload_styles_for_scripts' ], 1 );
	}

	/**
	 * Get list of plugin packages.
	 *
	 * @return array
	 */
	public function get_packages() {
		static $packages;

		if ( $packages ) {
			return $packages;
		}

		$packages = [
			'admin-bar'       => [
				'bundled'  => false,
				'handle'   => 'popup-maker-admin-bar',
				'styles'   => true,
				'varsName' => 'popupMakerAdminBar',
				'vars'     => [
					'i18n' => [
						'instructions' => __( 'After clicking ok, click the element you need a CSS selector for.', 'popup-maker' ),
						'results'      => _x( 'Selector', 'JS alert for CSS get selector tool', 'popup-maker' ),
						'close'        => _x( 'Close', 'JS alert for CSS get selector tool', 'popup-maker' ),
						'copy'         => _x( 'Copy', 'JS alert for CSS get selector tool', 'popup-maker' ),
						'copied'       => _x( 'Copied to clipboard', 'JS alert for CSS get selector tool', 'popup-maker' ),
					],
				],
			],
			'admin-marketing' => [
				'bundled'  => false,
				'handle'   => 'popup-maker-admin-marketing',
				'styles'   => true,
				'varsName' => 'popupMakerAdminMarketing',
				'vars'     => [],
			],
		];

		return $packages;
	}

	/**
	 * Register all package scripts & styles.
	 */
	public function register_scripts() {
		$packages = $this->get_packages();
		$path     = 'dist/packages/';

		foreach ( $packages as $package => $package_data ) {
			$handle = $package_data['handle'];

			$meta_path = $this->container->get_path( "$path/$package.asset.php" );
			$meta      = pum_get_asset_meta( $meta_path, [
				'version' => $this->container->get( 'version' ),
			] );

			$js_deps = isset( $package_data['deps'] ) ? $package_data['deps'] : [];

			if ( $package_data['bundled'] ) {
				pum_register_script( $handle, $this->container->get_url( "$path/$package.js" ), array_merge( $meta['dependencies'], $js_deps ), $meta['version'], true );
			} else {
				wp_register_script( $handle, $this->container->get_url( "$path/$package.js" ), array_merge( $meta['dependencies'], $js_deps ), $meta['version'], true );
			}

			if ( isset( $package_data['styles'] ) && $package_data['styles'] ) {
				$rtl = is_rtl() ? '-rtl' : '';

				if ( $package_data['bundled'] ) {
					pum_register_style( $handle, $this->container->get_url( "$path/$package{$rtl}.css" ), [ 'wp-components', 'wp-block-editor', 'dashicons' ], $meta['version'] );
				} else {
					wp_register_style( $handle, $this->container->get_url( "$path/$package{$rtl}.css" ), [ 'wp-components', 'wp-block-editor', 'dashicons' ], $meta['version'] );
				}
			}

			if ( isset( $package_data['varsName'] ) && ! empty( $package_data['vars'] ) ) {
				$localized_vars = apply_filters( "popup_maker/{$package}_localized_vars", $package_data['vars'] );

				if ( $package_data['bundled'] ) {
					pum_localize_script( $handle, $package_data['varsName'], $localized_vars );
				} else {
					wp_localize_script( $handle, $package_data['varsName'], $localized_vars );
				}
			}

			/**
			 * TODO Create pum_set_script_translations() function.
			 *
			 * May be extended to wp_set_script_translations( 'my-handle', 'my-domain',
			 * plugin_dir_path( MY_PLUGIN ) . 'languages' ) ). For details see
			 * https://make.wordpress.org/core/2018/11/09/new-javascript-i18n-support-in-wordpress/
			 */
			wp_set_script_translations( $handle, 'popup-maker' );
		}
	}

	/**
	 * Auto load styles if scripts are enqueued.
	 */
	public function autoload_styles_for_scripts() {
		$packages = $this->get_packages();

		foreach ( $packages as $package_data ) {
			if ( wp_script_is( $package_data['handle'], 'enqueued' ) ) {
				if ( isset( $package_data['styles'] ) && $package_data['styles'] ) {
					wp_enqueue_style( $package_data['handle'] );
				}
			}
		}
	}
}
