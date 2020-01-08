<?php
/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

class PUM_Integration_Form_GravityForms extends PUM_Abstract_Integration_Form {

	/**
	 * @var string
	 */
	public $key = 'gravityforms';

	/**
	 * @return string
	 */
	public function label() {
		return 'Gravity Forms';
	}

	/**
	 * @return bool
	 */
	public function enabled() {
		return class_exists( 'RGForms' );
	}

	/**
	 * @return array
	 */
	public function get_forms() {
		return GFAPI::get_forms();
	}

	/**
	 * @param string $id
	 *
	 * @return mixed
	 */
	public function get_form( $id ) {
		return GFAPI::get_forms( $id );
	}

	/**
	 * @return array
	 */
	public function get_form_selectlist() {
		$form_selectlist = [];

		$forms = $this->get_forms();

		foreach ( $forms as $form ) {
			$form_selectlist[ $form['id'] ] = $form['title'];
		}

		return $form_selectlist;
	}


	public function on_success( $callback ) {
		// TODO: Implement on_success() method.
	}

	/**
	 * @param array $js
	 *
	 * @return array
	 */
	public function custom_scripts( $js = [] ) {
		$js['gravityforms'] = [
			'content'  => file_get_contents( Popup_Maker::$DIR . 'assets/js/pum-integration-gravityforms.js' ),
			'priority' => 8,
		];

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
