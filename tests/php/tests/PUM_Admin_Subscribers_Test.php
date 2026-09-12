<?php
/**
 * Admin subscribers screen tests.
 *
 * @package Popup_Maker
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 */

/**
 * Verify the Subscribers screen security cleanup gate.
 *
 * @group ajax
 */
class PUM_Admin_Subscribers_Test extends WP_Ajax_UnitTestCase {

	/**
	 * Subscriber IDs created by a test.
	 *
	 * @var int[]
	 */
	private $subscriber_ids = [];

	/**
	 * Ensure the subscriber table exists for screen rendering.
	 */
	public function set_up() {
		parent::set_up();
		PUM_DB_Subscribers::instance()->create_table();
		PUM_Admin_Ajax::init();
	}

	/**
	 * Remove the migration state after each test.
	 */
	public function tear_down() {
		global $wpdb;

		foreach ( $this->subscriber_ids as $subscriber_id ) {
			$wpdb->delete( PUM_DB_Subscribers::instance()->table_name(), [ 'ID' => $subscriber_id ], [ '%d' ] );
		}

		remove_action( 'wp_ajax_pum_scrub_subscriber_names', [ 'PUM_Admin_Subscribers', 'scrub_subscriber_names' ] );
		delete_option( PUM_DB_Subscribers::NAME_SCRUB_OPTION );
		parent::tear_down();
	}

	/**
	 * Test subscriber records remain hidden while cleanup is incomplete.
	 */
	public function test_page_blocks_subscriber_table_until_scrub_is_complete() {
		for ( $index = 0; $index <= PUM_DB_Subscribers::NAME_SCRUB_BATCH_SIZE; $index++ ) {
			$this->insert_unsafe_subscriber( $index );
		}

		delete_option( PUM_DB_Subscribers::NAME_SCRUB_OPTION );

		ob_start();
		PUM_Admin_Subscribers::page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Subscriber security cleanup in progress', $output );
		$this->assertStringContainsString( 'pum_scrub_subscriber_names', $output );
		$this->assertStringContainsString( 'runBatch()', $output );
		$this->assertStringContainsString( 'window.location.reload()', $output );
		$this->assertStringContainsString( 'Refresh now', $output );
		$this->assertStringNotContainsString( 'pum-subscribers-list-form', $output );
		$this->assertFalse( PUM_DB_Subscribers::instance()->is_name_scrub_complete() );

		$this->_setRole( 'administrator' );
		$_POST['nonce'] = wp_create_nonce( 'pum_scrub_subscriber_names' );
		$response       = $this->dispatch_scrub_request();

		$this->assertTrue( $response['success'] );
		$this->assertTrue( $response['data']['complete'] );

		ob_start();
		PUM_Admin_Subscribers::page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'pum-subscribers-list-form', $output );
		$this->assertStringNotContainsString( 'Subscriber security cleanup in progress', $output );
	}

	/**
	 * Test the global admin AJAX bootstrap registers the scrub endpoint.
	 */
	public function test_ajax_endpoint_is_registered_globally() {
		$this->assertSame(
			10,
			has_action( 'wp_ajax_pum_scrub_subscriber_names', [ 'PUM_Admin_Subscribers', 'scrub_subscriber_names' ] )
		);
	}

	/**
	 * Test the AJAX endpoint sanitizes a batch and reports completion.
	 */
	public function test_ajax_scrubs_names_and_reports_completion() {
		global $wpdb;

		$this->_setRole( 'administrator' );
		$subscriber_id = $this->insert_unsafe_subscriber( 1 );

		$_POST['nonce'] = wp_create_nonce( 'pum_scrub_subscriber_names' );
		$response       = $this->dispatch_scrub_request();

		$name = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT name FROM %i WHERE ID = %d',
				PUM_DB_Subscribers::instance()->table_name(),
				$subscriber_id
			)
		);

		$this->assertTrue( $response['success'] );
		$this->assertTrue( $response['data']['complete'] );
		$this->assertSame( 'Unsafe Name', $name );
		$this->assertSame( 'complete', get_option( PUM_DB_Subscribers::NAME_SCRUB_OPTION ) );
	}

	/**
	 * Test the AJAX endpoint requires administrative access.
	 */
	public function test_ajax_rejects_users_without_permission() {
		$this->_setRole( 'subscriber' );
		$_POST['nonce'] = wp_create_nonce( 'pum_scrub_subscriber_names' );

		$response = $this->dispatch_scrub_request();

		$this->assertFalse( $response['success'] );
	}

	/**
	 * Test the AJAX endpoint rejects an invalid nonce.
	 */
	public function test_ajax_rejects_invalid_nonce() {
		$this->_setRole( 'administrator' );
		$_POST['nonce'] = 'invalid-subscriber-scrub-nonce';
		$rejected       = false;

		try {
			$this->_handleAjax( 'pum_scrub_subscriber_names' );
			$this->fail( 'The AJAX endpoint did not terminate the request.' );
		} catch ( WPAjaxDieStopException $exception ) {
			$rejected = true;
		}

		$this->assertTrue( $rejected );
		$this->assertFalse( PUM_DB_Subscribers::instance()->is_name_scrub_complete() );
	}

	/**
	 * Test the subscriber table renders after cleanup is complete.
	 */
	public function test_page_renders_subscriber_table_after_scrub_is_complete() {
		update_option( PUM_DB_Subscribers::NAME_SCRUB_OPTION, 'complete' );

		ob_start();
		PUM_Admin_Subscribers::page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'pum-subscribers-list-form', $output );
		$this->assertStringNotContainsString( 'Subscriber security cleanup in progress', $output );
		$this->assertStringNotContainsString( 'window.location.reload()', $output );
	}

	/**
	 * Insert an unsafe subscriber directly to simulate legacy stored data.
	 *
	 * @param int $index Unique fixture index.
	 *
	 * @return int Subscriber ID.
	 */
	private function insert_unsafe_subscriber( $index ) {
		global $wpdb;

		$email = "pum-scrub-test-{$index}@example.com";
		$wpdb->insert(
			PUM_DB_Subscribers::instance()->table_name(),
			[
				'email_hash'   => md5( $email ),
				'popup_id'     => 0,
				'user_id'      => 0,
				'email'        => $email,
				'name'         => '<strong>Unsafe Name</strong>',
				'fname'        => '',
				'lname'        => '',
				'uuid'         => wp_generate_uuid4(),
				'consent'      => 'no',
				'consent_args' => '',
				'created'      => current_time( 'mysql' ),
			]
		);

		$subscriber_id          = (int) $wpdb->insert_id;
		$this->subscriber_ids[] = $subscriber_id;

		return $subscriber_id;
	}

	/**
	 * Dispatch the subscriber scrub AJAX request.
	 *
	 * @return array{success:bool,data:array{complete?:bool}}
	 */
	private function dispatch_scrub_request() {
		$response = [];

		try {
			$this->_handleAjax( 'pum_scrub_subscriber_names' );
			$this->fail( 'The AJAX endpoint did not terminate the request.' );
		} catch ( WPAjaxDieContinueException $exception ) {
			$response = json_decode( $this->_last_response, true );
		}

		$this->assertIsArray( $response );

		return $response;
	}
}
