<?php
/**
 * Tests for Popup Maker telemetry notices.
 *
 * @package Popup_Maker
 */

/**
 * Verify telemetry campaign targeting and opt-in behavior.
 */
class PUM_Telemetry_Test extends WP_UnitTestCase {

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	private static $admin_id;

	/**
	 * Editor user ID.
	 *
	 * @var int
	 */
	private static $editor_id;

	/**
	 * Create shared user fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$admin_id  = $factory->user->create( [ 'role' => 'administrator' ] );
		self::$editor_id = $factory->user->create( [ 'role' => 'editor' ] );
	}

	/**
	 * Prepare an eligible telemetry prompt for each test.
	 */
	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( self::$admin_id );
		update_option( 'pum_installed_on', '2020-01-01 00:00:00' );
		pum_delete_option( 'telemetry' );
		delete_user_meta( self::$admin_id, '_pum_dismissed_alerts' );
	}

	/**
	 * Remove telemetry state after each test.
	 */
	public function tearDown(): void {
		pum_delete_option( 'telemetry' );
		delete_user_meta( self::$admin_id, '_pum_dismissed_alerts' );
		parent::tearDown();
	}

	/**
	 * The campaign explains the milestone and appears only in the panel.
	 */
	public function test_campaign_explains_the_milestone_and_benefits() {
		$alerts = PUM_Telemetry::optin_alert( [] );
		$alert  = $alerts[0];

		$this->assertCount( 1, $alerts );
		$this->assertSame( PUM_Telemetry::OPTIN_ALERT_CODE, $alert['code'] );
		$this->assertStringContainsString( '50 billion popup views', $alert['title'] );
		$this->assertStringContainsString( 'more accurate estimate', $alert['message'] );
		$this->assertStringContainsString( 'build even better ones', $alert['message'] );
		$this->assertSame( 'Count my popup views', $alert['actions'][0]['text'] );
		$this->assertArrayNotHasKey( 'display_inline', $alert );
		$this->assertTrue( PUM_Utils_Alerts::is_panel_eligible( $alert ) );
		$this->assertFalse( PUM_Utils_Alerts::is_inline_eligible( $alert ) );
	}

	/**
	 * The campaign is limited to administrators who have not enabled telemetry.
	 */
	public function test_campaign_targets_admins_with_telemetry_disabled() {
		$this->assertCount( 1, PUM_Telemetry::optin_alert( [] ) );

		pum_update_option( 'telemetry', true );
		$this->assertSame( [], PUM_Telemetry::optin_alert( [] ) );

		pum_delete_option( 'telemetry' );
		wp_set_current_user( self::$editor_id );
		$this->assertSame( [], PUM_Telemetry::optin_alert( [] ) );
	}

	/**
	 * The campaign action enables telemetry for an authorized administrator.
	 */
	public function test_campaign_action_enables_telemetry() {
		PUM_Telemetry::optin_alert_check( PUM_Telemetry::OPTIN_ALERT_CODE, 'pum_optin_check_allow' );

		$this->assertTrue( PUM_Telemetry::has_opted_in() );
		$this->assertSame( [], PUM_Telemetry::optin_alert( [] ) );
	}

	/**
	 * Dismissing the campaign prevents it from nagging the same user again.
	 */
	public function test_campaign_dismissal_is_permanent_for_current_user() {
		$this->assertTrue( PUM_Utils_Alerts::action_handler( PUM_Telemetry::OPTIN_ALERT_CODE, 'dismiss', '' ) );
		$this->assertTrue( PUM_Utils_Alerts::has_dismissed_alert( PUM_Telemetry::OPTIN_ALERT_CODE ) );
	}

	/**
	 * Dismissing the original prompt does not hide the new campaign.
	 */
	public function test_legacy_prompt_dismissal_does_not_hide_campaign() {
		$this->assertTrue( PUM_Utils_Alerts::action_handler( PUM_Telemetry::LEGACY_OPTIN_ALERT_CODE, 'dismiss', '' ) );

		$this->assertTrue( PUM_Utils_Alerts::has_dismissed_alert( PUM_Telemetry::LEGACY_OPTIN_ALERT_CODE ) );
		$this->assertFalse( PUM_Utils_Alerts::has_dismissed_alert( PUM_Telemetry::OPTIN_ALERT_CODE ) );
		$this->assertCount( 1, PUM_Telemetry::optin_alert( [] ) );
	}

	/**
	 * Stale links from the original prompt continue to enable telemetry.
	 */
	public function test_legacy_prompt_action_remains_compatible() {
		PUM_Telemetry::optin_alert_check( PUM_Telemetry::LEGACY_OPTIN_ALERT_CODE, 'pum_optin_check_allow' );

		$this->assertTrue( PUM_Telemetry::has_opted_in() );
	}

	/**
	 * Users without settings access cannot enable telemetry through the action.
	 */
	public function test_campaign_action_requires_settings_permission() {
		wp_set_current_user( self::$editor_id );

		PUM_Telemetry::optin_alert_check( PUM_Telemetry::OPTIN_ALERT_CODE, 'pum_optin_check_allow' );

		$this->assertFalse( PUM_Telemetry::has_opted_in() );
	}
}
