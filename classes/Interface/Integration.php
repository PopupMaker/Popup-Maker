<?php
/**
 * Integration Interface
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
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
