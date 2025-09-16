<?php
/**
 * REST API setup.
 *
 * @copyright (c) 2024, Code Atlantic LLC.
 * @package PopupMaker
 */

namespace PopupMaker\Controllers;

use PopupMaker\Plugin\Controller;
use PopupMaker\Plugin\Container;
use WP_Error;
use WP_REST_Server;
use WP_REST_Request;

/**
 * REST controller.
 *
 * @since 1.21.0
 */
class RestAPI extends Controller {

	/**
	 * Init controller.
	 *
	 * @return void
	 */
	public function init() {
		// Register custom REST API fields.
		add_action( 'init', [ $this, 'register_popup_rest_fields' ] );
		add_action( 'init', [ $this, 'register_cta_rest_fields' ] );

		// Register custom REST API endpoints.
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );

		// Register Pro upgrader REST routes.
		add_action( 'rest_api_init', [ $this, 'register_pro_upgrader_routes' ] );

		// Authentication.
		add_filter( 'rest_pre_dispatch', [ $this, 'rest_pre_dispatch' ], 10, 3 );

		// Legacy AJAX handlers for license status polling.
		add_action( 'wp_ajax_pum_check_license_status', [ $this, 'ajax_check_license_status' ] );

		// Sanitize and validate filters.
		// add_filter( 'popup_maker/sanitize_popup_settings', [ $this, 'sanitize_popup_settings' ], 10, 2 );
		// add_filter( 'popup_maker/validate_popup_settings', [ $this, 'validate_popup_settings' ], 10, 2 );
		add_filter( 'popup_maker/sanitize_call_to_action_settings', [ $this, 'sanitize_call_to_action_settings' ], 10, 2 );
		add_filter( 'popup_maker/validate_call_to_action_settings', [ $this, 'validate_call_to_action_settings' ], 10, 2 );
	}

	/**
	 * Register Rest API routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		( new \PopupMaker\RestAPI\Connect() )->register_routes();
		( new \PopupMaker\RestAPI\License() )->register_routes();
		( new \PopupMaker\RestAPI\ObjectSearch() )->register_routes();
	}

	/**
	 * Register Pro upgrader REST API routes.
	 *
	 * Registers endpoints for Pro upgrade workflow including license validation,
	 * connection verification, and upgrade processing.
	 *
	 * @return void
	 */
	public function register_pro_upgrader_routes() {
		// Register v2 REST API controllers.
		$this->register_v2_controllers();

		// Legacy v1 namespace for backward compatibility.
		$namespace = 'popup-maker/v1';

		// License validation endpoint.
		register_rest_route( $namespace, '/license/validate', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'rest_validate_license' ],
				'permission_callback' => [ $this, 'rest_pro_upgrade_permissions' ],
				'args'                => [
					'license_key' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $param ) {
							return is_string( $param ) && ! empty( trim( $param ) );
						},
					],
				],
			],
		] );

		// Connection verification endpoint.
		register_rest_route( $namespace, '/connect/verify', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'rest_verify_connection' ],
				'permission_callback' => '__return_true', // Webhook uses own authentication.
				'args'                => [
					'token' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'nonce' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			],
		] );

		// Pro upgrade installation endpoint.
		register_rest_route( $namespace, '/upgrade/install', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'rest_install_pro' ],
				'permission_callback' => '__return_true', // Webhook uses own authentication.
				'args'                => [
					'token' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'nonce' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'file'  => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
						'validate_callback' => function ( $param ) {
							return filter_var( $param, FILTER_VALIDATE_URL ) !== false;
						},
					],
					'slug'  => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $param ) {
							return is_string( $param ) && ! empty( trim( $param ) );
						},
					],
					'force' => [
						'required'          => false,
						'type'              => 'boolean',
						'default'           => false,
						'sanitize_callback' => function ( $param ) {
							return (bool) $param;
						},
					],
				],
			],
		] );

		// Connection info generation endpoint.
		register_rest_route( $namespace, '/connect/info', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'rest_get_connect_info' ],
				'permission_callback' => [ $this, 'rest_pro_upgrade_permissions' ],
			],
		] );
	}

	/**
	 * Register v2 REST API controllers.
	 *
	 * @return void
	 */
	private function register_v2_controllers() {
		// License controller.
		$license_controller = new \PopupMaker\RestAPI\License();
		$license_controller->register_routes();

		// Connect controller.
		$connect_controller = new \PopupMaker\RestAPI\Connect();
		$connect_controller->register_routes();
	}

	/**
	 * Permission callback for Pro upgrade endpoints.
	 *
	 * @return bool True if user has permission, false otherwise.
	 */
	public function rest_pro_upgrade_permissions() {
		return current_user_can( $this->container->get_permission( 'edit_popups' ) );
	}

	/**
	 * REST endpoint: Validate license for upgrade.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_validate_license( $request ) {
		$license_service = $this->container->get( 'license' );

		// If license key provided, update it first.
		$license_key = $request->get_param( 'license_key' );
		if ( ! empty( $license_key ) ) {
			$license_service->maybe_update_license_key( $license_key );
		}

		// Validate license for upgrade.
		$validation_result = $license_service->validate_for_upgrade();

		return rest_ensure_response( $validation_result );
	}

	/**
	 * REST endpoint: Verify webhook connection.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_verify_connection( $request ) {
		$connect_service = $this->container->get( 'connect' );

		// Verify the webhook request.
		$verification_result = $connect_service->verify_webhook_request( $request );

		if ( ! $verification_result['valid'] ) {
			return new WP_Error(
				'verification_failed',
				$verification_result['error'],
				[ 'status' => 403 ]
			);
		}

		return rest_ensure_response( [
			'success' => true,
			'message' => 'Connection verified successfully',
		] );
	}

	/**
	 * REST endpoint: Install Pro plugin.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_install_pro( $request ) {
		$connect_service = $this->container->get( 'connect' );

		// Verify the webhook request.
		$verification_result = $connect_service->verify_webhook_request( $request );

		if ( ! $verification_result['valid'] ) {
			return new WP_Error(
				'verification_failed',
				$verification_result['error'],
				[ 'status' => 403 ]
			);
		}

		// Prepare installation arguments.
		$args = [
			'file'  => $request->get_param( 'file' ),
			'type'  => 'plugin',
			'slug'  => $request->get_param( 'slug' ),
			'force' => $request->get_param( 'force' ),
		];

		// Verify webhook args.
		$connect_service->verify_webhook_args( $args );

		// Set the current screen to avoid undefined notices.
		set_current_screen( 'settings_page_popup-maker-settings' );

		try {
			// Install the plugin using the connect service.
			$connect_service->install_plugin( $args );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'installation_failed',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}

		// If we reach here, installation was successful.
		return rest_ensure_response( [
			'success' => true,
			'message' => 'Plugin installed and activated successfully',
		] );
	}

	/**
	 * REST endpoint: Get connection info.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_get_connect_info( $request ) {
		$license_service = $this->container->get( 'license' );

		// Generate connection info.
		$connect_info = $license_service->generate_connect_info();

		if ( null === $connect_info ) {
			return new WP_Error(
				'connection_unavailable',
				'Connection info not available. License may not be active or Pro may already be installed.',
				[ 'status' => 400 ]
			);
		}

		return rest_ensure_response( $connect_info );
	}


	protected function register_data_version_field( $post_type, $update_permission ) {
		register_rest_field( $post_type, 'data_version', [
			'get_callback'        => function ( $obj ) {
				return get_post_meta( $obj['id'], 'data_version', true );
			},
			'update_callback'     => function ( $value, $obj ) {
				// Update the field/meta value.
				update_post_meta( $obj->ID, 'data_version', $value );
			},
			'permission_callback' => function () use ( $update_permission ) {
				return current_user_can( $update_permission );
			},
		] );
	}

	/**
	 * Register common REST fields for a given post type
	 *
	 * @return void
	 */
	public function register_popup_rest_fields() {
		$post_type       = $this->container->get_controller( 'PostTypes' )->get_type_key( 'popup' );
		$edit_permission = $this->container->get_permission( 'edit_popups' );

		register_rest_field( $post_type, 'enabled', [
			'get_callback'        => function ( $obj ) {
				return get_post_meta( $obj['id'], 'enabled', true );
			},
			'update_callback'     => function ( $value, $obj ) {
				update_post_meta( $obj->ID, 'enabled', $value );
			},
			'permission_callback' => function () use ( $edit_permission ) {
				return current_user_can( $edit_permission );
			},
		] );

		register_rest_field( $post_type, 'settings', [
			'get_callback'        => function ( $obj, $field, $request ) {
				$popup = pum_get_popup( $obj['id'] );

				// If edit context, return the current settings.
				if ( 'edit' === $request['context'] ) {
					$settings = $popup->get_settings();
				} else {
					// Otherwise, return the public settings.
					$settings = $popup->get_public_settings();
				}

				return $settings;
			},
			'update_callback'     => function ( $value, $obj ) {
				$popup = pum_get_popup( $obj->ID );
				$popup->update_settings( $value );
			},
			'schema'              => [
				'type'        => 'object',
				'arg_options' => [
					'sanitize_callback' => function ( $settings, $request ) {
						/**
						 * Sanitize the popup settings.
						 *
						 * @param array<string,mixed> $settings The settings to sanitize.
						 * @param int   $id       The popup ID.
						 * @param \WP_REST_Request $request The request object.
						 *
						 * @return array<string,mixed> The sanitized settings.
						 */
						return apply_filters( 'popup_maker/sanitize_popup_settings', $settings, $request->get_param( 'id' ), $request );
					},
					'validate_callback' => function ( $settings, $request ) {
						/**
						 * Validate the popup settings.
						 *
						 * @param array<string,mixed> $settings The settings to validate.
						 * @param int   $id       The popup ID.
						 * @param \WP_REST_Request $request The request object.
						 *
						 * @return bool|\WP_Error True if valid, WP_Error if not.
						 */
						return apply_filters( 'popup_maker/validate_popup_settings', $settings, $request->get_param( 'id' ), $request );
					},
				],
			],
			'permission_callback' => function () use ( $edit_permission ) {
				return current_user_can( $edit_permission );
			},
		] );

		register_rest_field( $post_type, 'priority', [
			'get_callback'        => function ( $obj ) {
				return (int) get_post_field( 'menu_order', $obj['id'], 'raw' );
			},
			'update_callback'     => function ( $value, $obj ) {
				wp_update_post( [
					'ID'         => $obj->ID,
					'menu_order' => $value,
				] );
			},
			'permission_callback' => function () use ( $edit_permission ) {
				return current_user_can( $edit_permission );
			},
			'schema'              => [
				'type'        => 'integer',
				'arg_options' => [
					'sanitize_callback' => function ( $priority ) {
						return absint( $priority );
					},
					'validate_callback' => function ( $priority ) {
						return is_int( $priority );
					},
				],
			],
		] );

		// Register data version field.
		$this->register_data_version_field( $post_type, $edit_permission );
	}

	/**
	 * Sanitize popup settings.
	 *
	 * @param array<string,mixed> $settings The settings to sanitize.
	 * @param int                 $id       The popup ID.
	 *
	 * @return array<string,mixed> The sanitized settings.
	 */
	public function sanitize_popup_settings( $settings, $id ) {
		return $settings;
	}

	/**
	 * Validate popup settings.
	 *
	 * @param array<string,mixed> $settings The settings to validate.
	 * @param int                 $id       The popup ID.
	 *
	 * @return bool|\WP_Error True if valid, WP_Error if not.
	 */
	public function validate_popup_settings( $settings, $id ) {
		// TODO Validate all known settings by type.
		return true;
	}

	/**
	 * Registers custom REST API fields for call to action post type.
	 *
	 * @return void
	 */
	public function register_cta_rest_fields() {
		$post_type       = $this->container->get_controller( 'PostTypes' )->get_type_key( 'pum_cta' );
		$edit_permission = $this->container->get_permission( 'edit_ctas' );

		$ctas = $this->container->get( 'ctas' );

		$valid_statuses = [ 'publish', 'future', 'draft', 'pending', 'private', 'trash' ];

		register_rest_field( $post_type, 'status', [
			'get_callback'        => function ( $obj ) {
				return get_post_status( $obj['id'] );
			},
			'update_callback'     => function ( $value, $obj ) {
				wp_update_post( [
					'ID'          => $obj->ID,
					'post_status' => $value,
				] );
			},
			'permission_callback' => function () use ( $edit_permission ) {
				return current_user_can( $edit_permission );
			},
			'schema'              => [
				'type'        => 'string',
				'enum'        => [ 'publish', 'trash', 'draft' ],
				'default'     => 'publish',
				'arg_options' => [
					'sanitize_callback' => function ( $status ) use ( $valid_statuses ) {
						return in_array( $status, $valid_statuses, true ) ? $status : 'publish';
					},
					'validate_callback' => function ( $status ) use ( $valid_statuses ) {
						return in_array( $status, $valid_statuses, true );
					},
				],
			],
		] );

		// Register uuid field. Should be read-only &restricted to admins similar to edit only props.
		register_rest_field( $post_type, 'uuid', [
			'get_callback'        => function ( $obj ) {
				$cta = \PopupMaker\get_cta_by_id( $obj['id'] );

				if ( ! $cta ) {
					return null;
				}

				$uuid = $cta->get_uuid();

				return $uuid;
			},
			'permission_callback' => function () use ( $edit_permission ) {
				return current_user_can( $edit_permission );
			},
		] );

		// Register conversion counts field.
		register_rest_field(
			$post_type,
			'stats',
			[
				'get_callback'    => function ( $obj ) {
					$cta = \PopupMaker\get_cta_by_id( $obj['id'] );
					return [
						'conversions' => $cta->get_event_count( 'conversion' ),
					];
				},
				'update_callback' => null,
				'schema'          => [
					'description' => __( 'Stats for this CTA.', 'popup-maker' ),
					'type'        => 'object',
					'properties'  => [
						'conversion' => [
							'type'    => 'integer',
							'minimum' => 0,
						],
					],
				],
			]
		);

		// Register settings field.
		register_rest_field( $post_type, 'settings', [
			'get_callback'        => function ( $obj, $field, $request ) use ( $ctas ) {
				$cta = \PopupMaker\get_cta_by_id( $obj['id'] );

				if ( ! $cta ) {
					return [];
				}

				$settings = [];

				// If edit context, return the current settings.
				if ( 'edit' === $request['context'] ) {
					$settings = get_post_meta( $obj['id'], 'cta_settings', true );

					if ( empty( $settings ) ) {
						$settings = \PopupMaker\get_default_call_to_action_settings();
					}
				} else {
					// Otherwise, return the public settings.
					$settings = $cta->get_public_settings();
				}

				return $settings;
			},
			'update_callback'     => function ( $value, $obj ) {
				update_post_meta( $obj->ID, 'cta_settings', $value );
			},
			'schema'              => [
				'type'        => 'object',
				'arg_options' => [
					'sanitize_callback' => function ( $settings, $request ) {
						/**
						 * Sanitize the call to action settings.
						 *
						 * @param array<string,mixed> $settings The settings to sanitize.
						 * @param int   $id       The call to action ID.
						 * @param \WP_REST_Request $request The request object.
						 *
						 * @return array<string,mixed> The sanitized settings.
						 */
						return apply_filters( 'popup_maker/sanitize_call_to_action_settings', $settings, $request->get_param( 'id' ), $request );
					},
					'validate_callback' => function ( $settings, $request ) {
						/**
						 * Validate the popup settings.
						 *
						 * @param array<string,mixed> $settings The settings to validate.
						 * @param int   $id       The popup ID.
						 * @param \WP_REST_Request $request The request object.
						 *
						 * @return bool|\WP_Error True if valid, WP_Error if not.
						 */
						return apply_filters( 'popup_maker/validate_call_to_action_settings', $settings, $request->get_param( 'id' ), $request );
					},
				],
			],
			'permission_callback' => function () use ( $edit_permission ) {
				return current_user_can( $edit_permission );
			},
		] );

		// Register data version field.
		$this->register_data_version_field( $post_type, $edit_permission );
	}

	/**
	 * Sanitize call to action settings.
	 *
	 * @param array<string,mixed> $settings The settings to sanitize.
	 * @param int                 $id       The call to action ID.
	 *
	 * @return array<string,mixed> The sanitized settings.
	 */
	public function sanitize_call_to_action_settings( $settings, $id ) {
		return $settings;
	}

	/**
	 * Validate call to action settings.
	 *
	 * @param array<string,mixed> $settings The settings to validate.
	 * @param int                 $id       The call to action ID.
	 *
	 * @return bool|\WP_Error True if valid, WP_Error if not.
	 */
	public function validate_call_to_action_settings( $settings, $id ) {
		if ( empty( $settings['type'] ) ) {
			return new \WP_Error( 'missing_type', __( 'CTA type is required', 'popup-maker' ) );
		}

		// Get the CTA type handler
		$cta_types = $this->container->get( 'cta_types' );
		$cta_type  = $cta_types->get( $settings['type'] );

		if ( ! $cta_type ) {
			return new \WP_Error( 'invalid_type', __( 'Invalid CTA type', 'popup-maker' ), [ 'status' => 400 ] );
		}

		// Validate settings using the CTA type's validation method
		$validation_result = $cta_type->validate_settings( $settings );

		if ( is_array( $validation_result ) ) {
			// Merge each field error into a single error.
			$error = new \WP_Error();
			foreach ( $validation_result as $field_error ) {
				$error->add( $field_error->get_error_code(), $field_error->get_error_message(), $field_error->get_error_data() );
			}

			return $error;
		}

		if ( is_wp_error( $validation_result ) ) {
			return $validation_result;
		}

		return true;
	}

	/**
	 * Prevent access to the popups endpoint.
	 *
	 * @param mixed                                 $result Response to replace the requested version with.
	 * @param \WP_REST_Server                       $server Server instance.
	 * @param \WP_REST_Request<array<string,mixed>> $request  Request used to generate the response.
	 * @return mixed
	 */
	public function rest_pre_dispatch( $result, $server, $request ) {
		// Get the route being requested.
		$route = $request->get_route();

		if ( false === strpos( $route, '/popup-maker/v2' ) ) {
			return $result;
		}

		$current_user_can = true;

		// Only proceed if the current user has permission.
		if ( false !== strpos( $route, '/popup-maker/v2/popups' ) ) {
			$current_user_can = current_user_can( $this->container->get_permission( 'edit_popups' ) );
		} elseif ( false !== strpos( $route, '/popup-maker/v2/ctas' ) ) {
			$current_user_can = current_user_can( $this->container->get_permission( 'edit_ctas' ) );
		}

		// Prevent discovery of the endpoints data from unauthorized users.
		if ( ! $current_user_can ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Access to this endpoint requires authorization.', 'popup-maker' ),
				[
					'status' => rest_authorization_required_code(),
				]
			);
		}

		// Return data to the client to parse.
		return $result;
	}

	/**
	 * AJAX handler for license status checking.
	 *
	 * Legacy handler for license status polling system.
	 *
	 * @return void
	 */
	public function ajax_check_license_status() {
		// Verify nonce for security.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'popup-maker-settings' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Check user permissions.
		if ( ! current_user_can( $this->container->get_permission( 'edit_popups' ) ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$license_service = $this->container->get( 'license' );

		// Get license status data.
		$license_status = $license_service->get_license_status_data();
		$license_key    = $license_service->get_license_key();
		$is_active      = $license_service->is_license_active();

		// Check if Pro is installed.
		$is_pro_installed = $this->container->is_pro_installed();
		$is_pro_active    = $is_pro_installed && is_plugin_active( 'popup-maker-pro/popup-maker-pro.php' );

		$response_data = [
			'is_valid'         => $is_active,
			'license_key'      => $license_key,
			'status'           => $license_service->get_license_status(),
			'expires'          => ! empty( $license_status['expires'] ) ? $license_status['expires'] : null,
			'pro_installed'    => $is_pro_installed,
			'is_pro_installed' => $is_pro_installed,
			'is_pro_active'    => $is_pro_active,
		];

		wp_send_json_success( $response_data );
	}
}
