<?php
/**
 * Main plugin container.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Plugin;

/**
 * Main plugin container.
 *
 * @template-extends \PopupMaker\Base\Controller<\PopupMaker\Plugin\Core>
 *
 * @since 1.21.0
 */
abstract class Controller extends \PopupMaker\Base\Controller {

	/**
	 * Plugin Container.
	 *
	 * @var \PopupMaker\Plugin\Core
	 */
	public $container;
}
