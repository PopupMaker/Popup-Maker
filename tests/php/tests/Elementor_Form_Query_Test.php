<?php
/**
 * Elementor form integration query tests.
 *
 * @package Popup_Maker
 */

require_once dirname( __DIR__ ) . '/fixtures/class-elementor-submissions-query.php';

/**
 * Verify Elementor form discovery avoids per-page title queries.
 */
class Elementor_Form_Query_Test extends WP_UnitTestCase {

	/**
	 * Source page IDs.
	 *
	 * @var int[]
	 */
	private $post_ids = [];

	/**
	 * Fixture submissions table.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Set up Elementor submission fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		global $wpdb;

		if ( ! class_exists( 'PUM_Integration_Form_Elementor' ) ) {
			require_once dirname( __DIR__, 3 ) . '/classes/Integration/Form/Elementor.php';
		}

		$this->table_name = $wpdb->prefix . 'pum_elementor_query_test';
		\ElementorPro\Modules\Forms\Submissions\Database\Query::$table_name = $this->table_name;

		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $this->table_name ) );
		$wpdb->query(
			$wpdb->prepare(
				'CREATE TABLE %i (id bigint unsigned NOT NULL AUTO_INCREMENT, form_name varchar(255) NOT NULL, element_id varchar(255) NOT NULL, post_id bigint unsigned NOT NULL, PRIMARY KEY (id))',
				$this->table_name
			)
		);

		$this->post_ids = self::factory()->post->create_many( 10 );

		for ( $index = 0; $index < 20; $index++ ) {
			$wpdb->insert(
				$this->table_name,
				[
					'form_name'  => 'Form ' . $index,
					'element_id' => 'element-' . $index,
					'post_id'    => $this->post_ids[ $index % 10 ],
				]
			);
		}
	}

	/**
	 * Remove Elementor submission fixtures.
	 */
	public function tearDown(): void {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $this->table_name ) );
		delete_transient( 'pum_elementor_forms' );
		wp_cache_delete( 'pum_elementor_forms', 'popup_maker' );

		parent::tearDown();
	}

	/**
	 * Source page titles are fetched by the submissions query.
	 */
	public function test_form_discovery_avoids_post_title_n_plus_one_queries() {
		global $wpdb;

		wp_cache_flush();
		$query_count = $wpdb->num_queries;
		$forms       = ( new PUM_Integration_Form_Elementor() )->get_forms( true );
		$used_queries = $wpdb->num_queries - $query_count;

		$this->assertCount( 20, $forms );
		$this->assertSame( get_the_title( $this->post_ids[0] ), $forms['element-0']['post_title'] );
		$this->assertLessThanOrEqual( 5, $used_queries );
	}
}
