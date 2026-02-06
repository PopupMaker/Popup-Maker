<?php
/**
 * Tests for PopupMaker\Models\CallToAction.
 *
 * @package Popup_Maker
 */

use PopupMaker\Models\CallToAction;

/**
 * Test the CallToAction model.
 */
class PUM_Models_CallToAction_Test extends WP_UnitTestCase {

	/**
	 * Helper to create a CTA post and return the model instance.
	 *
	 * @param array $settings Optional CTA settings to store in meta.
	 * @param array $post_args Optional post creation overrides.
	 * @return CallToAction
	 */
	private function create_cta( $settings = [], $post_args = [] ) {
		$defaults = [
			'post_type'   => 'pum_cta',
			'post_title'  => 'Test CTA',
			'post_status' => 'publish',
			'post_name'   => 'test-cta',
		];

		$post_id = $this->factory->post->create( array_merge( $defaults, $post_args ) );

		if ( ! empty( $settings ) ) {
			update_post_meta( $post_id, 'cta_settings', $settings );
		}

		$post = get_post( $post_id );

		return new CallToAction( $post );
	}

	/**
	 * Test constructor sets ID from post.
	 */
	public function test_constructor_sets_id() {
		$cta = $this->create_cta();
		$this->assertGreaterThan( 0, $cta->ID );
	}

	/**
	 * Test constructor sets slug from post_name.
	 */
	public function test_constructor_sets_slug() {
		$cta = $this->create_cta( [], [ 'post_name' => 'my-slug' ] );
		$this->assertSame( 'my-slug', $cta->slug );
	}

	/**
	 * Test constructor sets title from post_title.
	 */
	public function test_constructor_sets_title() {
		$cta = $this->create_cta( [], [ 'post_title' => 'My CTA Title' ] );
		$this->assertSame( 'My CTA Title', $cta->title );
	}

	/**
	 * Test constructor sets status from post_status.
	 */
	public function test_constructor_sets_status() {
		$cta = $this->create_cta( [], [ 'post_status' => 'draft' ] );
		$this->assertSame( 'draft', $cta->status );
	}

	/**
	 * Test constructor loads settings from post meta.
	 */
	public function test_constructor_loads_settings_from_meta() {
		$settings = [ 'type' => 'redirect', 'url' => 'https://example.com' ];
		$cta      = $this->create_cta( $settings );

		$this->assertSame( $settings, $cta->get_settings() );
	}

	/**
	 * Test constructor uses defaults when no settings in meta.
	 */
	public function test_constructor_uses_default_settings() {
		$cta = $this->create_cta();

		$settings = $cta->get_settings();
		$this->assertIsArray( $settings );
		// Default settings should have a 'type' key.
		$this->assertArrayHasKey( 'type', $settings );
		$this->assertSame( 'link', $settings['type'] );
	}

	/**
	 * Test constructor sets data_version meta.
	 */
	public function test_constructor_sets_data_version() {
		$cta = $this->create_cta();

		$version = get_post_meta( $cta->ID, 'data_version', true );
		$this->assertEquals( CallToAction::MODEL_VERSION, $version );
	}

	/**
	 * Test get_settings returns array.
	 */
	public function test_get_settings_returns_array() {
		$cta = $this->create_cta( [ 'type' => 'link', 'url' => 'https://example.com' ] );
		$this->assertIsArray( $cta->get_settings() );
	}

	/**
	 * Test get_setting retrieves a direct key.
	 */
	public function test_get_setting_direct_key() {
		$cta = $this->create_cta( [ 'type' => 'redirect' ] );
		$this->assertSame( 'redirect', $cta->get_setting( 'type' ) );
	}

	/**
	 * Test get_setting returns default for missing key.
	 */
	public function test_get_setting_returns_default() {
		$cta = $this->create_cta( [ 'type' => 'link' ] );
		$this->assertSame( 'fallback', $cta->get_setting( 'nonexistent', 'fallback' ) );
	}

	/**
	 * Test get_setting returns false as default when no default given.
	 */
	public function test_get_setting_default_is_false() {
		$cta = $this->create_cta( [ 'type' => 'link' ] );
		$this->assertFalse( $cta->get_setting( 'nope' ) );
	}

	/**
	 * Test get_setting converts snake_case to camelCase lookup.
	 */
	public function test_get_setting_snake_case_to_camel() {
		$cta = $this->create_cta( [ 'buttonColor' => '#ff0000' ] );
		$this->assertSame( '#ff0000', $cta->get_setting( 'button_color' ) );
	}

	/**
	 * Test get_setting applies filter.
	 */
	public function test_get_setting_applies_filter() {
		$cta = $this->create_cta( [ 'type' => 'link' ] );

		add_filter( 'popup_maker/get_call_to_action_setting', function ( $value, $key ) {
			if ( 'type' === $key ) {
				return 'filtered_type';
			}
			return $value;
		}, 10, 2 );

		$this->assertSame( 'filtered_type', $cta->get_setting( 'type' ) );

		remove_all_filters( 'popup_maker/get_call_to_action_setting' );
	}

