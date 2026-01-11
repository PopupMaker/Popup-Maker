<?php
/**
 * Integration for HappyForms
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

class PUM_Integration_Form_HappyForms extends PUM_Abstract_Integration_Form {

	/**
	 * Unique key identifier for this provider.
	 *
	 * @var string
	 */
	public $key = 'happyforms';

	/**
	 * Constructor - Hook into HappyForms submission success action.
	 */
	public function __construct() {
		add_action( 'happyforms_submission_success', [ $this, 'on_success' ], 10, 2 );
	}

	/**
	 * Text label that will be used throughout the various options screens.
	 *
	 * @return string
	 */
	public function label() {
		// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Use HappyForms' own translations.
		return __( 'HappyForms', 'happyforms' );
	}

	/**
	 * Should return true when the required form plugin is active.
	 *
	 * @return bool
	 */
	public function enabled() {
		return class_exists( 'HappyForms_Core' );
	}

	/**
	 * Return a useable array of all forms from this provider.
	 *
	 * @return array<object{id:int,title:string}>
	 */
	public function get_forms() {
		$forms = [];

		// Query all HappyForms posts.
		$form_posts = get_posts(
			[
				'post_type'      => 'happyform',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		foreach ( $form_posts as $post ) {
			$forms[] = (object) [
				'id'    => $post->ID,
				'title' => $post->post_title,
			];
		}

		return $forms;
	}

	/**
	 * Return a single form by ID.
	 *
	 * @param string $id The form ID.
	 *
	 * @return object{id:int,title:string}|false
	 */
	public function get_form( $id ) {
		$id = absint( $id );
		if ( ! $id ) {
			return false;
		}

		$form_post = get_post( $id );

		if ( ! $form_post || 'happyform' !== $form_post->post_type ) {
			return false;
		}

		return (object) [
			'id'    => $form_post->ID,
			'title' => $form_post->post_title,
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
	 * Hooks in a success function specific to this provider for non-AJAX submission handling.
	 *
	 * @param array $submission The submission data.
	 * @param array $form       The form data.
	 */
	public function on_success( $submission, $form ) {
		if ( ! $this->should_process_submission() ) {
			return;
		}

		$popup_id = $this->get_popup_id();

		if ( ! $popup_id ) {
			return;
		}

		$this->increase_conversion( $popup_id );

		$form_id = isset( $form['id'] ) ? (string) $form['id'] : null;

		pum_integrated_form_submission(
			[
				'popup_id'      => $popup_id,
				'form_provider' => $this->key,
				'form_id'       => $form_id,
			]
		);
	}

	/**
	 * Load a custom script file to handle AJAX based submissions or other integrations with Popup Maker frontend.
	 *
	 * @param array $js JavaScript array.
	 *
	 * @return array
	 */
	public function custom_scripts( $js = [] ) {
		return $js;
	}

	/**
	 * Load custom styles for hacking some elements specifically inside popups.
	 *
	 * @param array $css CSS array.
	 *
	 * @return array
	 */
	public function custom_styles( $css = [] ) {
		return $css;
	}
}
