<?php
/**
 * Integration for Beaver Builder Forms
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

/**
 * Beaver Builder Forms Integration Class
 */
class PUM_Integration_Form_BeaverBuilder extends PUM_Abstract_Integration_Form {

	/**
	 * Integration key.
	 *
	 * @var string
	 */
	public $key = 'beaverbuilder';

	/**
	 * Get integration label.
	 *
	 * @return string
	 */
	public function label() {
		return 'Beaver Builder';
	}

	/**
	 * Check if integration is enabled.
	 *
	 * @return bool
	 */
	public function enabled() {
		return class_exists( 'FLBuilder' );
	}

	/**
	 * Get all Beaver Builder forms.
	 * BB forms are instances, not centrally registered.
	 * Return mock list for admin UI.
	 *
	 * @return array
	 */
	public function get_forms() {
		return [
			[
				'ID'         => 'contact_any',
				'post_title' => 'Any Contact Form',
			],
			[
				'ID'         => 'subscribe_any',
				'post_title' => 'Any Subscribe Form',
			],
			[
				'ID'         => 'login_any',
				'post_title' => 'Any Login Form',
			],
		];
	}

	/**
	 * Get a single form by ID.
	 *
	 * @param string $id Form ID.
	 *
	 * @return mixed
	 */
	public function get_form( $id ) {
		$forms = $this->get_forms();
		foreach ( $forms as $form ) {
			if ( $form['ID'] === $id ) {
				return $form;
			}
		}
		return null;
	}

	/**
	 * Get a select list of all forms.
	 *
	 * @return array
	 */
	public function get_form_selectlist() {
		return [
			'contact_any'   => 'Any Contact Form',
			'subscribe_any' => 'Any Subscribe Form',
			'login_any'     => 'Any Login Form',
		];
	}

	/**
	 * Custom scripts for Beaver Builder integration.
	 * All tracking happens via JavaScript events.
	 *
	 * @param array $js JavaScript array.
	 *
	 * @return array
	 */
	public function custom_scripts( $js = [] ) {
		return $js;
	}

	/**
	 * Custom styles for Beaver Builder integration.
	 *
	 * @param array $css CSS array.
	 *
	 * @return array
	 */
	public function custom_styles( $css = [] ) {
		return $css;
	}
}
