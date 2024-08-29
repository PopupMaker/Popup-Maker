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
 * Localized container class.
 */
abstract class Controller implements \PopupMaker\Interfaces\Controller {

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

	/**
	 * Check if controller is enabled.
	 *
	 * @return bool
	 */
	public function controller_enabled() {
		return true;
	}
}
