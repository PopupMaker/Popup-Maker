<?php
/**
 * Extension License Handler
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * License handler for Popup Maker
 *
 * This class should simplify the process of adding license information to new Popup Maker extensions.
 *
 * Note for wordpress.org admins. This is not called in the free hosted version and is simply used for hooking in addons to one update system rather than including it in each plugin.
 *
 * @version 1.2
 */
class PUM_Extension_License {

	/**
	 * EDD API URL.
	 *
	 * @var string
	 */
	const API_URL = 'https://wppopupmaker.com/edd-sl-api/';

	/**
	 * Plugin file.
	 *
	 * @var string
	 */
	private $file;

	/**
	 * License key.
	 *
	 * @var string
	 */
	private $license;

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	private $item_name;

	/**
	 * Plugin EDD item ID.
	 *
	 * @var int
	 */
	private $item_id;

	/**
	 * Plugin shortname.
	 *
	 * @var string
	 */
	private $item_shortname;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Plugin author.
	 *
	 * @var string
	 */
	private $author;

	/**
	 * API URL.
	 *
	 * @var string
	 */
	private $api_url = self::API_URL;

	/**
	 * Class constructor
	 *
	 * @param string $_file
	 * @param string $_item_name
	 * @param string $_version
	 * @param string $_author
	 * @param string $_optname
	 * @param string $_api_url
	 * @param int    $_item_id
	 */
	public function __construct( $_file, $_item_name, $_version, $_author, $_optname = null, $_api_url = null, $_item_id = null ) {
		$this->file      = $_file;
		$this->item_name = $_item_name;

		if ( is_numeric( $_item_id ) ) {
			$this->item_id = absint( $_item_id );
		}

		$this->item_shortname = 'popmake_' . preg_replace( '/[^a-zA-Z0-9_\s]/', '', str_replace( ' ', '_', strtolower( $this->item_name ) ) );
		$this->version        = $_version;
		$this->license        = $this->get_effective_license_key();
		$this->author         = $_author;
		$this->api_url        = is_null( $_api_url ) ? $this->api_url : $_api_url;

		/**
		 * Allows for backwards compatibility with old license options,
		 * i.e. if the plugins had license key fields previously, the license
		 * handler will automatically pick these up and use those in lieu of the
		 * user having to reactive their license.
		 */
		if ( ! empty( $_optname ) ) {
			$opt = PUM_Utils_Options::get( $_optname );

			if ( isset( $opt ) && empty( $this->license ) ) {
				$this->license = trim( $opt );
			}
		}

		// Setup hooks
		$this->includes();
		$this->hooks();
	}

	/**
	 * Get effective license key (Pro key if available, else extension-specific key).
	 *
	 * @return string
	 */
	private function get_effective_license_key() {
		// Check if Pro license is active.
		if ( $this->has_pro_license() ) {
			return $this->get_pro_license_key();
		}

		// Fallback to extension-specific key.
		return trim( PUM_Utils_Options::get( $this->item_shortname . '_license_key', '' ) );
	}

