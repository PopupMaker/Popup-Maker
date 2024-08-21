<?php
/**
 * Interface for Integration
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
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
