<?php
/**
 * Abstract for Handling Integrations
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */

/**
 * Class PUM_Abstract_Integration
 */
abstract class PUM_Abstract_Integration implements PUM_Interface_Integration {

	/**
	 * $key variable
	 *
	 * @var string
	 */
	public $key;

	/**
	 * $type variable
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Label function for Integration Abstract.
	 *
	 * @return string
	 */
	abstract public function label();

	/**
	 * Enabled function for Integration Abstract.
	 *
	 * @return bool
	 */
	abstract public function enabled();

}
