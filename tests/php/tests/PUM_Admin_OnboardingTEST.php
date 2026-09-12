<?php
/**
 * Class PUM_Admin_OnboardingTEST
 *
 * @package Popup_Maker
 */

/**
 * Test methods within our PUM_Admin_Onboarding class
 */
class PUM_Admin_OnboardingTEST extends WP_UnitTestCase {

	/**
	 * Tests to make sure data returned from `all_popups_main_tour` is valid.
	 */
	public function test_all_popups_pointers() {
		$pointers = PUM_Admin_Onboarding::all_popups_main_tour( [] );
		$this->assertIsArray( $pointers );
	}

	/**
	 * Tests to make sure data returned from `tips_alert` is valid.
	 */
	public function test_tips_alert() {
		$alerts = PUM_Admin_Onboarding::tips_alert( [] );
		$this->assertIsArray( $alerts );
	}

	/**
	 * Tests to make sure data returned from `get_random_tip` is valid.
	 */
	public function test_get_random_tip() {
		$tip = PUM_Admin_Onboarding::get_random_tip();
		$this->assertIsArray( $tip );

		$this->assertCount( 2, $tip );

		$this->assertArrayHasKey( 'msg', $tip );
		$this->assertArrayHasKey( 'link', $tip );
	}

	/**
	 * Tests to make sure data returned from `should_show_tip` is valid.
	 */
	public function test_should_show_tip() {
		$result = PUM_Admin_Onboarding::should_show_tip();
		$this->assertIsBool( $result );
	}

	/**
	 * Tests to make sure data returned from `has_turned_off_tips` is valid.
	 */
	public function test_has_turned_off_tips() {
		$result = PUM_Admin_Onboarding::has_turned_off_tips();
		$this->assertIsBool( $result );
	}

	/**
	 * Tests that the welcome page directs new users to one clear outcome.
	 */
	public function test_welcome_page_has_a_clear_primary_outcome() {
		ob_start();
		PUM_Admin_Onboarding::display_welcome_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Welcome to Popup Maker', $output );
		$this->assertStringContainsString( 'Start with one goal:', $output );
		$this->assertStringContainsString( 'dashicons-email-alt', $output );
		$this->assertStringContainsString( 'dashicons-layout', $output );
		$this->assertStringContainsString( 'aria-hidden="true"', $output );
		$this->assertStringContainsString( 'Create your first popup', $output );
		$this->assertStringContainsString( 'post-new.php?post_type=popup', $output );
		$this->assertStringNotContainsString( 'ProductHunt', $output );
	}
}
