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
	 * Register Beaver Builder's server-authoritative success hooks.
	 */
	public function __construct() {
		add_action( 'fl_module_contact_form_after_send', [ $this, 'on_contact_success' ], 10, 6 );
		add_action( 'fl_builder_subscribe_form_submission_complete', [ $this, 'on_subscribe_success' ], 10, 6 );
		add_action( 'fl_builder_login_form_submission_complete', [ $this, 'on_login_success' ], 10, 5 );
	}

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
	 * Normalize a Contact Form only after its native mail action succeeds.
	 *
	 * @param mixed $mailto   Recipient.
	 * @param mixed $subject  Subject.
	 * @param mixed $template Message template.
	 * @param mixed $headers  Mail headers.
	 * @param mixed $settings Module settings.
	 * @param mixed $result   Native wp_mail() result.
	 */
	public function on_contact_success( $mailto, $subject, $template, $headers, $settings, $result ) {
		if ( true !== $result ) {
			return;
		}

		$this->dispatch_success( 'contact' );
	}

	/**
	 * Normalize a Subscribe Form after Beaver Builder reports success.
	 *
	 * @param mixed $response    Native response.
	 * @param mixed $settings    Module settings.
	 * @param mixed $email       Submitted email.
	 * @param mixed $name        Submitted name.
	 * @param mixed $template_id Template ID.
	 * @param mixed $post_id     Post ID.
	 */
	public function on_subscribe_success( $response, $settings, $email, $name, $template_id, $post_id ) {
		$this->dispatch_success( 'subscribe' );
	}

	/**
	 * Normalize a Login Form after Beaver Builder reports success.
	 *
	 * @param mixed $settings    Module settings.
	 * @param mixed $password    Submitted password.
	 * @param mixed $name        Submitted username.
	 * @param mixed $template_id Template ID.
	 * @param mixed $post_id     Post ID.
	 */
	public function on_login_success( $settings, $password, $name, $template_id, $post_id ) {
		$this->dispatch_success( 'login' );
	}

	/**
	 * Dispatch one normalized receipt for the successful native module request.
	 *
	 * Beaver Builder verifies its AJAX request before firing these hooks. The
	 * node ID therefore identifies the same module instance used by Core's
	 * browser observation without adding a second transport or provider action.
	 *
	 * @param string $type Native module type.
	 */
	private function dispatch_success( $type ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Beaver Builder verifies the native AJAX request before its success hooks fire.
		$node_id = isset( $_REQUEST['node_id'] ) && is_scalar( $_REQUEST['node_id'] ) ? sanitize_key( wp_unslash( $_REQUEST['node_id'] ) ) : '';
		$form_id = $type . '_' . ( '' !== $node_id ? $node_id : 'any' );

		pum_integrated_form_submission(
			[
				'popup_id'      => $this->get_popup_id(),
				'form_provider' => $this->key,
				'form_id'       => $form_id,
			]
		);
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
