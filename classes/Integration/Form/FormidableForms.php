<?php
/**
 * Integration for FormidableForms Form
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

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
		add_action( 'frm_after_create_entry', [ $this, 'on_success' ], 10, 3 );
	}

	/**
	 * Text label that will be used throughout the various options screens.
	 *
	 * @return string
	 */
	public function label() {
		// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Use Formidable Forms' own translations.
		return __( 'Formidable Forms', 'formidable' );
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
	 * @param int   $entry_id The ID of the entry added.
	 * @param int   $form_id  The ID of the form.
	 * @param array $args     Provider callback context.
	 */
	public function on_success( $entry_id, $form_id, $args = [] ) {

		if ( ! $this->should_process_submission() || ! $this->is_successful_entry( $entry_id, $args ) ) {
			return;
		}

		$popup_id = $this->get_popup_id();
		$form     = $this->get_form( intval( $form_id ) );
		$is_ajax  = is_object( $form )
			&& isset( $form->options )
			&& is_array( $form->options )
			&& isset( $form->options['ajax_submit'] )
			&& true === $form->options['ajax_submit'];

		pum_integrated_form_submission(
			[
				'popup_id'      => $popup_id,
				'form_provider' => $this->key,
				'form_id'       => $form_id,
				'submission_id' => is_scalar( $entry_id ) ? $entry_id : null,
				'ajax'          => $is_ajax,
			]
		);
	}

	/**
	 * Confirm this is the submitted parent entry rather than a draft or repeater child.
	 *
	 * @param int   $entry_id The ID of the entry added.
	 * @param array $args     Provider callback context.
	 * @return bool
	 */
	protected function is_successful_entry( $entry_id, $args ) {
		if (
			! ( is_int( $entry_id ) || ( is_string( $entry_id ) && ctype_digit( $entry_id ) ) )
			|| (int) $entry_id < 1
			|| ! empty( $args['is_child'] )
		) {
			return false;
		}

		$entry = $this->get_entry( (int) $entry_id );

		return is_object( $entry )
			&& empty( $entry->is_draft )
			&& empty( $entry->parent_item_id );
	}

	/**
	 * Load the native entry for submission-state verification.
	 *
	 * @param int $entry_id Entry ID.
	 * @return object|null
	 */
	protected function get_entry( $entry_id ) {
		return is_callable( [ 'FrmEntry', 'getOne' ] ) ? FrmEntry::getOne( $entry_id ) : null;
	}

	/**
	 * Load a custom script file to handle AJAX based submissions or other integrations with Popup Maker frontend.
	 *
	 * @param array $js All JS to be enqueued for popup.
	 * @return array
	 */
	public function custom_scripts( $js = [] ) {
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
