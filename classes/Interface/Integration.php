<?php
/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

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
