<?php
/**
 * Integration for Kali Forms
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

class PUM_Integration_Form_KaliForms extends PUM_Abstract_Integration_Form {

	/**
	 * Unique key identifier for this provider.
	 *
	 * @var string
	 */
	public $key = 'kaliForms';

	/**
	 * Only used to hook in a custom action for non AJAX based submissions.
	 *
	 * Could be used for other initiations as well where needed.
	 */
	public function __construct() {
		add_action( 'kaliforms_after_form_process_action', [ $this, 'on_success' ], 10, 1 );
	}

	/**
	 * Text label that will be used throughout the various options screens.
	 *
	 * @return string
	 */
	public function label() {
		return __( 'Kali Forms', 'popup-maker' );
	}

	/**
	 * Should return true when the required form plugin is active.
	 *
	 * @return bool
	 */
	public function enabled() {
		return defined( 'KALIFORMS_VERSION' ) || class_exists( 'KaliForms\Inc\Frontend\Form_Processor' );
	}

	/**
	 * Return a useable array of all forms from this provider.
	 *
	 * @return array<object{id:int,title:string}>
	 */
	public function get_forms() {
		$forms = get_posts(
			[
				'post_type'      => 'kaliforms_forms',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		$form_list = [];

		foreach ( $forms as $form ) {
			$form_list[] = (object) [
				'id'    => $form->ID,
				'title' => $form->post_title,
			];
		}

		return $form_list;
	}

	/**
	 * Return a single form by ID.
	 *
	 * @param string $id Form ID.
	 *
	 * @return object{id:int,title:string}|null
	 */
	public function get_form( $id ) {
		$form = get_post( $id );

		if ( ! $form || 'kaliforms_forms' !== $form->post_type ) {
			return null;
		}

		return (object) [
			'id'    => $form->ID,
			'title' => $form->post_title,
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

		$forms = $this->get_forms();

		foreach ( $forms as $form ) {
			$form_selectlist[ $form->id ] = $form->title;
		}

		return $form_selectlist;
	}

	/**
	 * Hooks in a success function specific to this provider for non AJAX submission handling.
	 *
	 * @param array $args Arguments from kaliforms_after_form_process_action hook.
	 */
	public function on_success( $args ) {
		if ( ! $this->should_process_submission() ) {
			return;
		}

		// Get form ID from the data array.
		$form_id = isset( $args['data']['formId'] ) ? $args['data']['formId'] : null;

		if ( ! $form_id ) {
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
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['data'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw_data = wp_unslash( $_POST['data'] );

			// Handle JSON data first.
			$json_data = json_decode( $raw_data, true );
			if ( is_array( $json_data ) && isset( $json_data['pum_form_popup_id'] ) ) {
				return absint( $json_data['pum_form_popup_id'] );
			}

			// Parse the data if it's a URL-encoded string.
			if ( is_string( $raw_data ) ) {
				// Parse first to preserve URL encoding, then sanitize individual values.
				parse_str( $raw_data, $data );

				if ( isset( $data['pum_form_popup_id'] ) ) {
					return absint( $data['pum_form_popup_id'] );
				}
			}
		}

		return parent::get_popup_id();
	}

	/**
	 * Load a custom script file to handle AJAX based submissions or other integrations with Popup Maker frontend.
	 *
	 * @param array $js JavaScript files.
	 *
	 * @return array
	 */
	public function custom_scripts( $js = [] ) {
		return $js;
	}

	/**
	 * Load custom styles for hacking some elements specifically inside popups, such as datepickers.
	 *
	 * @param array $css CSS files.
	 *
	 * @return array
	 */
	public function custom_styles( $css = [] ) {
		return $css;
	}
}
