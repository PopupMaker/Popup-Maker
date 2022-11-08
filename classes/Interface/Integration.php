<?php
/**
 * Integration Interface
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */

interface PUM_Interface_Integration {

	/**
	 * Text label.
	 *
	 * @return string
	 */
	public function label();

	/**
	 * Checks if plugin is enabled.
	 *
	 * @return bool
	 */
	public function enabled();

}
