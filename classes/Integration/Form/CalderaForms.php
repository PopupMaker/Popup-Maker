<?php
/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

class PUM_Integration_Form_CalderaForms extends PUM_Abstract_Integration_Form {

	/**
	 * @var string
	 */
	public $key = 'calderaforms';

	public function __construct() {
		add_action( 'caldera_forms_submit_complete', array( $this, 'on_success' ) );
	}

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
	 * @param array $form
	 */
	public function on_success( $form ) {
		pum_integrated_form_submission( [
			'popup_id'      => isset( $_REQUEST['pum_form_popup_id'] ) && absint( $_REQUEST['pum_form_popup_id'] ) > 0 ? absint( $_REQUEST['pum_form_popup_id'] ) : false,
			'form_provider' => $this->key,
			'form_id'       => $form['ID'],
		] );
	}

	/**
	 * @param array $js
	 *
	 * @return array
	 */
	public function custom_scripts( $js = [] ) {
		$js[ $this->key ] = [
			'content'  => file_get_contents( Popup_Maker::$DIR . 'assets/js/pum-integration-' . $this->key . PUM_Site_Assets::$suffix . '.js' ),
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
