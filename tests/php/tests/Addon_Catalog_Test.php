<?php
/**
 * Static add-on catalog tests.
 *
 * @package PopupMaker
 * @subpackage Tests
 */

/**
 * Validates the local WordPress.org catalog boundary.
 */
class Addon_Catalog_Test extends WP_UnitTestCase {

	/**
	 * Catalog service under test.
	 *
	 * @var \PopupMaker\Services\AddonCatalog
	 */
	private $catalog;

	/**
	 * Set up the catalog service.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->catalog = new \PopupMaker\Services\AddonCatalog();
	}

	/**
	 * Product copy and public links are bundled rather than remotely fetched.
	 */
	public function test_catalog_contains_complete_static_product_copy() {
		$items = $this->catalog->get_items();

		$this->assertCount( 12, $items );
		$this->assertArrayNotHasKey( 'popup-maker-edd-pro', $items );
		$this->assertArrayNotHasKey( 'popup-maker-woocommerce-pro', $items );

		foreach ( $items as $item ) {
			$this->assertFalse( 0 === strpos( $item['name'], 'Popup Maker ' ) );
		}
		$this->assertArrayHasKey( 'popup-maker-ecommerce-popups', $items );
		$this->assertArrayHasKey( 'popup-maker-lms-popups', $items );

		foreach ( $items as $slug => $item ) {
			$this->assertSame( $slug, sanitize_key( $slug ) );
			$this->assertNotEmpty( $item['name'] );
			$this->assertNotEmpty( $item['description'] );
			$this->assertNotEmpty( $item['longDescription'] );
			$this->assertCount( 3, $item['features'] );
			$this->assertStringStartsWith( 'https://wppopupmaker.com/', $item['url'] );
		}
	}

	/**
	 * Only exact current or explicitly listed legacy basenames are detected.
	 */
	public function test_installed_detection_uses_exact_allowlisted_basenames() {
		$item = $this->catalog->get_item( 'popup-maker-aweber-integration' );

		$this->assertSame(
			'popup-maker-aweber-integration/pum-aweber-integration.php',
			$this->catalog->find_installed_basename(
				$item,
				[
					'popup-maker-aweber-integration/pum-aweber-integration.php' => [ 'Version' => '1.0.0' ],
				]
			)
		);

		$this->assertSame(
			'pum-aweber-integration/pum-aweber-integration.php',
			$this->catalog->find_installed_basename(
				$item,
				[
					'pum-aweber-integration/pum-aweber-integration.php' => [ 'Version' => '0.9.0' ],
				]
			)
		);

		$this->assertSame(
			'',
			$this->catalog->find_installed_basename(
				$item,
				[
					'unrelated/popup-maker-aweber-integration.php' => [ 'Version' => '9.9.9' ],
				]
			)
		);
	}

	/**
	 * Unknown or malformed slugs cannot resolve to lifecycle targets.
	 */
	public function test_unknown_catalog_items_fail_closed() {
		$this->assertNull( $this->catalog->get_item( 'not-in-the-catalog' ) );
		$this->assertNull( $this->catalog->get_item( '../popup-maker-videos' ) );
	}

	/**
	 * The retained submenu capability controls both the page and REST listing.
	 */
	public function test_extend_capability_uses_legacy_filter() {
		$filter = static function () {
			return 'edit_posts';
		};

		add_filter( 'popmake_admin_submenu_extensions_capability', $filter );

		try {
			$this->assertSame( 'edit_posts', PUM_Admin_Pages::get_submenu_capability( 'extensions' ) );
		} finally {
			remove_filter( 'popmake_admin_submenu_extensions_capability', $filter );
		}
	}
}
