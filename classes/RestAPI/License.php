<?php
/**
 * REST API License Controller.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\RestAPI;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * License REST API Controller.
 *
 * Handles license management endpoints for Pro upgrade workflow.
 *
 * @since 1.21.0
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
	 * Register the routes for the license endpoints.
	 *
	 * @return void
	 */
	public function register_routes() {
		// GET /license - Get license information.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_license' ],
					'permission_callback' => [ $this, 'get_license_permissions_check' ],
					'args'                => [],
				],
				'schema' => [ $this, 'get_license_schema' ],
			]
		);

		// POST /license/activate - Activate license key.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/activate',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'activate_license' ],
					'permission_callback' => [ $this, 'license_action_permissions_check' ],
					'args'                => $this->get_activate_license_args(),
				],
			]
		);

		// POST /license/activate-pro - Activate license and install Pro.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/activate-pro',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'activate_license_pro' ],
					'permission_callback' => [ $this, 'license_action_permissions_check' ],
					'args'                => $this->get_activate_license_args(),
				],
			]
		);

		// GET /license/connect-info - Get connection info for popup upgrade flow.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/connect-info',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_connect_info' ],
					'permission_callback' => [ $this, 'license_action_permissions_check' ],
					'args'                => $this->get_connect_info_args(),
				],
			]
		);

		// POST /license/activate-plugin - Activate Pro plugin if installed.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/activate-plugin',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'activate_plugin' ],
					'permission_callback' => [ $this, 'license_action_permissions_check' ],
					'args'                => [],
				],
			]
		);

		// POST /license/deactivate - Deactivate license.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/deactivate',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'deactivate_license' ],
					'permission_callback' => [ $this, 'license_action_permissions_check' ],
					'args'                => [],
				],
			]
		);
	}

	/**
	 * Get license information.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or WP_Error on failure.
	 */
	public function get_license( $request ) {
		$license_service = \PopupMaker\plugin( 'license' );

		$response_data = [
			'license_key'         => $license_service->get_license_key(),
			'status'              => $license_service->get_license_status(),
			'status_data'         => $license_service->get_license_status_data(),
			'is_active'           => $license_service->is_license_active(),
			'is_pro_installed'    => \PopupMaker\plugin()->is_pro_installed(),
			'is_pro_active'       => \PopupMaker\plugin()->is_pro_active(),
			'has_extensions'      => \PopupMaker\plugin()->has_extensions(),
			'has_pro_plus_addons' => \PopupMaker\plugin()->has_pro_plus_addons(),
			'can_upgrade'         => false,
			'connect_info'        => null,
		];

		// Add upgrade information if license is valid but Pro isn't installed.
		if ( $license_service->is_license_active() && ! \PopupMaker\plugin()->is_pro_installed() ) {
			$response_data['can_upgrade']  = true;
			$response_data['connect_info'] = $license_service->generate_connect_info();
		}

		return new WP_REST_Response( $response_data, 200 );
	}

	/**
	 * Activate license key.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or WP_Error on failure.
	 */
	public function activate_license( $request ) {
		$license_key     = $request->get_param( 'license_key' );
		$license_service = \PopupMaker\plugin( 'license' );

		try {
			$activated = $license_service->maybe_activate_license( $license_key );

			if ( ! $activated ) {
				$status_data   = $license_service->get_license_status_data();
				$error_message = $license_service->get_license_error_message( $status_data );

				return new WP_Error(
					'license_activation_failed',
					$error_message ?: __( 'License activation failed.', 'popup-maker' ),
					[ 'status' => 400 ]
				);
			}

			$response_data = [
				'success'          => true,
				'message'          => __( 'License activated successfully.', 'popup-maker' ),
				'license_key'      => $license_service->get_license_key(),
				'status'           => $license_service->get_license_status(),
				'status_data'      => $license_service->get_license_status_data(),
				'is_active'        => $license_service->is_license_active(),
				'is_pro_installed' => \PopupMaker\plugin()->is_pro_installed(),
				'is_pro_active'    => \PopupMaker\plugin()->is_pro_active(),
			];

			return new WP_REST_Response( $response_data, 200 );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'license_activation_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Activate license key and provide Pro installation info.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or WP_Error on failure.
	 */
	public function activate_license_pro( $request ) {
		$license_key     = $request->get_param( 'license_key' );
		$license_service = \PopupMaker\plugin( 'license' );

		try {
			$activated = $license_service->maybe_activate_license( $license_key );

			if ( ! $activated ) {
				$status_data   = $license_service->get_license_status_data();
				$error_message = $license_service->get_license_error_message( $status_data );

				return new WP_Error(
					'license_activation_failed',
					$error_message ?: __( 'License activation failed.', 'popup-maker' ),
					[ 'status' => 400 ]
				);
			}

			$response_data = [
				'success'          => true,
				'message'          => __( 'License activated successfully.', 'popup-maker' ),
				'license_key'      => $license_service->get_license_key(),
				'status'           => $license_service->get_license_status(),
				'status_data'      => $license_service->get_license_status_data(),
				'is_active'        => $license_service->is_license_active(),
				'is_pro_installed' => \PopupMaker\plugin()->is_pro_installed(),
				'is_pro_active'    => \PopupMaker\plugin()->is_pro_active(),
				'can_upgrade'      => false,
				'connect_info'     => null,
			];

			// Add upgrade information if Pro isn't installed.
			if ( ! \PopupMaker\plugin()->is_pro_installed() ) {
				$response_data['can_upgrade']  = true;
				$response_data['connect_info'] = $license_service->generate_connect_info();
				$response_data['message']      = __( 'License activated successfully. Ready for Pro upgrade.', 'popup-maker' );
			}

			return new WP_REST_Response( $response_data, 200 );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'license_activation_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Deactivate license.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or WP_Error on failure.
	 */
	public function deactivate_license( $request ) {
		$license_service = \PopupMaker\plugin( 'license' );

		try {
			$deactivated = $license_service->deactivate_license();

			if ( ! $deactivated ) {
				return new WP_Error(
					'license_deactivation_failed',
					__( 'License deactivation failed.', 'popup-maker' ),
					[ 'status' => 400 ]
				);
			}

			$response_data = [
				'success'          => true,
				'message'          => __( 'License deactivated successfully.', 'popup-maker' ),
				'license_key'      => $license_service->get_license_key(),
				'status'           => $license_service->get_license_status(),
				'status_data'      => $license_service->get_license_status_data(),
				'is_active'        => $license_service->is_license_active(),
				'is_pro_installed' => \PopupMaker\plugin()->is_pro_installed(),
				'is_pro_active'    => \PopupMaker\plugin()->is_pro_active(),
			];

			return new WP_REST_Response( $response_data, 200 );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'license_deactivation_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Check if a given request has access to get license information.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_license_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to view license information.', 'popup-maker' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to perform license actions.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function license_action_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to manage licenses.', 'popup-maker' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Get the arguments for license activation endpoints.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_activate_license_args() {
		return [
			'license_key' => [
				'description'       => __( 'License key to activate.', 'popup-maker' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function ( $param ) {
					if ( empty( $param ) || ! is_string( $param ) ) {
						return new WP_Error(
							'invalid_license_key',
							__( 'License key is required and must be a valid string.', 'popup-maker' ),
							[ 'status' => 400 ]
						);
					}
					return true;
				},
			],
		];
	}

	/**
	 * Get the schema for license endpoints.
	 *
	 * @return array<string,mixed>
	 */
	public function get_license_schema() {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'license',
			'type'       => 'object',
			'properties' => [
				'license_key'      => [
					'description' => __( 'The license key.', 'popup-maker' ),
					'type'        => 'string',
					'readonly'    => true,
				],
				'status'           => [
					'description' => __( 'The license status.', 'popup-maker' ),
					'type'        => 'string',
					'enum'        => [ 'empty', 'inactive', 'expired', 'error', 'valid' ],
					'readonly'    => true,
				],
				'status_data'      => [
					'description' => __( 'Detailed license status data.', 'popup-maker' ),
					'type'        => [ 'object', 'null' ],
					'readonly'    => true,
				],
				'is_active'        => [
					'description' => __( 'Whether the license is active.', 'popup-maker' ),
					'type'        => 'boolean',
					'readonly'    => true,
				],
				'is_pro_installed' => [
					'description' => __( 'Whether Pro is installed.', 'popup-maker' ),
					'type'        => 'boolean',
					'readonly'    => true,
				],
				'is_pro_active'    => [
					'description' => __( 'Whether Pro is active.', 'popup-maker' ),
					'type'        => 'boolean',
					'readonly'    => true,
				],
				'can_upgrade'      => [
					'description' => __( 'Whether Pro upgrade is available.', 'popup-maker' ),
					'type'        => 'boolean',
					'readonly'    => true,
				],
				'connect_info'     => [
					'description' => __( 'Connection information for Pro upgrade.', 'popup-maker' ),
					'type'        => [ 'object', 'null' ],
					'readonly'    => true,
				],
			],
		];
	}

	/**
	 * Get connection info for upgrade flow.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object or WP_Error on failure.
	 */
	public function get_connect_info( $request ) {
		$license_key = sanitize_text_field( $request->get_param( 'license_key' ) );
		$context     = $request->get_param( 'context' );

		// Parse context if provided as JSON string.
		if ( is_string( $context ) ) {
			$context = json_decode( $context, true );
			if ( null === $context && JSON_ERROR_NONE !== json_last_error() ) {
				return new WP_Error(
					'invalid_context_json',
					__( 'Invalid JSON provided for context parameter.', 'popup-maker' ),
					[ 'status' => 400 ]
				);
			}
		}

		if ( ! is_array( $context ) ) {
			$context = [];
		}

		// CRITICAL FIX: Use stored/activated license key instead of form field
		// This ensures we send the same license key that shows as "valid" locally
		$license_service    = \PopupMaker\plugin( 'license' );
		$stored_license_key = $license_service->get_license_key();

		// Prefer stored license key over form field, use form field only as fallback
		$final_license_key = ! empty( $stored_license_key ) ? $stored_license_key : $license_key;

		try {
			// Use the Connect service to generate proper connection info.
			$connect_service = \PopupMaker\plugin( 'connect' );
			$connect_info    = $connect_service->get_connect_info( $final_license_key );

			// Parse the URL to extract individual parameters for frontend use.
			$parsed_url   = wp_parse_url( $connect_info['url'] );
			$query_params = [];

			if ( isset( $parsed_url['query'] ) ) {
				parse_str( $parsed_url['query'], $query_params );
			}

			// Return individual parameters that the frontend can use.
			$response_data = [
				'success' => true,
				'data'    => array_merge( $query_params, [
					'product'  => $context['product'] ?? 'popup-maker-pro',
					'source'   => $context['source'] ?? 'rest-api',
					'campaign' => $context['campaign'] ?? 'upgrade-flow',
					'base_url' => $parsed_url['scheme'] . '://' . $parsed_url['host'] . ( $parsed_url['path'] ?? '' ),
					'full_url' => $connect_info['url'],
					'back_url' => $connect_info['back_url'],
				]),
			];

			return new WP_REST_Response( $response_data, 200 );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'connect_info_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Get arguments for connect-info endpoint.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_connect_info_args() {
		return [
			'license_key' => [
				'description'       => __( 'License key for connection.', 'popup-maker' ),
				'type'              => 'string',
				'required'          => false,
				'validate_callback' => function ( $param ) {
					return is_string( $param ) && strlen( $param ) <= 100;
				},
				'sanitize_callback' => 'sanitize_text_field',
			],
			'context'     => [
				'description'       => __( 'Additional context information.', 'popup-maker' ),
				'type'              => [ 'string', 'object' ],
				'required'          => false,
				'validate_callback' => function ( $param ) {
					if ( is_string( $param ) ) {
						$decoded = json_decode( $param, true );
						return json_last_error() === JSON_ERROR_NONE;
					}
					return is_array( $param );
				},
			],
		];
	}

	/**
	 * Activate Pro plugin if installed but not active.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object or WP_Error on failure.
	 */
	public function activate_plugin( $request ) {
		// Check if Pro plugin is installed.
		if ( ! \PopupMaker\plugin()->is_pro_installed() ) {
			return new WP_Error(
				'pro_not_installed',
				__( 'Pro plugin is not installed.', 'popup-maker' ),
				[ 'status' => 404 ]
			);
		}

		// Check if Pro plugin is already active.
		if ( \PopupMaker\plugin()->is_pro_active() ) {
			return new WP_Error(
				'pro_already_active',
				__( 'Pro plugin is already active.', 'popup-maker' ),
				[ 'status' => 400 ]
			);
		}

		// Activate the Pro plugin.
		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_path = 'popup-maker-pro/popup-maker-pro.php';
		$result      = activate_plugin( $plugin_path, '', false, true );

		if ( is_wp_error( $result ) ) {
			return new WP_Error(
				'activation_failed',
				sprintf(
					/* translators: %s - Error message */
					__( 'Something went wrong, the Pro plugin could not be activated: %s', 'popup-maker' ),
					$result->get_error_message()
				),
				[ 'status' => 500 ]
			);
		}

		// Return success response.
		return new WP_REST_Response( [
			'success'          => true,
			'message'          => __( 'Pro plugin activated successfully.', 'popup-maker' ),
			'is_pro_installed' => \PopupMaker\plugin()->is_pro_installed(),
			'is_pro_active'    => \PopupMaker\plugin()->is_pro_active(),
		], 200 );
	}

	/**
	 * Handle webhook request from upgrade server.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object or WP_Error on failure.
	 */
	public function handle_webhook( $request ) {
		try {
			// Use Connect service to handle the webhook processing.
			$connect_service = \PopupMaker\plugin( 'connect' );

			// Verify the webhook request is valid and secure.
			$verification = $connect_service->verify_webhook_request( $request );

			if ( ! $verification['valid'] ) {
				return new WP_Error(
					'webhook_verification_failed',
					$verification['error'] ?: __( 'Webhook verification failed.', 'popup-maker' ),
					[ 'status' => 403 ]
				);
			}

			// Process the webhook - this will handle the Pro installation.
			$connect_service->process_webhook();

			// If we get here, the webhook was processed successfully.
			return new WP_REST_Response( [
				'success' => true,
				'message' => __( 'Webhook processed successfully.', 'popup-maker' ),
			], 200 );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'webhook_processing_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Check permissions for webhook endpoint.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if allowed, WP_Error otherwise.
	 */
	public function webhook_permissions_check( $request ) {
		// Webhook requests come from external upgrade server with special authentication.
		// The Connect service handles its own authentication via tokens and signatures.
		return true;
	}
}
