<?php
/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

class PUM_Integration_Form_MC4WP extends PUM_Abstract_Integration_Form {

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
		return __( 'MailChimp for WordPress' );
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
