<?php
/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

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
