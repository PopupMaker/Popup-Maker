<?php
/**
 * Interface for Integration
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

interface PUM_Interface_Integration {

	/**
	 * @return string
	 */
	public function label();

	/**
	 * @return bool
	 */
	public function enabled();

}
