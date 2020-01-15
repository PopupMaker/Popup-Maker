<?php
/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

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
		add_action( 'wpcf7_mail_sent', array( $this, 'on_success' ), 1 );
	}

	/**
	 * Text label that will be used throughout the various options screens.
	 *
	 * @return string
	 */
	public function label() {
		return __( 'Contact Form 7' );
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
		return get_posts( [
			'post_type'      => 'wpcf7_contact_form',
			'posts_per_page' => - 1,
		] );
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
		/**
		 * @see pum_integrated_form_submission
		 */
		pum_integrated_form_submission( [
			'popup_id'      => isset( $_REQUEST['pum_form_popup_id'] ) && absint( $_REQUEST['pum_form_popup_id'] ) > 0 ? absint( $_REQUEST['pum_form_popup_id'] ) : false,
			'form_provider' => $this->key,
			'form_id'       => $cfdata->id(),
		] );
	}

	/**
	 * Load a custom script file to handle AJAX based submissions or other integrations with Popup Maker frontend.
	 *
	 * @param array $js
	 *
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
	 * @param array $css
	 *
	 * @return array
	 */
	public function custom_styles( $css = [] ) {
		return $css;
	}
}
