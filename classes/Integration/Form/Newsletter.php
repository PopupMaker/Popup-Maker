<?php
/**
 * Integration for Newsletter Plugin
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Integration_Form_Newsletter
 *
 * Integrates with "The Newsletter Plugin" for form submission tracking.
 *
 * Newsletter uses fetch-based AJAX and replaces the form innerHTML with
 * a success message. They don't fire any JavaScript events, so we use
 * MutationObserver on the client side to detect success.
 *
 * Server-side, we hook into the success action for non-AJAX submissions
 * and conversion tracking when popup ID is available.
 */
class PUM_Integration_Form_Newsletter extends PUM_Abstract_Integration_Form {

	/**
	 * Unique key identifier for this provider.
	 *
	 * @var string
	 */
	public $key = 'newsletter';

	/**
	 * Register the Newsletter subscription hook used to process non-AJAX form submissions.
	 *
	 * Hooks `newsletter_user_post_subscribe` to this class's on_success method so server-side
	 * submission processing and conversion tracking can run when a user subscribes via the Newsletter plugin.
	 */
	public function __construct() {
		// Fires after a user successfully subscribes.
		add_action( 'newsletter_user_post_subscribe', [ $this, 'on_success' ], 10, 1 );
	}

	/**
	 * Provides the display label for this integration.
	 *
	 * @return string The localized label "Newsletter".
	 */
	public function label() {
		return __( 'Newsletter', 'popup-maker' );
	}

	/**
	 * Determine whether the Newsletter plugin is active.
	 *
	 * @return bool true if the Newsletter plugin is active, false otherwise.
	 */
	public function enabled() {
		return defined( 'NEWSLETTER_VERSION' ) || class_exists( 'Newsletter' );
	}

	/**
	 * Provide available Newsletter forms for integration; Newsletter uses shortcodes so there are none.
	 *
	 * @return array An empty array because the Newsletter plugin exposes forms via shortcodes rather than discrete form instances.
	 */
	public function get_forms() {
		return [];
	}

	/**
	 * Retrieve a Newsletter form by its ID (not supported for Newsletter shortcodes).
	 *
	 * @param string $id Form ID (unused).
	 * @return null Always null because the Newsletter plugin uses shortcodes rather than discrete forms.
	 */
	public function get_form( $id ) {
		return null;
	}

	/**
	 * Provide select list options for available Newsletter forms.
	 *
	 * @return array An empty array because Newsletter uses shortcodes and does not expose discrete selectable forms.
	 */
	public function get_form_selectlist() {
		return [];
	}

	/**
	 * Handle form submission success for Newsletter integrations on the server.
	 *
	 * Processes non-AJAX subscription submissions, records a conversion when a popup ID is present,
	 * and logs an integrated form submission with the provider key.
	 *
	 * @param object $user Newsletter user object that was created or updated.
	 * @return object The same Newsletter user object passed in.
	 */
	public function on_success( $user ) {
		// Only process server-side for non-AJAX requests.
		if ( ! $this->should_process_submission() ) {
			return $user;
		}

		$popup_id = $this->get_popup_id();

		if ( $popup_id ) {
			$this->increase_conversion( $popup_id );
		}

		pum_integrated_form_submission(
			[
				'popup_id'      => $popup_id,
				'form_provider' => $this->key,
				'form_id'       => null,
			]
		);

		return $user;
	}
}