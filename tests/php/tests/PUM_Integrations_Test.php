<?php
/**
 * Tests for context-aware integration loading.
 *
 * @package Popup_Maker
 */

/**
 * Verify integration providers load only when available.
 */
class PUM_Integrations_Test extends WP_UnitTestCase {

	/**
	 * Active providers retain their form and builder integrations.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_active_provider_integrations_are_registered() {
		require_once dirname( __DIR__ ) . '/fixtures/class-fl-builder.php';

		PUM_Integrations::init();

		$this->assertInstanceOf( PUM_Integration_Form_BeaverBuilder::class, PUM_Integrations::$integrations['beaverbuilder'] );
		$this->assertInstanceOf( PUM_Integration_Builder_BeaverBuilder::class, PUM_Integrations::$integrations['beaverbuilder_button'] );
	}

	/**
	 * Providers that finish booting later still register their submission hooks.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_late_booting_provider_integration_is_registered() {
		define( 'ELEMENTOR_PRO_VERSION', 'test' );

		PUM_Integrations::init();

		$integration = PUM_Integrations::$integrations['elementor'];

		$this->assertInstanceOf( PUM_Integration_Form_Elementor::class, $integration );
		$this->assertSame( 10, has_action( 'elementor_pro/forms/new_record', [ $integration, 'on_success' ] ) );
	}

	/**
	 * Custom integrations registered through the public filter are preserved.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_filtered_integrations_are_registered() {
		$custom_integration = new class() extends PUM_Abstract_Integration {
			public $key = 'custom';

			public function label() {
				return 'Custom';
			}

			public function enabled() {
				return true;
			}
		};

		$register_custom = function ( $integrations ) use ( $custom_integration ) {
			$integrations['custom'] = $custom_integration;
			return $integrations;
		};

		add_filter( 'pum_integrations', $register_custom );
		PUM_Integrations::init();
		remove_filter( 'pum_integrations', $register_custom );

		$this->assertSame( $custom_integration, PUM_Integrations::$integrations['custom'] );
	}
}
