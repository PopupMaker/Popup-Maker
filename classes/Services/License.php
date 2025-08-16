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
 * Pro license management.
 *
 * NOTE: For wordpress.org admins: This is only used if:
 * - The user explicitly entered a Pro license key.
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
	const ID = 480187;

	/**
	 * Option key.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'popup_maker_license';

	/**
	 * License data
	 *
	 * @var array{key:string|null,status:array{success:bool,license:'invalid'|'valid',item_id:int|false,item_name:string,license_limit:int,site_count:int,expires:string,activations_left:int,checksum:string,payment_id:int,customer_name:string,customer_email:string,price_id:string|int,error?:'no_activations_left'|'license_not_activable'|'missing'|'invalid'|'expired'|'revoked'|'item_name_mismatch'|'site_inactive'|'no_activations_left'|string|null,error_message?:string}|null}|null
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
				$this->maybe_activate_license( $key );
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
			wp_schedule_event( time(), 'weekly', 'popup_maker_license_status_check' );
		}
	}

	/**
	 * Get license data.
	 *
	 * @return array{key:string|null,status:array{success:bool,license:'invalid'|'valid',item_id:int|false,item_name:string,license_limit:int,site_count:int,expires:string,activations_left:int,checksum:string,payment_id:int,customer_name:string,customer_email:string,price_id:string|int,error?:'no_activations_left'|'license_not_activable'|'missing'|'invalid'|'expired'|'revoked'|'item_name_mismatch'|'site_inactive'|'no_activations_left'|string|null,error_message?:string}|null}
	 */
	public function get_license_data() {
		if ( ! isset( $this->license_data ) ) {
			$this->license_data = \get_option( self::OPTION_KEY, [
				'key'    => '',
				'status' => null,
			] );
		}

		return $this->license_data;
	}

	/**
	 * Update license data.
	 *
	 * @param array{key:string|null,status:array{success:bool,license:'invalid'|'valid',item_id:int|false,item_name:string,license_limit:int,site_count:int,expires:string,activations_left:int,checksum:string,payment_id:int,customer_name:string,customer_email:string,price_id:string|int,error?:'no_activations_left'|'license_not_activable'|'missing'|'invalid'|'expired'|'revoked'|'item_name_mismatch'|'site_inactive'|'no_activations_left'|string|null,error_message?:string}|null} $license_data License data.
	 *
	 * @return bool
	 */
	private function update_license_data( array $license_data ): bool {
		if ( \update_option( self::OPTION_KEY, $license_data ) ) {
			$this->license_data = $license_data;

			return true;
		}

		return false;
	}

	/**
	 * Get license key.
	 *
	 * @uses \PopupMaker\Services\License::get_license_data() For source of truth.
	 *
	 * @return string
	 */
	public function get_license_key(): string {
		$license_data = $this->get_license_data();
		return ! empty( $license_data['key'] ) ? $license_data['key'] : '';
	}

	/**
	 * Get license status.
	 *
	 * @uses \PopupMaker\Services\License::get_license_data() For source of truth.
	 *
	 * @param bool $refresh Whether to refresh license status.
	 *
	 * @return array{success:bool,license:'invalid'|'valid',item_id:int|false,item_name:string,license_limit:int,site_count:int,expires:string,activations_left:int,checksum:string,payment_id:int,customer_name:string,customer_email:string,price_id:string|int,error?:'no_activations_left'|'license_not_activable'|'missing'|'invalid'|'expired'|'revoked'|'item_name_mismatch'|'site_inactive'|'no_activations_left'|string|null,error_message?:string}|null
	 */
	public function get_license_status_data( ?bool $refresh = false ): array|null {
		if ( $refresh ) {
			$this->refresh_license_status();
		}

		$license_data = $this->get_license_data();

		$license_status = isset( $license_data['status'] ) ? wp_parse_args( $license_data['status'], [
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
		] ) : null;

		return $license_status;
	}

	/**
	 * Get license status.
	 *
	 * @return 'empty'|'inactive'|'expired'|'error'|'valid'
	 */
	public function get_license_status(): string {
		$license_key = $this->get_license_key();
		$status_data = $this->get_license_status_data();

		if ( empty( $license_key ) || empty( $status_data ) ) {
			return 'empty';
		}

		$status = 'inactive';

		// activate_license 'invalid' on anything other than valid, so if there was an error capture it
		if ( false === $status_data['success'] ) {
			$error = isset( $status_data['error'] ) ? $status_data['error'] : $status;

			switch ( $error ) {
				case 'expired':
					$status = 'expired';
					break;
				case 'revoked':
				case 'missing':
				case 'invalid':
				case 'site_inactive':
				case 'item_name_mismatch':
				case 'no_activations_left':
				case 'license_not_activable':
				default:
					$status = 'error';
					break;
			}
		} elseif ( 'valid' === $status_data['license'] ) {
				$status = 'valid';
		} else {
			$status = 'deactivated';
		}

		return $status;
	}

	/**
	 * Get license level.
	 *
	 * Only used in pro version.
	 *
	 * @return int Integer representing license level.
	 */
	public function get_license_level(): int {
		$license_status = $this->get_license_status_data();

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
	 * Get license tier (pro or pro_plus).
	 *
	 * @return string 'pro' or 'pro_plus' based on license data.
	 */
	public function get_license_tier(): string {
		$license_status = $this->get_license_status_data();

		if ( empty( $license_status ) ) {
			return 'pro';
		}

		// Check if license_tier is explicitly set in the license data.
		if ( ! empty( $license_status['license_tier'] ) ) {
			return in_array( $license_status['license_tier'], [ 'pro', 'pro_plus' ], true ) 
				? $license_status['license_tier'] 
				: 'pro';
		}

		// Fallback to 'pro' if not specified.
		return 'pro';
	}

	/**
	 * Update license key.
	 *
	 * Side Effects:
	 * - Update License Data
	 * - Reset License Status
	 * - Fire action: popup_maker_license_key_changed
	 *
	 * @param string $key License key.
	 *
	 * @return bool
	 */
	private function update_license_key( string $key ): bool {
		$license_data = $this->get_license_data();

		$new_key = trim( $key );
		$old_key = isset( $license_data['key'] ) ? $license_data['key'] : '';

		if ( $old_key === $new_key ) {
			return false;
		}

		$updated = $this->update_license_data( [
			'key'    => $new_key,
			'status' => null,
		] );

		if ( $updated ) {
			/**
			 * Fires when license key is changed.
			 *
			 * @param string $key License key.
			 * @param string $old_key Old license key.
			 */
			\do_action( 'popup_maker_license_key_changed', $new_key, $old_key );
		}

		return $updated;
	}

	/**
	 * Update license status.
	 *
	 * @param array<string,mixed> $license_status License status data.
	 *
	 * @return bool
	 */
	private function update_license_status( array $license_status ): bool {
		$license_data = $this->get_license_data();

		$previous_status = isset( $license_data['status'] ) ? $license_data['status'] : [];

		if ( ! empty( $license_status['error'] ) ) {
			$license_status['error_message'] = $this->get_license_error_message( $license_status );
		} else {
			unset( $license_status['error_message'] );
			unset( $license_status['error'] );
		}

		$license_data['status'] = $license_status;

		if ( $this->update_license_data( $license_data ) ) {
			/**
			 * Fires when license status is updated.
			 *
			 * @param array $license_status License status data.
			 * @param array $previous_status Previous license status data.
			 */
			\do_action( 'popup_maker_license_status_updated', $license_status, $previous_status );

			return true;
		}

		return false;
	}

	/**
	 * Get license expiration from license status data.
	 *
	 * @param bool $as_datetime Whether to return as DateTime object.
	 *
	 * @return string|null|\DateTime
	 */
	public function get_license_expiration( ?bool $as_datetime = false ): \DateTime|string|null {
		$status_data = $this->get_license_status_data();

		if ( empty( $status_data ) ) {
			return null;
		}

		$expiration = isset( $status_data['expires'] ) ? $status_data['expires'] : null;

		return $as_datetime ?
			\DateTime::createFromFormat( 'Y-m-d H:i:s', $expiration ) :
			$expiration;
	}

	/**
	 * Fetch license status from remote server.
	 * This is a blocking request.
	 *
	 * @return bool
	 */
	public function refresh_license_status(): bool {
		$key = $this->get_license_key();

		if ( empty( $key ) ) {
			return false;
		}

		try {
			$status = $this->check_license_status();
		} catch ( \Exception $e ) {
			$status = null;
		}

		return $this->update_license_status( $status );
	}

	/**
	 * Call the API.
	 *
	 * @param string     $action The action to call.
	 * @param array|null $params The parameters to pass to the API.
	 *
	 * @return array|null
	 *
	 * @throws \Exception If there is an error.
	 */
	private function api_call( string $action, ?array $params = null ): array|null {
		$key = $this->get_license_key();

		if ( empty( $key ) ) {
			return null;
		}

		$api_params = [
			'edd_action'  => $action,
			'license'     => $key,
			'item_id'     => self::ID,
			'item_name'   => rawurlencode( 'Popup Maker Pro' ),
			'url'         => home_url(),
			'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
		];

		// Call the custom API.
		$response = wp_remote_post(
			self::API_URL,
			array_merge( $params ?? [], [
				'timeout'   => 15,
				'sslverify' => ! in_array( wp_get_environment_type(), [ 'local', 'development' ], true ),
				'body'      => $api_params,
			] )
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

		if ( empty( $license_status ) ) {
			return null;
		}

		// No structure validation Fix: Validate expected fields exist and types match

		return $license_status;
	}

	/**
	 * Get license status from remote server.
	 *
	 * Side Effects:
	 * - API Call: Check License Status
	 *
	 * @return array{success:bool,license:'invalid'|'valid',item_id:int|false,item_name:string,license_limit:int,site_count:int,expires:string,activations_left:int,checksum:string,payment_id:int,customer_name:string,customer_email:string,price_id:string|int,error?:'no_activations_left'|'license_not_activable'|'missing'|'invalid'|'expired'|'revoked'|'item_name_mismatch'|'site_inactive'|'no_activations_left'|string|null,error_message?:string}|null
	 *
	 * @throws \Exception If there is an error.
	 */
	private function check_license_status(): array|null {
		return $this->api_call( 'check_license' );
	}

	/**
	 * Activate license.
	 *
	 * Side Effects:
	 * - API Call: Check License Status
	 * - Update Pro Activation Date
	 * - Update License Status
	 *
	 * @return bool
	 *
	 * @throws \Exception If there is an error.
	 */
	public function activate_license(): bool {
		$license_status = $this->api_call( 'activate_license' );

		// Bail early if the license status is empty.
		if ( empty( $license_status ) ) {
			return false;
		}

		$this->update_license_status( $license_status );

		if ( $this->is_license_active() ) {
			if ( ! \get_option( 'popup_maker_pro_activation_date', false ) ) {
				\update_option( 'popup_maker_pro_activation_date', time() );
			}
		}

		/**
		 * Fires when license is activated.
		 *
		 * @param array $license_status License status data.
		 */
		\do_action( 'popup_maker_license_activated', $license_status );

		return $this->is_license_active();
	}

	/**
	 * Deactivate license.
	 *
	 * Side Effects:
	 * - API Call: Deactivate License
	 * - Update License Status
	 *
	 * @return bool
	 *
	 * @throws \Exception If there is an error.
	 */
	public function deactivate_license(): bool {
		$license_status = $this->api_call( 'deactivate_license' );

		$this->update_license_status( $license_status );

		if ( empty( $license_status ) ) {
			return false;
		}

		$succeeded = 'deactivated' === $license_status['license'];

		/**
		 * Fires when license is activated.
		 *
		 * @param array $license_status License status data.
		 * @param bool  $succeeded      Whether the license was deactivated successfully.
		 */
		\do_action( 'popup_maker_license_deactivated', $license_status, $succeeded );

		return $succeeded;
	}

	/**
	 * Convert license error to human readable message.
	 *
	 * @param array{success:bool,license:'invalid'|'valid',item_id:int|false,item_name:string,license_limit:int,site_count:int,expires:string,activations_left:int,checksum:string,payment_id:int,customer_name:string,customer_email:string,price_id:string|int,error?:'no_activations_left'|'license_not_activable'|'missing'|'invalid'|'expired'|'revoked'|'item_name_mismatch'|'site_inactive'|'no_activations_left'|string|null,error_message?:string}|null $license_status License status data.
	 *
	 * @return string
	 */
	public function get_license_error_message( ?array $license_status = null ): string {
		if ( empty( $license_status ) ) {
			$license_status = $this->get_license_status_data();
		}

		if ( empty( $license_status['error'] ) ) {
			return '';
		}

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
	 * Caution: Destructive, this will remove the license key and status.
	 *
	 * @return bool
	 */
	public function remove_license(): bool {
		try {
			// Might be an invalid but used key, so we want to deactivate it. without checking if active.
			$this->deactivate_license();

			if ( \delete_option( self::OPTION_KEY ) ) {
				// Delete the old license key option & status as well.
				\PUM_Utils_Options::delete( 'popup_maker_pro_license_key' );
				delete_option( 'popup_maker_pro_license_active' );

				/**
				 * Fires when license is removed.
				 */
				\do_action( 'popup_maker_license_removed' );

				return true;
			}
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Do nothing.
		}

		return false;
	}

	/**
	 * Safely update license key.
	 *
	 * Side effect:
	 * - This will remove the license if the key is empty.
	 * - This will update the license key if its different from the current key.
	 *
	 * @param string $key License key.
	 *
	 * @return bool
	 */
	public function maybe_update_license_key( string $key ): bool {
		if ( empty( $key ) ) {
			$this->remove_license();
			return true;
		} else {
			// If the key is starred, get the original key.
			if ( strpos( $key, '*' ) !== false ) {
				$key = $this->get_license_key();
			}

			return $this->update_license_key( $key );
		}
	}

	/**
	 * Safely attempt to activate the license.
	 *
	 * Proper external handlers with error control.
	 *
	 * @param string $key License key.
	 *
	 * @return bool
	 */
	public function maybe_activate_license( ?string $key = null ): bool {
		try {
			// Update the license key separately to avoid race condition.
			if ( $key ) {
				$this->maybe_update_license_key( $key );
			}

			// Activate the license.
			return $this->activate_license();
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Check if license is active.
	 *
	 * @return bool
	 */
	public function is_license_active() {
		return 'valid' === $this->get_license_status();
	}

	/**
	 * Check if license is auto-activated via POPUP_MAKER_LICENSE_KEY constant.
	 *
	 * @return bool
	 */
	public function is_auto_activated() {
		$constant_key = defined( 'POPUP_MAKER_LICENSE_KEY' ) && ! empty( POPUP_MAKER_LICENSE_KEY ) ? POPUP_MAKER_LICENSE_KEY : null;
		$current_key  = $this->get_license_key();

		return $constant_key && $current_key === $constant_key && $this->is_license_active();
	}
}
