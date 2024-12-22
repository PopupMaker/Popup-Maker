<?php
/**
 * Main plugin.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Plugin;

use PopupMaker\Base\Container;
use PopupMaker\Interfaces\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 *
 * @since X.X.X
 */
class Core {

	/**
	 * Exposed container.
	 *
	 * @var Container
	 */
	public $container;

	/**
	 * Array of controllers.
	 *
	 * Useful to unhook actions/filters from global space.
	 *
	 * @var Container
	 */
	public $controllers;

	/**
	 * Initiate the plugin.
	 *
	 * @param array<string,string|bool> $config Configuration variables passed from main plugin file.
	 */
	public function __construct( $config ) {
		$this->container   = new Container( $config );
		$this->controllers = new Container();

		$this->register_services();
		$this->define_paths();
		$this->initiate_controllers();

		$this->check_version();

		add_action( 'init', [ $this, 'load_textdomain' ] );
	}

	/**
	 * Check if this is the core plugin or an extension.
	 *
	 * @return bool
	 */
	public function is_core_plugin() {
		return get_called_class() === __CLASS__;
	}

	/**
	 * Check if this is the core plugin or an extension.
	 *
	 * @return bool
	 */
	public function is_addon_plugin() {
		return ! $this->is_core_plugin();
	}

	/**
	 * Update & track version info.
	 *
	 * @return void
	 */
	protected function check_version() {
		// Get the version of the current plugin code.
		$version = $this->get( 'version' );

		// Get the plugin version stored in the database.
		$current_data = \get_option( 'popup_maker_version_info', false );

		$data = wp_parse_args(
			// If the current data exists, use it.
			false !== $current_data ? $current_data : [],
			[
				'version'         => $version,
				'upgraded_from'   => null,
				'initial_version' => $version,
				'installed_on'    => gmdate( 'Y-m-d H:i:s' ),
			]
		);

		// Process old version data storage, only runs once ever.
		if ( false === $current_data ) {
			$data = $this->process_version_data_migration( $data );

			if ( \update_option( 'popup_maker_version_info', $data ) ) {
				\PopupMaker\cleanup_old_install_data();
			}
		}

		if ( version_compare( $data['version'], (string) $version, '<' ) ) {
			// Allow processing of small core upgrades.

			/**
			 * Fires when the plugin version is updated.
			 *
			 * Note: Old version is still available in options.
			 *
			 * @param string $old_version The old version.
			 * @param string $new_version The new version.
			 */
			do_action( 'popup_maker/update_version', $data['version'], $version );

			/**
			 * Fires when the plugin version is updated.
			 *
			 * Allow processing of small core upgrades
			 *
			 * @param string $version The old version.
			 *
			 * @since 1.8.0
			 * @deprecated X.X.X
			 */
			do_action( 'pum_update_core_version', $data['version'] );

			// Save Upgraded From option.
			$data['upgraded_from'] = $data['version'];
			$data['version']       = $version;
		}

		if ( $current_data !== $data ) {
			\update_option( 'popup_maker_version_info', $data );
		}
	}

	/**
	 * Look for old version data and migrate it to the new format.
	 *
	 * @param array<string,string|null> $data Array of data.
	 *
	 * @return array{
	 *     version: string,
	 *     upgraded_from: string,
	 *     initial_version: string,
	 *     installed_on: string,
	 * }
	 */
	protected function process_version_data_migration( $data ) {
		// This class can be extended for addons, only do the following if this is core and not an extended class.
		// If the current instance is not an extended class, check if old settings exist.
		if ( $this->is_core_plugin() ) {
			$version         = \PopupMaker\detect_previous_install_version();
			$initial_version = \PopupMaker\detect_initial_install_version();
			$installed_on    = \PopupMaker\detect_initial_install_date();
			$upgraded_from   = get_option( 'pum_ver_upgraded_from', null );

			$data = [
				// Setting to 0.0.0 if not set forces a "migration" to run.
				'version'         => $version ? $version : '0.0.0',
				'upgraded_from'   => $upgraded_from ? (string) $upgraded_from : null,
				'initial_version' => $initial_version ? $initial_version : $version,
				'installed_on'    => $installed_on,
			];
		}

		return $data;
	}

