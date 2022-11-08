<?php
/**
 * Settings Integration for Interface
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */

interface PUM_Interface_Integration_Settings extends PUM_Interface_Integration {

	/**
	 * Appends fields.
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function append_fields( $fields = [] );

}
