<?php
/**
 * Connect service.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Services;

use PopupMaker\Base\Service;
use function PopupMaker\plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Connection management.
 *
 * NOTE: For wordpress.org admins: This is not called in the free, hosted version. This is only used if:
 * - The user explicitly entered a license key.
 * - This then opens a window to our site allowing the user to authorize one-time oauth-style connection & installation of pro.
 *
 * @package PopupMaker
 */
class Connect extends Service {

	const API_URL           = 'https://upgrade.wppopupmaker.com/';
	const TOKEN_OPTION_NAME = 'popup_maker_connect_token';
	const NONCE_OPTION_NAME = 'popup_maker_connect_nonce';

	const ERROR_REFERRER       = 1;
	const ERROR_AUTHENTICATION = 2;
	const ERROR_USER_AGENT     = 3;
	const ERROR_SIGNATURE      = 4;
	const ERROR_NONCE          = 5;
	const ERROR_WEBHOOK_ARGS   = 6;

	/**
	 * Check if debug mode is enabled.
	 *
	 * @return bool
	 */
	public function debug_mode_enabled() {
		return defined( '\WP_DEBUG' ) && \WP_DEBUG;
	}

	/**
	 * Generate a new authorizatin token.
	 *
	 * @return string
	 */
	public function generate_token() {
		$token = hash( 'sha512', (string) wp_rand() );

		\set_site_transient( self::TOKEN_OPTION_NAME, $token, HOUR_IN_SECONDS );

		return $token;
	}

	/**
	 * Get the current token.
	 *
	 * @return string|false
	 */
	public function get_access_token() {
		return \get_site_transient( self::TOKEN_OPTION_NAME );
	}

	/**
	 * Get the current nonce.
	 *
	 * @param string $token Token.
	 *
	 * @return string|false
	 */
	public function get_nonce_name( $token ) {
		return self::NONCE_OPTION_NAME . '_' . $token;
	}

	/**
	 * Log a message to the debug log if enabled.
	 *
	 * Here to prevent constant conditional checks for the debug mode.
	 *
	 * @param string $message Message.
	 * @param string $type    Type.
	 *
	 * @return void
	 */
	public function debug_log( $message, $type = 'INFO' ) {
		if ( $this->debug_mode_enabled() ) {
			plugin( 'logging' )->log( "Plugin\Connect.$type: $message" );
		}
	}

