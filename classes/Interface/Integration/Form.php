<?php
/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

interface PUM_Interface_Integration_Form extends PUM_Interface_Integration {

	/**
	 * @return array
	 */
	public function get_forms();

	/**
	 * @param string $id
	 *
	 * @return mixed
	 */
	public function get_form( $id );

	/**
	 * @return array
	 */
	public function get_form_selectlist();

	/**
	 * @param array $js
	 *
	 * @return array
	 */
	public function custom_scripts( $js = [] );

	/**
	 * @param array $css
	 *
	 * @return array
	 */
	public function custom_styles( $css = [] );

}
