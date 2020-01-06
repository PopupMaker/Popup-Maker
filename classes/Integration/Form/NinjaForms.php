<?php
/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

class PUM_Integration_Form_NinjaForms extends PUM_Abstract_Integration_Form {

	/**
	 * @var string
	 */
	public $key = 'ninjaforms';

	/**
	 * @return string
	 */
	public function label() {
		return 'Ninja Forms';
	}

	/**
	 * @return bool
	 */
	public function enabled() {
		return class_exists( 'Ninja_Forms' ) && ! ( version_compare( get_option( 'ninja_forms_version', '0.0.0' ), '3.0', '<' ) || get_option( 'ninja_forms_load_deprecated', false ) );
	}

	/**
	 * @return array
	 */
	public function get_forms() {
		return Ninja_Forms()->form()->get_forms();
	}

	/**
	 * @param string $id
	 *
	 * @return mixed
	 */
	public function get_form( $id ) {
		return Ninja_Forms()->form( $id )->get();
	}

	/**
	 * @return array
	 */
	public function get_form_selectlist() {
		$form_selectlist = [];

		$forms = $this->get_forms();

		foreach ( $forms as $form ) {
			$form_selectlist[ $form->get_id() ] = $form->get_setting( 'title' );
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
		$js['ninjaforms'] = [
			'content'  => file_get_contents( Popup_Maker::$DIR . 'assets/js/pum-integration-ninjaforms.js' ),
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