	/**
	 * Get header Authorization
	 *
	 * @return null|string
	 */
	public function get_request_authorization_header() {
		$headers = null;

		if ( isset( $_SERVER['Authorization'] ) ) {
			$headers = trim( sanitize_text_field( wp_unslash( $_SERVER['Authorization'] ) ) );
		} elseif ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) { // Nginx or fast CGI.
			$headers = trim( sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) ) );
		} elseif ( function_exists( 'apache_request_headers' ) ) {
			$request_headers = apache_request_headers();
			// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization).
			$request_headers = array_combine( array_map( 'ucwords', array_keys( $request_headers ) ), array_values( $request_headers ) );
			if ( isset( $request_headers['Authorization'] ) ) {
				$headers = trim( $request_headers['Authorization'] );
			}
		}

		// Fallback: Some servers strip Authorization headers, check for custom headers
		if ( ! $headers ) {
			$fallback_headers = [
				'HTTP_X_AUTH_TOKEN'      => 'X-Auth-Token',
				'HTTP_X_AUTH_TOKEN_UC'   => 'X-AUTH-TOKEN (uppercase)', // This won't match, but keeping for clarity
				'HTTP_HTTP_X_AUTH_TOKEN' => 'HTTP_X_AUTH_TOKEN (server format)',
			];

			foreach ( $fallback_headers as $server_key => $header_name ) {
				if ( isset( $_SERVER[ $server_key ] ) ) {
					$token   = sanitize_text_field( wp_unslash( $_SERVER[ $server_key ] ) );
					$headers = 'Bearer ' . $token;
					// Using fallback header.
					break;
				}
			}
		}

		return $headers;
	}

	/**
	 * Get access token from header.
	 *
	 * @return string|null
	 */
	public function get_request_token() {
		$headers = $this->get_request_authorization_header();
		// HEADER: Get the access token from the header.
		if ( ! empty( $headers ) ) {
			if ( preg_match( '/Bearer\s(\S+)/', $headers, $matches ) ) {
				return trim( $matches[1], ', ' );
			}
		}
		return null;
	}

	/**
	 * Get nonce from header.
	 *
	 * @return string|null
	 */
	public function get_request_nonce() {
		$headers = $this->get_request_authorization_header();
		// HEADER: Get the nonce from the header.
		if ( ! empty( $headers ) ) {
			if ( preg_match( '/Nonce\s(\S+)/', $headers, $matches ) ) {
				return trim( $matches[1], ', ' );
			}
		}
		return null;
	}

	/**
	 * Get the OAuth connect URL.
	 *
	 * @param string $license_key License key.
	 *
	 * @return array{url:string,back_url:string}
	 */
	public function get_connect_info( $license_key ) {
		$token    = $this->generate_token();
		$nonce    = wp_create_nonce( $this->get_nonce_name( $token ) );
		$webhook  = rest_url( 'popup-maker/v2/connect/install' );
		$redirect = add_query_arg(
			[
				'post_type' => 'popup',
				'page'      => 'pum-settings',
				// TODO This should be set to the Pro upgrade page?
				'view'      => 'upgrade',
				'tab'       => 'general',
			],
			admin_url( 'edit.php' )
		);

		$url = add_query_arg(
			[
				'key'      => $license_key,
				'token'    => $token,
				'nonce'    => $nonce,
				'webhook'  => $webhook,
				'version'  => plugin( 'version' ),
				'siteurl'  => admin_url(),
				'homeurl'  => home_url(),
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'redirect' => rawurlencode( base64_encode( $redirect ) ),
			],
			self::API_URL
		);

		$this->debug_log( 'Generated new connection.' );
		$this->debug_log( 'Token: ' . $token );
		$this->debug_log( 'Nonce: ' . $nonce );
		$this->debug_log( 'Redirect URL: ' . $redirect );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$this->debug_log( 'Base64 Redirect: ' . base64_encode( $redirect ) );
		$this->debug_log( 'Final URL: ' . $url );

		return [
			'url'      => $url,
			'back_url' => add_query_arg(
				[
					'action' => 'popup_maker_connect',
					'token'  => $token,
				],
				$webhook
			),
		];
	}

	/**
	 * Kill the connection with no permission.
	 *
	 * @param int          $error_no Error number.
	 * @param string|false $message Error message.
	 *
	 * @return void
	 */
	public function kill_connection( $error_no = self::ERROR_REFERRER, $message = false ) {
		$this->debug_log( "Killing connection with error ($error_no) message: " . $message, 'ERROR' );

		wp_die(
			esc_html(
				$this->debug_mode_enabled() && $message ?
					$message :
						__( 'Sorry, You Are Not Allowed to Access This Page.', 'popup-maker' )
			),
			esc_attr( (string) $error_no ),
			[ 'response' => 403 ]
		);
	}

	/**
	 * Verify the user agent.
	 *
	 * @return void
	 */
	public function verify_user_agent() {
		// Check user agent matches Popup Maker Upgrader.
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		if ( strpos( $user_agent, 'PopupMakerUpgrader' ) !== 0 ) {
			$this->kill_connection( self::ERROR_USER_AGENT, 'User agent invalid: ' . $user_agent );
		}
	}

	/**
	 * Verify the referrer.
	 *
	 * @return void
	 */
	public function verify_referrer() {
		$referer = isset( $_SERVER['HTTP_X_SENDING_DOMAIN'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_SENDING_DOMAIN'] ) ) : '';

		if ( ! $referer ) {
			$this->kill_connection( self::ERROR_REFERRER, 'Missing referrer' );
		}

		$referer_host = wp_parse_url( $referer, PHP_URL_HOST );

		if ( ! $referer_host ) {
			$this->kill_connection( self::ERROR_REFERRER, 'Invalid referrer' );
		}

		$allowed_hosts = [
			'wppopupmaker.com',
			'upgrade.wppopupmaker.com',
		];

		if ( ! in_array( $referer_host, $allowed_hosts, true ) ) {
			$this->kill_connection( self::ERROR_REFERRER, 'Referrer doesn\'t match' );
		}
	}

	/**
	 * Verify the nonce.
	 *
	 * @deprecated 2.0.0 Don't use, it doesn't work as its a separate server making request.
	 *
	 * @return void
	 */
	public function verify_nonce() {
		$token = $this->get_access_token();
		$nonce = $this->get_request_nonce();

		if ( ! $nonce ) {
			$this->kill_connection( self::ERROR_NONCE, 'Missing nonce' );
		}

		if ( false === wp_verify_nonce( $nonce, $this->get_nonce_name( $token ) ) ) {
			$this->debug_log( 'Nonce mismatch: ' . $nonce, 'DEBUG' );
			$this->debug_log( 'Nonce Name: ' . $this->get_nonce_name( $token ) );
			$this->kill_connection( self::ERROR_NONCE, 'Invalid nonce' );
		}
	}

	/**
	 * Verify the authentication token.
	 *
	 * @return void
	 */
	public function verify_authentication() {
		// Get token from header Bearer.
		$token      = $this->get_access_token();
		$auth_token = $this->get_request_token();

		if ( ! $token || ! $auth_token ) {
			$this->kill_connection( self::ERROR_AUTHENTICATION, 'Missing authentication' );
		}

		// Verify hashes match.
		if ( ! hash_equals( $token, $auth_token ) ) {
			$this->kill_connection( self::ERROR_AUTHENTICATION, 'Invalid authentication' );
		}
	}

	/**
	 * Generate signature hash.
	 *
	 * This must match the hash generated on the server.
	 *
	 * @see \PopupMakerUpgrader\App::generate_hash()
	 *
	 * @param array<string,mixed>|string $data Data to hash.
	 * @param string                     $token Token to hash with.
	 * @return string
	 */
	public function generate_hash( $data, $token ) {
		// Convert boolean values to their string representation.
		array_walk_recursive($data, function ( &$value ) {
			if ( is_bool( $value ) ) {
				$value = $value ? '1' : '0';
			}
		});

		if ( ! is_string( $data ) ) {
			// Sort the array before encoding it as JSON.
			ksort( $data );

			// Ignored due to wp_json_encode stripping slashes and breaking hashes.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			$data = json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		}

		// Generate the hash binary.
		$hash = hash_hmac( 'sha256', $data, $token, true );

		// Generate HMAC-SHA256 signature.

		// Encode the hash in base64 to make it URL safe.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return base64_encode( $hash );
	}

	/**
	 * Verify the signature of the requester.
	 *
	 * @return void
	 */
	public function verify_signature() {
		if ( ! isset( $_SERVER['HTTP_X_POPUPMAKER_SIGNATURE'] ) ) {
			$this->kill_connection( self::ERROR_SIGNATURE, 'Missing signature' );
		}

		// Verify the webhook signature.
		$signature = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_POPUPMAKER_SIGNATURE'] ) );

		// Get the request data for signature calculation.
		$request_data = json_decode( file_get_contents( 'php://input' ), true );

		// Fallback to $_POST if JSON body is empty (backwards compatibility).
		if ( empty( $request_data ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$request_data = $_POST;
		}

		// Calculate the expected signature.
		$expected_signature = $this->generate_hash( $request_data, $this->get_access_token() );

		// Compare the expected signature to the received signature.
		if ( ! hash_equals( $expected_signature, $signature ) ) {
			$this->kill_connection( self::ERROR_SIGNATURE, 'Invalid signature' );
		}
	}

	/**
	 * Validate the connection.
	 *
	 * @return void
	 */
	public function validate_connection() {
		// Validate connection security layers.
		$this->verify_user_agent();
		$this->verify_referrer();
		$this->verify_authentication();
		$this->verify_signature();
	}

	/**
	 * Verify the connection.
	 *
	 * @return void
	 */
	public function process_verify_connection() {
		$this->validate_connection();
		wp_send_json_success();
	}

	/**
	 * Get the webhook args.
	 *
	 * @return array{file:string,type:string,slug:string,force:boolean}
	 */
	public function get_webhook_args() {
		// First try to get data from JSON body (modern REST API approach)
		$json_data = json_decode( file_get_contents( 'php://input' ), true );

		if ( is_array( $json_data ) && ! empty( $json_data ) ) {
			$this->debug_log( 'Using JSON body data for webhook args', 'DEBUG' );
			$this->debug_log( 'JSON data: ' . wp_json_encode( $json_data, JSON_PRETTY_PRINT ), 'DEBUG' );

			$args = [
				'file'  => ! empty( $json_data['file'] ) ? esc_url_raw( $json_data['file'] ) : '',
				'type'  => ! empty( $json_data['type'] ) ? sanitize_text_field( $json_data['type'] ) : 'plugin',
				'slug'  => ! empty( $json_data['slug'] ) ? sanitize_text_field( $json_data['slug'] ) : '',
				'force' => ! empty( $json_data['force'] ) ? (bool) $json_data['force'] : false,
			];
		} else {
			// Fallback to $_REQUEST for backwards compatibility
			$this->debug_log( 'Using $_REQUEST data for webhook args', 'DEBUG' );
			$args = [
				// phpcs:disable WordPress.Security.NonceVerification.Recommended
				'file'  => ! empty( $_REQUEST['file'] ) ? esc_url_raw( wp_unslash( $_REQUEST['file'] ) ) : '',
				'type'  => ! empty( $_REQUEST['type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['type'] ) ) : 'plugin',
				'slug'  => ! empty( $_REQUEST['slug'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['slug'] ) ) : '',
				'force' => ! empty( $_REQUEST['force'] ) ? (bool) $_REQUEST['force'] : false,
				// phpcs:enable WordPress.Security.NonceVerification.Recommended
			];
		}

		$this->debug_log( 'Parsed webhook args: ' . wp_json_encode( $args, JSON_PRETTY_PRINT ), 'DEBUG' );
		$this->verify_webhook_args( $args );

		return $args;
	}

	/**
	 * Verify and return webhook args.
	 *
	 * @param array{file:string,type:string,slug:string,force:bool} $args The webhook args.
	 *
	 * @return void
	 */
	public function verify_webhook_args( $args ) {
		$file_url  = ! empty( $args['file'] ) ? $args['file'] : false;
		$file_type = ! empty( $args['type'] ) ? $args['type'] : false;
		$file_slug = ! empty( $args['slug'] ) ? $args['slug'] : false;
		$force     = ! empty( $args['force'] ) ? (bool) $args['force'] : false;

		if ( ! $file_url || ! $file_type || ! $file_slug ) {
			$this->kill_connection( self::ERROR_WEBHOOK_ARGS, 'Missing webhook args' );
		}

		if ( ! in_array( $file_type, [ 'plugin', 'theme' ], true ) ) {
			$this->kill_connection( self::ERROR_WEBHOOK_ARGS, 'Invalid webhook args' );
		}
	}

	/**
	 * Listen for incoming secure webhooks from the API server.
	 *
	 * @return void
	 */
	public function process_webhook() {
		// 1. Validate the connection is secure & from the API server.
		$this->validate_connection();

		$error = esc_html__( 'There was an error while installing an upgrade. Please download the plugin from wppopupmaker.com and install it manually.', 'popup-maker' );

		// 2. Get the webhook data.
		$args = $this->get_webhook_args();

		// 3. Validate license key.
		if ( ! plugin( 'license' )->is_license_active() ) {
			$this->debug_log( 'License not active', 'DEBUG' );
			wp_send_json_error( $error );
		}

		// Set the current screen to avoid undefined notices.
		set_current_screen( 'settings_page_popup-maker-settings' );

		// 4. Install the plugin.
		switch ( $args['type'] ) {
			case 'plugin':
				$this->install_plugin( $args );
				break;
		}

		// 5. Delete the token to prevent abuse. Doing so last means it should be possible to retry if something goes wrong.
		if ( ! $this->debug_mode_enabled() ) {
			$this->debug_log( 'Deleting token', 'DEBUG' );
			\delete_site_transient( self::TOKEN_OPTION_NAME );
		}
	}

	/**
	 * Install a plugin.
	 *
	 * @param array{file:string,type:string,slug:string,force:bool} $args The file args.
	 * @return void
	 */
	public function install_plugin( $args ) {
		$this->debug_log( 'Installing plugin...', 'DEBUG' );

		// If not forcing, and already active, return success.
		if ( ! $args['force'] && is_plugin_active( "{$args['slug']}/{$args['slug']}.php" ) ) {
			$this->debug_log( 'Plugin already installed & active.', 'DEBUG' );
			wp_send_json_success( esc_html__( 'Plugin installed & activated.', 'popup-maker' ) );
		}

		// Get the upgrader.
		$upgrader = $this->container->get( 'upgrader' );

		// Install the plugin. (if installed already, this will replace it using upgrade).
		$installed = $upgrader->install_plugin( $args['file'] );

		if ( ! is_wp_error( $installed ) ) {
			$this->debug_log( 'Plugin installed & activated successfully.', 'DEBUG' );
			wp_send_json_success( esc_html__( 'Plugin installed & activated.', 'popup-maker' ) );
		}

		switch ( $installed->get_error_code() ) {
			default:
				$error = $installed->get_error_message();
		}

		if ( empty( $error ) ) {
			$error = esc_html__( 'There was an error while installing an upgrade. Please download the plugin from wppopupmaker.com and install it manually.', 'popup-maker' );
		}

		$this->debug_log( 'Plugin install failed: ' . $error, 'DEBUG' );
		wp_send_json_error( $installed->get_error_message() );
	}

	/**
	 * Verify webhook request for REST API.
	 *
	 * Validates webhook requests coming through REST endpoints.
	 * This method performs the same security checks as the AJAX webhook
	 * but is designed for REST API integration.
	 *
	 * @param \WP_REST_Request $request REST request object.
	 * @return array{valid:bool,error:string|null}
	 */
	public function verify_webhook_request( $request ) {
		$this->debug_log( 'Verifying webhook request via REST API...', 'DEBUG' );

		try {
			// Validate the connection security.
			$this->validate_connection();

			// Validate license is active.
			if ( ! plugin( 'license' )->is_license_active() ) {
				$this->debug_log( 'License not active for webhook request', 'DEBUG' );
				return [
					'valid' => false,
					'error' => 'License not active',
				];
			}

			$this->debug_log( 'Webhook request verified successfully', 'DEBUG' );
			return [
				'valid' => true,
				'error' => null,
			];
		} catch ( \Exception $e ) {
			$this->debug_log( 'Webhook verification failed: ' . $e->getMessage(), 'ERROR' );
			return [
				'valid' => false,
				'error' => $e->getMessage(),
			];
		}
	}

	/**
	 * Generate webhook URL for REST endpoints.
	 *
	 * Creates properly formatted webhook URLs for REST API endpoints
	 * with appropriate namespace and security parameters.
	 *
	 * @param string $endpoint The REST endpoint (e.g., 'connect/verify', 'upgrade/install').
	 * @param array  $args     Additional query arguments.
	 * @return string The webhook URL.
	 */
	public function generate_webhook_url( $endpoint, $args = [] ) {
		// Generate security token.
		$token = $this->generate_token();
		$nonce = wp_create_nonce( $this->get_nonce_name( $token ) );

		// Base REST URL with our namespace.
		$base_url = rest_url( 'popup-maker/v2/' . ltrim( $endpoint, '/' ) );

		// Add security parameters.
		$default_args = [
			'token' => $token,
			'nonce' => $nonce,
		];

		$query_args = array_merge( $default_args, $args );

		$webhook_url = add_query_arg( $query_args, $base_url );

		$this->debug_log( 'Generated webhook URL: ' . $webhook_url );
		$this->debug_log( 'Token: ' . $token );
		$this->debug_log( 'Nonce: ' . $nonce );

		return $webhook_url;
	}
}
