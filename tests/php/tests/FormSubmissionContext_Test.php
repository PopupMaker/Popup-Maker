<?php
/**
 * Normalized form submission context tests.
 *
 * @package Popup_Maker
 */

/**
 * Test the normalized form submission context contract.
 */
class FormSubmissionContext_Test extends WP_UnitTestCase {
	/** @var callable|null Context filter registered by a test. */
	private $context_filter;

	/** @var callable|null Submission action registered by a test. */
	private $submission_action;

	/** @var string|null Original request referrer. */
	private $original_referer;

	/**
	 * Capture the original request state.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_referer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : null;
		unset( $_SERVER['HTTP_REFERER'] );
	}

	/**
	 * Reset shared integration state after each test.
	 */
	public function tearDown(): void {
		PUM_Integrations::$form_submission = null;

		if ( $this->context_filter ) {
			remove_filter( 'pum_integrated_form_submission_args', $this->context_filter );
		}
		if ( $this->submission_action ) {
			remove_action( 'pum_integrated_form_submission', $this->submission_action );
		}

		if ( null === $this->original_referer ) {
			unset( $_SERVER['HTTP_REFERER'] );
		} else {
			$_SERVER['HTTP_REFERER'] = $this->original_referer;
		}

		parent::tearDown();
	}

	/**
	 * Missing context receives portable defaults and a generated identifier.
	 */
	public function test_context_fields_have_defaults() {
		pum_integrated_form_submission(
			[
				'form_provider' => 'gravityforms',
				'form_id'       => 7,
			]
		);

		$submission = PUM_Integrations::$form_submission;

		$this->assertTrue( wp_is_uuid( $submission['submission_id'], 4 ) );
		$this->assertNull( $submission['source_post_id'] );
		$this->assertNull( $submission['source_url'] );
		$this->assertSame( [], $submission['context'] );
	}

	/**
	 * Extension filters receive a canonical submission identifier.
	 */
	public function test_filter_receives_generated_submission_id() {
		$seen_submission_id = null;

		$this->context_filter = static function ( $args ) use ( &$seen_submission_id ) {
			$seen_submission_id = $args['submission_id'];

			return $args;
		};
		add_filter( 'pum_integrated_form_submission_args', $this->context_filter );

		pum_integrated_form_submission();

		$this->assertTrue( wp_is_uuid( $seen_submission_id, 4 ) );
		$this->assertSame( $seen_submission_id, PUM_Integrations::$form_submission['submission_id'] );
	}

	/**
	 * Provider identifiers and extension context survive normalized dispatch.
	 */
	public function test_extension_context_is_preserved() {
		$received = null;

		$this->context_filter = static function ( $args ) {
			$args['context']['example_extension'] = [
				'campaign_id' => 42,
			];

			return $args;
		};
		add_filter( 'pum_integrated_form_submission_args', $this->context_filter );

		$this->submission_action = static function ( $args ) use ( &$received ) {
			$received = $args;
		};
		add_action( 'pum_integrated_form_submission', $this->submission_action );

		pum_integrated_form_submission(
			[
				'form_provider'  => 'gravityforms',
				'form_id'        => 7,
				'submission_id'  => 'entry-99',
				'source_post_id' => 123,
				'source_url'     => 'https://example.com/guide/',
			]
		);

		$this->assertSame( 'entry-99', $received['submission_id'] );
		$this->assertSame( 123, $received['source_post_id'] );
		$this->assertSame( 'https://example.com/guide/', $received['source_url'] );
		$this->assertSame( 42, $received['context']['example_extension']['campaign_id'] );
	}

	/**
	 * Request referrers resolve into source URLs and WordPress post IDs.
	 */
	public function test_source_context_is_resolved_from_request_referer() {
		$post_id                 = self::factory()->post->create();
		$_SERVER['HTTP_REFERER'] = get_permalink( $post_id );

		pum_integrated_form_submission();

		$submission = PUM_Integrations::$form_submission;

		$this->assertSame( $post_id, $submission['source_post_id'] );
		$this->assertSame( get_permalink( $post_id ), $submission['source_url'] );
	}

	/**
	 * Invalid extension values cannot break the portable envelope.
	 */
	public function test_invalid_context_values_are_normalized() {
		$this->context_filter = static function ( $args ) {
			$args['submission_id']  = [];
			$args['source_post_id'] = 'not-a-post';
			$args['source_url']     = [];
			$args['context']        = 'not-an-array';

			return $args;
		};
		add_filter( 'pum_integrated_form_submission_args', $this->context_filter );

		pum_integrated_form_submission();

		$submission = PUM_Integrations::$form_submission;

		$this->assertTrue( wp_is_uuid( $submission['submission_id'], 4 ) );
		$this->assertNull( $submission['source_post_id'] );
		$this->assertNull( $submission['source_url'] );
		$this->assertSame( [], $submission['context'] );
	}

	/**
	 * Non-AJAX submissions retain context when mapped into frontend arguments.
	 */
	public function test_submission_context_is_remapped_for_javascript() {
		PUM_Integrations::$form_submission = [
			'form_provider'    => 'fluentforms',
			'form_id'          => 4,
			'form_instance_id' => 2,
			'submission_id'    => 'entry-12',
			'popup_id'         => 55,
			'source_post_id'   => 78,
			'source_url'       => 'https://example.com/guide/',
			'context'          => [ 'example_extension' => [ 'campaign_id' => 90 ] ],
		];

		$vars       = PUM_Integrations::pum_vars();
		$submission = $vars['form_submission'];

		$this->assertSame( 'fluentforms', $submission['formProvider'] );
		$this->assertSame( 4, $submission['formId'] );
		$this->assertSame( 2, $submission['formInstanceId'] );
		$this->assertSame( 'entry-12', $submission['submissionId'] );
		$this->assertSame( 55, $submission['popupId'] );
		$this->assertSame( 78, $submission['sourcePostId'] );
		$this->assertSame( 'https://example.com/guide/', $submission['sourceUrl'] );
		$this->assertSame( 90, $submission['context']['example_extension']['campaign_id'] );
	}
}
