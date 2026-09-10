<?php
/**
 * Tests for Popup Maker editor stylesheet loading.
 *
 * @package Popup_Maker
 */

/**
 * Verify editor styles do not require HTTP requests.
 */
class PUM_Admin_Shortcode_UI_Test extends WP_UnitTestCase {

	/**
	 * Prepare an editor-capable user.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );

		wp_set_current_user( $user_id );
		update_user_option( $user_id, 'rich_editing', 'true' );
	}

	/**
	 * TinyMCE retains both Popup Maker editor stylesheets.
	 *
	 * @return void
	 */
	public function test_mce_css_includes_popup_maker_styles() {
		PUM_Admin_Shortcode_UI::init_editor();

		$stylesheets = apply_filters( 'mce_css', 'https://example.com/theme.css' );

		$this->assertStringContainsString( 'https://example.com/theme.css', $stylesheets );
		$this->assertStringContainsString( Popup_Maker::$URL . 'dist/assets/site.css', $stylesheets );
		$this->assertStringContainsString( Popup_Maker::$URL . 'dist/assets/admin-editor-styles.css', $stylesheets );
	}

	/**
	 * RTL editors load only the RTL Popup Maker stylesheets.
	 *
	 * @return void
	 */
	public function test_mce_css_selects_rtl_popup_maker_styles() {
		global $wp_locale;

		$original_direction        = $wp_locale->text_direction;
		$wp_locale->text_direction = 'rtl';

		try {
			PUM_Admin_Shortcode_UI::init_editor();

			$stylesheets = apply_filters( 'mce_css', '' );
		} finally {
			$wp_locale->text_direction = $original_direction;
		}

		$this->assertStringContainsString( Popup_Maker::$URL . 'dist/assets/site-rtl.css', $stylesheets );
		$this->assertStringContainsString( Popup_Maker::$URL . 'dist/assets/admin-editor-styles-rtl.css', $stylesheets );
		$this->assertStringNotContainsString( Popup_Maker::$URL . 'dist/assets/site.css', $stylesheets );
		$this->assertStringNotContainsString( Popup_Maker::$URL . 'dist/assets/admin-editor-styles.css', $stylesheets );
	}

	/**
	 * Block editor settings receive local CSS without an HTTP request.
	 *
	 * @return void
	 */
	public function test_block_editor_settings_include_local_styles() {
		$site_styles_path  = Popup_Maker::$DIR . 'dist/assets/site.css';
		$admin_styles_path = Popup_Maker::$DIR . 'dist/assets/admin-editor-styles.css';

		if ( ! is_readable( $site_styles_path ) || ! is_readable( $admin_styles_path ) ) {
			$this->markTestSkipped( 'Dist assets not built in test environment.' );
		}

		$http_requests = 0;
		$filter        = function ( $response ) use ( &$http_requests ) {
			++$http_requests;

			return $response;
		};

		add_filter( 'pre_http_request', $filter );

		PUM_Admin_Shortcode_UI::init_editor();

		$settings = apply_filters( 'block_editor_settings_all', [ 'styles' => [] ], null );

		remove_filter( 'pre_http_request', $filter );

		$this->assertSame( 0, $http_requests );

		$styles = array_column( $settings['styles'], 'css' );

		$this->assertContains( file_get_contents( $site_styles_path ), $styles );
		$this->assertContains( file_get_contents( $admin_styles_path ), $styles );
	}
}
