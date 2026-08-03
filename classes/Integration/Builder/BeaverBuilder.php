<?php
/**
 * Integration for Beaver Builder Button module.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds an "Open Popup" option to Beaver Builder's Button module.
 *
 * When a popup is selected, the button is rendered with the `popmake-{id}`
 * class, which Popup Maker's built-in Click Open trigger uses to open the
 * popup — the same mechanism as the block editor button and shortcodes.
 *
 * @since 1.24.0
 */
class PUM_Integration_Builder_BeaverBuilder extends PUM_Abstract_Integration {

	/**
	 * Integration key.
	 *
	 * @var string
	 */
	public $key = 'beaverbuilder_button';

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $type = 'builder';

	/**
	 * Integration label.
	 *
	 * @return string
	 */
	public function label() {
		// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Use Beaver Builder's own translations.
		return __( 'Beaver Builder', 'fl-builder' );
	}

	/**
	 * Whether Beaver Builder is active.
	 *
	 * @return bool
	 */
	public function enabled() {
		return class_exists( 'FLBuilder' );
	}

	/**
	 * Hook into Beaver Builder's module settings + rendered attributes.
	 */
	public function __construct() {
		add_filter( 'fl_builder_register_module_settings_form', [ $this, 'add_settings_field' ], 10, 2 );
		add_filter( 'fl_builder_module_attributes', [ $this, 'add_trigger_class' ], 10, 2 );
	}

	/**
	 * Add an "Open Popup" click action to the Button module's settings form.
	 *
	 * Registers "Open Popup" as a Click Action option (alongside Link, Button,
	 * Lightbox) and adds a popup selector that only shows when it is chosen.
	 *
	 * @param array  $form Registered settings form.
	 * @param string $slug Module slug.
	 *
	 * @return array
	 */
	public function add_settings_field( $form, $slug ) {
		if ( 'button' !== $slug ) {
			return $form;
		}

		$fields = &$form['general']['sections']['general']['fields'];

		if ( ! isset( $fields['click_action'] ) || ! is_array( $fields['click_action'] ) ) {
			return $form;
		}

		// Add "Open Popup" as a click action option.
		$fields['click_action']['options']['popup'] = __( 'Open Popup', 'popup-maker' );

		// Reveal the popup selector only when the Open Popup action is chosen.
		$fields['click_action']['toggle']['popup'] = [
			'fields' => [ 'pum_open_popup' ],
		];

		// The popup selector itself.
		$fields['pum_open_popup'] = [
			'type'    => 'select',
			'label'   => __( 'Popup', 'popup-maker' ),
			'default' => '',
			'options' => $this->get_popup_options(),
			'help'    => __( 'Open the selected popup when this button is clicked.', 'popup-maker' ),
		];

		return $form;
	}

	/**
	 * Append the Popup Maker trigger class to the button when a popup is set.
	 *
	 * @param array  $attrs  Module node attributes ('class' is an array).
	 * @param object $module Beaver Builder module instance.
	 *
	 * @return array
	 */
	public function add_trigger_class( $attrs, $module ) {
		if ( ! isset( $module->slug ) || 'button' !== $module->slug ) {
			return $attrs;
		}

		// Only when the Open Popup click action is selected.
		if ( ! isset( $module->settings->click_action ) || 'popup' !== $module->settings->click_action ) {
			return $attrs;
		}

		$popup_id = isset( $module->settings->pum_open_popup ) ? absint( $module->settings->pum_open_popup ) : 0;

		if ( $popup_id <= 0 ) {
			return $attrs;
		}

		if ( ! isset( $attrs['class'] ) || ! is_array( $attrs['class'] ) ) {
			$attrs['class'] = isset( $attrs['class'] ) ? (array) $attrs['class'] : [];
		}

		$attrs['class'][] = 'popmake-' . $popup_id;

		// Force the referenced popup to load on this page regardless of its own
		// display conditions — otherwise the button would render but the popup
		// it targets might not be enqueued. Skipped in the builder UI/admin.
		// Mirrors the popup-trigger shortcode. maybe_preload_popup() still
		// respects the popup's enabled state.
		if ( ! is_admin() && ! ( class_exists( 'FLBuilderModel' ) && FLBuilderModel::is_builder_active() ) ) {
			\PopupMaker\plugin()->get_controller( 'Frontend\Popups' )->maybe_preload_popup( $popup_id );
		}

		return $attrs;
	}

	/**
	 * Build the popup options list for the select field.
	 *
	 * @return array<int|string,string>
	 */
	private function get_popup_options() {
		$options = [
			'' => __( '— None —', 'popup-maker' ),
		];

		$popups = pum_get_all_popups();

		if ( empty( $popups ) || ! is_array( $popups ) ) {
			return $options;
		}

		foreach ( $popups as $popup ) {
			if ( ! pum_is_popup( $popup ) ) {
				continue;
			}

			// Use the post title (the popup's admin name); get_title() reads the
			// separate popup_title meta, which is usually empty.
			$title = $popup->post_title;

			if ( '' === trim( (string) $title ) ) {
				$title = __( '(no title)', 'popup-maker' );
			}

			$options[ $popup->ID ] = sprintf(
				/* translators: 1: popup title, 2: popup ID. */
				__( '%1$s (ID: %2$d)', 'popup-maker' ),
				$title,
				$popup->ID
			);
		}

		return $options;
	}
}
