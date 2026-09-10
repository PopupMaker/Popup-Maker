<?php
/**
 * Newsletter submission tests.
 *
 * @package Popup_Maker
 */

/**
 * Verify newsletter submission sanitization.
 */
class PUM_Newsletters_Test extends WP_UnitTestCase {

	/**
	 * Subscriber fixture IDs.
	 *
	 * @var int[]
	 */
	private $subscriber_ids = [];

	/**
	 * Remove subscriber fixtures from the custom table.
	 */
	public function tearDown(): void {
		foreach ( $this->subscriber_ids as $subscriber_id ) {
			PUM_DB_Subscribers::instance()->delete( $subscriber_id );
		}

		parent::tearDown();
	}

	/**
	 * Mixed-case subscriber keys must be normalized before sanitization.
	 */
	public function test_sanitization_normalizes_mixed_case_subscriber_keys() {
		$payload = '<div class="contextual-help-tabs"><a href="&lt;img src=x onerror=alert(document.domain)&gt;">marker</a></div>';
		$values  = PUM_Newsletters::sanitization(
			[
				'Email' => 'subscriber@example.com',
				'Name'  => $payload,
			]
		);

		$this->assertArrayNotHasKey( 'Email', $values );
		$this->assertArrayNotHasKey( 'Name', $values );
		$this->assertSame( 'subscriber@example.com', $values['email'] );
		$this->assertSame( 'marker', $values['name'] );
		$this->assertStringNotContainsString( '<', $values['name'] );
		$this->assertStringNotContainsString( 'onerror', $values['name'] );
	}

	/**
	 * Canonical keys win when a mixed-case duplicate is submitted.
	 */
	public function test_sanitization_prefers_canonical_subscriber_keys() {
		$values = PUM_Newsletters::sanitization(
			[
				'email' => 'canonical@example.com',
				'Email' => 'duplicate@example.com',
				'name'  => 'Canonical Name',
				'Name'  => '<b>Duplicate Name</b>',
			]
		);

		$this->assertSame( 'canonical@example.com', $values['email'] );
		$this->assertSame( 'Canonical Name', $values['name'] );
	}

	/**
	 * Provider-specific field names retain their original case.
	 */
	public function test_sanitization_preserves_custom_field_key_case() {
		$values = PUM_Newsletters::sanitization(
			[
				'email'       => 'subscriber@example.com',
				'CustomField' => 'provider-value',
			]
		);

		$this->assertSame( 'provider-value', $values['CustomField'] );
	}

	/**
	 * The persistence boundary ignores mixed-case keys and sanitizes canonical values.
	 */
	public function test_record_submission_enforces_canonical_sanitized_values() {
		$email   = 'persistence-boundary@example.com';
		$payload = '<div class="contextual-help-tabs"><a href="&lt;img src=x onerror=alert(document.domain)&gt;">payload</a></div>';
		$db      = PUM_DB_Subscribers::instance();

		$db->create_table();

		PUM_Newsletters::record_submission(
			[
				'email'   => $email,
				'name'    => '<strong>Canonical Name</strong>',
				'Name'    => $payload,
				'Fname'   => $payload,
				'user_id' => 999,
			]
		);

		$rows = $db->query( [ 'where' => [ 'email' => $email ] ], 'ARRAY_A' );

		$this->assertCount( 1, $rows );
		$this->subscriber_ids[] = (int) $rows[0]['ID'];
		$this->assertSame( 'Canonical Name', $rows[0]['name'] );
		$this->assertSame( '', $rows[0]['fname'] );
		$this->assertSame( '0', $rows[0]['user_id'] );
		$this->assertStringNotContainsString( 'onerror', $rows[0]['name'] );
	}
}
