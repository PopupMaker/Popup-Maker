<?php
/**
 * Local legacy extension notification tests.
 *
 * @package PopupMaker
 */

require_once dirname( __DIR__ ) . '/fixtures/class-pum-test-legacy-pro-upsell.php';

/**
 * Validates the aggregated Legacy to Pro notification state machine.
 */
class Legacy_Extension_Guidance_Test extends WP_UnitTestCase {

	/**
	 * @var \PopupMaker\Services\Notifications\LegacyExtensionGuidance
	 */
	private $guidance;

	/** Set up an administrator and provider. */
	public function setUp(): void {
		parent::setUp();
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		$this->guidance = new \PopupMaker\Services\Notifications\LegacyExtensionGuidance( \PopupMaker\plugin() );
	}

	/** Pro-active sites suppress sales guidance. */
	public function test_active_pro_suppresses_sales_guidance() {
		$this->assertNull( $this->guidance->build_alert( $this->extensions(), 'active', 'valid' ) );
		$this->assertNull( $this->guidance->build_alert( $this->extensions(), 'active', 'missing' ) );
	}

	/** An expired local Pro license gets a local management action, not a sale. */
	public function test_expired_active_pro_uses_local_license_action() {
		$alert = $this->guidance->build_alert( $this->extensions(), 'active', 'expired' );

		$this->assertSame( 'pm_legacy_extensions_to_pro_renew_v1', $alert['code'] );
		$this->assertStringContainsString( 'page=pum-settings', $alert['actions'][0]['href'] );
		$this->assertStringNotContainsString( 'utm_', $alert['actions'][0]['href'] );
	}

	/** Installed inactive Pro receives a nonce-protected activation action. */
	public function test_inactive_pro_uses_activation_action() {
		$alert = $this->guidance->build_alert( $this->extensions(), 'inactive', 'expired' );

		$this->assertSame( 'pm_legacy_extensions_to_pro_activate_v1', $alert['code'] );
		$this->assertStringContainsString( 'action=activate', html_entity_decode( $alert['actions'][0]['href'] ) );
		$this->assertStringContainsString( '_wpnonce=', html_entity_decode( $alert['actions'][0]['href'] ) );
	}

	/** Valid legacy licenses receive evergreen consolidation copy. */
	public function test_valid_legacy_license_uses_consolidation_message() {
		$extensions                     = $this->extensions();
		$extensions[0]['license_state'] = 'valid';
		$alert                          = $this->guidance->build_alert( $extensions, 'missing', 'missing' );

		$this->assertSame( 'pm_legacy_extensions_to_pro_consolidate_v1', $alert['code'] );
		$this->assertStringContainsString( 'Exit Intent', $alert['message'] );
		$this->assertStringContainsString( 'Terms &amp; Conditions', $alert['message'] );
		$this->assertStringNotContainsString( '$', $alert['message'] );
		$this->assertTrue( $alert['display_inline'] );
	}

	/** Expired/missing licenses receive included-in-Pro guidance. */
	public function test_missing_legacy_license_uses_included_message() {
		$alert = $this->guidance->build_alert( $this->extensions(), 'missing', 'missing' );

		$this->assertSame( 'pm_legacy_extensions_to_pro_included_v1', $alert['code'] );
		$this->assertSame( 72, $alert['priority'] );
		$this->assertSame( 'dismiss', $alert['actions'][1]['action'] );
		$this->assertArrayNotHasKey( 'expires', $alert['actions'][1] );
	}

	/** A fully deactivated installation is deliberately lower priority. */
	public function test_deactivated_extensions_are_lower_priority() {
		$extensions = $this->extensions();
		foreach ( $extensions as &$extension ) {
			$extension['active'] = false;
		}
		unset( $extension );

		$alert = $this->guidance->build_alert( $extensions, 'missing', 'missing' );
		$this->assertSame( 45, $alert['priority'] );
	}

	/** Core removes only the known shipped framework offer callbacks. */
	public function test_removes_shipped_framework_offer_callbacks() {
		$legacy    = new \PopupMaker\ExtensionFramework\Controllers\Admin\ProUpsell();
		$unrelated = static function ( $value = null ) {
			return $value;
		};

		add_action( 'admin_notices', [ $legacy, 'admin_notice' ] );
		add_action( 'admin_enqueue_scripts', [ $legacy, 'enqueue_dismiss_script' ] );
		add_filter( 'plugin_row_meta', [ $legacy, 'plugin_row_meta' ], 10, 2 );
		add_filter( 'pum_alert_list', [ $legacy, 'register_panel_notification' ] );
		add_filter( 'pum_alert_list', $unrelated );

		$this->guidance->remove_legacy_framework_offers();

		$this->assertFalse( has_action( 'admin_notices', [ $legacy, 'admin_notice' ] ) );
		$this->assertFalse( has_action( 'admin_enqueue_scripts', [ $legacy, 'enqueue_dismiss_script' ] ) );
		$this->assertFalse( has_filter( 'plugin_row_meta', [ $legacy, 'plugin_row_meta' ] ) );
		$this->assertFalse( has_filter( 'pum_alert_list', [ $legacy, 'register_panel_notification' ] ) );
		$this->assertSame( 10, has_filter( 'pum_alert_list', $unrelated ) );

		remove_filter( 'pum_alert_list', $unrelated );
	}

	/** Representative aggregate context. */
	private function extensions() {
		return [
			[
				'feature_name'  => 'Exit Intent',
				'active'        => true,
				'license_state' => 'expired',
			],
			[
				'feature_name'  => 'Terms & Conditions',
				'active'        => false,
				'license_state' => 'missing',
			],
		];
	}
}