	/**
	 * Test get_uuid returns a non-empty string.
	 */
	public function test_get_uuid_returns_string() {
		$cta  = $this->create_cta();
		$uuid = $cta->get_uuid();

		$this->assertIsString( $uuid );
		$this->assertNotEmpty( $uuid );
	}

	/**
	 * Test get_uuid stores the UUID in post meta.
	 */
	public function test_get_uuid_persists_in_meta() {
		$cta  = $this->create_cta();
		$uuid = $cta->get_uuid();

		$stored = get_post_meta( $cta->ID, 'cta_uuid', true );
		$this->assertSame( $uuid, $stored );
	}

	/**
	 * Test get_uuid returns cached value on second call.
	 */
	public function test_get_uuid_is_cached() {
		$cta = $this->create_cta();

		$first  = $cta->get_uuid();
		$second = $cta->get_uuid();

		$this->assertSame( $first, $second );
	}

	/**
	 * Test get_uuid uses existing meta value.
	 */
	public function test_get_uuid_uses_existing_meta() {
		$post_id = $this->factory->post->create( [ 'post_type' => 'pum_cta' ] );
		update_post_meta( $post_id, 'cta_uuid', 'preset-uuid-123' );

		$post = get_post( $post_id );
		$cta  = new CallToAction( $post );

		$this->assertSame( 'preset-uuid-123', $cta->get_uuid() );
	}

	/**
	 * Test get_description returns excerpt when set.
	 */
	public function test_get_description_returns_excerpt() {
		$post_id = $this->factory->post->create( [
			'post_type'    => 'pum_cta',
			'post_excerpt' => 'Custom excerpt here.',
		] );

		$post = get_post( $post_id );
		$cta  = new CallToAction( $post );

		$this->assertSame( 'Custom excerpt here.', $cta->get_description() );
	}

	/**
	 * Test get_description returns default when no excerpt.
	 */
	public function test_get_description_returns_default_message() {
		$cta = $this->create_cta();

		$desc = $cta->get_description();
		$this->assertNotEmpty( $desc );
		// Default message is "This content is restricted." when get_the_excerpt() is empty.
		$this->assertIsString( $desc );
	}

	/**
	 * Test generate_url adds cta query arg.
	 */
	public function test_generate_url_adds_cta_param() {
		$cta = $this->create_cta();
		$url = $cta->generate_url( 'https://example.com/page' );

		$this->assertStringContainsString( 'cta=', $url );
		$this->assertStringContainsString( 'https://example.com/page', $url );
	}

	/**
	 * Test generate_url includes extra args.
	 */
	public function test_generate_url_includes_extra_args() {
		$cta = $this->create_cta();
		$url = $cta->generate_url( 'https://example.com', [ 'ref' => 'email' ] );

		$this->assertStringContainsString( 'ref=email', $url );
		$this->assertStringContainsString( 'cta=', $url );
	}

	/**
	 * Test generate_url works with empty base URL.
	 */
	public function test_generate_url_empty_base() {
		$cta = $this->create_cta();
		$url = $cta->generate_url();

		$this->assertStringContainsString( 'cta=', $url );
	}

	/**
	 * Test to_array returns expected structure.
	 */
	public function test_to_array_structure() {
		$cta = $this->create_cta(
			[ 'type' => 'link', 'url' => 'https://example.com' ],
			[
				'post_title'  => 'Array CTA',
				'post_name'   => 'array-cta',
				'post_status' => 'publish',
			]
		);

		$arr = $cta->to_array();

		$this->assertArrayHasKey( 'id', $arr );
		$this->assertArrayHasKey( 'slug', $arr );
		$this->assertArrayHasKey( 'title', $arr );
		$this->assertArrayHasKey( 'description', $arr );
		$this->assertArrayHasKey( 'status', $arr );
		// Settings are merged in.
		$this->assertArrayHasKey( 'type', $arr );
	}

	/**
	 * Test to_array id matches post ID.
	 */
	public function test_to_array_id_matches() {
		$cta = $this->create_cta();
		$arr = $cta->to_array();

		$this->assertSame( $cta->ID, $arr['id'] );
	}

	/**
	 * Test get_event_count returns 0 for new CTA.
	 */
	public function test_get_event_count_returns_zero_for_new() {
		$cta = $this->create_cta();

		$this->assertSame( 0, $cta->get_event_count( 'conversion', 'current' ) );
		$this->assertSame( 0, $cta->get_event_count( 'conversion', 'total' ) );
	}

