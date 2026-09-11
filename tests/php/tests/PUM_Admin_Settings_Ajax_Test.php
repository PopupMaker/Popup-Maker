<?php
/**
 * AJAX tests for the Popup Maker CSS viewer.
 *
 * @package Popup_Maker
 */

/**
 * Verify the CSS viewer endpoint security boundary.
 *
 * @group ajax
 */
class PUM_Admin_Settings_Ajax_Test extends WP_Ajax_UnitTestCase {

	/**
	 * Register the plugin endpoint in the AJAX test environment.
	 *
	 * The normal test bootstrap does not enter wp-admin, so the admin loader does
	 * not register this production hook automatically.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		add_action( 'wp_ajax_pum_get_css_styles', [ 'PUM_Admin_Settings', 'ajax_get_css_styles' ] );
	}

	/**
	 * Remove the test-registered endpoint.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_action( 'wp_ajax_pum_get_css_styles', [ 'PUM_Admin_Settings', 'ajax_get_css_styles' ] );

		parent::tear_down();
	}

	/**
	 * Invalid nonces are rejected before CSS generation.
	 *
	 * @return void
	 */
	public function test_css_viewer_ajax_rejects_invalid_nonce() {
		$this->_setRole( 'administrator' );
		$_POST['nonce'] = 'invalid-css-viewer-nonce';

		$response = $this->dispatch_css_viewer_request();

		$this->assertFalse( $response['success'] );
		$this->assertSame(
			'The CSS viewer request expired. Refresh the page and try again.',
			$response['data']['message']
		);
	}

	/**
	 * Users without settings access cannot load CSS after nonce validation.
	 *
	 * @return void
	 */
	public function test_css_viewer_ajax_rejects_missing_capability() {
		$this->_setRole( 'subscriber' );
		$_POST['nonce'] = wp_create_nonce( 'pum_get_css_styles' );

		$response = $this->dispatch_css_viewer_request();

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'You do not have permission to view these styles.', $response['data']['message'] );
	}

	/**
	 * Dispatch the registered CSS viewer endpoint and decode its response.
	 *
	 * @return array{success:bool,data:array{message:string}}
	 */
	private function dispatch_css_viewer_request() {
		$response = [];

		try {
			$this->_handleAjax( 'pum_get_css_styles' );
			$this->fail( 'The AJAX endpoint did not terminate the request.' );
		} catch ( WPAjaxDieContinueException $exception ) {
			$response = json_decode( $this->_last_response, true );
		}

		$this->assertIsArray( $response );

		return $response;
	}
}
