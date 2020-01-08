<?php
/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

class PUM_Integration_Form_CalderaForms extends PUM_Abstract_Integration_Form {

	/**
	 * @var string
	 */
	public $key = 'calderaforms';

	/**
	 * @return string
	 */
	public function label() {
		return 'Caldera Forms';
	}

	/**
	 * @return bool
	 */
	public function enabled() {
		return defined( 'CFCORE_VER' ) && CFCORE_VER;
	}

	/**
	 * @return array
	 */
	public function get_forms() {
		return Caldera_Forms_Forms::get_forms( true );
	}

	/**
	 * @param string $id
	 *
	 * @return mixed
	 */
	public function get_form( $id ) {
		return Caldera_Forms_Forms::get_form( $id );
	}

	/**
	 * @return array
	 */
	public function get_form_selectlist() {
		$form_selectlist = [];

		$forms = $this->get_forms();

		foreach ( $forms as $form ) {
			$form_selectlist[ $form['ID'] ] = $form['name'];
		}

		return $form_selectlist;
	}

	/**
	 * @param callable $callback
	 *
	 * @return void
	 */
	public function on_success( $callback ) {
		// TODO: Implement on_success() method.
	}

	/**
	 * @param array $js
	 *
	 * @return array
	 */
	public function custom_scripts( $js = [] ) {
		$js['calderaforms'] = [
			'content'  => file_get_contents( Popup_Maker::$DIR . 'assets/js/pum-integration-calderaforms.js' ),
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
		$css[ $this->key ] = [
			'content'  => ".pac-container { z-index: 2000000000 !important; }\n",
			'priority' => 8,
		];

		return $css;
	}


}
