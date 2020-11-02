<?php
/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

class PUM_Integration_Form_PirateForms extends PUM_Abstract_Integration_Form {

	/**
	 * @var string
	 */
	public $key = 'pirateforms';

	public function __construct() {
		// add_action( 'wpforms_process_complete', array( $this, 'on_success' ), 10, 4 );
	}

	/**
	 * @return string
	 */
	public function label() {
		return 'Pirate Forms';
	}

	/**
	 * @return bool
	 */
	public function enabled() {
		return defined( 'PIRATE_FORMS_VERSION' ) && PIRATE_FORMS_VERSION;
	}

	/**
	 * @return array
	 */
	public function get_forms() {
		// Union those arrays, as array_merge() does keys reindexing.
		$forms = $this->get_default_forms() + $this->get_pro_forms();

		// Sort by IDs ASC.
		ksort( $forms );

		return $forms;
	}

	/**
	 * Pirate Forms has a default form, which doesn't have an ID.
	 *
	 * @since 1.4.9
	 *
	 * @return array
	 */
	protected function get_default_forms() {

		$form = PirateForms_Util::get_form_options();

		// Just make sure that it's there and not broken.
		if ( empty( $form ) ) {
			return array();
		}

		return array( 0 => esc_html__( 'Default Form', 'wpforms-lite' ) );
	}

	/**
	 * Copy-paste from Pro plugin code, it doesn't have API to get this data easily.
	 *
	 * @since 1.4.9
	 *
	 * @return array
	 */
	protected function get_pro_forms() {

		$forms = array();
		$query = new WP_Query(
			array(
				'post_type'              => 'pf_form',
				'post_status'            => 'publish',
				'posts_per_page'         => - 1,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$forms[ get_the_ID() ] = get_the_title();
			}
		}

		return $forms;
	}

	/**
	 * Get a single form options.
	 *
	 * @since 1.4.9
	 *
	 * @param int $id Form ID.
	 *
	 * @return array
	 */
	public function get_form( $id ) {
		return PirateForms_Util::get_form_options( (int) $id );
	}

	/**
	 * @return array
	 */
	public function get_form_selectlist() {
		$form_selectlist = [];

		$forms = $this->get_forms();

		foreach ( $forms as $form ) {
			$form_selectlist[ $form['ID'] ] = $form['name'];
		}

		return $form_selectlist;
	}

	/**
	 * @link https://wpforms.com/developers/wpforms_process_complete/
	 *
	 * @param array $fields Sanitized entry field values/properties.
	 * @param array $entry Original $_POST global.
	 * @param array $form_data Form data and settings.
	 * @param int $entry_id Entry ID. Will return 0 if entry storage is disabled or using WPForms Lite.
	 */
	public function on_success( $fields, $entry, $form_data, $entry_id ) {
		if ( ! self::should_process_submission() ) {
			return;
		}
		$popup_id = self::get_popup_id();
		self::increase_conversion( $popup_id );
		pum_integrated_form_submission( [
			'popup_id'      => $popup_id,
			'form_provider' => $this->key,
			'form_id'       => $form_data['id'],
		] );
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
		$css[ $this->key ] = [
			'content'  => ".pac-container { z-index: 2000000000 !important; }\n",
			'priority' => 8,
		];

		return $css;
	}


}
