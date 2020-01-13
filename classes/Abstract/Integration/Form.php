<?php
/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

abstract class PUM_Abstract_Integration_Form extends PUM_Abstract_Integration implements PUM_Interface_Integration_Form {

	/**
	 * @var string
	 */
	public $type = 'form';

	/**
	 * @return array
	 */
	abstract public function get_forms();

	/**
	 * @param string $id
	 *
	 * @return mixed
	 */
	abstract public function get_form( $id );

	/**
	 * @return array
	 */
	abstract public function get_form_selectlist();

	/**
	 * @param array $js
	 *
	 * @return array
	 */
	public function custom_scripts( $js = []) {
		return $js;
	}

	/**
	 * @param array $css
	 *
	 * @return array
	 */
	public function custom_styles( $css = [] ) {
		return $css;
	}

}
