<?php
/**
 * Integration for MC4WP Form
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

class PUM_Integration_Form_MC4WP extends PUM_Abstract_Integration_Form {

	/**
	 * Register the provider's server-authoritative accepted-subscription hook.
	 */
	public function __construct() {
		add_action( 'mc4wp_form_subscribed', [ $this, 'on_subscribed' ], 10, 4 );
	}

	/**
	 * Unique key identifier for this provider.
	 *
	 * @var string
	 */
	public $key = 'mc4wp';

	/**
	 * Text label that will be used throughout the various options screens.
	 *
	 * @return string
	 */
	public function label() {
		// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		return __( 'MailChimp for WordPress', 'mc4wp' );
	}

	/**
	 * Should return true when the required form plugin is active.
	 *
	 * @return bool
	 */
	public function enabled() {
		return defined( 'MC4WP_VERSION' ) && MC4WP_VERSION;
	}

	/**
	 * Return a useable array of all forms from this provider.
	 *
	 * @return MC4WP_Form[]
	 */
	public function get_forms() {
		return mc4wp_get_forms();
	}

	/**
	 * Return a single form by ID.
	 *
	 * @param string $id
	 *
	 * @return MC4WP_Form
	 */
	public function get_form( $id ) {
		return mc4wp_get_form( $id );
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
			$form_selectlist[ $form->ID ] = $form->name;
		}

		return $form_selectlist;
	}

	/**
	 * Normalize a subscription only after Mailchimp accepted it.
	 *
	 * API errors and blocked duplicate attempts do not fire this hook.
	 *
	 * @param mixed $form  Submitted MC4WP form.
	 * @param mixed $email Accepted subscriber email.
	 * @param mixed $data  Submitted merge fields.
	 * @param mixed $map   Subscriber map keyed by list ID.
	 * @return void
	 */
	public function on_subscribed( $form, $email, $data, $map ) {
		$form_id = is_object( $form ) && isset( $form->ID ) && is_scalar( $form->ID ) ? absint( $form->ID ) : 0;
		if ( 0 === $form_id ) {
			return;
		}

		pum_integrated_form_submission(
			[
				'popup_id'      => $this->get_popup_id(),
				'form_provider' => $this->key,
				'form_id'       => $form_id,
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
