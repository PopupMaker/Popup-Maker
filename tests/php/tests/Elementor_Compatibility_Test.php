<?php
/**
 * Elementor builder compatibility tests.
 *
 * @package Popup_Maker
 */

/**
 * Test Elementor popup preview requests.
 */
class Elementor_Compatibility_Test extends WP_UnitTestCase {

	/**
	 * Original query parameters.
	 *
	 * @var array<string,mixed>
	 */
	private $original_get;

	/**
	 * Preserve query parameters before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Test fixture preserves request globals.
		$this->original_get = $_GET;
	}

	/**
	 * Restore global state after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$_GET = $this->original_get;
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * An authorized Elementor preview can query its popup.
	 *
	 * @return void
	 */
	public function test_authorized_elementor_preview_restores_popup_post_type() {
		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		wp_set_current_user( $admin_id );
		$_GET = [
			'elementor-preview' => (string) $popup_id,
			'p'                 => (string) $popup_id,
			'post_type'         => 'popup',
		];

		$query_vars = apply_filters( 'request', [ 'p' => (string) $popup_id ] );

		$this->assertSame( $popup_id, $query_vars['p'] );
		$this->assertSame( 'popup', $query_vars['post_type'] );
		$this->assertFalse( apply_filters( 'pum_popup_is_loadable', true, $popup_id ) );
	}

	/**
	 * A mismatched preview ID must not expose a popup query.
	 *
	 * @return void
	 */
	public function test_mismatched_elementor_preview_does_not_restore_popup_post_type() {
		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		wp_set_current_user( $admin_id );
		$_GET = [
			'elementor-preview' => (string) $popup_id,
			'p'                 => (string) ( $popup_id + 1 ),
			'post_type'         => 'popup',
		];

		$query_vars = apply_filters( 'request', [ 'p' => (string) ( $popup_id + 1 ) ] );

		$this->assertArrayNotHasKey( 'post_type', $query_vars );
	}

	/**
	 * Logged-out preview requests must remain non-queryable.
	 *
	 * @return void
	 */
	public function test_logged_out_elementor_preview_does_not_restore_popup_post_type() {
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		$_GET = [
			'elementor-preview' => (string) $popup_id,
			'p'                 => (string) $popup_id,
			'post_type'         => 'popup',
		];

		$query_vars = apply_filters( 'request', [ 'p' => (string) $popup_id ] );

		$this->assertArrayNotHasKey( 'post_type', $query_vars );
		$this->assertTrue( apply_filters( 'pum_popup_is_loadable', true, $popup_id ) );
	}

	/**
	 * A logged-in user without popup permissions must remain blocked.
	 *
	 * @return void
	 */
	public function test_user_without_popup_permission_does_not_restore_popup_post_type() {
		$subscriber_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );
		$popup_id      = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		wp_set_current_user( $subscriber_id );
		$_GET = [
			'elementor-preview' => (string) $popup_id,
			'p'                 => (string) $popup_id,
			'post_type'         => 'popup',
		];

		$query_vars = apply_filters( 'request', [ 'p' => (string) $popup_id ] );

		$this->assertArrayNotHasKey( 'post_type', $query_vars );
		$this->assertTrue( apply_filters( 'pum_popup_is_loadable', true, $popup_id ) );
	}
}
