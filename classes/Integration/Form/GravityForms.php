<?php
/**
 * Integration for GravityForms Form
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

class PUM_Integration_Form_GravityForms extends PUM_Abstract_Integration_Form {

	/**
	 * @var string
	 */
	public $key = 'gravityforms';

	public function __construct() {
		add_action( 'gform_after_submission', [ $this, 'on_success' ], 10, 2 );
	}

	/**
	 * @return string
	 */
	public function label() {
		// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Use Gravity Forms' own translations.
		return __( 'Gravity Forms', 'gravityforms' );
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
		if ( ! $this->should_process_submission() ) {
			return;
		}

		$popup_id = $this->get_popup_id();
		// Gravity Forms may use its own AJAX transport without admin-ajax.php.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Provider transport marker only.
		$is_ajax = isset( $_POST['gform_ajax'] ) && null !== $_POST['gform_ajax'];

		pum_integrated_form_submission(
			[
				'popup_id'      => $popup_id,
				'form_provider' => $this->key,
				'form_id'       => $form['id'],
				'submission_id' => isset( $entry['id'] ) && is_scalar( $entry['id'] ) ? $entry['id'] : null,
				'ajax'          => $is_ajax,
			]
		);
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
