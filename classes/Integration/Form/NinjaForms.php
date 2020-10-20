<?php
/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

class PUM_Integration_Form_NinjaForms extends PUM_Abstract_Integration_Form {

	/**
	 * @var string
	 */
	public $key = 'ninjaforms';

	public function __construct() {
		add_action( 'ninja_forms_pre_process', array( $this, 'on_success_v2' ) );
		add_action( 'ninja_forms_after_submission', array( $this, 'on_success_v3' ) );
	}

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

	/**
	 * @global $ninja_forms_processing
	 */
	public function on_success_v2() {
		global $ninja_forms_processing;

		if ( ! self::should_process_submission() ) {
			return;
		}
		$popup_id = self::get_popup_id();
		self::increase_conversion( $popup_id );

		pum_integrated_form_submission( [
			'popup_id'      => $popup_id,
			'form_provider' => $this->key,
			'form_id'       => $ninja_forms_processing->get_form_ID(),
		] );
	}

	/**
	 * @param $form_data
	 */
	public function on_success_v3( $form_data ) {
		if ( ! self::should_process_submission() ) {
			return;
		}
		$popup_id = self::get_popup_id();
		self::increase_conversion( $popup_id );
		pum_integrated_form_submission( [
			'popup_id'      => $popup_id,
			'form_provider' => $this->key,
			'form_id'       => $form_data['form_id'],
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
