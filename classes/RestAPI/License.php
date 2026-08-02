<?php
/**
 * Legacy Pro license REST compatibility controller.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\RestAPI;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps released Pro versions compatible without exposing installation routes.
 *
 * @deprecated 1.23.0 Temporary Pro 1.1.0 compatibility. Scheduled for removal in Core 1.25.0.
 */
class License extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'popup-maker/v2';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'license';

	/**
	 * Register license-only routes.
	 *
	 * @deprecated 1.23.0 Temporary Pro 1.1.0 compatibility. Scheduled for removal in Core 1.25.0.
	 *
	 * @return void
	 */
	public function register_routes() {
		if ( ! $this->compatibility_enabled() ) {
			return;
		}

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_license' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/activate',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'activate_license' ],
					'permission_callback' => [ $this, 'permissions_check' ],
					'args'                => [
						'license_key' => [
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/deactivate',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'deactivate_license' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
			]
		);
	}

	/**
	 * Get license information.
	 *
	 * @deprecated 1.23.0 Temporary Pro 1.1.0 compatibility. Scheduled for removal in Core 1.25.0.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_license( $request ) {
		unset( $request );

		return new WP_REST_Response( $this->response_data(), 200 );
	}

	/**
	 * Activate a stored license.
	 *
	 * Kept callable so existing Core 1.23.x and Pro versions older than 1.2.0
	 * can activate, deactivate, and recover licenses during the short migration
	 * window. Pro 1.2.0+ does not use this endpoint.
	 *
	 * @deprecated 1.23.0 Temporary Pro 1.1.0 compatibility. Scheduled for removal in Core 1.25.0.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function activate_license( $request ) {
		$license = \PopupMaker\plugin( 'license' );
		$key     = $request->get_param( 'license_key' );

		if ( ! is_string( $key ) || '' === trim( $key ) ) {
			return new WP_Error(
				'invalid_license_key',
				__( 'Enter a valid license key.', 'popup-maker' ),
				[ 'status' => 400 ]
			);
		}

		if ( ! $license->maybe_activate_license( $key ) ) {
			$error_message = $license->get_license_error_message();

			if ( '' === trim( $error_message ) ) {
				$error_message = __( 'License activation failed. Check your connection and try again.', 'popup-maker' );
			}

			return new WP_Error(
				'license_activation_failed',
				$error_message,
				[ 'status' => 400 ]
			);
		}

		return new WP_REST_Response( array_merge( [ 'success' => true ], $this->response_data() ), 200 );
	}

	/**
	 * Deactivate the stored license.
	 *
	 * @deprecated 1.23.0 Temporary Pro 1.1.0 compatibility. Scheduled for removal in Core 1.25.0.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function deactivate_license( $request ) {
		unset( $request );

		try {
			$deactivated = \PopupMaker\plugin( 'license' )->deactivate_license();
		} catch ( \Exception $e ) {
			$deactivated = false;
		}

		if ( ! $deactivated ) {
			return new WP_Error(
				'license_deactivation_failed',
				__( 'License deactivation failed.', 'popup-maker' ),
				[ 'status' => 400 ]
			);
		}

		return new WP_REST_Response( array_merge( [ 'success' => true ], $this->response_data() ), 200 );
	}

	/**
	 * Check license-management permissions.
	 *
	 * @deprecated 1.23.0 Temporary Pro 1.1.0 compatibility. Scheduled for removal in Core 1.25.0.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return true|WP_Error
	 */
	public function permissions_check( $request ) {
		unset( $request );

		if ( $this->compatibility_enabled() && current_user_can( 'manage_options' ) ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to manage licenses.', 'popup-maker' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}

	/**
	 * Check the guarded legacy compatibility state.
	 *
	 * @since 1.23.0
	 *
	 * @deprecated 1.23.0 Temporary Pro 1.1.0 compatibility. Scheduled for removal in Core 1.25.0.
	 *
	 * @return bool
	 */
	protected function compatibility_enabled() {
		return \PopupMaker\plugin()->should_run_legacy_license_compatibility();
	}

	/**
	 * Build a compatibility response without installation metadata.
	 *
	 * @return array<string,mixed>
	 */
	private function response_data() {
		$license = \PopupMaker\plugin( 'license' );

		return [
			'license_key' => $license::star_key( $license->get_license_key() ),
			'status'      => $license->get_license_status(),
			'status_data' => $license->get_license_status_data(),
			'is_active'   => $license->is_license_active(),
		];
	}
}
