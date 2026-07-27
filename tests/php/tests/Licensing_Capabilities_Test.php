<?php
/**
 * Licensing capability contract tests.
 *
 * @package PopupMaker\Tests
 */

/**
 * Tests the transition Core capability contract.
 */
class Licensing_Capabilities_Test extends WP_UnitTestCase {

	/**
	 * Core must explicitly deny remote installation.
	 *
	 * @return void
	 */
	public function test_remote_installation_is_unavailable() {
		$capabilities = \PopupMaker\licensing_capabilities();

		$this->assertSame( 1, $capabilities['contract_version'] );
		$this->assertFalse( $capabilities['remote_installation'] );
		$this->assertTrue( $capabilities['legacy_core_license_service'] );
	}

	/**
	 * Released Pro can still resolve the service it requests during init.
	 *
	 * @return void
	 */
	public function test_legacy_license_service_remains_resolvable() {
		$plugin = \PopupMaker\plugin();

		$this->assertTrue( $plugin->offsetExists( 'license' ) );
		$this->assertInstanceOf( \PopupMaker\Services\License::class, $plugin->get( 'license' ) );
	}

	/**
	 * A provider can claim ownership through the documented filter.
	 *
	 * @return void
	 */
	public function test_pro_can_claim_license_ownership() {
		$claim = function ( $capabilities ) {
			$capabilities['license_provider'] = 'popup-maker-pro';
			$capabilities['license_ui_owner'] = 'popup-maker-pro';

			return $capabilities;
		};

		add_filter( 'popup_maker/licensing_capabilities', $claim );
		$capabilities = \PopupMaker\licensing_capabilities();

		$this->assertSame( 'popup-maker-pro', $capabilities['license_provider'] );
		$this->assertSame( 'popup-maker-pro', $capabilities['license_ui_owner'] );
		$this->assertFalse( \PopupMaker\owns_licensing_capability( 'license_ui_owner', 'popup-maker' ) );

		remove_filter( 'popup_maker/licensing_capabilities', $claim );
		$this->assertTrue( \PopupMaker\owns_licensing_capability( 'license_ui_owner', 'popup-maker' ) );
	}
}
