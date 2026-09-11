<?php
/**
 * Tests for deferred legacy hook handlers.
 *
 * @package Popup_Maker
 */

/**
 * Verify legacy services keep their public hooks without eager class loading.
 */
class PUM_Deferred_Hooks_Test extends WP_UnitTestCase {

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_frontend_registers_legacy_hooks_without_loading_handlers() {
		$this->assertFalse( class_exists( 'PUM_Privacy', false ) );
		$this->assertFalse( class_exists( 'PUM_Utils_Alerts', false ) );
		$this->assertFalse( class_exists( 'PUM_Telemetry', false ) );

		$this->assertSame( 10, has_filter( 'wp_privacy_personal_data_exporters', [ 'PUM_Privacy', 'register_exporter' ] ) );
		$this->assertSame( 10, has_filter( 'wp_privacy_personal_data_erasers', [ 'PUM_Privacy', 'register_erasers' ] ) );
		$this->assertSame( 20, has_action( 'admin_init', [ 'PUM_Privacy', 'privacy_policy_content' ] ) );
		$this->assertSame( 10, has_action( 'pum_save_popup', [ 'PUM_Privacy', 'clear_cookie_list' ] ) );

		$this->assertSame( 10, has_action( 'admin_init', [ 'PUM_Utils_Alerts', 'hooks' ] ) );
		$this->assertSame( 10, has_action( 'admin_init', [ 'PUM_Utils_Alerts', 'php_handler' ] ) );
		$this->assertSame( 10, has_action( 'wp_ajax_pum_alerts_action', [ 'PUM_Utils_Alerts', 'ajax_handler' ] ) );
		$this->assertSame( 10, has_filter( 'pum_alert_list', [ 'PUM_Utils_Alerts', 'translation_request' ] ) );
		$this->assertSame( 999, has_action( 'admin_menu', [ 'PUM_Utils_Alerts', 'append_alert_count' ] ) );

		$this->assertSame( 10, has_action( 'pum_daily_scheduled_events', [ 'PUM_Telemetry', 'track_check' ] ) );
		$this->assertSame( 10, has_filter( 'pum_alert_list', [ 'PUM_Telemetry', 'optin_alert' ] ) );
		$this->assertSame( 10, has_action( 'pum_alert_dismissed', [ 'PUM_Telemetry', 'optin_alert_check' ] ) );
	}
}
