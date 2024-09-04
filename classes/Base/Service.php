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
 */
abstract class Service implements \PopupMaker\Interfaces\Service {
	/**
	 * Plugin Container.
	 *
	 * @var \PopupMaker\Plugin\Core
	 */
	public $container;

	/**
	 * Initialize based on dependency injection principles.
	 *
	 * @param \PopupMaker\Plugin\Core $container Plugin container.
	 * @return void
	 */
	public function __construct( $container ) {
		$this->container = $container;
	}
}
