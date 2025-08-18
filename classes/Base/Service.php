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
 * Localized service class.
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
