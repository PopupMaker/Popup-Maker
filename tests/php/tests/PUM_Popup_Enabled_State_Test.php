<?php
/**
 * Regression checks for the popup editor's enabled metadata.
 *
 * @package Popup_Maker
 */

/**
 * Keep native Custom Fields from competing with the popup toggle.
 */
class PUM_Popup_Enabled_State_Test extends WP_UnitTestCase {

	/**
	 * Only the popup editor's enabled flag receives extra protection.
	 *
	 * @dataProvider protection_cases
	 * @param string|null $post_type Current post type, or null without a post.
	 * @param string      $meta_key  Metadata key.
	 * @param string      $meta_type Metadata object type.
	 * @param bool        $expected Expected protection.
	 */
	public function test_protection_is_scoped_to_popup_enabled_meta( $post_type, $meta_key, $meta_type, $expected ) {
		global $post;
		$original_post = $post;
		$post          = null === $post_type ? null : new WP_Post( (object) [
			'ID'        => 1,
			'filter'    => 'raw',
			'post_type' => $post_type,
		] );

		try {
			$this->assertSame( $expected, is_protected_meta( $meta_key, $meta_type ) );
		} finally {
			$post = $original_post;
		}
	}

	/**
	 * Editor context and metadata combinations.
	 *
	 * @return array<string,array{string|null,string,string,bool}>
	 */
	public function protection_cases() {
		return [
			'popup enabled'           => [ 'popup', 'enabled', 'post', true ],
			'ordinary custom field'   => [ 'popup', 'custom_field', 'post', false ],
			'already protected field' => [ 'popup', '_private', 'post', true ],
			'another post type'       => [ 'post', 'enabled', 'post', false ],
			'another metadata type'   => [ 'popup', 'enabled', 'user', false ],
			'no current post'         => [ null, 'enabled', 'post', false ],
		];
	}

	/**
	 * Native Custom Fields excludes the toggle's metadata from form submission.
	 */
	public function test_native_custom_fields_does_not_render_enabled_row() {
		require_once ABSPATH . 'wp-admin/includes/template.php';
		global $post;
		$original_post = $post;
		$post          = new WP_Post( (object) [
			'ID'        => 1,
			'filter'    => 'raw',
			'post_type' => 'popup',
		] );
		$count         = 0;

		try {
			$row = _list_meta_row(
				[
					'meta_id'    => 123,
					'meta_key'   => 'enabled', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Metabox row fixture, not a query.
					'meta_value' => '0', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Metabox row fixture, not a query.
				],
				$count
			);
			$this->assertSame( '', $row );
			$this->assertSame( 0, $count );
		} finally {
			$post = $original_post;
		}
	}
}
