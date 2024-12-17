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
		add_action( 'enqueue_block_editor_assets', [ $this, 'register_scripts' ], 1 );
		add_action( 'admin_print_scripts', [ $this, 'autoload_styles_for_scripts' ], 1 );
		// Add a hook to fix old handles that might be enqueueed and not loaded, load their replacements.
		add_action( 'wp_enqueue_scripts', [ $this, 'fix_old_handles' ], 1 );
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
				'handle' => 'popup-maker-admin-marketing',
				'styles' => true,
			],
			'block-editor'    => [
				'handle'   => 'popup-maker-block-editor',
				'styles'   => true,
				'varsName' => 'popupMakerBlockEditor',
				'vars'     => [
					'popups'                     => pum_get_all_popups(),
					'popupTriggerExcludedBlocks' => apply_filters(
						'pum_block_editor_popup_trigger_excluded_blocks',
						[
							'core/nextpage',
						]
					),
				],
			],
			'block-library'   => [
				'bundled'  => false,
				'handle'   => 'popup-maker-block-library',
				'styles'   => true,
				'varsName' => 'popupMakerBlockLibrary',
				'vars'     => [],
			],
			'components'      => [
				'bundled'  => false,
				'handle'   => 'popup-maker-components',
				'styles'   => true,
				'varsName' => 'popupMakerComponents',
				'vars'     => function () {
					return [
						'popups' => \pum_get_all_popups(),
					];
				},
			],
			'core-data'       => [
				'bundled'  => false,
				'handle'   => 'popup-maker-core-data',
				'styles'   => false,
				'varsName' => 'popupMakerCoreData',
				'vars'     => function () {
					return [
						// TODO Migrate to use plugin('options')->get_all();
						'currentSettings' => \pum_get_options(),
					];
				},
			],
			'data'            => [
				'bundled'  => false,
				'handle'   => 'popup-maker-data',
				'styles'   => false,
				'varsName' => 'popupMakerData',
				'vars'     => [],
			],
			'fields'          => [
				'bundled'  => false,
				'handle'   => 'popup-maker-fields',
				'styles'   => false,
				'varsName' => 'popupMakerFields',
				'vars'     => [],
			],
			'icons'           => [
				'bundled'  => false,
				'handle'   => 'popup-maker-icons',
				'styles'   => true,
				'varsName' => 'popupMakerIcons',
				'vars'     => [],
			],
			'utils'           => [
				'bundled'  => false,
				'handle'   => 'popup-maker-utils',
				'styles'   => false,
				'varsName' => 'popupMakerUtils',
				'vars'     => [],
			],
		];

		return $packages;
	}

	/**
	 * Register all package scripts & styles.
	 */
	public function register_scripts() {
		static $registered;

		if ( $registered ) {
			return;
		}
		$registered = true;

		$path          = 'dist/packages';
		$packages_meta = pum_get_asset_group_meta( 'package', [
			'version' => $this->container->get( 'version' ),
		] );

		$packages = $this->get_packages();

		$screen = is_admin() ? get_current_screen() : false;
		$rtl    = is_rtl() ? '-rtl' : '';

		foreach ( $packages as $package => $package_data ) {
			if (
			! isset( $package_data['handle'] ) ||
			! isset( $packages_meta[ "$package.js" ] )
			) {
				// Skip packages that don't have a handle or meta.
				continue;
			}

			$handle       = $package_data['handle'];
			$package_data = wp_parse_args( $package_data, [
				'bundled' => true,
			] );

			$bundled = (bool) $package_data['bundled'];

			$meta = $packages_meta[ "$package.js" ];

			$js_file = $this->container->get_url( "$path/$package.js" );
			$js_deps = array_merge(
				// Automated dependency registration.
				$meta['dependencies'],
				// Manual dependency registration.
				isset( $package_data['deps'] ) ? $package_data['deps'] : []
			);

			if ( 'block-editor' === $package ) {
				if ( 'widgets' !== $screen->id ) {
					$js_deps = array_merge( $js_deps, [ 'wp-edit-post' ] );
				}
			}

			if ( $bundled ) {
				pum_register_script( $handle, $js_file, $js_deps, $meta['version'], true );
			} else {
				// Though pum_* asset functions pass through to wp_* automatically when disabled, admin packages should never be bundled.
				wp_register_script( $handle, $js_file, $js_deps, $meta['version'], true );
			}

			if ( isset( $package_data['styles'] ) && $package_data['styles'] ) {
				$css_file = $this->container->get_url( "$path/$package{$rtl}.css" );
				$css_deps = [ 'wp-components', 'wp-block-editor', 'dashicons' ];

				if ( $bundled ) {
					pum_register_style( $handle, $css_file, $css_deps, $meta['version'] );
				} else {
					// Though pum_* asset functions pass through to wp_* automatically when disabled, admin packages should never be bundled.
					wp_register_style( $handle, $css_file, $css_deps, $meta['version'] );
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
	 * Setup global vars.
	 *
	 * @return void
	 */
	public function setup_global_vars() {
		static $inited;

		if ( $inited ) {
			return;
		}

		$inited = true;

		add_action( 'wp_footer', [ $this, 'print_global_vars' ] );
		add_action( 'admin_footer', [ $this, 'print_global_vars' ] );
	}

	/**
	 * Get global vars.
	 *
	 * @return array
	 */
	private function get_global_vars() {
		$additional_global_vars = is_admin() ?
		$this->get_admin_global_vars() :
		$this->get_frontend_global_vars();

		return apply_filters(
			'popup_maker/global_vars',
			array_merge(
				[
					'version'   => $this->container->get( 'version' ),
					'pluginUrl' => $this->container->get_url( '' ),
					'assetsUrl' => $this->container->get_url( 'assets/' ),
					'nonce'     => wp_create_nonce( 'popup-maker' ),
				],
				$additional_global_vars
			)
		);
	}

	/**
	 * Get admin-onlyglobal vars.
	 *
	 * @return array
	 */
	private function get_admin_global_vars() {
		return apply_filters( 'popup_maker/admin_global_vars', [
			'adminUrl' => admin_url(),
		] );
	}

	/**
	 * Get frontend-only global vars.
	 *
	 * @return array
	 */
	private function get_frontend_global_vars() {
		return apply_filters( 'popup_maker/frontend_global_vars', [] );
	}

	/**
	 * Print global vars.
	 *
	 * @return void
	 */
	public function print_global_vars() {
		static $printed;

		if ( $printed ) {
			return;
		}

		$printed = true;

		$global_vars = $this->get_global_vars();

		?>
		<script id="popup-maker-global-vars">
		window.popupMaker = window.popupMaker || {};
		window.popupMaker.globalVars = <?php echo wp_json_encode( $global_vars ); ?>;
		</script>
		<?php
	}

	/**
	 * Auto load styles if scripts are enqueued.
	 */
	public function autoload_styles_for_scripts() {
		$packages = $this->get_packages();

		foreach ( $packages as $package => $package_data ) {
			if ( ! isset( $package_data['handle'] ) ) {
				// Skip packages that don't have a handle or meta.
				continue;
			}

			$handle       = $package_data['handle'];
			$package_data = wp_parse_args( $package_data, [
				'bundled' => true,
			] );

			$bundled = (bool) $package_data['bundled'];

			if ( wp_script_is( $handle, 'enqueued' ) ) {
				$this->setup_global_vars();

				if ( isset( $package_data['styles'] ) && $package_data['styles'] ) {
					if ( $bundled ) {
						pum_enqueue_style( $handle );
					} else {
						// Though pum_* asset functions pass through to wp_* automatically when disabled, admin packages should never be bundled.
						wp_enqueue_style( $handle );
					}
				}

				if ( isset( $package_data['varsName'] ) && ! empty( $package_data['vars'] ) ) {
					$localized_vars = is_callable( $package_data['vars'] ) ?
						call_user_func( $package_data['vars'] ) :
						$package_data['vars'];

					$localized_vars = apply_filters( "popup_maker/{$package}_localized_vars", $localized_vars );

					if ( $bundled ) {
						pum_localize_script( $handle, $package_data['varsName'], $localized_vars );
					} else {
						// Though pum_* asset functions pass through to wp_* automatically when disabled, admin packages should never be bundled.
						wp_localize_script( $handle, $package_data['varsName'], $localized_vars );
					}
				}
			}
		}
	}

	/**
	 * Fix old handles that might be enqueueed and not loaded, load their replacements.
	 *
	 * @return void
	 */
	public function fix_old_handles() {
	}
}
