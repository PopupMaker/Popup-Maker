<?php
/**
 * GravityForms Form Integration Handler
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */
class PUM_Integration_Form_GravityForms extends PUM_Abstract_Integration_Form {

	/**
	 * Unique key identifier for this provider.
	 *
	 * @var string
	 */
	public $key = 'gravityforms';

	/**
	 * Constructor - action for after form is successfully submitted.
	 */
	public function __construct() {
		add_action( 'gform_after_submission', [ $this, 'on_success' ], 10, 2 );
	}

	/**
	 * Text label for Gravity Forms.
	 *
	 * @return string
	 */
	public function label() {
		return 'Gravity Forms';
	}

	/**
	 * Returns true when form is enabled.
	 *
	 * @return bool
	 */
	public function enabled() {
		return class_exists( 'RGForms' );
	}

	/**
	 * Gets forms.
	 *
	 * @return array
	 */
	public function get_forms() {
		return GFAPI::get_forms();
	}

	/**
	 * Gets single form by id.
	 *
	 * @param string $id
	 *
	 * @return mixed
	 */
	public function get_form( $id ) {
		return GFAPI::get_forms( $id );
	}

	/**
	 * Gets selectlist of forms.
	 *
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
	 * Handles form submission. 
	 *
	 * @param $entry Form entry.
	 * @param $form  Form
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

		pum_integrated_form_submission(
			[
				'popup_id'      => $popup_id,
				'form_provider' => $this->key,
				'form_id'       => $form['id'],
			]
		);
	}

	/**
	 * Custom scripts for Gravity Forms form.
	 *
	 * @param array $js
	 *
	 * @return array
	 */
	public function custom_scripts( $js = [] ) {
		return $js;
	}

	/**
	 * Custom styles.
	 *
	 * @param array $css
	 *
	 * @return array
	 */
	public function custom_styles( $css = [] ) {
		return $css;
	}
}
