<?php
/**
 * Call To Action abstract class.
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Base;

defined( 'ABSPATH' ) || exit;


/**
 * Class CallToAction
 *
 * @since X.X.X
 */
abstract class CallToAction implements \PopupMaker\Interfaces\CallToAction {

	/**
	 * Unique identifier token.
	 *
	 * @var string
	 */
	public $key;

	/**
	 * Whether the CTA requires the user to be logged in.
	 *
	 * @var bool
	 */
	public $login_required = false;

	/**
	 * Label for reference.
	 *
	 * @return string
	 */
	abstract public function label(): string;

	/**
	 * Function that returns array of fields by group.
	 *
	 * @return array
	 */
	abstract public function fields(): array;

	/**
	 * Whether the CTA requires the user to be logged in.
	 *
	 * @return void
	 */
	public function check_login_required() {
		if ( $this->login_required ) {
			if ( ! is_user_logged_in() ) {
				// Get current URL including query args safely using WordPress functions.
				$current_url = add_query_arg( [] );
				$this->safe_redirect( wp_login_url( $current_url ) );
			}
		}
	}

	/**
	 * Handle the CTA action.
	 *
	 * @param \PopupMaker\Models\CallToAction $call_to_action Call to action object.
	 * @param array                           $extra_args     Optional. Additional data passed to the handler (will include popup_id).
	 *
	 * @return void
	 */
	abstract public function action_handler( \PopupMaker\Models\CallToAction $call_to_action, array $extra_args = [] ): void;

	/**
	 * Returns an array that represents the cta.
	 *
	 * Used to pass configs to JavaScript.
	 *
	 * @return array
	 */
	public function as_array(): array {
		return [
			'key'            => $this->key,
			'label'          => $this->label(),
			'login_required' => $this->login_required,
			'fields'         => $this->fields(),
		];
	}

	/**
	 * Safely redirect user with fallback handling & sanitization.
	 *
	 * This function handles the common pattern of redirecting with fallbacks
	 * used across PopupMaker Pro helper classes.
	 *
	 * @param string $redirect_url Redirect URL.
	 * @param string $fallback_url Fallback URL.
	 * @return void
	 */
	public function safe_redirect( string $redirect_url = '', string $fallback_url = '' ): void {
		if ( ! empty( $redirect_url ) && filter_var( $redirect_url, FILTER_VALIDATE_URL ) ) {
			\PopupMaker\safe_redirect( $redirect_url );
		} elseif ( ! empty( $fallback_url ) ) {
			\PopupMaker\safe_redirect( $fallback_url );
		} else {
			// Default fallback.
			$cta_args = apply_filters( 'popup_maker/cta_valid_url_args', [ 'cta', 'pid' ] );
			$url      = remove_query_arg( $cta_args );
			\PopupMaker\safe_redirect( $url );
		}
		exit;
	}
}
