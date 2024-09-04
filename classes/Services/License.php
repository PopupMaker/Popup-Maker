<?php
/**
 * License management service.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Services;

use PopupMaker\Base\Service;

defined( 'ABSPATH' ) || exit;

/**
 * License management.
 *
 * NOTE: For wordpress.org admins: This is only used if:
 * - The user explicitly entered a license key.
 *
 * @package PopupMaker
 */
class License extends Service {

	/**
	 * EDD API URL.
	 *
	 * @var string
	 */
	const API_URL = 'https://wppopupmaker.com/edd-sl-api/';

	/**
	 * Item ID.
	 *
	 * @var int
	 */
	const ID = 45;

	/**
	 * Option key.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'popup_maker_license';

	/**
	 * License data
	 *
	 * @var array<string,mixed>|null
	 */
	private $license_data;

	/**
	 * Initialize license management.
	 *
	 * @param \PopupMaker\Plugin\Core $container Plugin container.
	 */
	public function __construct( $container ) {
		parent::__construct( $container );

		$this->register_hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'init', [ $this, 'autoregister' ] );
		add_action( 'popup_maker_license_status_check', [ $this, 'refresh_license_status' ] );
		add_action( 'admin_init', [ $this, 'schedule_crons' ] );
	}

	/**
	 * Autoregister license.
	 *
	 * @return void
	 */
	public function autoregister() {
		$key = defined( '\POPUP_MAKER_LICENSE_KEY' ) && '' !== \POPUP_MAKER_LICENSE_KEY ? \POPUP_MAKER_LICENSE_KEY : false;

		if ( $key && '' === $this->get_license_key() ) {
			try {
				$this->activate_license( \POPUP_MAKER_LICENSE_KEY );
			// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			} catch ( \Exception $e ) {
				// Do nothing.
			}
		}
	}

	/**
	 * Schedule cron jobs.
	 *
	 * @return void
	 */
	public function schedule_crons() {
		if ( ! wp_next_scheduled( 'popup_maker_license_status_check' ) ) {
			wp_schedule_event( time(), 'daily', 'popup_maker_license_status_check' );
		}
	}

	/**
	 * Get license data.
	 *
	 * @return array{key:string|null,status:array<string,mixed>|null}
	 */
	public function get_license_data() {
		if ( ! isset( $this->license_data ) ) {
			$this->license_data = \get_option( self::OPTION_KEY, [
				'key'    => '',
				'status' => [
					'success'          => false, // bool.
					'license'          => 'invalid', // valid or invalid.
					'item_id'          => self::ID, // int or false.
					'item_name'        => '', // string.
					'license_limit'    => 1, // int. 0 = unlimited.
					'site_count'       => 0,
					'expires'          => '', // date or 'lifetime'.
					'activations_left' => 0, // int or 'unlimited'.
					'checksum'         => '', // string.
					'payment_id'       => 0, // int.
					'customer_name'    => '', // string.
					'customer_email'   => '', // string.
					'price_id'         => 0, // string or int.
					'error'            => null, // string. expired, missing, invalid, item_name_mismatch, no_activations_left etc.
				],
			] );
		}

		return $this->license_data;
	}

	/**
	 * Get license key.
	 *
	 * @return string
	 */
	public function get_license_key() {
		$license_data = $this->get_license_data();
		return ! empty( $license_data['key'] ) ? $license_data['key'] : '';
	}

	/**
	 * Get license status.
	 *
	 * @param bool $refresh Whether to refresh license status.
	 *
	 * @return array<string,mixed> Array of license status data.
	 */
	public function get_license_status( $refresh = false ) {
		if ( $refresh ) {
			$this->refresh_license_status();
		}

		$license_data = $this->get_license_data();

		$license_status = isset( $license_data['status'] ) ? $license_data['status'] : [];

		return $license_status;
	}

	/**
	 * Get license level.
	 *
	 * Only used in pro version.
	 *
	 * @return int Integer representing license level.
	 */
	public function get_license_level() {
		$license_status = $this->get_license_status();

		if ( empty( $license_status ) ) {
			return -1;
		}

		$price_id = isset( $license_status['price_id'] ) ? $license_status['price_id'] : null;

		switch ( $price_id ) {
			default:
				return -1;

			case false:
			case 0:
				return 0;

			case 1:
			case 2:
			case 3:
			case 4:
				return absint( $price_id );
		}
	}

	/**
	 * Update license data.
	 *
	 * @param array{key:string|null,status:array<string,mixed>|null} $license_data License data.
	 *
	 * @return bool
	 */
	public function udpate_license_data( $license_data ) {
		if ( \update_option( self::OPTION_KEY, $license_data ) ) {
			$this->license_data = $license_data;
			return true;
		}

		return false;
	}

	/**
	 * Update license key.
	 *
	 * @param string $key License key.
	 *
	 * @return void
	 */
	public function update_license_key( $key ) {
		$license_data = $this->get_license_data();

		$old_key = isset( $license_data['key'] ) ? $license_data['key'] : '';

		$license_data['key'] = trim( $key );

		$this->udpate_license_data( $license_data );

		/**
		 * Fires when license key is changed.
		 *
		 * @param string $key License key.
		 * @param string $old_key Old license key.
		 */
		\do_action( 'popup_maker_license_key_changed', $key, $old_key );
	}

	/**
	 * Update license status.
	 *
	 * @param array<string,mixed> $license_status License status data.
	 *
	 * @return void
	 */
	public function update_license_status( $license_status ) {
		$license_data = $this->get_license_data();

		$previous_status = isset( $license_data['status'] ) ? $license_data['status'] : [];

		if ( ! empty( $license_status['error'] ) ) {
			$license_status['error_message'] = $this->get_license_error_message( $license_status );
		}

		$license_data['status'] = $license_status;

		$this->udpate_license_data( $license_data );

		/**
		 * Fires when license status is updated.
		 *
		 * @param array $license_status License status data.
		 * @param array $previous_status Previous license status data.
		 */
		\do_action( 'popup_maker_license_status_updated', $license_status, $previous_status );
	}

	/**
	 * Get license expiration from license status data.
	 *
	 * @param bool $as_datetime Whether to return as DateTime object.
	 *
	 * @return \DateTime|false|null
	 */
	public function get_license_expiration( $as_datetime = false ) {
		$status = $this->get_license_status();

		$expiration = isset( $status['expires'] ) ? $status['expires'] : null;

		return $as_datetime ?
			\DateTime::createFromFormat( 'Y-m-d H:i:s', $expiration ) :
			$expiration;
	}

	/**
	 * Fetch license status from remote server.
	 * This is a blocking request.
	 *
	 * @return void
	 */
	public function refresh_license_status() {
		$key = $this->get_license_key();

		if ( empty( $key ) ) {
			return;
		}

		try {
			$status = $this->check_license();
		} catch ( \Exception $e ) {
			$status = [];
		}

		$this->update_license_status( $status );
	}

	/**
	 * Get license status from remote server.
	 *
	 * @return array<string,mixed> License status data.
	 *
	 * @throws \Exception If there is an error.
	 */
	private function check_license() {
		$api_params = [
			'edd_action'  => 'check_license',
			'license'     => $this->get_license_key(),
			'item_id'     => self::ID,
			'item_name'   => rawurlencode( 'Popup Maker Pro' ),
			'url'         => home_url(),
			'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
		];

		// Call the custom API.
		$response = wp_remote_post(
			self::API_URL,
			[
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params,
			]
		);

		if ( is_wp_error( $response ) ) {
			throw new \Exception( esc_html( $response->get_error_message() ) );
		}

		$license_status = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $license_status ) ) {
			return [];
		}

		return $license_status;
	}

	/**
	 * Activate license.
	 *
	 * @param string $key License key.
	 *
	 * @return array<string,mixed> License status data.
	 *
	 * @throws \Exception If there is an error.
	 */
	public function activate_license( $key = null ) {
		if ( ! empty( $key ) ) {
			$this->update_license_key( $key );
		} else {
			$key = $this->get_license_key();
		}

		$api_params = [
			'edd_action'  => 'activate_license',
			'license'     => $key,
			'item_id'     => self::ID,
			'url'         => home_url(),
			'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
		];

		// Call the custom API.
		$response = wp_remote_post(
			self::API_URL,
			[
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params,
			]
		);

		// Make sure the response came back okay.
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.', 'popup-maker' );
			}

			throw new \Exception( esc_html( $message ) );
		}

		$license_status = json_decode( wp_remote_retrieve_body( $response ), true );

		$this->update_license_status( $license_status );

		if ( $this->is_license_active() ) {
			if ( ! \get_option( 'popup_maker_pro_activation_date', false ) ) {
				\update_option( 'popup_maker_pro_activation_date', time() );
			}
		}

		return $this->get_license_status();
	}

	/**
	 * Deactivate license.
	 *
	 * @return array<string,mixed> License status data.
	 *
	 * @throws \Exception If there is an error.
	 */
	public function deactivate_license() {
		$api_params = [
			'edd_action'  => 'deactivate_license',
			'license'     => $this->get_license_key(),
			'item_id'     => self::ID,
			'item_name'   => rawurlencode( 'Popup Maker' ),
			'url'         => home_url(),
			'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
		];

		// Call the custom API.
		$response = wp_remote_post(
			self::API_URL,
			[
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params,
			]
		);

		// Make sure the response came back okay.
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.', 'popup-maker' );
			}

			throw new \Exception( esc_html( $message ) );
		}

		$license_status = json_decode( wp_remote_retrieve_body( $response ), true );

		$this->update_license_status( $license_status );

		return $license_status;
	}

	/**
	 * Convert license error to human readable message.
	 *
	 * @param array<string,mixed> $license_status License status data.
	 *
	 * @return string
	 */
	public function get_license_error_message( $license_status ) {
		switch ( $license_status['error'] ) {
			case 'expired':
				$message = sprintf(
					/* translators: the license key expiration date */
					__( 'Your license key expired on %s.', 'popup-maker' ),
					date_i18n( \get_option( 'date_format' ), strtotime( $license_status['expires'], time() ) )
				);
				break;

			case 'disabled':
			case 'revoked':
				$message = __( 'Your license key has been disabled.', 'popup-maker' );
				break;

			case 'missing':
					$message = __( 'Invalid license.', 'popup-maker' );
				break;

			case 'invalid':
			case 'site_inactive':
				$message = __( 'Your license is not active for this URL.', 'popup-maker' );
				break;

			case 'item_name_mismatch':
				$message = sprintf(
					/* translators: the plugin name */
					__( 'This appears to be an invalid license key for %s.', 'popup-maker' ),
					__( 'Popup Maker', 'popup-maker' )
				);
				break;

			case 'no_activations_left':
				$message = __( 'Your license key has reached its activation limit.', 'popup-maker' );
				break;

			default:
				$message = __( 'An error occurred, please try again.', 'popup-maker' );
				break;
		}

		return $message;
	}

	/**
	 * Remove license.
	 *
	 * @return void
	 */
	public function remove_license() {
		try {
			$deactivated = $this->deactivate_license();
		} catch ( \Exception $e ) {
			$deactivated = [];
		}

		if ( ! empty( $deactivated['license'] ) && 'active' !== $deactivated['license'] ) {
			\delete_option( self::OPTION_KEY );
		}
	}

	/**
	 * Check if license is active.
	 *
	 * @return bool
	 */
	public function is_license_active() {
		$license_status = $this->get_license_status();

		if ( empty( $license_status ) ) {
			return false;
		}

		return 'valid' === $license_status['license'];
	}
}
