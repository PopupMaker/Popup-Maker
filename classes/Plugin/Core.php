<?php
/**
 * Main plugin.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Plugin;

use PopupMaker\Base\Container;
use PopupMaker\Plugin\Options;
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

		add_action( 'init', [ $this, 'load_textdomain' ] );
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
		if ( get_called_class() === __CLASS__ ) {
			// $this->container['options'] =
				/**
				 * Get plugin options.
				 *
				 * @return Options
				 */
				// function ( $c ) {
				// return new Options( $c->get( 'option_prefix' ) );
				// };

			// $this->container['connect'] =
				/**
				 * Get plugin connect.
				 *
				 * @return Connect
				 */
				// function ( $c ) {
				// return new \PopupMaker\Plugin\Connect( $c );
				// };

			// $this->container['license'] =
				/**
				 * Get plugin license.
				 *
				 * @return License
				 */
				// function () {
				// return new \PopupMaker\Plugin\License();
				// };

			// $this->container['logging'] =
				/**
				 * Get plugin logging.
				 *
				 * @return Logging
				 */
				// function () {
				// return new \PopupMaker\Plugin\Logging();
				// };

			// $this->container['upgrader'] =
				/**
				 * Get plugin upgrader.
				 *
				 * @return Upgrader
				 */
				// function ( $c ) {
				// return new \PopupMaker\Plugin\Upgrader( $c );
				// };

			// $this->container['rules'] =
				/**
				 * Get plugin rules.
				 *
				 * @return \PopupMaker\RuleEngine\Rules
				 */
				// function () {
				// return new \PopupMaker\RuleEngine\Rules();
				// };

			// $this->container['restrictions'] =
				/**
				 * Get plugin restrictions.
				 *
				 * @return \PopupMaker\Services\Restrictions
				 */
				// function () {
				// return new \PopupMaker\Services\Restrictions();
				// };

			// $this->container['globals'] =
				/**
				 * Get plugin global manager.
				 *
				 * @return \PopupMaker\Services\Globals
				 */
				// function () {
				// return new \PopupMaker\Services\Globals();
				// };
		}

		apply_filters( "{$this->get( 'option_prefix' )}/register_services", $this->container, $this );
	}

	/**
	 * Update & track version info.
	 *
	 * @return array<string,\PopupMaker\Base\Controller>
	 */
	protected function registered_controllers() {
		return [
			// 'PostTypes'              => new \PopupMaker\Controllers\PostTypes( $this ),
			// 'Assets'                 => new \PopupMaker\Controllers\Assets( $this ),
			// 'Admin'                  => new \PopupMaker\Controllers\Admin( $this ),
			// 'Compatibility'          => new \PopupMaker\Controllers\Compatibility( $this ),
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
	public function get_path( $path ) {
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
		if ( get_called_class() !== __CLASS__ ) {
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