	/**
	 * Internationalization.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( $this->container['text_domain'], false, $this->get_path( 'languages' ) );
	}

	/**
	 * Add default services to our Container.
	 *
	 * @return void
	 */
	public function register_services() {
		/**
		 * Self reference for deep DI lookup.
		 */
		$this->container['plugin'] = $this;

		/**
		 * Attach our container to the global.
		 */
		$GLOBALS[ $this->get( 'option_prefix' ) ] = $this->container;

		/**
		 * Check if this is the core plugin.
		 *
		 * Because extensions extend this class for access to services,
		 * we only want to load the core services if this is the core plugin.
		 */
		if ( $this->is_core_plugin() ) {
			$this->container['options'] =
				/**
				 * Get plugin options.
				 *
				 * @return \PopupMaker\Services\Options
				 */
				function ( $container ) {
					return new \PopupMaker\Services\Options( $container );
				};

			$this->container['connect'] =
				/**
				 * Get plugin connect.
				 *
				 * @return Connect
				 */
				function ( $container ) {
					return new \PopupMaker\Services\Connect( $container );
				};

			$this->container['license'] =
				/**
				 * Get plugin license.
				 *
				 * @return License
				 */
				function ( $container ) {
					return new \PopupMaker\Services\License( $container );
				};

			$this->container['logging'] =
				/**
				 * Get plugin logging.
				 *
				 * @return Logging
				 */
				function ( $container ) {
					return new \PopupMaker\Services\Logging( $container );
				};

			$this->container['upgrader'] =
				/**
				 * Get plugin upgrader.
				 *
				 * @return Upgrader
				 */
				function ( $container ) {
					return new \PopupMaker\Services\Upgrader( $container );
				};

			$this->container['ctas'] =
				/**
				 * Get user call to actions from the database.
				 *
				 * @return \PopupMaker\Services\Repository\CallToActions
				 */
				function ( $container ) {
					return new \PopupMaker\Services\Repository\CallToActions( $container );
				};

			$this->container['cta_types'] =
				/**
				 * Get registered call to actions types.
				 *
				 * @return \PopupMaker\Services\Collector\CallToActions
				 */
				function ( $container ) {
					return new \PopupMaker\Services\Collector\CallToActions( $container );
				};

			// $this->container['rules'] =
				/**
				 * Get plugin rules.
				 *
				 * @return \PopupMaker\RuleEngine\Rules
				 */
				// function () {
				// return new \PopupMaker\RuleEngine\Rules();
				// };

			$this->container['globals'] =
				/**
				 * Get plugin global manager.
				 *
				 * @return \PopupMaker\Services\Globals
				 */
				function () {
					return new \PopupMaker\Services\Globals();
				};
		}

		apply_filters( 'popup_maker/register_services', $this->container, $this );
	}

	/**
	 * Update & track version info.
	 *
	 * @return array<string,\PopupMaker\Base\Controller>
	 */
	protected function registered_controllers() {
		return [
			'PostTypes'     => new \PopupMaker\Controllers\PostTypes( $this ),
			'Assets'        => new \PopupMaker\Controllers\Assets( $this ),
			'Admin'         => new \PopupMaker\Controllers\Admin( $this ),
			'Compatibility' => new \PopupMaker\Controllers\Compatibility( $this ),
			// 'RestAPI'                => new \PopupMaker\Controllers\RestAPI( $this ),
			// 'BlockEditor'            => new \PopupMaker\Controllers\BlockEditor( $this ),
			// 'Frontend'               => new \PopupMaker\Controllers\Frontend( $this ),
			// 'Shortcodes'             => new \PopupMaker\Controllers\Shortcodes( $this ),
			// 'TrustedLoginController' => new \PopupMaker\Controllers\TrustedLogin( $this ),
		];
	}

	/**
	 * Initiate internal components.
	 *
	 * @return void
	 */
	protected function initiate_controllers() {
		$this->register_controllers( $this->registered_controllers() );
	}

	/**
	 * Register controllers.
	 *
	 * @param array<string,Controller> $controllers Array of controllers.
	 * @return void
	 */
	public function register_controllers( $controllers = [] ) {
		foreach ( $controllers as $name => $controller ) {
			if ( $controller instanceof Controller ) {
				if ( $controller->controller_enabled() ) {
					$controller->init();
				}
				$this->controllers->set( $name, $controller );
			}
		}
	}

	/**
	 * Get a controller.
	 *
	 * @param string $name Controller name.
	 *
	 * @return Controller|null
	 */
	public function get_controller( $name ) {
		$controller = $this->controllers->get( $name );

		if ( $controller instanceof Controller ) {
			return $controller;
		}

		return null;
	}

