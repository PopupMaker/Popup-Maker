<?php
/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

class PUM_Integration_Form_ContactForm7 extends PUM_Abstract_Integration_Form {

	/**
	 * @var string
	 */
	public $key = 'contactform7';

	/**
	 * @return string
	 */
	public function label() {
		return 'Contact Form 7';
	}

	/**
	 * @return bool
	 */
	public function enabled() {
		return class_exists( 'WPCF7' ) || ( defined( 'WPCF7_VERSION' ) && WPCF7_VERSION );
	}

	/**
	 * @return array
	 */
	public function get_forms() {
		return get_posts( [
			'post_type'      => 'wpcf7_contact_form',
			'posts_per_page' => - 1,
		] );
	}

	/**
	 * @param string $id
	 *
	 * @return mixed
	 */
	public function get_form( $id ) {
		return get_post( $id );
	}

	/**
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

	public function on_success( $callback ) {
		// TODO: Implement on_success() method.
	}

	/**
	 * @param array $js
	 *
	 * @return array
	 */
	public function custom_scripts( $js = [] ) {
		$js['contactform7'] = [
			'content'  => file_get_contents( Popup_Maker::$DIR . 'assets/js/pum-integration-contactform7.js' ),
			'priority' => 8,
		];

		return $js;
	}

	/**
	 * @param array $css
	 *
	 * @return array
	 */
	public function custom_styles( $css = [] ) {
		return $css;
	}
}
