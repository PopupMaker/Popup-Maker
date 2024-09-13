<?php
/**
 * Integration for ContactForm7 Form
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

class PUM_Integration_Form_ContactForm7 extends PUM_Abstract_Integration_Form {

	/**
	 * Unique key identifier for this provider.
	 *
	 * @var string
	 */
	public $key = 'contactform7';

	/**
	 * Only used to hook in a custom action for non AJAX based submissions.
	 *
	 * Could be used for other initiations as well where needed.
	 */
	public function __construct() {
		add_action( 'wpcf7_mail_sent', [ $this, 'on_success' ], 1 );
	}

	/**
	 * Text label that will be used throughout the various options screens.
	 *
	 * @return string
	 */
	public function label() {
		// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		return __( 'Contact Form 7', 'contact-form-7' );
	}

	/**
	 * Should return true when the required form plugin is active.
	 *
	 * @return bool
	 */
	public function enabled() {
		return class_exists( 'WPCF7' ) || ( defined( 'WPCF7_VERSION' ) && WPCF7_VERSION );
	}

	/**
	 * Return a useable array of all forms from this provider.
	 *
	 * @return array
	 */
	public function get_forms() {
		return get_posts(
			[
				'post_type'      => 'wpcf7_contact_form',
				'posts_per_page' => - 1,
			]
		);
	}

	/**
	 * Return a single form by ID.
	 *
	 * @param string $id
	 *
	 * @return mixed
	 */
	public function get_form( $id ) {
		return get_post( $id );
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
			$form_selectlist[ $form->ID ] = $form->post_title;
		}

		return $form_selectlist;
	}

	/**
	 * Hooks in a success functions specific to this provider for non AJAX submission handling.
	 *
	 * @param WPCF7_ContactForm $cfdata
	 */
	public function on_success( $cfdata ) {

		if ( ! self::should_process_submission() ) {
			return;
		}
		$popup_id = self::get_popup_id();
		self::increase_conversion( $popup_id );

		pum_integrated_form_submission(
			[
				'popup_id'      => $popup_id,
				'form_provider' => $this->key,
				'form_id'       => $cfdata->id(),
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
