<?php
/**
 * Standard REST aliases for Popup Maker post types.
 *
 * @package Popup_Maker
 */

/**
 * Test standard WordPress REST aliases.
 */
class REST_PostTypeAliases_Test extends WP_UnitTestCase {

	/**
	 * Reset the REST server after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		global $wp_rest_server;

		$wp_rest_server = null;

		parent::tearDown();
	}

	/**
	 * Test the standard aliases and existing versioned routes are registered.
	 *
	 * @return void
	 */
	public function test_post_type_routes_are_registered_in_both_namespaces() {
		global $wp_rest_server;

		$wp_rest_server = null;
		$routes         = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/popup-maker/v2/popups', $routes );
		$this->assertArrayHasKey( '/popup-maker/v2/popup-themes', $routes );
		$this->assertArrayHasKey( '/wp/v2/popups', $routes );
		$this->assertArrayHasKey( '/wp/v2/popup-themes', $routes );
	}

	/**
	 * Test an authorized user can read a popup through the standard alias.
	 *
	 * @return void
	 */
	public function test_standard_popup_alias_uses_core_permissions_and_response() {
		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Builder-compatible popup',
			]
		);

		wp_set_current_user( $admin_id );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/popups/' . $popup_id ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $popup_id, $response->get_data()['id'] );
	}
}
