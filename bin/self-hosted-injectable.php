<?php
/**
 * Self-hosted update system for Popup Maker core plugin.
 *
 * This file enables automatic updates for the core plugin from wppopupmaker.com.
 * Ensures you always have access to the latest version of the plugin, from a reliable source.
 * Provides an easy way to opt in and test beta releases.
 *
 * Injected into self-hosted versions downloaded outside wordpress.org.
 *
 * @package PopupMaker
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Self-hosted updater for Popup Maker core.
 */
class PUM_SelfHosted_Updater {

	/**
	 * Initialize the self-hosted updater.
	 */
	public function init() {
		// Hook into WordPress plugins_loaded to ensure core is available.
		add_action( 'plugins_loaded', [ $this, 'setup_updater' ], 1 );

		// Force telemetry enabled for self-hosted versions.
		add_action( 'plugins_loaded', [ $this, 'enable_telemetry' ], 1 );
	}

	/**
	 * Setup the updater system.
	 */
	public function setup_updater() {
		// Ensure we have the core plugin loaded.
		if ( ! function_exists( 'PopupMaker\\plugin' ) ) {
			return;
		}

		// Hook into WordPress init to setup updater after everything is loaded.
		add_action( 'init', [ $this, 'initiate_updater' ] );
	}

	/**
	 * Initiate the self-hosted updater.
	 */
	public function initiate_updater() {
		static $initiated = false;

		if ( $initiated ) {
			return;
		}

		$initiated = true;

		// Get the core plugin instance.
		$core = \PopupMaker\plugin();
		if ( ! $core ) {
			return;
		}

		// Get beta preference from core settings.
		$beta = false;
		if ( class_exists( '\PUM_Admin_Tools' ) ) {
			$beta = \PUM_Admin_Tools::extension_has_beta_support( 'popup-maker' );
		}

		// Check if Extension Updater is available (it should be in core).
		if ( ! class_exists( '\PUM_Extension_Updater' ) ) {
			return;
		}

		// Get plugin file path.
		$plugin_file = $core->get( 'file' );
		if ( ! $plugin_file ) {
			return;
		}

		// Get the license key from the constant if defined.
		$license_key = defined( 'POPUP_MAKER_FREE_LICENSE' ) ? POPUP_MAKER_FREE_LICENSE : '';

		// Initiate the updater for core plugin.
		new \PUM_Extension_Updater(
			'https://wppopupmaker.com/edd-sl/',
			$plugin_file,
			[
				'version'   => $core->get( 'version' ),
				'license'   => $license_key, // Use the free license key
				'item_name' => 'Popup Maker',
				'item_id'   => 482276, // EDD ID #482276
				'author'    => 'Daniel Iser',
				'beta'      => $beta,
			]
		);
	}

	/**
	 * Force telemetry enabled for self-hosted versions.
	 */
	public function enable_telemetry() {
		// Force telemetry enabled - maximum priority to override any user attempts.
		add_filter( 'popmake_get_option', [ $this, 'force_telemetry_enabled' ], PHP_INT_MAX, 3 );
		add_filter( 'pum_settings_fields', [ $this, 'filter_settings_fields' ] );
		add_filter( 'pum_alert_list', [ $this, 'disable_telemetry_alert' ] );
	}

	/**
	 * Force telemetry enabled when self-hosted version is active.
	 *
	 * @param mixed  $value The option value.
	 * @param string $key The option key.
	 * @param mixed  $default_value The default value.
	 *
	 * @return mixed
	 */
	public function force_telemetry_enabled( $value, $key, $default_value ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( 'telemetry' === $key ) {
			// Don't force telemetry on local development environments.
			if ( $this->is_local_environment() ) {
				return $value;
			}

			return true;
		}

		return $value;
	}

	/**
	 * Remove telemetry setting field from admin settings.
	 *
	 * @param array<string, array<string, array<string, array<string, mixed>>>> $fields Settings fields.
	 *
	 * @return array<string, array<string, array<string, array<string, mixed>>>>
	 */
	public function filter_settings_fields( $fields ) {
		if ( isset( $fields['general']['main']['telemetry'] ) ) {
			unset( $fields['general']['main']['telemetry'] );
		}

		return $fields;
	}

	/**
	 * Remove telemetry opt-in alert for self-hosted users.
	 *
	 * @param array<string, array{code?: string}> $alerts Alert list.
	 *
	 * @return array<string, array{code?: string}>
	 */
	public function disable_telemetry_alert( $alerts ) {
		foreach ( $alerts as $key => $alert ) {
			if ( isset( $alert['code'] ) && 'pum_telemetry_notice' === $alert['code'] ) {
				unset( $alerts[ $key ] );
			}
		}

		return $alerts;
	}

	/**
	 * Check if we're in a local development environment.
	 *
	 * @return bool
	 */
	private function is_local_environment() {
		// WordPress 5.5+ environment type check.
		if ( function_exists( 'wp_get_environment_type' ) ) {
			$env_type = wp_get_environment_type();
			if ( in_array( $env_type, [ 'local', 'development' ], true ) ) {
				return true;
			}
		}

		// Fallback to original telemetry localhost check.
		if ( class_exists( '\PUM_Telemetry' ) && \PUM_Telemetry::is_localhost() ) {
			return true;
		}

		return false;
	}
}

// Initialize the self-hosted updater.
$pum_self_hosted_updater = new PUM_SelfHosted_Updater();
$pum_self_hosted_updater->init();
