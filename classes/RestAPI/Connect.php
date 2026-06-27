<?php
/**
 * REST API Webhook Controller.
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

use function PopupMaker\plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Connect REST API Controller.
 *
 * Handles secure connection endpoints for Pro installation workflow.
 * Implements multi-layer security with authentication, signature verification,
 * and referrer validation.
 *
 * @since 1.21.0
 */
class Connect extends WP_REST_Controller {

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
	protected $rest_base = 'connect';

	/**
	 * Connect service instance.
	 *
	 * @var \PopupMaker\Services\Connect
	 */
	private $connect_service;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->connect_service = plugin( 'connect' );
	}

	/**
	 * Register the routes for the connection endpoints.
	 *
	 * @return void
	 */
	public function register_routes() {
		// POST /connect/install - Install Pro plugin via secure connection.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/install',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'install_webhook' ],
					'permission_callback' => [ $this, 'webhook_permissions_check' ],
					'args'                => $this->get_install_webhook_args(),
				],
			]
		);

		// POST /connect/verify - Verify connection for testing.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/verify',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'verify_webhook' ],
					'permission_callback' => [ $this, 'webhook_permissions_check' ],
					'args'                => [],
				],
			]
		);
	}

	/**
	 * Handle secure install webhook.
	 *
	 * This endpoint receives secure requests from the upgrade server to install Pro.
	 * Multiple security layers are enforced:
	 * 1. User agent verification
	 * 2. Referrer domain validation
	 * 3. Bearer token authentication
	 * 4. HMAC signature verification
	 * 5. License validation
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or WP_Error on failure.
	 */
	public function install_webhook( $request ) {
		try {
			// Determine up front whether this is a verification ping or a real install.
			$json_data       = json_decode( file_get_contents( 'php://input' ), true );
			$is_verification = 'verify' === $request->get_param( 'action' ) ||
				( is_array( $json_data ) && isset( $json_data['action'] ) && 'verify' === $json_data['action'] );

			// Validate the connection using multi-layer security.
			$this->validate_secure_connection( $request );

			// Check if this is a verification request.
			if ( $is_verification ) {
				$this->connect_service->debug_log( 'Processing webhook verification request', 'DEBUG' );
				return new WP_REST_Response(
					[
						'success'  => true,
						'message'  => __( 'Webhook verification successful.', 'popup-maker' ),
						'verified' => true,
					],
					200
				);
			}

			// Get webhook installation arguments.
			$args = $this->get_webhook_install_args( $request );

			// Validate license is active before proceeding.
			if ( ! plugin( 'license' )->is_license_active() ) {
				$this->connect_service->debug_log( 'License not active for webhook install', 'ERROR' );
				return new WP_Error(
					'license_inactive',
					__( 'License must be active to install Pro.', 'popup-maker' ),
					[ 'status' => 403 ]
				);
			}

			// Install the plugin based on type.
			switch ( $args['type'] ) {
				case 'plugin':
					return $this->install_plugin_via_webhook( $args );
				default:
					return new WP_Error(
						'invalid_install_type',
						__( 'Invalid installation type.', 'popup-maker' ),
						[ 'status' => 400 ]
					);
			}
		} catch ( \Exception $e ) {
			$this->connect_service->debug_log( 'Webhook install failed: ' . $e->getMessage(), 'ERROR' );
			return new WP_Error(
				'webhook_install_failed',
				$e->getMessage(),
				[ 'status' => $this->get_exception_status( $e ) ]
			);
		} finally {
			// Only clean up token for actual installation, not verification
			$json_data       = json_decode( file_get_contents( 'php://input' ), true );
			$is_verification = is_array( $json_data ) && isset( $json_data['action'] ) && 'verify' === $json_data['action'];

			if ( ! $is_verification && ! $this->connect_service->debug_mode_enabled() ) {
				$this->connect_service->debug_log( 'Cleaning up access token after successful installation', 'DEBUG' );
				$this->clean_up_access_token();
			} elseif ( $is_verification ) {
				$this->connect_service->debug_log( 'Skipping token cleanup for verification request - token preserved for installation', 'DEBUG' );
			}
		}
	}

	/**
	 * Verify webhook connection for testing.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or WP_Error on failure.
	 */
	public function verify_webhook( $request ) {
		try {
			// Validate the connection using multi-layer security.
			$this->validate_secure_connection( $request );

			return new WP_REST_Response(
				[
					'success' => true,
					'message' => __( 'Webhook connection verified successfully.', 'popup-maker' ),
				],
				200
			);
		} catch ( \Exception $e ) {
			$this->connect_service->debug_log( 'Webhook verification failed: ' . $e->getMessage(), 'ERROR' );
			return new WP_Error(
				'webhook_verify_failed',
				$e->getMessage(),
				[ 'status' => $this->get_exception_status( $e ) ]
			);
		}
	}

	/**
	 * Validate secure connection with multi-layer security.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @throws \Exception If validation fails.
	 * @return void
	 */
	private function validate_secure_connection( $request ) {
		$this->connect_service->validate_rest_connection( $request );
		$this->connect_service->debug_log( 'All security layers validated successfully', 'DEBUG' );
	}

	/**
	 * Get HTTP status for a webhook exception.
	 *
	 * @param \Exception $e Exception.
	 * @return int HTTP status.
	 */
	private function get_exception_status( \Exception $e ) {
		$security_errors = [
			\PopupMaker\Services\Connect::ERROR_REFERRER,
			\PopupMaker\Services\Connect::ERROR_AUTHENTICATION,
			\PopupMaker\Services\Connect::ERROR_USER_AGENT,
			\PopupMaker\Services\Connect::ERROR_SIGNATURE,
			\PopupMaker\Services\Connect::ERROR_NONCE,
		];

		if ( in_array( (int) $e->getCode(), $security_errors, true ) ) {
			return 403;
		}

		if ( \PopupMaker\Services\Connect::ERROR_WEBHOOK_ARGS === (int) $e->getCode() ) {
			return 400;
		}

		return 500;
	}

	/**
	 * Get webhook installation arguments from request.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return array<string,mixed> Validated installation arguments.
	 * @throws \Exception If required arguments are missing or invalid.
	 */
	private function get_webhook_install_args( $request ) {
		// Try multiple parameter names and sources to handle different formats
		$args = [
			// Map upgrade server parameter names to our internal names
			'file'  => $this->get_param_from_multiple_sources( $request, 'download_url' )
					?: $this->get_param_from_multiple_sources( $request, 'file' ),
			'type'  => $this->get_param_from_multiple_sources( $request, 'type' ) ?: 'plugin',
			'slug'  => $this->get_param_from_multiple_sources( $request, 'plugin_slug' )
					?: $this->get_param_from_multiple_sources( $request, 'slug' ),
			'force' => (bool) ( $this->get_param_from_multiple_sources( $request, 'force_update' )
					?: $this->get_param_from_multiple_sources( $request, 'force' ) ),
		];

		$this->connect_service->debug_log( 'Webhook install args: ' . wp_json_encode( $args, JSON_PRETTY_PRINT ), 'DEBUG' );

		// Validate required parameters.
		if ( empty( $args['file'] ) || empty( $args['slug'] ) ) {
			$this->connect_service->debug_log( 'Missing required parameters - file: ' . ( $args['file'] ?: 'MISSING' ) . ', slug: ' . ( $args['slug'] ?: 'MISSING' ), 'ERROR' );
			throw new \Exception( esc_html__( 'Missing required installation parameters.', 'popup-maker' ), \PopupMaker\Services\Connect::ERROR_WEBHOOK_ARGS );
		}

		// Validate installation type.
		if ( ! in_array( $args['type'], [ 'plugin', 'theme' ], true ) ) {
			throw new \Exception( esc_html__( 'Invalid installation type.', 'popup-maker' ), \PopupMaker\Services\Connect::ERROR_WEBHOOK_ARGS );
		}

		// Validate the download URL host against the allowlist.
		// Without this, a request that satisfies the connection checks could install
		// an arbitrary package from any attacker-controlled URL.
		if ( ! $this->is_allowed_download_url( $args['file'] ) ) {
			$this->connect_service->debug_log( 'Rejected install: download URL host not allowed: ' . $args['file'], 'ERROR' );
			throw new \Exception( esc_html__( 'Download URL is not from an allowed source.', 'popup-maker' ), \PopupMaker\Services\Connect::ERROR_WEBHOOK_ARGS );
		}

		return $args;
	}

	/**
	 * Get the list of hosts allowed to serve installable packages.
	 *
	 * @return array<int,string> Lowercase hostnames.
	 */
	private function get_allowed_download_hosts() {
		$allowed_hosts = [
			'wppopupmaker.com',
			'upgrade.wppopupmaker.com',
		];

		/**
		 * Filter: popup_maker/connect_allowed_download_hosts
		 *
		 * Allows trusted hosts that may serve installable Pro packages to be customized.
		 * Returned hostnames are compared case-insensitively against the URL host.
		 *
		 * @param array<int,string> $allowed_hosts Allowed hostnames.
		 */
		$allowed_hosts = apply_filters( 'popup_maker/connect_allowed_download_hosts', $allowed_hosts );

		return array_map( 'strtolower', array_filter( (array) $allowed_hosts ) );
	}

	/**
	 * Determine whether a download URL points at an allowed host.
	 *
	 * Accepts only HTTPS URLs whose host exactly matches (or is a subdomain of)
	 * an allowlisted host.
	 *
	 * @param string $url Download URL.
	 * @return bool True when the URL is allowed.
	 */
	private function is_allowed_download_url( $url ) {
		if ( empty( $url ) || ! is_string( $url ) ) {
			return false;
		}

		$host   = wp_parse_url( $url, PHP_URL_HOST );
		$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );

		if ( empty( $host ) || 'https' !== $scheme ) {
			return false;
		}

		$host = strtolower( $host );

		foreach ( $this->get_allowed_download_hosts() as $allowed_host ) {
			if ( $host === $allowed_host || substr( $host, -strlen( '.' . $allowed_host ) ) === '.' . $allowed_host ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get parameter from multiple sources (REST params, JSON body, $_REQUEST).
	 *
	 * @param WP_REST_Request $request The request object.
	 * @param string          $param_name Parameter name.
	 * @return mixed Parameter value or null if not found.
	 */
	private function get_param_from_multiple_sources( $request, $param_name ) {
		// First try REST API parameters.
		$value = $request->get_param( $param_name );
		if ( ! empty( $value ) ) {
			return $value;
		}

		// Try JSON body.
		$json_data = json_decode( file_get_contents( 'php://input' ), true );
		if ( is_array( $json_data ) && isset( $json_data[ $param_name ] ) ) {
			return $json_data[ $param_name ];
		}

		// Fallback to $_REQUEST.

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST[ $param_name ] ) ) {
			return sanitize_text_field( wp_unslash( $_REQUEST[ $param_name ] ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return null;
	}

	/**
	 * Install plugin via webhook.
	 *
	 * @param array<string,mixed> $args Installation arguments.
	 * @return WP_REST_Response|WP_Error Response object or WP_Error on failure.
	 */
	private function install_plugin_via_webhook( $args ) {
		$this->connect_service->debug_log( 'Installing plugin via webhook...', 'DEBUG' );

		// Check if plugin is already active and not forcing reinstall.
		$plugin_file = "{$args['slug']}/{$args['slug']}.php";
		if ( ! $args['force'] && is_plugin_active( $plugin_file ) ) {
			$this->connect_service->debug_log( 'Plugin already installed and active', 'DEBUG' );
			return new WP_REST_Response(
				[
					'success' => true,
					'message' => __( 'Plugin is already installed and activated.', 'popup-maker' ),
				],
				200
			);
		}

		// Load required WordPress files for plugin installation in REST context
		if ( ! function_exists( 'request_filesystem_credentials' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( ! class_exists( 'WP_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
		}

		$this->connect_service->debug_log( 'Required WordPress files loaded for plugin installation', 'DEBUG' );

		// Get the upgrader service and install the plugin.
		$upgrader = plugin( 'upgrader' );
		$result   = $upgrader->install_plugin( $args['file'] );

		if ( is_wp_error( $result ) ) {
			$this->connect_service->debug_log( 'Plugin installation failed: ' . $result->get_error_message(), 'ERROR' );
			return new WP_Error(
				'plugin_install_failed',
				$result->get_error_message(),
				[ 'status' => 500 ]
			);
		}

		$this->connect_service->debug_log( 'Plugin installed successfully', 'DEBUG' );
		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Plugin installed and activated successfully.', 'popup-maker' ),
			],
			200
		);
	}

	/**
	 * Clean up access token after use.
	 *
	 * @return void
	 */
	private function clean_up_access_token() {
		$this->connect_service->debug_log( 'Cleaning up access token', 'DEBUG' );
		delete_site_transient( \PopupMaker\Services\Connect::TOKEN_OPTION_NAME );
	}

	/**
	 * Check webhook permissions.
	 *
	 * This is a specialized permission check that doesn't rely on WordPress user capabilities
	 * since webhook requests come from external servers. Instead, it validates the secure
	 * connection through the multi-layer security system.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error True if authorized, WP_Error otherwise.
	 */
	public function webhook_permissions_check( $request ) {
		// For webhook endpoints, we don't check user capabilities since these are
		// server-to-server requests. The security is handled through the multi-layer
		// validation system in the actual endpoint methods.
		return true;
	}

	/**
	 * Get the arguments for install webhook endpoint.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_install_webhook_args() {
		return [
			'file'  => [
				'description'       => __( 'Download URL for the plugin file.', 'popup-maker' ),
				'type'              => 'string',
				'required'          => false,
				'format'            => 'uri',
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => function ( $param ) {
					if ( empty( $param ) || ! filter_var( $param, FILTER_VALIDATE_URL ) ) {
						return new WP_Error(
							'invalid_file_url',
							__( 'Valid file URL is required.', 'popup-maker' ),
							[ 'status' => 400 ]
						);
					}
					// Reject any URL that is not served from an allowed host.
					if ( ! $this->is_allowed_download_url( $param ) ) {
						return new WP_Error(
							'invalid_file_url',
							__( 'Download URL is not from an allowed source.', 'popup-maker' ),
							[ 'status' => 400 ]
						);
					}
					return true;
				},
			],
			'type'  => [
				'description'       => __( 'Type of installation (plugin or theme).', 'popup-maker' ),
				'type'              => 'string',
				'default'           => 'plugin',
				'enum'              => [ 'plugin', 'theme' ],
				'sanitize_callback' => 'sanitize_text_field',
			],
			'slug'  => [
				'description'       => __( 'Plugin or theme slug.', 'popup-maker' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function ( $param ) {
					if ( empty( $param ) || ! preg_match( '/^[a-z0-9-_]+$/', $param ) ) {
						return new WP_Error(
							'invalid_slug',
							__( 'Valid slug is required (letters, numbers, hyphens, and underscores only).', 'popup-maker' ),
							[ 'status' => 400 ]
						);
					}
					return true;
				},
			],
			'force' => [
				'description'       => __( 'Force reinstallation even if already installed.', 'popup-maker' ),
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => function ( $param ) {
					return (bool) $param;
				},
			],
		];
	}
}
