<?php
/**
 * Local legacy extension catalog tests.
 *
 * @package PopupMaker
 */

/**
 * Validates the bundled legacy-to-Pro compatibility catalog.
 */
class Legacy_Extension_Catalog_Test extends WP_UnitTestCase {

	/**
	 * @var \PopupMaker\Services\LegacyExtensionCatalog
	 */
	private $catalog;

	/** Set up the catalog. */
	public function setUp(): void {
		parent::setUp();
		$this->catalog = new \PopupMaker\Services\LegacyExtensionCatalog();
	}

	/** Old versions remain detectable without registering metadata. */
	public function test_bundled_catalog_contains_retired_pro_features() {
		$items = $this->catalog->get_bundled_items();

		$this->assertCount( 8, $items );
		$this->assertSame( 'Exit Intent', $items['exit-intent-popups']['feature_name'] );
		$this->assertContains(
			'popup-maker-terms-conditions-popups/popup-maker-terms-conditions-popups.php',
			$items['terms-conditions-popups']['plugin_basenames']
		);
		$this->assertContains(
			'pum-scheduling/pum-scheduling.php',
			$items['scheduling']['plugin_basenames']
		);
	}

	/** Newer local registration enhances rather than replaces compatibility. */
	public function test_local_metadata_registration_preserves_bundled_identifiers() {
		$filter = static function ( $items ) {
			$items['terms-conditions-popups'] = [
				'feature_name'       => 'Terms Acceptance',
				'plugin_basenames'   => [ 'custom-terms/custom-terms.php' ],
				'license_shortnames' => [ 'popmake_custom_terms' ],
			];

			return $items;
		};

		add_filter( 'popup_maker/legacy_extension_catalog', $filter );

		try {
			$item = $this->catalog->get_items()['terms-conditions-popups'];
			$this->assertSame( 'Terms Acceptance', $item['feature_name'] );
			$this->assertContains( 'custom-terms/custom-terms.php', $item['plugin_basenames'] );
			$this->assertContains(
				'popup-maker-terms-conditions-popups/popup-maker-terms-conditions-popups.php',
				$item['plugin_basenames']
			);
		} finally {
			remove_filter( 'popup_maker/legacy_extension_catalog', $filter );
		}
	}

	/** A broken callback cannot erase Core's backwards-compatibility catalog. */
	public function test_bundled_catalog_cannot_be_filtered_away() {
		$filter = static function () {
			return [];
		};
		add_filter( 'popup_maker/legacy_extension_catalog', $filter );

		try {
			$this->assertCount( 8, $this->catalog->get_items() );
		} finally {
			remove_filter( 'popup_maker/legacy_extension_catalog', $filter );
		}
	}

	/** Site and network activation are resolved from local WordPress state. */
	public function test_installed_context_aggregates_active_and_inactive_extensions() {
		$plugins = [
			'popup-maker-exit-intent-popups/popup-maker-exit-intent-popups.php' => [ 'Version' => '1.4.0' ],
			'popup-maker-terms-conditions-popups/popup-maker-terms-conditions-popups.php' => [ 'Version' => '1.1.2' ],
		];

		$items = $this->catalog->get_installed_items(
			$plugins,
			[],
			[ 'popup-maker-exit-intent-popups/popup-maker-exit-intent-popups.php' => 123 ],
			[],
			[]
		);

		$this->assertCount( 2, $items );
		$this->assertSame( 'exit-intent-popups', $items[0]['slug'] );
		$this->assertTrue( $items[0]['active'] );
		$this->assertTrue( $items[0]['network_active'] );
		$this->assertSame( 'terms-conditions-popups', $items[1]['slug'] );
		$this->assertFalse( $items[1]['active'] );
	}

	/** Stored key and status determine state without a remote check. */
	public function test_license_state_uses_only_injected_local_options() {
		$item = $this->catalog->get_items()['exit-intent-popups'];

		$this->assertSame(
			'valid',
			$this->catalog->get_license_state(
				$item,
				[ 'popmake_exit_intent_popups_license_key' => 'local-key' ],
				[
					'popmake_exit_intent_popups' => (object) [
						'success' => true,
						'license' => 'valid',
					],
				]
			)
		);

		$this->assertSame(
			'expired',
			$this->catalog->get_license_state(
				$item,
				[ 'popmake_exit_intent_popups_license_key' => 'local-key' ],
				[
					'popmake_exit_intent_popups' => [
						'success' => false,
						'license' => 'invalid',
						'error'   => 'expired',
					],
				]
			)
		);

		$this->assertSame( 'missing', $this->catalog->get_license_state( $item, [], [] ) );
	}
}
