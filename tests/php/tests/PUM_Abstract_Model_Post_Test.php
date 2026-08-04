<?php
/**
 * Tests for PUM_Abstract_Model_Post compatibility behavior.
 *
 * @package Popup_Maker
 */

/**
 * Test the post model's extension property compatibility layer.
 */
class PUM_Abstract_Model_Post_Test extends WP_UnitTestCase {

	/**
	 * Create a popup post object with extension-added properties.
	 *
	 * @return WP_Post
	 */
	private function make_extended_post() {
		$post_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Extension Property Test',
			]
		);
		$post    = get_post( $post_id );

		$post->ec_order_status = 'completed';
		$post->valid           = false;

		return $post;
	}

	/**
	 * Extension-added WP_Post fields remain readable without dynamic properties.
	 */
	public function test_extension_post_property_is_available_without_dynamic_model_property() {
		$post         = $this->make_extended_post();
		$deprecations = [];

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Runtime deprecations are the behavior under test.
		set_error_handler(
			static function ( $severity, $message ) use ( &$deprecations ) {
				if ( E_DEPRECATED === $severity ) {
					$deprecations[] = $message;

					return true;
				}

				return false;
			}
		);

		try {
			$popup = new PUM_Model_Popup( $post );
		} finally {
			restore_error_handler();
		}

		$this->assertSame( [], $deprecations );
		$this->assertSame( 'completed', $popup->ec_order_status );
		$this->assertArrayNotHasKey( 'ec_order_status', get_object_vars( $popup ) );
		$this->assertSame( 'completed', $popup->to_array()['ec_order_status'] );
	}

	/**
	 * Extension property writes, isset(), and unset() use the compatibility bag.
	 */
	public function test_extension_property_write_isset_and_unset() {
		$popup = new PUM_Model_Popup( $this->make_extended_post() );

		$popup->extension_state = 'active';

		$this->assertSame( 'active', $popup->extension_state );
		$this->assertTrue( isset( $popup->extension_state ) );

		unset( $popup->extension_state );

		$this->assertFalse( isset( $popup->extension_state ) );
		$this->assertInstanceOf( WP_Error::class, $popup->extension_state );
	}

	/**
	 * Extension property arrays support indirect mutations.
	 */
	public function test_extension_property_supports_indirect_array_mutation() {
		$popup = new PUM_Model_Popup( $this->make_extended_post() );

		$popup->extension_state   = [];
		$popup->extension_state[] = 'active';

		$this->assertSame( [ 'active' ], $popup->extension_state );
	}

	/**
	 * Extension properties take precedence over model getters with the same name.
	 */
	public function test_extension_property_takes_precedence_over_matching_getter() {
		$popup          = new PUM_Model_Popup( $this->make_extended_post() );
		$custom_cookies = [ [ 'name' => 'extension-cookie' ] ];

		$popup->cookies = $custom_cookies;

		$this->assertSame( $custom_cookies, $popup->cookies );
	}

	/**
	 * Extension fields cannot overwrite protected model state.
	 */
	public function test_extension_property_cannot_overwrite_protected_model_state() {
		$popup = new PUM_Model_Popup( $this->make_extended_post() );

		$this->assertTrue( $popup->is_valid() );

		$popup->valid = false;

		$this->assertTrue( $popup->is_valid() );
		$this->assertFalse( $popup->valid );
		$this->assertFalse( $popup->to_array()['valid'] );
	}

	/**
	 * Extension data hydrates public properties declared by child models.
	 */
	public function test_extension_data_hydrates_declared_public_model_property() {
		$post       = $this->make_extended_post();
		$post->mock = true;

		$popup = new PUM_Model_Popup( $post );

		$this->assertTrue( $popup->mock );
		$this->assertFalse( $popup->get_meta( 'extension_meta' ) );
	}

	/**
	 * Declared WP_Post fields and meta-backed magic reads remain unchanged.
	 */
	public function test_declared_post_fields_and_meta_reads_are_unchanged() {
		$post = $this->make_extended_post();
		update_post_meta( $post->ID, 'extension_meta', 'stored-value' );

		$popup = new PUM_Model_Popup( $post );

		$this->assertSame( $post->ID, $popup->ID );
		$this->assertSame( 'Extension Property Test', $popup->post_title );
		$this->assertSame( 'stored-value', $popup->extension_meta );
	}
}
