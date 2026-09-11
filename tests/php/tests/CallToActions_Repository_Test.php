<?php
/**
 * Call to action repository tests.
 *
 * @package Popup_Maker
 */

use function PopupMaker\plugin;

/**
 * Test query behavior in the call to action repository.
 */
class CallToActions_Repository_Test extends WP_UnitTestCase {

	/**
	 * CTA fixture ID.
	 *
	 * @var int
	 */
	private $cta_id;

	/**
	 * CTA fixture UUID.
	 *
	 * @var string
	 */
	private $uuid;

	/**
	 * Set up the CTA fixture.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->uuid   = 'repository-query-' . wp_generate_uuid4();
		$this->cta_id = wp_insert_post(
			[
				'post_type'   => 'pum_cta',
				'post_status' => 'publish',
				'post_title'  => 'Repository Query Test',
			]
		);

		update_post_meta( $this->cta_id, 'cta_uuid', $this->uuid );
		update_post_meta( $this->cta_id, 'cta_settings', [] );
		update_post_meta( $this->cta_id, 'data_version', 1 );
	}

	/**
	 * Remove the CTA fixture and lookup cache.
	 */
	public function tearDown(): void {
		wp_cache_delete( 'popup_maker_cta_id_by_uuid_' . $this->uuid, 'popup_maker_ctas' );
		wp_delete_post( $this->cta_id, true );

		parent::tearDown();
	}

	/**
	 * Test a cold UUID lookup skips the unused found-rows query.
	 */
	public function test_get_by_uuid_uses_three_queries() {
		global $wpdb;

		clean_post_cache( $this->cta_id );
		wp_cache_delete( 'popup_maker_cta_id_by_uuid_' . $this->uuid, 'popup_maker_ctas' );
		$query_count = $wpdb->num_queries;

		$cta = plugin( 'ctas' )->get_by_uuid( $this->uuid );

		$this->assertNotNull( $cta );
		$this->assertSame( $this->cta_id, $cta->ID );
		$this->assertSame( 3, $wpdb->num_queries - $query_count );
	}
}
