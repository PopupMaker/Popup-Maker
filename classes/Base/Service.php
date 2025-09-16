<?php
/**
 * Plugin controller.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Base;

defined( 'ABSPATH' ) || exit;

/**
 * Base service class for dependency injection.
 *
 * Provides container access for all services implementing the service interface.
 *
 * @template TContainer of \PopupMaker\Plugin\Core
 */
abstract class Service implements \PopupMaker\Interfaces\Service {
	/**
	 * Plugin Container.
	 *
	 * @var TContainer
	 */
	public $container;

	/**
	 * Initialize based on dependency injection principles.
	 *
	 * @param TContainer $container Plugin container.
	 * @return void
	 */
	public function __construct( $container ) {
		$this->container = $container;
	}
}
