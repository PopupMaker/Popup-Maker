<?php
/**
 * Extension base class
 *
 * @package   PopupMaker\Plugin
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract base class for all Popup Maker extensions.
 *
 * @since 1.21.0
 */
abstract class Extension extends Container {

	/**
	 * Core plugin instance.
	 *
	 * @var \PopupMaker\Plugin\Core
	 */
	protected $core;

	/**
	 * Extension constructor.
	 *
	 * @param array<string,string|bool> $config Configuration variables.
	 */
	public function __construct( $config ) {
		parent::__construct( $config );

		// Get core plugin instance.
		$this->core = \PopupMaker\plugin();
	}

	/**
	 * Register all controllers.
	 *
	 * @return array<string,\PopupMaker\Base\Controller>
	 */
	abstract protected function registered_controllers();

	/**
	 * Get core plugin instance.
	 *
	 * @return \PopupMaker\Plugin\Core
	 */
	public function core() {
		return $this->core;
	}

	/**
	 * Get a service or configuration variable.
	 *
	 * @param string $id Service or configuration variable ID.
	 * @return mixed
	 * @throws \PopupMaker\Vendor\Pimple\Exception\UnknownIdentifierException
	 */
	public function get( $id ) {
		try {
			return parent::get( $id );
		} catch ( \PopupMaker\Vendor\Pimple\Exception\UnknownIdentifierException $e ) {
			if ( $this->core()->offsetExists( $id ) ) {
				return $this->core()->get( $id );
			}

			// Re-throw the exception if we couldn't find the service.
			throw $e;
		}
	}
}
