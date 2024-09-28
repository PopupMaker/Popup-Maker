<?php
/**
 * Integration for FluentForms
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

class PUM_Integration_Form_FluentForms extends PUM_Abstract_Integration_Form {

	/**
	 * Unique key identifier for this provider.
	 *
	 * @var string
	 */
	public $key = 'fluentforms';

	/**
	 * Only used to hook in a custom action for non AJAX based submissions.
	 *
	 * Could be used for other initiations as well where needed.
	 */
	public function __construct() {
		add_action( 'fluentform/before_submission_confirmation', [ $this, 'on_success' ], 10, 3 );
	}

	/**
	 * Text label that will be used throughout the various options screens.
	 *
	 * @return string
	 */
	public function label() {
		// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		return __( 'Fluent Forms', 'fluentform' );
	}

	/**
	 * Should return true when the required form plugin is active.
	 *
	 * @return bool
	 */
	public function enabled() {
		return ( defined( 'FLUENTFORM_VERSION' ) && FLUENTFORM_VERSION );
	}

	/**
	 * Return a useable array of all forms from this provider.
	 *
	 * @return array<object{id:int,title:string}>
	 */
	public function get_forms() {
		$form_query = fluentFormApi( 'forms' )->forms([
			'per_page' => 10000,
		]);

		return $form_query['data'];
	}

	/**
	 * Return a single form by ID.
	 *
	 * @param string $id
	 *
	 * @return object{id:int,title:string}
	 */
	public function get_form( $id ) {
		return fluentFormApi( 'forms' )->find( $id );
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

		$forms = $this->get_forms();

		foreach ( $forms as $form ) {
			$form_selectlist[ $form->id ] = $form->title;
		}

		return $form_selectlist;
	}

	/**
	 * Hooks in a success functions specific to this provider for non AJAX submission handling.
	 *
	 * @param string $submission_id
	 * @param array  $form_data
	 * @param array  $form
	 */
	public function on_success( $submission_id, $form_data, $form ) {
		if ( ! $this->should_process_submission() ) {
			return;
		}

		$popup_id = $this->get_popup_id();
		$this->increase_conversion( $popup_id );

		pum_integrated_form_submission(
			[
				'popup_id'      => $popup_id,
				'form_provider' => $this->key,
				'form_id'       => isset( $form['attributes']['id'] ) ? $form['attributes']['id'] : null,
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
		if ( isset( $_POST['data'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$raw_data = sanitize_text_field( wp_unslash( $_POST['data'] ) );

			// Parse the URL-encoded string into an associative array
			parse_str( $raw_data, $data );

			if ( isset( $data['pum_form_popup_id'] ) ) {
				return absint( $data['pum_form_popup_id'] );
			}
		}

		return parent::get_popup_id();
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
