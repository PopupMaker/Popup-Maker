<?php
/**
 * Integration for WPForms Form
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

class PUM_Integration_Form_WPForms extends PUM_Abstract_Integration_Form {

	/**
	 * @var string
	 */
	public $key = 'wpforms';

	public function __construct() {
		add_action( 'wpforms_process_complete', [ $this, 'on_success' ], 10, 4 );
	}

	/**
	 * @return string
	 */
	public function label() {
		return 'WP Forms';
	}

	/**
	 * @return bool
	 */
	public function enabled() {
		return defined( 'WPFORMS_VERSION' ) && WPFORMS_VERSION;
	}

	/**
	 * @return array|bool|null|WP_Post[]
	 */
	public function get_forms() {
		return wpforms()->form->get( null, [ 'posts_per_page' => - 1 ] );
	}

	/**
	 * @param int|string $id
	 *
	 * @return array|bool|null|WP_Post
	 */
	public function get_form( $id ) {
		return wpforms()->form->get( $id );
	}

	/**
	 * @return array
	 */
	public function get_form_selectlist() {
		$form_selectlist = [];

		$forms = $this->get_forms();

		if ( is_array( $forms ) ) {
			foreach ( $forms as $form ) {
				$form_selectlist[ $form->ID ] = $form->post_title;
			}
		}

		return $form_selectlist;
	}

	/**
	 * @link https://wpforms.com/developers/wpforms_process_complete/
	 *
	 * @param array $fields Sanitized entry field values/properties.
	 * @param array $entry Original $_POST global.
	 * @param array $form_data Form data and settings.
	 * @param int   $entry_id Entry ID. Will return 0 if entry storage is disabled or using WPForms Lite.
	 */
	public function on_success( $fields, $entry, $form_data, $entry_id ) {
		if ( ! self::should_process_submission() ) {
			return;
		}
		$popup_id = self::get_popup_id();
		self::increase_conversion( $popup_id );
		pum_integrated_form_submission(
			[
				'popup_id'      => $popup_id,
				'form_provider' => $this->key,
				'form_id'       => $form_data['id'],
			]
		);
	}

	/**
	 * @param array $js
	 *
	 * @return array
	 */
	public function custom_scripts( $js = [] ) {
		return $js;
	}

	/**
	 * @param array $css
	 *
	 * @return array
	 */
	public function custom_styles( $css = [] ) {
		// $css[ $this->key ] = [
		// 'content'  => ".pac-container { z-index: 2000000000 !important; }\n",
		// 'priority' => 8,
		// ];

		return $css;
	}
}
