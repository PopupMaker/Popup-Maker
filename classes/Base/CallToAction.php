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
 * @since 1.21.0
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
	 * @return array<string, array<string, mixed>[]> Array of field groups where each group contains field configurations
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
	 * @param array<string, mixed>            $extra_args Optional. Additional data passed to the handler (will include popup_id).
	 *
	 * @return void
	 */
	abstract public function action_handler( \PopupMaker\Models\CallToAction $call_to_action, array $extra_args = [] ): void;

	/**
	 * Validate CTA settings array before saving.
	 *
	 * @param array<string, mixed> $settings The raw settings array to validate.
	 *
	 * @return true|\WP_Error|\WP_Error[] True if valid, WP_Error if validation fails.
	 */
	abstract public function validate_settings( array $settings );

	/**
	 * Validate required fields.
	 *
	 * @param array<string, mixed> $settings The raw settings array to validate.
	 *
	 * @return true|\WP_Error|\WP_Error[] True if valid, WP_Error if validation fails.
	 */
	public function validate_required_fields( array $settings ) {
		// Default implementation: validate required fields.
		/** @var string[] $errors */
		$errors = [];
		$fields = $this->fields();

		// Check all fields in all tabs for required validation.
		foreach ( $fields as $tab_fields ) {
			foreach ( $tab_fields as $field_id => $field ) {
				if ( empty( $field['required'] ) ) {
					continue;
				}

				// Check dependencies - field should only be validated if dependencies are met.
				if ( ! empty( $field['dependencies'] ) ) {
					$dependencies_met = true;
					foreach ( $field['dependencies'] as $key => $expected_value ) {
						$actual_value = $settings[ $key ] ?? '';

						if ( is_string( $expected_value ) ) {
							if ( $actual_value !== $expected_value ) {
								$dependencies_met = false;
								break;
							}
						}

						if ( is_bool( $expected_value ) ) {
							if ( (bool) $actual_value !== $expected_value ) {
								$dependencies_met = false;
								break;
							}
						}
					}

					if ( ! $dependencies_met ) {
						continue; // Skip validation if dependencies not met.
					}
				}

				// Check if required field is empty.
				$field_value = $settings[ $field_id ] ?? '';
				if ( empty( $field_value ) || ( is_string( $field_value ) && '' === trim( $field_value ) ) ) {
					$field_label = $field['label'] ?? $field_id;
					/* translators: %s: Field label */
					$errors[] = sprintf( __( '%s is required', 'popup-maker' ), $field_label );
				}
			}
		}

		if ( ! empty( $errors ) ) {
			return new \WP_Error( 'validation_failed', implode( ', ', $errors ), $errors );
		}

		return true;
	}

	/**
	 * Returns an array that represents the cta.
	 *
	 * Used to pass configs to JavaScript.
	 *
	 * @return array{
	 *     key: string,
	 *     label: string,
	 *     login_required: bool,
	 *     fields: array<string, array<string, mixed>[]>
	 * }
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
			/** @var string[] $cta_args */
			$cta_args = apply_filters( 'popup_maker/cta_valid_url_args', [ 'cta', 'pid' ] );
			$url      = remove_query_arg( $cta_args );
			\PopupMaker\safe_redirect( $url );
		}
		exit;
	}
}
