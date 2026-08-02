<?php
/**
 * Tests for PUM_Utils_Upgrades.
 *
 * @package Popup_Maker
 */

/**
 * Test upgrade form rendering.
 */
class PUM_Utils_Upgrades_Test extends WP_UnitTestCase {

	/**
	 * Clean up upgrade state.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		delete_option( 'pum_doing_upgrade' );

		parent::tearDown();
	}

	/**
	 * Test the upgrade form does not depend on admin-only template functions.
	 *
	 * @return void
	 */
	public function test_render_form_uses_frontend_safe_submit_input() {
		delete_option( 'pum_doing_upgrade' );

		$output = $this->render_form();

		$this->assertStringContainsString( '<input type="submit" name="submit" id="submit" class="button"', $output );
		$this->assertStringContainsString( 'value="Process Changes"', $output );
	}

	/**
	 * Test the form retains its resume-upgrade button label.
	 *
	 * @return void
	 */
	public function test_render_form_uses_resume_upgrade_label() {
		update_option(
			'pum_doing_upgrade',
			[
				'upgrade_id' => 'core-v1_7-popups',
				'step'       => 2,
			]
		);

		$output = $this->render_form();

		$this->assertStringContainsString( 'value="Finish Upgrades"', $output );
		$this->assertStringContainsString( 'data-step="2"', $output );
	}

	/**
	 * Render the upgrade form.
	 *
	 * @return string
	 */
	private function render_form() {
		ob_start();
		PUM_Utils_Upgrades::instance()->render_form();

		return (string) ob_get_clean();
	}
}
