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

		parent::tearDown();
	}

	/**
	 * New context fields receive backward-compatible defaults.
	 */
	public function test_context_fields_have_defaults() {
		pum_integrated_form_submission(
			[
				'form_provider' => 'gravityforms',
				'form_id'       => 7,
			]
		);

		$submission = PUM_Integrations::$form_submission;

		$this->assertNull( $submission['submission_id'] );
		$this->assertNull( $submission['source_post_id'] );
		$this->assertSame( [], $submission['context'] );
	}

	/**
	 * Extension context survives filtering and normalized dispatch.
	 */
	public function test_extension_context_is_preserved() {
		$received = null;

		$this->context_filter = static function ( $args ) {
				$args['context']['content_upgrade'] = [
					'incentive_id' => 42,
				];

				return $args;
		};
		add_filter(
			'pum_integrated_form_submission_args',
			$this->context_filter
		);

		$this->submission_action = static function ( $args ) use ( &$received ) {
			$received = $args;
		};
		add_action(
			'pum_integrated_form_submission',
			$this->submission_action
		);

		pum_integrated_form_submission(
			[
				'form_provider'  => 'gravityforms',
				'form_id'        => 7,
				'submission_id'  => 'entry-99',
				'source_post_id' => 123,
			]
		);

		$this->assertSame( 'entry-99', $received['submission_id'] );
		$this->assertSame( 123, $received['source_post_id'] );
		$this->assertSame( 42, $received['context']['content_upgrade']['incentive_id'] );
	}
}
