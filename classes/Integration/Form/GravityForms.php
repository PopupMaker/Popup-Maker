<?php
/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

class PUM_Integration_Form_GravityForms extends PUM_Abstract_Integration_Form {

	/**
	 * @var string
	 */
	public $key = 'gravityforms';

	public function __construct() {
		add_action( 'gform_after_submission', array( $this, 'on_success' ), 10, 2 );
	}

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


	/**
	 * @param $entry
	 * @param $form
	 */
	public function on_success( $entry, $form ) {
		if ( ! self::should_process_submission() ) {
			return;
		}

		// This key is set when Gravity Forms is submitted via AJAX.
		if ( isset( $_POST['gform_ajax'] ) && ! is_null( $_POST['gform_ajax'] ) ) {
			return;
		}

		$popup_id = self::get_popup_id();
		self::increase_conversion( $popup_id );

		pum_integrated_form_submission( [
			'popup_id'      => $popup_id,
			'form_provider' => $this->key,
			'form_id'       => $form['id'],
		] );
	}

	/**
	 * @param array $js
	 *
	 * @return array
	 */
	public function custom_scripts( $js = [] ) {
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
