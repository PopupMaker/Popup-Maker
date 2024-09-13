<?php
/**
 * Abstract class for Integrations.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

/**
 * Abstract class for Integrations.
 */
abstract class PUM_Abstract_Integration implements PUM_Interface_Integration {

	/**
	 * @var string
	 */
	public $key;

	/**
	 * @var string
	 */
	public $type;

	/**
	 * @return string
	 */
	abstract public function label();

	/**
	 * @return bool
	 */
	abstract public function enabled();
}
