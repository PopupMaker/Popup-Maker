<?php
/**
 * Call To Action interface.
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Interface CallToAction
 *
 * @since 1.21.0
 */
interface CallToAction {

	/**
	 * Label for reference.
	 *
	 * @return string
	 */
	public function label();

	/**
	 * Function that returns array of fields by group.
	 *
	 * @return array
	 */
	public function fields();

	/**
	 * Handle the CTA action.
	 *
	 * @param \PopupMaker\Models\CallToAction $call_to_action Call to action object.
	 * @param array                           $extra_args     Optional. Additional data passed to the handler (will include popup_id).
	 *
	 * @return void
	 */
	public function action_handler( \PopupMaker\Models\CallToAction $call_to_action, array $extra_args = [] ): void;

	/**
	 * Validate CTA settings array before saving.
	 *
	 * @param array $settings The raw settings array to validate.
	 *
	 * @return true|\WP_Error|\WP_Error[] True if valid, WP_Error if validation fails.
	 */
	public function validate_settings( array $settings );

	/**
	 * Returns an array that represents the cta.
	 *
	 * Used to pass configs to JavaScript.
	 *
	 * @return array
	 */
	public function as_array();
}
