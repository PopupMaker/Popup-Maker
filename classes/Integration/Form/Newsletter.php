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
 */
class PUM_Integration_Form_Newsletter extends PUM_Abstract_Integration_Form {

	/**
	 * Unique key identifier for this provider.
	 *
	 * @var string
	 */
	public $key = 'newsletter';

	/**
	 * Hook for non-AJAX form submissions.
	 */
	public function __construct() {
		add_action( 'newsletter_user_post_subscribe', [ $this, 'on_success' ], 10, 1 );
	}

	/**
	 * Text label that will be used throughout the various options screens.
	 *
	 * @return string
	 */
	public function label() {
		return __( 'Newsletter', 'popup-maker' );
	}

	/**
	 * Should return true when the required form plugin is active.
	 *
	 * @return bool
	 */
	public function enabled() {
		return defined( 'NEWSLETTER_VERSION' ) || class_exists( 'Newsletter' );
	}

	/**
	 * Newsletter uses shortcodes, not discrete forms.
	 *
	 * @return array
	 */
	public function get_forms() {
		return [];
	}

	/**
	 * Return a single form by ID.
	 *
	 * @param string $id Form ID.
	 *
	 * @return mixed
	 */
	public function get_form( $id ) {
		return null;
	}

	/**
	 * Returns an array of options for a select list.
	 *
	 * @return array
	 */
	public function get_form_selectlist() {
		return [];
	}

	/**
	 * Handle non-AJAX form submission success.
	 *
	 * @param object $user Newsletter user object.
	 *
	 * @return object
	 */
	public function on_success( $user ) {
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

