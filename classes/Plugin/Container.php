<?php
/**
 * Main plugin container.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Plugin;

use PopupMaker\Base\Controller;

/**
 * Main plugin container.
 */
class Container extends \PopupMaker\Base\Container {

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
		parent::__construct( $config );

		$this->controllers = new \PopupMaker\Base\Container();
		$this->initiate_controllers();
	}

	/**
	 * Update & track version info.
	 *
	 * @return array<string,\PopupMaker\Base\Controller>
	 */
	protected function registered_controllers() {
		return [];
	}

	/**
	 * Register controllers.
	 *
	 * @param array<string,Controller> $controllers Array of controllers.
	 * @return void
	 */
	public function register_controllers( $controllers = [] ) {
		foreach ( $controllers as $name => $controller ) {
			if ( $controller instanceof \PopupMaker\Base\Controller ) {
				if ( $controller->controller_enabled() ) {
					$controller->init();
				}
				$this->controllers->set( $name, $controller );
			}
		}
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

	public function offsetExists( $id ) {
		if ( parent::offsetExists( $id ) ) {
			return true;
		}

		if ( $this->controllers->offsetExists( $id ) ) {
			return true;
		}

		return false;
	}

	public function get( $id ) {
		// 1. Check if the item exists in the controllers container.
		if ( $this->controllers->offsetExists( $id ) ) {
			// Doing it wrong. Use get_controller() instead.
			_deprecated_function( __METHOD__, 'X.X.X', __CLASS__ . '::get_controller()' );
			return $this->controllers->get( $id );
		}

		return parent::get( $id );
	}
}