	/**
	 * Initiate internal paths.
	 *
	 * @return void
	 */
	protected function define_paths() {
		/**
		 * Attach utility functions.
		 */
		$this->container['get_path'] = [ $this, 'get_path' ];
		$this->container['get_url']  = [ $this, 'get_url' ];

		// Define paths.
		$this->container['dist_path'] = $this->get_path( 'dist' ) . '/';
	}

	/**
	 * Utility method to get a path.
	 *
	 * @param string $path Subpath to return.
	 * @return string
	 */
	public function get_path( $path = '' ) {
		return $this->container['path'] . $path;
	}

	/**
	 * Utility method to get a url.
	 *
	 * @param string $path Sub url to return.
	 * @return string
	 */
	public function get_url( $path = '' ) {
		return $this->container['url'] . $path;
	}

	/**
	 * Get item from container
	 *
	 * @param string $id Key for the item.
	 *
	 * @return mixed Current value of the item.
	 */
	public function get( $id ) {
		// 1. Check if the item exists in the container.
		if ( $this->container->offsetExists( $id ) ) {
			return $this->container->get( $id );
		}

		// 2. Check if the item exists in the controllers container.
		if ( $this->controllers->offsetExists( $id ) ) {
			return $this->controllers->get( $id );
		}

		// 3. Check if the item exists in the global space.
		if ( $this->is_addon_plugin() ) {
			// If this is an addon, check if the service exists in the core plugin.
			// Get core plugin container and see if the service exists there.
			$plugin_service = \PopupMaker\plugin( $id );

			if ( $plugin_service ) {
				return $plugin_service;
			}
		}

		// 5. Return null, item not found.
		return null;
	}

	/**
	 * Set item in container
	 *
	 * @param string $id Key for the item.
	 * @param mixed  $value Value to set.
	 *
	 * @return void
	 */
	public function set( $id, $value ) {
		$this->container->set( $id, $value );
	}

	/**
	 * Get plugin option.
	 *
	 * @param string        $key Option key.
	 * @param boolean|mixed $default_value Default value.
	 * @return mixed
	 */
	public function get_option( $key, $default_value = false ) {
		// Use old class directly to get all old options.
		$deprecated_options = \PUM_Utils_Options::get_all();

		if ( isset( $deprecated_options[ $key ] ) ) {
			// Use the old class to get the option, specifically for backwards compatibility as it has filters.
			return \PUM_Utils_Options::get( $key, $default_value );
		}

		return $this->get( 'options' )->get( $key, $default_value );
	}

	/**
	 * Get plugin permissions.
	 *
	 * @return array<string,string> Array of permissions.
	 */
	public function get_permissions() {
		$permissions = \PopupMaker\get_default_permissions();

		$user_permisions = $this->get( 'options' )->get( 'permissions', [] );

		if ( ! empty( $user_permisions ) ) {
			foreach ( $user_permisions as $cap => $user_permission ) {
				if ( ! empty( $user_permission ) ) {
					$permissions[ $cap ] = $user_permission;
				}
			}
		}

		return $permissions;
	}

	/**
	 * Get plugin permission for capability.
	 *
	 * @param string $cap Permission key.
	 *
	 * @return string User role or cap required.
	 */
	public function get_permission( $cap ) {
		$permissions = $this->get_permissions();

		return isset( $permissions[ $cap ] ) ? $permissions[ $cap ] : 'manage_options';
	}

	/**
	 * Check if debug mode is enabled.
	 *
	 * This is only used to change from minified to unminified
	 * assets to make debugging easier, specifically when logged out.
	 *
	 * @return boolean
	 */
	public function is_debug_mode_enabled() {
		// Ignored as we are simply checking for a query var's existence.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['pum_debug'] ) || \PUM_Utils_Options::get( 'debug_mode', false );
	}

	/**
	 * Check if pro version is installed.
	 *
	 * @return boolean
	 */
	public function is_pro_installed() {
		return file_exists( WP_PLUGIN_DIR . '/popup-maker-pro/popup-maker-pro.php' );
	}

	/**
	 * Check if pro version is active.
	 *
	 * @return boolean
	 */
	public function is_pro_active() {
		return $this->is_pro_installed() && function_exists( '\PopupMaker\Pro\plugin' );
	}

	/**
	 * Check if license is active.
	 *
	 * @return boolean
	 */
	public function is_license_active() {
		return $this->get( 'license' )->is_license_active();
	}
}
