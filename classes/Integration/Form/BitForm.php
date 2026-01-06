<?php
/**
 * Integration for Bit Form
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

class PUM_Integration_Form_BitForm extends PUM_Abstract_Integration_Form {

	/**
	 * Unique key identifier for this provider.
	 *
	 * @var string
	 */
	public $key = 'bitform';

	/**
	 * Constructor hooks into Bit Form success action.
	 */
	public function __construct() {
		add_action( 'bitforms_submit_success', [ $this, 'on_success' ], 10, 3 );
	}

	/**
	 * Text label that will be used throughout the various options screens.
	 *
	 * @return string
	 */
	public function label() {
		return __( 'Bit Form', 'popup-maker' );
	}

	/**
	 * Should return true when the required form plugin is active.
	 *
	 * @return bool
	 */
	public function enabled() {
		return defined( 'BITFORMS_VERSION' );
	}

	/**
	 * Return a useable array of all forms from this provider.
	 *
	 * @return array
	 */
	public function get_forms() {
		global $wpdb;

		$forms = $wpdb->get_results(
			"SELECT id, form_name FROM {$wpdb->prefix}bitforms_form ORDER BY form_name ASC"
		);

		if ( ! $forms ) {
			return [];
		}

		return array_map(
			function ( $form ) {
				return [
					'id'    => $form->id,
					'title' => $form->form_name,
				];
			},
			$forms
		);
	}

	/**
	 * Returns an array of options for a select list.
	 *
	 * Should be in the format of $formId => $formLabel
	 *
	 * @return array
	 */
	public function get_form_selectlist() {
		$forms           = [];
		$available_forms = $this->get_forms();

		foreach ( $available_forms as $form ) {
			$forms[ $form['id'] ] = $form['title'];
		}

		return $forms;
	}

	/**
	 * Hooks in a success function specific to this provider for non AJAX submission handling.
	 *
	 * @param mixed $form_id   Form ID.
	 * @param mixed $entry_id  Entry ID.
	 * @param mixed $form_data Form data.
	 */
	public function on_success( $form_id, $entry_id, $form_data ) {
		if ( ! self::should_process_submission() ) {
			return;
		}

		$popup_id = self::get_popup_id();
		self::increase_conversion( $popup_id );

		pum_integrated_form_submission(
			[
				'popup_id'      => $popup_id,
				'form_provider' => $this->key,
				'form_id'       => (string) $form_id,
			]
		);
	}
}
