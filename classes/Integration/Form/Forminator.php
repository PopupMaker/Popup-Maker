<?php
/**
 * Integration for Forminator
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2025, Code Atlantic LLC
 */

class PUM_Integration_Form_Forminator extends PUM_Abstract_Integration_Form {

	/**
	 * Unique key identifier for this provider.
	 *
	 * @var string
	 */
	public $key = 'forminator';

	/**
	 * Only used to hook in a custom action for non AJAX based submissions.
	 *
	 * Could be used for other initiations as well where needed.
	 */
	public function __construct() {
		add_action( 'forminator_custom_form_submit_before_set_fields', [ $this, 'on_success' ], 10, 3 );
	}

	/**
	 * Text label that will be used throughout the various options screens.
	 *
	 * @return string
	 */
	public function label() {
		return __( 'Forminator', 'popup-maker' );
	}

	/**
	 * Should return true when the required form plugin is active.
	 *
	 * @return bool
	 */
	public function enabled() {
		return defined( 'FORMINATOR_VERSION' );
	}

	/**
	 * Return a useable array of all forms from this provider.
	 *
	 * @return array<object{id:int,title:string}>
	 */
	public function get_forms() {
		if ( ! $this->enabled() ) {
			return [];
		}

		$forms = [];
		$query = Forminator_API::get_forms( null, 1, 9999 );

		if ( ! empty( $query ) && is_array( $query ) ) {
			foreach ( $query as $form ) {
				$forms[] = (object) [
					'id'    => $form->id,
					'title' => $form->name,
				];
			}
		}

		return $forms;
	}

	/**
	 * Return a single form by ID.
	 *
	 * @param string $id Form ID.
	 *
	 * @return object{id:int,title:string}|null
	 */
	public function get_form( $id ) {
		if ( ! $this->enabled() ) {
			return null;
		}

		$form = Forminator_API::get_form( absint( $id ) );

		if ( ! $form ) {
			return null;
		}

		return (object) [
			'id'    => $form->id,
			'title' => $form->name,
		];
	}

	/**
	 * Returns an array of options for a select list.
	 *
	 * Should be in the format of $formId => $formLabel
	 *
	 * @return array
	 */
	public function get_form_selectlist() {
		$form_selectlist = [];
		$forms           = $this->get_forms();

		foreach ( $forms as $form ) {
			$form_selectlist[ $form->id ] = $form->title;
		}

		return $form_selectlist;
	}

	/**
	 * Hooks in a success function specific to this provider for non AJAX submission handling.
	 *
	 * @param object $entry    Form entry object.
	 * @param int    $form_id  Form ID.
	 * @param array  $field_data_array Field data array.
	 */
	public function on_success( $entry, $form_id, $field_data_array ) {
		if ( ! $this->should_process_submission() ) {
			return;
		}

		$popup_id = $this->get_popup_id();
		$this->increase_conversion( $popup_id );

		pum_integrated_form_submission(
			[
				'popup_id'      => $popup_id,
				'form_provider' => $this->key,
				'form_id'       => $form_id,
			]
		);
	}

	/**
	 * Get the popup ID for this form submission.
	 *
	 * @return int|false
	 */
	public function get_popup_id() {
		// There is no raw nonce passed with this endpoint, so we need to check the raw data.
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_POST['forminator-form-id'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$form_data = $_POST;

			if ( isset( $form_data['pum_form_popup_id'] ) ) {
				return absint( $form_data['pum_form_popup_id'] );
			}
		}

		return parent::get_popup_id();
	}

	/**
	 * Load a custom script file to handle AJAX based submissions or other integrations with Popup Maker frontend.
	 *
	 * @param array $js JavaScript files array.
	 *
	 * @return array
	 */
	public function custom_scripts( $js = [] ) {
		return $js;
	}

	/**
	 * Load custom styles for hacking some elements specifically inside popups, such as datepickers.
	 *
	 * @param array $css CSS files array.
	 *
	 * @return array
	 */
	public function custom_styles( $css = [] ) {
		return $css;
	}
}
