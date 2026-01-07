<?php
/**
 * Integration for Elementor Pro Forms
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

class PUM_Integration_Form_Elementor extends PUM_Abstract_Integration_Form {

	/**
	 * Unique key identifier for this provider.
	 *
	 * @var string
	 */
	public $key = 'elementor';

	/**
	 * Only used to hook in a custom action for non AJAX based submissions.
	 *
	 * Could be used for other initiations as well where needed.
	 */
	public function __construct() {
		add_action( 'elementor_pro/forms/new_record', [ $this, 'on_success' ], 10, 2 );
	}

	/**
	 * Text label that will be used throughout the various options screens.
	 *
	 * @return string
	 */
	public function label() {
		return __( 'Elementor Pro', 'popup-maker' );
	}

	/**
	 * Should return true when the required form plugin is active.
	 *
	 * @return bool
	 */
	public function enabled() {
		return did_action( 'elementor_pro/init' ) && class_exists( '\\ElementorPro\\Modules\\Forms\\Module' );
	}

	/**
	 * Return a useable array of all forms from this provider.
	 *
	 * Elementor forms are widget instances, not centrally registered.
	 *
	 * @return array
	 */
	public function get_forms() {
		return [];
	}

	/**
	 * Return a single form by ID.
	 *
	 * Elementor forms are widget instances, not centrally registered.
	 *
	 * @param string $id
	 *
	 * @return mixed
	 */
	public function get_form( $id ) {
		return null;
	}

	/**
	 * Returns an array of options for a select list.
	 *
	 * Should be in the format of $formId => $formLabel
	 *
	 * @return array
	 */
	public function get_form_selectlist() {
		return [ 'any' => __( 'Any Elementor Form', 'popup-maker' ) ];
	}

	/**
	 * Hooks in a success functions specific to this provider for non AJAX submission handling.
	 *
	 * @param object $record
	 * @param object $ajax_handler
	 */
	public function on_success( $record, $ajax_handler ) {
		if ( ! $this->should_process_submission() ) {
			return;
		}

		$form_name = $record->get_form_settings( 'form_name' );
		$popup_id  = $this->get_popup_id();

		$this->increase_conversion( $popup_id );

		pum_integrated_form_submission(
			[
				'popup_id'      => $popup_id,
				'form_provider' => $this->key,
				'form_id'       => $form_name ? $form_name : 'unknown',
			]
		);
	}

	/**
	 * Load a custom script file to handle AJAX based submissions or other integrations with Popup Maker frontend.
	 *
	 * @param array $js
	 *
	 * @return array
	 */
	public function custom_scripts( $js = [] ) {
		return $js;
	}

	/**
	 * Load custom styles for hacking some elements specifically inside popups, such as datepickers.
	 *
	 * @param array $css
	 *
	 * @return array
	 */
	public function custom_styles( $css = [] ) {
		return $css;
	}
}
