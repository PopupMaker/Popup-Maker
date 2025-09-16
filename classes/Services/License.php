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
	 * Settings key.
	 *
	 * @var string
	 */
	const SETTINGS_KEY = 'popup_maker_pro_license_key';

	/**
	 * Loss delay transient.
	 *
	 * @var string
	 */
	const DELAY_TRANSIENT = 'popup_maker_license_loss_delay';

	/**
	 * Loss check transient.
	 *
	 * @var string
	 */
	const CHECK_TRANSIENT = 'popup_maker_license_loss_check';

	/**
	 * License data
	 *
	 * @var array{key:string|null,status:array{success:bool,license:'invalid'|'valid',item_id:int|false,item_name:string,license_limit:int,site_count:int,expires:string,activations_left:int,checksum:string,payment_id:int,customer_name:string,customer_email:string,price_id:string|int,error?:'no_activations_left'|'license_not_activable'|'missing'|'invalid'|'expired'|'revoked'|'item_name_mismatch'|'site_inactive'|'no_activations_left'|string|null,error_message?:string}|null,auto_activation?:array{enabled:bool,first_activated:string|null,last_seen:string|null,key_missing_since:string|null,key_hash:string|null}}|null
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
		add_filter( 'pum_settings_editor_args', [ $this, 'filter_settings_editor_args' ] );
	}

	/**
	 * Autoregister license with simplified security approach.
	 *
	 * For auto-activated licenses:
	 * - Never stores the actual key in database (uses placeholder)
	 * - Simple detection and setup without complex monitoring
	 * - Self-healing: automatically corrects DB state if needed
	 * - Detects loss of auto-activation for debugging
	 *
	 * @return void
	 */
	public function autoregister() {
		$constant_key = defined( 'POPUP_MAKER_PRO_LICENSE' ) && '' !== POPUP_MAKER_PRO_LICENSE ? POPUP_MAKER_PRO_LICENSE : false;
		$license_data = $this->get_license_data();

		// Handle new auto-activation setup.
		if ( $constant_key ) {
			// Clear any existing delay transients when key is detected.
			$this->clear_transients();

			$this->ensure_auto_activation_setup( $constant_key );
		} else {
			$is_auto_activated = isset( $license_data['auto_activation']['enabled'] );
			$constant_lost     = ! empty( $license_data['auto_activation']['constant_lost_at'] );

			// If auto-activation is enabled and constant is not lost, handle loss.
			if ( $is_auto_activated && ! $constant_lost ) {
				$this->handle_auto_activation_loss_with_delay();
			}
		}
	}

	/**
	 * Clear transients.
	 *
	 * @return void
	 */
	private function clear_transients(): void {
		delete_transient( self::DELAY_TRANSIENT );
		delete_transient( self::CHECK_TRANSIENT );
	}

	/**
	 * Handle loss of auto-activation constant with transient-based delay.
	 *
	 * Uses simple transient system to delay loss detection by 5 minutes,
	 * preventing false positives during plugin updates and WordPress operations.
	 *
	 * @return void
	 */
	private function handle_auto_activation_loss_with_delay(): void {
		// If delay transient exists, we're still waiting - do nothing
		if ( get_transient( self::DELAY_TRANSIENT ) ) {
			return;
		}

		// If check transient doesn't exist, start the delay period (first detection)
		if ( ! get_transient( self::CHECK_TRANSIENT ) ) {
			set_transient( self::DELAY_TRANSIENT, true, 10 );
			set_transient( self::CHECK_TRANSIENT, true, 0 ); // Non-expiring transient
			return;
		}

		// Delay expired and we still don't have the constant - mark as lost
		$this->handle_auto_activation_loss();

		// Clean up the check transient
		delete_transient( self::CHECK_TRANSIENT );
	}

	/**
	 * Ensure auto-activation is properly set up with simplified approach.
	 *
	 * @param string $key The license key from constant.
	 *
	 * @return void
	 */
	private function ensure_auto_activation_setup( string $key ): void {
		$license_data = $this->get_license_data();
		$db_key       = isset( $license_data['key'] ) ? $license_data['key'] : '';

		// Self-healing: ensure DB has placeholder, not real key
		if ( '***AUTO***' !== $db_key || empty( $license_data['auto_activation']['enabled'] ) ) {
			$license_data = [
				'key'             => '***AUTO***',
				'status'          => $license_data['status'] ?? null, // Preserve existing status
				'auto_activation' => [
					'enabled' => true,
				],
			];

			$this->update_license_data( $license_data );

			// Activate using the constant key if not already active
			if ( ! $this->is_license_active() ) {
				try {
					$this->maybe_activate_license( $key );
				// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				} catch ( \Exception $e ) {
					// Do nothing on activation failure.
				}
			}
		} elseif ( ! empty( $license_data['auto_activation']['constant_lost_at'] ) ) {
			// Constant was restored - clear the loss timestamp and grace period
			unset( $license_data['auto_activation']['constant_lost_at'] );
			$this->update_license_data( $license_data );

			// Clear delay transients since constant is back
			$this->clear_transients();

			// Log restoration for debugging
			\PopupMaker\logging()->info( 'Auto-activation constant POPUP_MAKER_PRO_LICENSE was restored at ' . gmdate( 'Y-m-d H:i:s' ) );
		}
	}

	/**
	 * Handle loss of auto-activation constant.
	 *
	 * Tracks when auto-activation is lost for debugging and monitoring.
	 * Updates the license data with loss information and clears transients.
	 * Also clears license status to reflect the loss in UI.
	 *
	 * @return void
	 */
	private function handle_auto_activation_loss(): void {
		$license_data = $this->get_license_data();

		// Mark as lost and clear license status so UI reflects the change
		$license_data['auto_activation']['constant_lost_at'] = gmdate( 'Y-m-d H:i:s' );

		// Mark as lost and clear license status so UI reflects the change
		$license_data['auto_activation']['enabled'] = false;

		// Clear the key and status so UI shows inactive.
		$license_data['key']    = '';
		$license_data['status'] = null; // Clear cached status so UI shows inactive

		// Update the license data.
		$this->update_license_data( $license_data );

		// Clear the check transient since we've now marked it as lost
		$this->clear_transients();

		$current_time = gmdate( 'Y-m-d H:i:s' );

		// Log for debugging
		\PopupMaker\logging()->warning( 'Auto-activation constant POPUP_MAKER_PRO_LICENSE was removed at ' . $current_time );
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
	 * @return array{key:string|null,status:array{success:bool,license:'invalid'|'valid',item_id:int|false,item_name:string,license_limit:int,site_count:int,expires:string,activations_left:int,checksum:string,payment_id:int,customer_name:string,customer_email:string,price_id:string|int,error?:'no_activations_left'|'license_not_activable'|'missing'|'invalid'|'expired'|'revoked'|'item_name_mismatch'|'site_inactive'|'no_activations_left'|string|null,error_message?:string}|null,auto_activation?:array{enabled:bool,constant_lost_at?:string|null}}
	 */
	public function get_license_data() {
		if ( ! isset( $this->license_data ) ) {
			$this->license_data = \get_option( self::OPTION_KEY, [
				'key'             => '',
				'status'          => null,
				'auto_activation' => null,
			] );
		}

		return $this->license_data;
	}

	/**
	 * Update license data.
	 *
	 * @param array{key:string|null,status:array{success:bool,license:'invalid'|'valid',item_id:int|false,item_name:string,license_limit:int,site_count:int,expires:string,activations_left:int,checksum:string,payment_id:int,customer_name:string,customer_email:string,price_id:string|int,error?:'no_activations_left'|'license_not_activable'|'missing'|'invalid'|'expired'|'revoked'|'item_name_mismatch'|'site_inactive'|'no_activations_left'|string|null,error_message?:string}|null,auto_activation?:array{enabled:bool,constant_lost_at?:string|null}} $license_data License data.
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
	 * Get license key for display purposes.
	 *
	 * For auto-activated licenses, this always returns the constant value
	 * or placeholder - never exposes real keys from database.
	 *
	 * @uses \PopupMaker\Services\License::get_license_data() For source of truth.
	 *
	 * @return string
	 */
	public function get_license_key(): string {
		$license_data = $this->get_license_data();

		// Handle auto-activated licenses securely
		if ( ! empty( $license_data['auto_activation']['enabled'] ) ) {
			// For auto-activated, always return constant or placeholder
			return defined( 'POPUP_MAKER_PRO_LICENSE' ) && ! empty( POPUP_MAKER_PRO_LICENSE )
				? POPUP_MAKER_PRO_LICENSE
				: $license_data['key']; // Will be '***AUTO***' placeholder
		}

		// Return regular stored key
		return ! empty( $license_data['key'] ) ? $license_data['key'] : '';
	}

	/**
	 * Get raw license key for internal API calls only.
	 *
	 * This method should NEVER be used for display purposes.
	 * For auto-activated licenses, it always retrieves from the constant.
	 *
	 * @return string|null
	 */
	private function get_raw_license_key(): ?string {
		$license_data = $this->get_license_data();

		// For auto-activated licenses, always use the constant
		if ( ! empty( $license_data['auto_activation']['enabled'] ) ) {
			return defined( 'POPUP_MAKER_PRO_LICENSE' ) && ! empty( POPUP_MAKER_PRO_LICENSE ) ? POPUP_MAKER_PRO_LICENSE : null;
		}

		// For regular licenses, use stored key
		return ! empty( $license_data['key'] ) ? $license_data['key'] : null;
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
	public function get_license_status_data( ?bool $refresh = false ) {
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
			\PUM_Utils_Options::update( self::SETTINGS_KEY, $new_key );

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
	public function get_license_expiration( ?bool $as_datetime = false ) {
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
		$key = $this->get_raw_license_key();

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
	private function api_call( string $action, ?array $params = null ) {
		$key = $this->get_raw_license_key();

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
	private function check_license_status() {
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
				\PUM_Utils_Options::delete( self::SETTINGS_KEY );
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
	 * - Auto-activated licenses cannot be modified via this method.
	 *
	 * @param string $key License key.
	 *
	 * @return bool
	 */
	public function maybe_update_license_key( string $key ): bool {
		// Prevent modification of auto-activated licenses
		$license_data = $this->get_license_data();
		if ( ! empty( $license_data['auto_activation']['enabled'] ) ) {
			return false;
		}

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
	 * Check if license is auto-activated via POPUP_MAKER_PRO_LICENSE constant.
	 *
	 * Uses database flag for reliable detection even when constant is removed.
	 *
	 * @return bool
	 */
	public function is_auto_activated(): bool {
		$license_data = $this->get_license_data();
		return ! empty( $license_data['auto_activation']['enabled'] );
	}

	/**
	 * Get auto-activation info for debugging.
	 *
	 * @return array{enabled:bool,constant_present:bool,db_key:string,constant_lost_at:string|null,status:string}|null
	 */
	public function get_auto_activation_info(): ?array {
		$license_data = $this->get_license_data();

		if ( ! isset( $license_data['auto_activation']['enabled'] ) ) {
			return null;
		}

		$constant_key     = defined( 'POPUP_MAKER_PRO_LICENSE' ) && ! empty( POPUP_MAKER_PRO_LICENSE ) ? POPUP_MAKER_PRO_LICENSE : null;
		$constant_present = null !== $constant_key;
		$constant_lost_at = $license_data['auto_activation']['constant_lost_at'] ?? null;

		// Determine status
		$status = 'unknown';
		if ( $constant_present ) {
			$status = $constant_lost_at ? 'restored' : 'active';
		} elseif ( $constant_lost_at ) {
			$status = 'lost';
		} else {
			$status = 'missing';
		}

		return [
			'enabled'          => true,
			'constant_present' => $constant_present,
			'db_key'           => $license_data['key'] ?? '',
			'constant_lost_at' => $constant_lost_at,
			'status'           => $status,
		];
	}

	/**
	 * Generate connection information for Pro upgrade.
	 *
	 * This method creates the necessary connection data for upgrading to Pro
	 * when Pro is not already installed.
	 *
	 * @return array{url:string,back_url:string}|null Returns connection info or null if conditions not met.
	 */
	public function generate_connect_info(): ?array {
		// Only generate connect info if license is active and Pro is not installed.
		if ( ! $this->is_license_active() || $this->container->is_pro_installed() ) {
			return null;
		}

		$license_key = $this->get_license_key();
		if ( empty( $license_key ) ) {
			return null;
		}

		// Use the Connect service to generate connection info.
		$connect_service = $this->container->get( 'connect' );
		return $connect_service->get_connect_info( $license_key );
	}

	/**
	 * Validate license for Pro upgrade workflow.
	 *
	 * Determines if the current license state allows for Pro upgrade.
	 *
	 * @return array{valid:bool,can_upgrade:bool,reason:string}
	 */
	public function validate_for_upgrade(): array {
		$license_key      = $this->get_license_key();
		$license_status   = $this->get_license_status();
		$is_pro_installed = $this->container->is_pro_installed();

		// No license key provided.
		if ( empty( $license_key ) ) {
			return [
				'valid'       => false,
				'can_upgrade' => false,
				'reason'      => 'no_license_key',
			];
		}

		// License is not active.
		if ( 'valid' !== $license_status ) {
			return [
				'valid'       => false,
				'can_upgrade' => false,
				'reason'      => "license_{$license_status}",
			];
		}

		// Pro is already installed.
		if ( $is_pro_installed ) {
			return [
				'valid'       => true,
				'can_upgrade' => false,
				'reason'      => 'pro_already_installed',
			];
		}

		// License is valid and Pro is not installed - can upgrade.
		return [
			'valid'       => true,
			'can_upgrade' => true,
			'reason'      => 'ready_for_upgrade',
		];
	}

	/**
	 * Filter settings editor args.
	 *
	 * Add the license key value to the settings editor args, since its not actually stored in options array.
	 *
	 * @param mixed $args Settings editor args.
	 * @return mixed Settings editor args.
	 */
	public function filter_settings_editor_args( $args ) {
		$value = isset( $args['current_values'][ self::SETTINGS_KEY ] ) ? $args['current_values'][ self::SETTINGS_KEY ] : '';

		try {
			$license_service = \PopupMaker\plugin( 'license' );
			$license_status  = $license_service->get_license_status_data();

			// Get the comprehensive license status from service
			$actual_status = $license_service->get_license_status();

			// Get the actual license key (either from form input or service)
			// $license_key = ! empty( $value ) ? trim( $value ) : $license_service->get_license_key();
			$license_key = $license_service->get_license_key();

			$license_key = self::star_key( $license_key );

			// Use the mapping function to get proper status and classes
			$status_mapping = $this->map_license_status( $actual_status );

			// Get the license tier (pro or pro_plus).
			$license_tier = $license_service->get_license_tier();

			// Get Pro installation status for template conditionals.
			$is_pro_installed = $this->container->is_pro_installed();
			$is_pro_active    = $this->container->is_pro_active();
			$pro_version      = $this->container->get_pro_version();

			// Check for auto-activation status messages
			$messages = ! empty( $license_status['error_message'] ) ? [ $license_status['error_message'] ] : [];

			// Add auto-activation disconnect message if applicable
			$auto_info = $license_service->get_auto_activation_info();
			if ( ! empty( $auto_info['constant_lost_at'] ) ) {
				$lost_date  = $auto_info['constant_lost_at'];
				$messages[] = sprintf(
					/* translators: the date and time of the auto-activation disconnect */
					__( 'Auto-activation was disconnected on %s. The POPUP_MAKER_PRO_LICENSE constant was removed from your configuration.', 'popup-maker' ),
					date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $lost_date ) )
				);
			}

			// Pass anything we want to the template here.
			$args['current_values'][ self::SETTINGS_KEY ] = [
				'key'              => $license_key,
				'status'           => $status_mapping['status'],
				'messages'         => $messages,
				'expires'          => $license_service->get_license_expiration(),
				'classes'          => $status_mapping['classes'],
				'license_tier'     => $license_tier,
				'is_pro_installed' => $is_pro_installed,
				'is_pro_active'    => $is_pro_active,
				'pro_version'      => $pro_version,
			];
		} catch ( \Exception $e ) {
			$args['current_values'][ self::SETTINGS_KEY ] = [
				'key'              => $this->star_key( trim( $value ) ),
				'status'           => 'invalid',
				/* translators: %s is the error message */
				'messages'         => [ sprintf( __( 'Error loading license status: %s', 'popup-maker' ), $e->getMessage() ) ],
				'expires'          => '',
				'classes'          => 'pum-license-invalid',
				'license_tier'     => 'pro', // Default to pro on error.
				'is_pro_installed' => $this->container->is_pro_installed(),
				'is_pro_active'    => $this->container->is_pro_active(),
				'pro_version'      => $this->container->get_pro_version(),
			];
		}

		return $args;
	}

	/**
	 * Map license status to template data.
	 *
	 * @param string $status License status from service.
	 * @return array Template data for license status.
	 */
	public function map_license_status( string $status ): array {
		switch ( $status ) {
			case 'valid':
				return [
					'status'  => 'valid',
					'classes' => 'pum-license-valid',
				];
			case 'deactivated':
				return [
					'status'  => 'deactivated',
					'classes' => 'pum-license-deactivated',
				];
			case 'expired':
				return [
					'status'  => 'expired',
					'classes' => 'pum-license-expired',
				];
			case 'empty':
				return [
					'status'  => 'empty',
					'classes' => 'pum-license-empty',
				];
			case 'error':
			default:
				return [
					'status'  => 'invalid',
					'classes' => 'pum-license-invalid',
				];
		}
	}

		/**
		 * Star the key.
		 *
		 * @param string $key The key to star.
		 *
		 * @return string The starred key.
		 */
	public static function star_key( string $key ): string {
		if ( empty( $key ) ) {
			return '';
		}

		return substr( $key, 0, 3 ) . str_repeat( '*', max( 0, strlen( $key ) - 6 ) ) . substr( $key, -3 );
	}
}