	/**
	 * Check if Pro/Pro+ license key exists (active or not).
	 *
	 * This prevents users from managing extension licenses when a Pro key
	 * is present, even if that Pro key is currently deactivated.
	 *
	 * @return bool
	 */
	private function has_pro_license() {
		if ( ! function_exists( '\PopupMaker\plugin' ) ) {
			return false;
		}

		try {
			$license_service = \PopupMaker\plugin()->get( 'license' );
			$license_key     = $license_service->get_license_key();
			// Check if Pro key exists and is not empty.
			return ! empty( $license_key );
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Get Pro license key.
	 *
	 * @return string
	 */
	private function get_pro_license_key() {
		if ( ! function_exists( '\PopupMaker\plugin' ) ) {
			return '';
		}

		try {
			$license_service = \PopupMaker\plugin()->get( 'license' );

			if ( method_exists( $license_service, 'get_api_license_key' ) ) {
				return $license_service->get_api_license_key();
			}

			return $license_service->get_license_key();
		} catch ( \Exception $e ) {
			return '';
		}
	}

	/**
	 * Resolve the license key used for updates and API calls.
	 *
	 * @return string
	 */
	private function get_license_for_updates() {
		if ( $this->has_pro_license() ) {
			return $this->get_pro_license_key();
		}

		return trim( PUM_Utils_Options::get( $this->item_shortname . '_license_key', '' ) );
	}

	/**
	 * Whether SSL should be verified for EDD API requests.
	 *
	 * @return bool
	 */
	private function should_verify_ssl() {
		return ! in_array( wp_get_environment_type(), [ 'local', 'development' ], true );
	}

	/**
	 * Get stored license status from the database.
	 *
	 * @return object|null
	 */
	private function get_license_status() {
		$license = get_option( $this->item_shortname . '_license_active' );

		return is_object( $license ) ? $license : null;
	}

	/**
	 * Whether the extension license is currently active.
	 *
	 * @return bool
	 */
	private function is_license_active() {
		$license = $this->get_license_status();

		return is_object( $license ) && ! empty( $license->success ) && 'valid' === $license->license;
	}

	/**
	 * Call the EDD licensing API.
	 *
	 * @param string      $action      EDD action name.
	 * @param string|null $license_key Optional license key override.
	 *
	 * @return object|null
	 *
	 * @throws \Exception When the HTTP request fails.
	 */
	private function api_call( $action, $license_key = null ) {
		$license_key = null !== $license_key ? $license_key : $this->get_license_for_updates();

		if ( empty( $license_key ) ) {
			return null;
		}

		$api_params = [
			'edd_action'  => $action,
			'license'     => $license_key,
			'url'         => home_url(),
			'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
		];

		if ( ! empty( $this->item_id ) ) {
			$api_params['item_id'] = $this->item_id;
		} else {
			$api_params['item_name'] = rawurlencode( $this->item_name );
		}

		$response = wp_remote_post(
			$this->api_url,
			[
				'timeout'   => 15,
				'sslverify' => $this->should_verify_ssl(),
				'body'      => $api_params,
			]
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.', 'popup-maker' );
			}

			throw new \Exception( esc_html( $message ) );
		}

		$license_status = json_decode( wp_remote_retrieve_body( $response ) );

		if ( empty( $license_status ) ) {
			return null;
		}

		return $license_status;
	}

	/**
	 * Persist license status and enrich with user-facing error messages.
	 *
	 * @param object|null $license_status License status from the API.
	 *
	 * @return bool
	 */
	private function update_license_status( $license_status ) {
		if ( empty( $license_status ) || ! is_object( $license_status ) ) {
			return false;
		}

		$previous_status = $this->get_license_status();

		if ( ! empty( $license_status->error ) ) {
			$messages = PUM_Licensing::get_status_messages(
				$license_status,
				$this->get_license_for_updates(),
				$this->item_name
			);

			if ( ! empty( $messages[0] ) ) {
				$license_status->error_message = $messages[0];
			}
		} elseif ( isset( $license_status->error_message ) ) {
			unset( $license_status->error_message );
		}

		$updated = update_option( $this->item_shortname . '_license_active', $license_status );

		if ( $updated ) {
			/**
			 * Fires when an extension license status is updated.
			 *
			 * @param object      $license_status  Current license status.
			 * @param object|null $previous_status Previous license status.
			 * @param string      $item_shortname  Extension license shortname.
			 */
			do_action( 'popup_maker_extension_license_status_updated', $license_status, $previous_status, $this->item_shortname );
		}

		return (bool) $updated;
	}

	/**
	 * Store a local API failure without clearing a previously valid license.
	 *
	 * @param string $message Error message.
	 *
	 * @return void
	 */
	private function store_api_error( $message ) {
		$this->update_license_status(
			(object) [
				'success'       => false,
				'license'       => 'invalid',
				'error'         => 'api_error',
				'error_message' => $message,
			]
		);
	}

	/**
	 * Resolve a submitted license key, including masked values.
	 *
	 * @param string $license Submitted license key.
	 *
	 * @return string
	 */
	private function resolve_submitted_license_key( $license ) {
		$license = trim( (string) $license );

		if ( '' === $license ) {
			return '';
		}

		if ( false !== strpos( $license, '*' ) ) {
			return trim( PUM_Utils_Options::get( $this->item_shortname . '_license_key', '' ) );
		}

		return $license;
	}

	/**
	 * Get Pro license tier (pro or pro_plus).
	 *
	 * @return string
	 */
	private function get_pro_license_tier() {
		if ( ! $this->has_pro_license() ) {
			return '';
		}

		try {
			$license_service = \PopupMaker\plugin()->get( 'license' );
			return $license_service->get_license_tier();
		} catch ( \Exception $e ) {
			return '';
		}
	}

	/**
	 * Include the updater class
	 *
	 * @access  private
	 * @return  void
	 */
	private function includes() {
	}

	/**
	 * Setup hooks
	 *
	 * @access  private
	 * @return  void
	 */
	private function hooks() {

		// Register settings
		add_filter( 'pum_settings_fields', [ $this, 'settings' ], 1 );

		// Activate license key on settings save
		add_action( 'admin_init', [ $this, 'activate_license' ] );

		// Deactivate license key
		add_action( 'admin_init', [ $this, 'deactivate_license' ] );

		// Check that license is valid once per week.
		add_action( 'pum_weekly_scheduled_events', [ $this, 'weekly_license_check' ] );

		// For testing license notices, uncomment this line to force checks on every page load
		// add_action( 'admin_init', array( $this, 'weekly_license_check' ) );

		// Updater
		add_action( 'admin_init', [ $this, 'auto_updater' ], 0 );

		// Display notices to admins
		// add_action( 'admin_notices', array( $this, 'notices' ) );

		// Display notices to admins
		add_filter( 'pum_alert_list', [ $this, 'alerts' ] );

		add_action( 'in_plugin_update_message-' . plugin_basename( $this->file ), [ $this, 'plugin_row_license_missing' ], 10, 2 );

		// Register plugins for beta support
		add_filter( 'pum_beta_enabled_extensions', [ $this, 'register_beta_support' ] );
	}

	/**
	 * Auto updater
	 *
	 * @access  private
	 * @return  void
	 */
	public function auto_updater() {
		// To support auto-updates, this needs to run during the wp_version_check cron job for privileged users.
		$doing_cron = defined( 'DOING_CRON' ) && DOING_CRON;
		if ( ! current_user_can( 'manage_options' ) && ! $doing_cron ) {
			return;
		}

		$args = [
			'version' => $this->version,
			'license' => $this->get_license_for_updates(),
			'item_id' => $this->item_id,
			'author'  => $this->author,
			'beta'    => PUM_Admin_Tools::extension_has_beta_support( $this->item_shortname ),
		];

		if ( ! empty( $this->item_id ) ) {
			$args['item_id'] = $this->item_id;
		} else {
			$args['item_name'] = $this->item_name;
		}

		// Setup the updater
		new PUM_Extension_Updater( $this->api_url, $this->file, $args );
	}


	/**
	 * Add license field to settings
	 *
	 * @access  public
	 *
	 * @param array $tabs
	 *
	 * @return  array
	 */
	public function settings( $tabs = [] ) {
		static $license_help_text = false;

		if ( ! $license_help_text && ! isset( $tabs['licenses']['main']['license_help_text'] ) ) {
			$license_help_text = true;

			$tabs['licenses']['main']['license_help_text'] = [
				'type'     => 'html',
				'content'  => '<p><strong>' . sprintf(
					/* translators: 1. opening link text, 2. closing link text */
					esc_html__( 'Enter your extension license keys here to receive updates for purchased extensions. If your license key has expired, please %1$srenew your license%2$s.', 'popup-maker' ),
					'<a href="https://wppopupmaker.com/docs/policies/license-renewal/?utm_medium=license-help-text&utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab" target="_blank">',
					'</a>'
				) . '</strong></p>',
				'priority' => 0,
			];
		}

		$tabs['licenses']['main'][ $this->item_shortname . '_license_key' ] = [
			'type'    => 'license_key',
			'label'   => esc_attr( $this->item_name ),
			'options' => [
				'is_valid_license_option' => $this->item_shortname . '_license_active',
				'activation_callback'     => [ $this, 'activate_license' ],
				'using_pro_license'       => $this->has_pro_license(),
				'pro_license_tier'        => $this->get_pro_license_tier(),
				'product_name'            => $this->item_name,
			],
		];

		return $tabs;
	}

	/**
	 * Activate the license key
	 *
	 * @access  public
	 * @return  void
	 */
	public function activate_license() {
		// Skip activation if using Pro license.
		if ( $this->has_pro_license() ) {
			return;
		}

		if ( ! isset( $_POST['pum_settings_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['pum_settings_nonce'] ) ), 'pum_settings_nonce' ) ) {
			return;
		}

		if ( ! isset( $_POST['pum_settings'] ) ) {
			return;
		}

		if ( ! isset( $_POST['pum_settings'][ $this->item_shortname . '_license_key' ] ) ) {
			return;
		}

		// Don't activate a key when deactivating a different key.
		if ( ! empty( $_POST['pum_license_deactivate'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( $this->is_license_active() ) {
			return;
		}

		$license = sanitize_text_field( wp_unslash( $_POST['pum_settings'][ $this->item_shortname . '_license_key' ] ) );

		if ( empty( $license ) && empty( $_POST['pum_license_activate'][ $this->item_shortname . '_license_key' ] ) ) {
			return;
		}

		$license = $this->resolve_submitted_license_key( $license );

		if ( empty( $license ) ) {
			return;
		}

		PUM_Utils_Options::update( $this->item_shortname . '_license_key', $license );
		$this->license = $license;

		try {
			$license_data = $this->api_call( 'activate_license', $license );

			if ( empty( $license_data ) ) {
				return;
			}

			set_site_transient( 'update_plugins', null );
			$this->update_license_status( $license_data );

			if ( $this->is_license_active() ) {
				/**
				 * Fires when an extension license is activated.
				 *
				 * @param object $license_data   License status data.
				 * @param string $item_shortname Extension license shortname.
				 */
				do_action( 'popup_maker_extension_license_activated', $license_data, $this->item_shortname );
			}
		} catch ( \Exception $e ) {
			$this->store_api_error( $e->getMessage() );
		}
	}

	/**
	 * Deactivate the license key
	 *
	 * @access  public
	 * @return  void
	 */
	public function deactivate_license() {
		// Skip deactivation if using Pro license.
		if ( $this->has_pro_license() ) {
			return;
		}

		if ( ! isset( $_POST['pum_settings_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['pum_settings_nonce'] ) ), 'pum_settings_nonce' ) ) {
			return;
		}

		if ( ! isset( $_POST['pum_settings'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_POST['pum_license_deactivate'][ $this->item_shortname . '_license_key' ] ) ) {
			return;
		}

		$license_data = null;
		$succeeded    = false;

		try {
			$license_data = $this->api_call( 'deactivate_license' );
			$succeeded    = is_object( $license_data ) && ! empty( $license_data->license ) && 'deactivated' === $license_data->license;
		} catch ( \Exception $e ) {
			$this->store_api_error( $e->getMessage() );
		}

		delete_option( $this->item_shortname . '_license_active' );

		/**
		 * Fires when an extension license is deactivated.
		 *
		 * @param object|null $license_data   License status data.
		 * @param bool        $succeeded      Whether deactivation succeeded.
		 * @param string      $item_shortname Extension license shortname.
		 */
		do_action( 'popup_maker_extension_license_deactivated', $license_data, $succeeded, $this->item_shortname );
	}


	/**
	 * Check if license key is valid once per week
	 *
	 * @access  public
	 * @since   2.5
	 * @return  void
	 */
	public function weekly_license_check() {
		// Skip weekly check if using Pro license.
		if ( $this->has_pro_license() ) {
			return;
		}

		// Simply checking existence.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['popmake_settings'] ) ) {
			return; // Don't fire when saving settings.
		}

		if ( empty( $this->get_license_for_updates() ) ) {
			return;
		}

		try {
			$license_data = $this->api_call( 'check_license' );

			if ( ! empty( $license_data ) ) {
				$this->update_license_status( $license_data );
			}
		} catch ( \Exception $e ) {
			// Preserve the last known good status when the remote check fails.
			unset( $e );
		}
	}

	/**
	 * Adds an alert to the Popup Maker notification area when the license is invalid, expired, or empty
	 *
	 * @param array $alerts The existing alerts from the pum_alert_list filter
	 * @return array Our modified array of alerts
	 */
	public function alerts( $alerts = [] ) {

		static $showed_invalid_message;

		// Pro license covers extension updates; skip extension-specific alerts.
		if ( $this->has_pro_license() ) {
			return $alerts;
		}

		// If user can't manage it, or we already showed this alert abort.
		if ( ! current_user_can( 'manage_options' ) || $showed_invalid_message ) {
			return $alerts;
		}

		// If this alert is already in the list of alerts, abort.
		foreach ( $alerts as $alert ) {
			if ( 'license_not_valid' === $alert['code'] ) {
				return $alerts;
			}
		}

		// If this license key is not empty, check if it's valid.
		$license_key = $this->get_license_for_updates();

		if ( ! empty( $license_key ) ) {
			$license = $this->get_license_status();

			if ( ! is_object( $license ) || 'valid' === $license->license ) {
				return $alerts;
			}
		}

		$showed_invalid_message = true;
		$licenses_url           = admin_url( 'edit.php?post_type=popup&page=pum-settings&tab=licenses' );
		$stored_license         = $this->get_license_status();

		if ( empty( $license_key ) ) {
			$alerts[] = [
				'code'        => 'license_not_valid',
				'message'     => sprintf(
					/* translators: 1. Extension name, 2. opening link text, 3. closing link text */
					__( '%1$s is missing a license key. You will not receive updates until a valid key is entered on the %2$sLicenses page%3$s.', 'popup-maker' ),
					esc_html( $this->item_name ),
					'<a href="' . esc_url( $licenses_url ) . '">',
					'</a>'
				),
				'type'        => 'error',
				'dismissible' => '4 weeks',
				'priority'    => 0,
			];
		} else {
			$error_message = is_object( $stored_license ) && ! empty( $stored_license->error_message )
				? $stored_license->error_message
				: sprintf(
					/* translators: 1. Extension name, 2. opening link text, 3. closing link text */
					__( '%1$s has an invalid or expired license key. Please visit the %2$sLicenses page%3$s to correct this.', 'popup-maker' ),
					esc_html( $this->item_name ),
					'<a href="' . esc_url( $licenses_url ) . '">',
					'</a>'
				);

			$alerts[] = [
				'code'        => 'license_not_valid',
				'message'     => $error_message,
				'type'        => 'error',
				'dismissible' => '4 weeks',
				'priority'    => 0,
			];
		}

		return $alerts;
	}

	/**
	 * Admin notices for errors
	 *
	 * @access  public
	 * @return  void
	 */
	public function notices() {

		static $showed_invalid_message;

		if ( empty( $this->license ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) || $showed_invalid_message ) {
			return;
		}

		$messages = [];

		$license = get_option( $this->item_shortname . '_license_active' );

		if ( is_object( $license ) && 'valid' !== $license->license ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( empty( $_GET['tab'] ) || 'licenses' !== $_GET['tab'] ) {
				$messages[] = sprintf(
					/* translators: 1. opening link text, 2. closing link text */
					esc_html__( 'You have invalid or expired license keys for Popup Maker. Please go to the %1$sLicenses page%2$s to correct this issue.', 'popup-maker' ),
					'<a href="' . admin_url( 'edit.php?post_type=popup&page=pum-settings&tab=licenses' ) . '">',
					'</a>'
				);

				$showed_invalid_message = true;
			}
		}

		if ( ! empty( $messages ) ) {
			foreach ( $messages as $message ) {
				echo '<div class="error">';
				echo '<p>' . wp_kses( $message, wp_kses_allowed_html( 'data' ) ) . '</p>';
				echo '</div>';
			}
		}
	}

	/**
	 * Displays message inline on plugin row that the license key is missing
	 */
	public function plugin_row_license_missing( $plugin_data, $version_info ) {

		static $showed_imissing_key_message;

		if ( $this->has_pro_license() ) {
			return;
		}

		$license = $this->get_license_status();

		if ( ( ! is_object( $license ) || 'valid' !== $license->license ) && empty( $showed_imissing_key_message[ $this->item_shortname ] ) ) {
			$message = is_object( $license ) && ! empty( $license->error_message )
				? wp_strip_all_tags( $license->error_message )
				: __( 'Enter valid license key for automatic updates.', 'popup-maker' );

			echo '&nbsp;<strong><a href="' . esc_url( admin_url( 'edit.php?post_type=popup&page=pum-settings&tab=licenses' ) ) . '">' . esc_html( $message ) . '</a></strong>';
			$showed_imissing_key_message[ $this->item_shortname ] = true;
		}
	}

	/**
	 * Adds this plugin to the beta page
	 *
	 * @access  public
	 *
	 * @param   array $products
	 *
	 * @return array
	 */
	public function register_beta_support( $products ) {
		$products[ $this->item_shortname ] = $this->item_name;

		return $products;
	}
}
