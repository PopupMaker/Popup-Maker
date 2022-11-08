<?php
/**
 * CalderaForms Form Integration Handler
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */

class PUM_Integration_Form_CalderaForms extends PUM_Abstract_Integration_Form {

	/**
	 * $key variable
	 *
	 * @var string
	 */
	public $key = 'calderaforms';

	/**
	 * Constructor - adds action for when caldera form is successfully submitted.
	 */
	public function __construct() {
		add_action( 'caldera_forms_submit_complete', [ $this, 'on_success' ] );
	}

	/**
	 * Label function.
	 *
	 * @return string
	 */
	public function label() {
		return 'Caldera Forms';
	}

	/**
	 * Function enabled() for Caldera Forms.
	 *
	 * @return bool
	 */
	public function enabled() {
		return defined( 'CFCORE_VER' ) && CFCORE_VER;
	}

	/**
	 * Gets forms from Caldera Forms.
	 *
	 * @return array
	 */
	public function get_forms() {
		return Caldera_Forms_Forms::get_forms( true );
	}

	/**
	 * Gets specified form.
	 *
	 * @param string $id  Form id.
	 *
	 * @return mixed
	 */
	public function get_form( $id ) {
		return Caldera_Forms_Forms::get_form( $id );
	}

	/**
	 * Gets form selectlist.
	 *
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
	 * Form was submitted successfully.
	 *
	 * @param array $form  Specified form.
	 */
	public function on_success( $form ) {
		if ( ! self::should_process_submission() ) {
			return;
		}
		$popup_id = self::get_popup_id();
		self::increase_conversion( $popup_id );

		pum_integrated_form_submission(
			[
				'popup_id'      => $popup_id,
				'form_provider' => $this->key,
				'form_id'       => $form['ID'],
			]
		);
	}

	/**
	 * Custom scripts for Caldera Forms.
	 *
	 * @param array $js  Array of custom js.
	 *
	 * @return array
	 */
	public function custom_scripts( $js = [] ) {
		return $js;
	}

	/**
	 * Custom styles.
	 *
	 * @param array $css  Array of css styles.
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
