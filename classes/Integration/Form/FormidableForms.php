<?php
/************************************
 * Copyright (c) 2020, Popup Maker
 ************************************/

/**
 * Handles the integration with Formidable Forms (https://wordpress.org/plugins/formidable/)
 *
 * @since 1.12
 */
class PUM_Integration_Form_FormidableForms extends PUM_Abstract_Integration_Form {

	/**
	 * Unique key identifier for this provider.
	 *
	 * @var string
	 */
	public $key = 'formidableforms';

	/**
	 * Only used to hook in a custom action for non AJAX based submissions.
	 *
	 * Could be used for other initiations as well where needed.
	 */
	public function __construct() {
		add_action( 'frm_after_create_entry', array( $this, 'on_success' ), 1, 2 );
	}

	/**
	 * Text label that will be used throughout the various options screens.
	 *
	 * @return string
	 */
	public function label() {
		return __( 'Formidable Forms' );
	}

	/**
	 * Should return true when the required form plugin is active.
	 *
	 * @return bool
	 */
	public function enabled() {
		return class_exists( 'FrmEntry' );
	}

	/**
	 * Return a useable array of all forms from this provider.
	 *
	 * @return array
	 */
	public function get_forms() {
		return FrmForm::getAll();
	}

	/**
	 * Return a single form by ID.
	 *
	 * @param string $id The ID of the form.
	 * @return mixed
	 */
	public function get_form( $id ) {
		return FrmForm::getOne( intval( $id ) );
	}

	/**
	 * Returns an array of options for a select list.
	 * Should be in the format of $formId => $formLabel.
	 *
	 * @return array The array of options
	 */
	public function get_form_selectlist() {
		$form_selectlist = [];

		$forms = $this->get_forms();

		foreach ( $forms as $form ) {
			$form_selectlist[ $form->id ] = $form->name;
		}

		return $form_selectlist;
	}

	/**
	 * Hooks in a success functions specific to this provider for non AJAX submission handling.
	 *
	 * @param int $entry_id The ID of the entry added.
	 * @param int $form_id The ID of the form.
	 */
	public function on_success( $entry_id, $form_id ) {

		// Determine if form has AJAX submission enabled. Only do our form submission method if AJAX is not enabled.
		$form = $this->get_form( intval( $form_id ) );
		if ( isset( $form->options['ajax_submit'] ) && true == $form->options['ajax_submit'] ) {
			return;
		}

		// @see pum_integrated_form_submission
		pum_integrated_form_submission(
			[
				'popup_id'      => isset( $_REQUEST['pum_form_popup_id'] ) && absint( $_REQUEST['pum_form_popup_id'] ) > 0 ? absint( $_REQUEST['pum_form_popup_id'] ) : false,
				'form_provider' => $this->key,
				'form_id'       => $form_id,
			]
		);
	}

	/**
	 * Load a custom script file to handle AJAX based submissions or other integrations with Popup Maker frontend.
	 *
	 * @param array $js All JS to be enqueued for popup.
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
	 * Load custom styles for hacking some elements specifically inside popups, such as datepickers.
	 *
	 * @param array $css All CSS enqueued for the popup.
	 * @return array
	 */
	public function custom_styles( $css = [] ) {
		return $css;
	}
}
