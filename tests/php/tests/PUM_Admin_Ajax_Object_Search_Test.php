<?php
/**
 * AJAX tests for object search.
 *
 * @package Popup_Maker
 */

/**
 * Verify object search response escaping.
 *
 * @group ajax
 */
class PUM_Admin_Ajax_Object_Search_Test extends WP_Ajax_UnitTestCase {

	/**
	 * Register the plugin endpoint in the AJAX test environment.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		add_action( 'wp_ajax_pum_object_search', [ 'PUM_Admin_Ajax', 'object_search' ] );
	}

	/**
	 * Remove the test-registered endpoint.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_action( 'wp_ajax_pum_object_search', [ 'PUM_Admin_Ajax', 'object_search' ] );

		parent::tear_down();
	}

	/**
	 * Entity-encoded post titles remain escaped in AJAX output.
	 *
	 * @return void
	 */
	public function test_post_title_is_escaped_in_ajax_response() {
		$this->_setRole( 'administrator' );

		$post_id = self::factory()->post->create(
			[
				'post_title' => 'PUM XSS &lt;img src=x onerror=alert(document.domain)&gt;',
			]
		);

		$_POST = [
			'nonce'       => wp_create_nonce( 'pum_ajax_object_search_nonce' ),
			'object_type' => 'post_type',
			'object_key'  => 'post',
			'include'     => [ $post_id ],
		];

		$response = $this->dispatch_object_search_request();
		$item     = current(
			array_filter(
				$response['items'],
				function ( $candidate ) use ( $post_id ) {
					return $post_id === (int) $candidate['id'];
				}
			)
		);

		$this->assertIsArray( $item );
		$this->assertStringContainsString( '&lt;img', $item['text'] );
		$this->assertStringNotContainsString( '<img', $item['text'] );
	}

	/**
	 * Dispatch object search and decode its JSON response.
	 *
	 * @return array<string,mixed>
	 */
	private function dispatch_object_search_request() {
		$response = [];

		try {
			$this->_handleAjax( 'pum_object_search' );
			$this->fail( 'The AJAX endpoint did not terminate the request.' );
		} catch ( WPAjaxDieContinueException $exception ) {
			$response = json_decode( $this->_last_response, true );
		}

		$this->assertIsArray( $response );

		return $response;
	}
}
