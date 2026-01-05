<?php
/**
 * Integration for HTML Forms
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */
class PUM_Integration_Form_HTMLForms extends PUM_Abstract_Integration_Form {

	/**
	 * Integration key (must match JS formProvider)
	 *
	 * @var string
	 */
	public $key = 'htmlforms';

	/**
	 * Constructor - Register hooks
	 */
	public function __construct() {
		// Hook server-side form submission success
		// HTML Forms fires this after successful submission with $submission and $form objects
		add_action( 'hf_form_success', [ $this, 'on_success' ], 10, 2 );
	}

	/**
	 * Get integration label for admin UI
	 *
	 * @return string
	 */
	public function label() {
		return 'HTML Forms';
	}

	/**
	 * Check if plugin is active/enabled
	 *
	 * @return bool
	 */
	public function enabled() {
		// Check for HTML Forms functions (namespaced)
		return function_exists( 'hf_get_forms' );
	}

	/**
	 * Get all forms for admin dropdown
	 *
	 * @return array Array of form objects with 'id' and 'title' keys
	 */
	public function get_forms() {
		if ( ! $this->enabled() ) {
			return [];
		}

		// HTML Forms provides hf_get_forms() to get all forms
		// Forms are stored as 'html-form' custom post type
		$html_forms = hf_get_forms();

		// Transform to expected format
		return array_map( function ( $form ) {
			return [
				'id'    => $form->ID,
				'title' => $form->title,
			];
		}, $html_forms );
	}

	/**
	 * Get form select list for admin UI
	 *
	 * @return array Associative array of form_id => form_title
	 */
	public function get_form_selectlist() {
		$form_selectlist = [];

		foreach ( $this->get_forms() as $form ) {
			$form_selectlist[ $form['id'] ] = $form['title'];
		}

		return $form_selectlist;
	}

	/**
	 * Get single form by ID
	 *
	 * @param string|int $id Form ID
	 * @return mixed Form object or null
	 */
	public function get_form( $id ) {
		if ( ! $this->enabled() ) {
			return null;
		}

		try {
			return hf_get_form( $id );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Handle form submission success (server-side)
	 *
	 * IMPORTANT: Use defensive parameter typing - no strict types!
	 * Third-party plugins can change hook signatures.
	 *
	 * @param mixed $submission Submission object from HTML Forms
	 * @param mixed $form       Form object from HTML Forms
	 */
	public function on_success( $submission, $form ) {
		// Defensive validation - never assume parameter types
		if ( ! is_object( $form ) ) {
			return;
		}

		// Check if submission should be processed
		if ( ! self::should_process_submission() ) {
			return;
		}

		// Extract form ID from Form object
		// HTML Forms Form object has public $ID property (and magic getter for lowercase 'id')
		$form_id = isset( $form->ID ) ? $form->ID : null;

		if ( ! $form_id ) {
			return;
		}

		// Get popup ID from session
		$popup_id = self::get_popup_id();

		// Increase conversion count
		self::increase_conversion( $popup_id );

		// Track integrated form submission
		pum_integrated_form_submission([
			'popup_id'      => $popup_id,
			'form_provider' => $this->key,
			'form_id'       => (string) $form_id,
		]);
	}
}