	/**
	 * Test get_event_count initializes meta when missing.
	 */
	public function test_get_event_count_initializes_meta() {
		$cta = $this->create_cta();

		// Call to trigger initialization.
		$cta->get_event_count( 'conversion', 'current' );

		$stored = get_post_meta( $cta->ID, 'cta_conversion_count', true );
		$this->assertEquals( 0, $stored );
	}

	/**
	 * Test get_event_count returns stored current count.
	 */
	public function test_get_event_count_current() {
		$cta = $this->create_cta();
		update_post_meta( $cta->ID, 'cta_conversion_count', 5 );

		$this->assertSame( 5, $cta->get_event_count( 'conversion', 'current' ) );
	}

	/**
	 * Test get_event_count returns stored total count.
	 */
	public function test_get_event_count_total() {
		$cta = $this->create_cta();
		update_post_meta( $cta->ID, 'cta_conversion_count_total', 42 );

		$this->assertSame( 42, $cta->get_event_count( 'conversion', 'total' ) );
	}

	/**
	 * Test get_event_count returns 0 for unknown which parameter.
	 */
	public function test_get_event_count_unknown_which() {
		$cta = $this->create_cta();
		$this->assertSame( 0, $cta->get_event_count( 'conversion', 'unknown' ) );
	}

	/**
	 * Test increase_event_count increments both current and total.
	 */
	public function test_increase_event_count() {
		$cta = $this->create_cta();

		$cta->increase_event_count( 'conversion' );

		$this->assertSame( 1, $cta->get_event_count( 'conversion', 'current' ) );
		$this->assertSame( 1, $cta->get_event_count( 'conversion', 'total' ) );
	}

	/**
	 * Test increase_event_count increments from existing values.
	 */
	public function test_increase_event_count_from_existing() {
		$cta = $this->create_cta();
		update_post_meta( $cta->ID, 'cta_conversion_count', 3 );
		update_post_meta( $cta->ID, 'cta_conversion_count_total', 10 );

		$cta->increase_event_count( 'conversion' );

		$current = (int) get_post_meta( $cta->ID, 'cta_conversion_count', true );
		$total   = (int) get_post_meta( $cta->ID, 'cta_conversion_count_total', true );

		$this->assertSame( 4, $current );
		$this->assertSame( 11, $total );
	}

	/**
	 * Test increase_event_count returns true.
	 */
	public function test_increase_event_count_returns_true() {
		$cta    = $this->create_cta();
		$result = $cta->increase_event_count( 'conversion' );

		$this->assertTrue( $result );
	}

	/**
	 * Test track_conversion increments conversion count.
	 */
	public function test_track_conversion_increments() {
		// Remove controller hook that expects popup_id/notrack keys in $args.
		remove_all_actions( 'popup_maker/cta_conversion' );

		$cta = $this->create_cta();

		$cta->track_conversion();

		$this->assertSame( 1, $cta->get_event_count( 'conversion', 'current' ) );
	}

	/**
	 * Test track_conversion fires action.
	 */
	public function test_track_conversion_fires_action() {
		// Remove controller hook that expects popup_id/notrack keys in $args.
		remove_all_actions( 'popup_maker/cta_conversion' );

		$cta    = $this->create_cta();
		$fired  = false;

		add_action( 'popup_maker/cta_conversion', function () use ( &$fired ) {
			$fired = true;
		} );

		$cta->track_conversion();

		$this->assertTrue( $fired );

		remove_all_actions( 'popup_maker/cta_conversion' );
	}

	/**
	 * Test track_conversion with notrack skips counting.
	 */
	public function test_track_conversion_notrack() {
		$cta = $this->create_cta();

		$cta->track_conversion( [ 'notrack' => true ] );

		$this->assertSame( 0, $cta->get_event_count( 'conversion', 'current' ) );
	}

	/**
	 * Test track_conversion with notrack fires notrack action.
	 */
	public function test_track_conversion_notrack_fires_action() {
		$cta   = $this->create_cta();
		$fired = false;

		add_action( 'popup_maker/cta_conversion_notrack', function () use ( &$fired ) {
			$fired = true;
		} );

		$cta->track_conversion( [ 'notrack' => true ] );

		$this->assertTrue( $fired );

		remove_all_actions( 'popup_maker/cta_conversion_notrack' );
	}

	/**
	 * Test increase_event_count works with custom event names.
	 */
	public function test_increase_event_count_custom_event() {
		$cta = $this->create_cta();

		$cta->increase_event_count( 'click' );

		$current = (int) get_post_meta( $cta->ID, 'cta_click_count', true );
		$total   = (int) get_post_meta( $cta->ID, 'cta_click_count_total', true );

		$this->assertSame( 1, $current );
		$this->assertSame( 1, $total );
	}

	/**
	 * Test get_public_settings returns empty array.
	 */
	public function test_get_public_settings_returns_empty() {
		$cta = $this->create_cta( [ 'type' => 'link' ] );
		$this->assertSame( [], $cta->get_public_settings() );
	}
}
