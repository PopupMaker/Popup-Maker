<?php
/**
 * Main plugin container.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Plugin;

use PopupMaker\Plugin\Core as Container;

/**
 * Main plugin container.
 */
abstract class Controller extends \PopupMaker\Base\Controller {

	/**
	 * Plugin Container.
	 *
	 * @var \PopupMaker\Plugin\Core
	 */
	public $container;
}
