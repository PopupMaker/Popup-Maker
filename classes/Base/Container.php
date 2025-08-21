<?php
/**
 * Plugin container.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Base;

defined( 'ABSPATH' ) || exit;

/**
 * Localized container class.
 *
 * Extends Pimple dependency injection container with convenience methods
 * for WordPress plugin architecture. Supports service registration,
 * factory patterns, and parameter storage.
 */
class Container extends \PopupMaker\Vendor\Pimple\Container {
	/**
	 * Get item from container.
	 *
	 * Retrieves a service or parameter from the container. If the service is a factory,
	 * it will be instantiated. If it's a protected service, it will be returned as-is.
	 *
	 * @param string $id The unique identifier for the parameter or service.
	 *
	 * @return mixed The value of the parameter or an instantiated service.
	 *
	 * @throws \PopupMaker\Vendor\Pimple\Exception\UnknownIdentifierException If the identifier is not defined.
	 */
	public function get( $id ) {
		return $this->offsetGet( $id );
	}

	/**
	 * Set item in container.
	 *
	 * Stores a parameter or service definition in the container. Parameters are stored
	 * as-is, while service definitions (callables) are stored for lazy instantiation.
	 *
	 * @param string $id The unique identifier for the parameter or service.
	 * @param mixed  $value The value of the parameter or a callable service definition.
	 *
	 * @return void
	 *
	 * @throws \PopupMaker\Vendor\Pimple\Exception\FrozenServiceException If attempting to override a frozen service.
	 */
	public function set( $id, $value ) {
		$this->offsetSet( $id, $value );
	}
}
