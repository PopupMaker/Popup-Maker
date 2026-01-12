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
		// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Use Beaver Builder's own translations.
		return __( 'Beaver Builder', 'fl-builder' );
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
				'post_title' => __( 'Any Contact Form', 'popup-maker' ),
			],
			[
				'ID'         => 'subscribe_any',
				'post_title' => __( 'Any Subscribe Form', 'popup-maker' ),
			],
			[
				'ID'         => 'login_any',
				'post_title' => __( 'Any Login Form', 'popup-maker' ),
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
			'contact_any'   => __( 'Any Contact Form', 'popup-maker' ),
			'subscribe_any' => __( 'Any Subscribe Form', 'popup-maker' ),
			'login_any'     => __( 'Any Login Form', 'popup-maker' ),
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
