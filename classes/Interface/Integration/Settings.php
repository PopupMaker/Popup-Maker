<?php
/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

interface PUM_Interface_Integration_Settings extends PUM_Interface_Integration {

	/**
	 * @param array $fields
	 *
	 * @return array
	 */
	public function append_fields( $fields = [] );

}
