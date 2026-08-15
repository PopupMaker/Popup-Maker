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
	 * Draft entries awaiting a successful update in the current request.
	 *
	 * @var array<int,bool>
	 */
	private $draft_transitions = [];

	/**
	 * Unique key identifier for this provider.
	 *
	 * @var string
	 */
	public $key = 'formidableforms';

	/**
	 * Register native success hooks for new and resumed submissions.
	 */
	public function __construct() {
		add_action( 'frm_after_create_entry', [ $this, 'on_success' ], 10, 3 );
		add_filter( 'frm_pre_update_entry', [ $this, 'capture_draft_transition' ], 9, 2 );
		add_action( 'frm_after_update_entry', [ $this, 'on_update_success' ], 10, 2 );
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
	 * Remember a frontend draft-to-submitted transition before Formidable persists it.
	 *
	 * @param mixed $values   Updated entry values.
	 * @param mixed $entry_id Native entry ID.
	 * @return mixed
	 */
	public function capture_draft_transition( $values, $entry_id ) {
		$normalized_id = $this->normalize_entry_id( $entry_id );
		$is_admin      = is_callable( [ 'FrmAppHelper', 'is_admin' ] ) && FrmAppHelper::is_admin();
		if ( null === $normalized_id || ! is_array( $values ) || $is_admin ) {
			return $values;
		}

		unset( $this->draft_transitions[ $normalized_id ] );

		if ( 1 === (int) ( isset( $values['frm_saving_draft'] ) ? $values['frm_saving_draft'] : 0 ) || ! empty( $values['is_draft'] ) ) {
			return $values;
		}

		$entry = $this->get_entry( $normalized_id );
		if ( is_object( $entry ) && ! empty( $entry->is_draft ) && empty( $entry->parent_item_id ) ) {
			$this->draft_transitions[ $normalized_id ] = true;
		}

		return $values;
	}

	/**
	 * Normalize a resumed draft after Formidable has persisted its submitted state.
	 *
	 * @param mixed $entry_id Native entry ID.
	 * @param mixed $form_id  Native form ID.
	 * @return void
	 */
	public function on_update_success( $entry_id, $form_id ) {
		$normalized_id = $this->normalize_entry_id( $entry_id );
		if ( null === $normalized_id || empty( $this->draft_transitions[ $normalized_id ] ) ) {
			return;
		}

		unset( $this->draft_transitions[ $normalized_id ] );
		$this->on_success( $normalized_id, $form_id );
	}

	/**
	 * Confirm this is the submitted parent entry rather than a draft or repeater child.
	 *
	 * @param int   $entry_id The ID of the entry added.
	 * @param mixed $args     Provider callback context.
	 * @return bool
	 */
	protected function is_successful_entry( $entry_id, $args ) {
		$entry_id = $this->normalize_entry_id( $entry_id );
		$args     = is_array( $args ) ? $args : [];
		if ( null === $entry_id || ! empty( $args['is_child'] ) ) {
			return false;
		}

		$entry = $this->get_entry( $entry_id );

		return is_object( $entry )
			&& empty( $entry->is_draft )
			&& empty( $entry->parent_item_id );
	}

	/**
	 * Normalize a native positive-integer entry ID.
	 *
	 * @param mixed $entry_id Entry ID.
	 * @return int|null
	 */
	private function normalize_entry_id( $entry_id ) {
		if ( ! ( is_int( $entry_id ) || ( is_string( $entry_id ) && ctype_digit( $entry_id ) ) ) || (int) $entry_id < 1 ) {
			return null;
		}

		return (int) $entry_id;
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
