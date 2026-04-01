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
	 * Stored popup ID from form submission.
	 *
	 * @var int|false
	 */
	protected $stored_popup_id = false;

	/**
	 * Constructor hooks into Bit Form success action and filters POST data.
	 */
	public function __construct() {
		add_action( 'bitform_submit_success', [ $this, 'on_success' ], 10, 3 );
		add_action( 'init', [ $this, 'filter_post_data' ], 5 );
	}

	/**
	 * Store and remove Popup Maker fields from POST data before Bit Form processing.
	 *
	 * Bit Form iterates all POST data expecting only their defined fields.
	 * We store the popup ID in memory before removing it from POST to prevent warnings.
	 */
	public function filter_post_data() {
		// Only filter if this is a Bit Form submission.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['bitforms_id'] ) ) {
			return;
		}

		// Store popup ID before removing from POST.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->stored_popup_id = isset( $_POST['pum_form_popup_id'] ) ? absint( $_POST['pum_form_popup_id'] ) : false;

		// Remove our hidden field to prevent Bit Form warnings.
		unset( $_POST['pum_form_popup_id'] );
	}

	/**
	 * Get popup ID from stored value.
	 *
	 * Overrides parent to use stored value since we remove it from POST.
	 *
	 * @return int|false
	 */
	public function get_popup_id() {
		return $this->stored_popup_id > 0 ? $this->stored_popup_id : false;
	}

	/**
	 * Text label that will be used throughout the various options screens.
	 *
	 * @return string
	 */
	public function label() {
		// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Use Bit Form's own translations.
		return __( 'Bit Form', 'bit-form' );
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$forms = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, form_name FROM %i ORDER BY form_name ASC',
				$wpdb->prefix . 'bitforms_form'
			)
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
	 * Get a single form by ID.
	 *
	 * Bit Form uses pattern: bitforms_{formId}_{postId}_{instanceCounter}
	 * JavaScript sends: {formId}_{postId}_{instanceCounter} (e.g., "1_995_1")
	 * This method extracts the numeric formId for database lookup.
	 *
	 * @param int|string $id Form ID or full identifier (e.g., "1" or "1_995_1").
	 * @return array|null Form data or null if not found.
	 */
	public function get_form( $id ) {
		// Extract numeric form ID from full identifier (e.g., "1_995_1" -> "1").
		$numeric_id = is_numeric( $id ) ? $id : preg_replace( '/^(\d+).*/', '$1', $id );

		$cache_key = 'pum_bitform_' . $numeric_id;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$form = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, form_name FROM %i WHERE id = %d',
				$wpdb->prefix . 'bitforms_form',
				$numeric_id
			)
		);

		if ( ! $form ) {
			// Cache negative result for 1 hour to avoid repeated failed queries.
			set_transient( $cache_key, null, HOUR_IN_SECONDS );
			return null;
		}

		$result = [
			'id'    => $form->id,
			'title' => $form->form_name,
		];

		// Cache for 12 hours.
		set_transient( $cache_key, $result, 12 * HOUR_IN_SECONDS );

		return $result;
	}

	/**
	 * Hooks in a success function specific to this provider for non AJAX submission handling.
	 *
	 * @param mixed $form_id   Form ID.
	 * @param mixed $entry_id  Entry ID.
	 * @param mixed $form_data Form data.
	 */
	public function on_success( $form_id, $entry_id, $form_data ) {
		if ( ! $this->should_process_submission() ) {
			return;
		}

		$popup_id = $this->get_popup_id();

		if ( $popup_id ) {
			$this->increase_conversion( $popup_id );
		}

		pum_integrated_form_submission(
			[
				'popup_id'      => $popup_id,
				'form_provider' => $this->key,
				'form_id'       => (string) $form_id,
			]
		);
	}
}
