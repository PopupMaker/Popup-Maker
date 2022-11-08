<?php
/**
 * Form Integration Handler for Interface
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */

interface PUM_Interface_Integration_Form extends PUM_Interface_Integration {

	/**
	 * Gets forms.
	 *
	 * @return array
	 */
	public function get_forms();

	/**
	 * Gets specified form.
	 *
	 * @param string $id  Form id.
	 *
	 * @return mixed
	 */
	public function get_form( $id );

	/**
	 * Gets form selectlist.
	 *
	 * @return array
	 */
	public function get_form_selectlist();

	/**
	 * Custom scripts.
	 *
	 * @param array $js  Array of custom js.
	 *
	 * @return array
	 */
	public function custom_scripts( $js = [] );

	/**
	 * Custom styles.
	 *
	 * @param array $css  Array of custom styles.
	 *
	 * @return array
	 */
	public function custom_styles( $css = [] );

}
