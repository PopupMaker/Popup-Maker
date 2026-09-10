<?php
/**
 * Tests for context-aware legacy admin assets.
 *
 * @package Popup_Maker
 */

/**
 * Verify legacy admin data is built only when its script dependency is used.
 */
class PUM_Admin_Assets_Test extends WP_UnitTestCase {

	/**
	 * Whether WordPress's admin script registry has been initialized.
	 *
	 * @var bool
	 */
	private static $core_scripts_initialized = false;

	/**
	 * Initialize WordPress-owned script registrations for each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		set_current_screen( 'dashboard' );

		if ( ! self::$core_scripts_initialized ) {
			wp_default_scripts( wp_scripts() );
			self::$core_scripts_initialized = true;
		}
	}

	/**
	 * Reset asset queues between tests.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$handles = [ 'pum-admin-general', 'pum-admin-batch', 'pum-admin-popup-editor', 'pum-admin-header-consumer', 'pum-admin-late-consumer', 'pum-admin-footer-consumer', 'pum-admin-batch-consumer' ];

		foreach ( $handles as $handle ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}

		$wp_scripts        = wp_scripts();
		$wp_scripts->done  = array_values( array_diff( $wp_scripts->done, $handles ) );
		$wp_scripts->to_do = array_values( array_diff( $wp_scripts->to_do, $handles ) );

		wp_dequeue_style( 'pum-admin-general' );
		remove_action( 'admin_footer', [ 'PUM_Admin_Templates', 'render' ] );

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function test_admin_vars_are_deferred_until_general_script_is_needed() {
		$filter_calls = 0;

		add_filter(
			'pum_admin_vars',
			function ( $vars ) use ( &$filter_calls ) {
				++$filter_calls;

				return $vars;
			}
		);

		PUM_Admin_Assets::init();
		PUM_Admin_Assets::register_admin_scripts();

		$this->assertSame( 0, $filter_calls );

		wp_enqueue_script( 'pum-admin-popup-editor' );
		PUM_Admin_Assets::maybe_localize_and_templates();

		$this->assertSame( 1, $filter_calls );

		$localized_data = wp_scripts()->get_data( 'pum-admin-general', 'data' );

		$this->assertIsString( $localized_data );
		$this->assertStringContainsString( 'var pum_admin_vars', $localized_data );
		$this->assertStringContainsString( 'var pum_admin', $localized_data );
	}

	/**
	 * Legacy extensions (e.g. Popup Analytics) enqueue only the
	 * pum-admin-general STYLE on their own admin pages while their JS reads
	 * window.pum_admin_vars. The style enqueue must count as a signal.
	 *
	 * @return void
	 */
	public function test_admin_vars_localize_when_only_general_style_is_enqueued() {
		PUM_Admin_Assets::init();
		PUM_Admin_Assets::register_admin_scripts();
		PUM_Admin_Assets::register_admin_styles();

		$template_priority = has_action( 'admin_footer', [ 'PUM_Admin_Templates', 'render' ] );

		wp_enqueue_style( 'pum-admin-general' );
		PUM_Admin_Assets::maybe_localize_and_templates();

		$localized_data = wp_scripts()->get_data( 'pum-admin-general', 'data' );

		$this->assertIsString( $localized_data );
		$this->assertStringContainsString( 'var pum_admin_vars', $localized_data );
		$this->assertTrue( wp_script_is( 'pum-admin-general', 'enqueued' ) );
		$this->assertSame( $template_priority, has_action( 'admin_footer', [ 'PUM_Admin_Templates', 'render' ] ) );

		$output = wp_scripts()->print_extra_script( 'pum-admin-general', false );

		$this->assertIsString( $output );
		$this->assertStringContainsString( 'var pum_admin_vars', $output );
	}

	/**
	 * Late script consumers restore the templates skipped for style-only usage.
	 *
	 * @return void
	 */
	public function test_late_script_consumer_loads_templates_after_style_only_compatibility() {
		PUM_Admin_Assets::init();
		PUM_Admin_Assets::register_admin_scripts();
		PUM_Admin_Assets::register_admin_styles();

		wp_enqueue_style( 'pum-admin-general' );
		PUM_Admin_Assets::maybe_localize_and_templates();

		$this->assertTrue( wp_scripts()->get_data( 'pum-admin-general', 'pum_style_only_compat' ) );
		$this->assertFalse( has_action( 'admin_footer', [ 'PUM_Admin_Templates', 'render' ] ) );

		wp_register_script( 'pum-admin-late-consumer', 'https://example.com/late.js', [ 'pum-admin-general' ], '1.0.0', true );
		wp_enqueue_script( 'pum-admin-late-consumer' );
		PUM_Admin_Assets::maybe_localize_and_templates();

		$this->assertFalse( wp_scripts()->get_data( 'pum-admin-general', 'pum_style_only_compat' ) );
		$this->assertSame( 10, has_action( 'admin_footer', [ 'PUM_Admin_Templates', 'render' ] ) );
	}

	/**
	 * Header consumers receive legacy globals before their scripts print.
	 *
	 * @return void
	 */
	public function test_admin_vars_print_before_header_script_consumers() {
		PUM_Admin_Assets::init();
		PUM_Admin_Assets::register_admin_scripts();

		$enqueue_consumer = function () {
			wp_register_script( 'pum-admin-header-consumer', 'https://example.com/header.js', [ 'pum-admin-general' ], '1.0.0', false );
			wp_script_add_data( 'pum-admin-general', 'group', 0 );
			wp_enqueue_script( 'pum-admin-header-consumer' );
		};

		add_action( 'admin_print_scripts', $enqueue_consumer, 19 );

		$this->assertSame( -1, has_action( 'admin_print_scripts', [ 'PUM_Admin_Assets', 'maybe_localize_and_templates' ] ) );

		ob_start();
		do_action( 'admin_print_scripts' );
		$output = ob_get_clean();

		remove_action( 'admin_print_scripts', $enqueue_consumer, 19 );

		$this->assertIsString( $output );
		$this->assertStringContainsString( 'var pum_admin_vars', $output );
		$this->assertLessThan( strpos( $output, 'header.js' ), strpos( $output, 'var pum_admin_vars' ) );
	}

	/**
	 * Footer consumers enqueued after the early localization pass still receive globals.
	 *
	 * @return void
	 */
	public function test_admin_vars_print_for_scripts_enqueued_during_footer_hook() {
		PUM_Admin_Assets::init();
		PUM_Admin_Assets::register_admin_scripts();

		$enqueue_consumer = function () {
			wp_register_script( 'pum-admin-footer-consumer', 'https://example.com/footer.js', [ 'pum-admin-general' ], '1.0.0', true );
			wp_enqueue_script( 'pum-admin-footer-consumer' );
		};
		$printer_priority = has_action( 'admin_print_footer_scripts', '_wp_footer_scripts' );

		$this->assertIsInt( $printer_priority );
		$enqueue_priority = $printer_priority - 1;
		add_action( 'admin_print_footer_scripts', $enqueue_consumer, $enqueue_priority );

		$this->assertFalse( wp_scripts()->get_data( 'pum-admin-general', 'data' ) );

		ob_start();
		do_action( 'admin_footer', '' );
		ob_end_clean();

		ob_start();
		do_action( 'admin_print_footer_scripts' );
		$output = ob_get_clean();

		remove_action( 'admin_print_footer_scripts', $enqueue_consumer, $enqueue_priority );

		$localized_data = wp_scripts()->get_data( 'pum-admin-general', 'data' );

		$this->assertIsString( $localized_data );
		$this->assertStringContainsString( 'var pum_admin_vars', $localized_data );
		$this->assertIsString( $output );
		$this->assertStringContainsString( 'var pum_admin_vars', $output );
		$this->assertStringContainsString( 'footer.js', $output );
		$this->assertLessThan( strpos( $output, 'footer.js' ), strpos( $output, 'var pum_admin_vars' ) );
		$this->assertSame( 1, substr_count( $output, 'id="tmpl-pum-field-text"' ) );
	}

	/**
	 * Batch variables are localized when the batch script is a queued dependency.
	 *
	 * @return void
	 */
	public function test_batch_vars_localize_for_queued_dependency() {
		PUM_Admin_Assets::init();
		PUM_Admin_Assets::register_admin_scripts();

		wp_register_script( 'pum-admin-batch-consumer', 'https://example.com/batch.js', [ 'pum-admin-batch' ], '1.0.0', true );
		wp_enqueue_script( 'pum-admin-batch-consumer' );

		PUM_Admin_Assets::maybe_localize_and_templates();

		$localized_data = wp_scripts()->get_data( 'pum-admin-batch', 'data' );

		$this->assertIsString( $localized_data );
		$this->assertStringContainsString( 'var pum_batch_vars', $localized_data );
	}

	/**
	 * Similar variable names do not suppress the required legacy globals.
	 *
	 * @return void
	 */
	public function test_admin_vars_require_complete_variable_declarations() {
		PUM_Admin_Assets::init();
		PUM_Admin_Assets::register_admin_scripts();

		wp_localize_script( 'pum-admin-general', 'pum_admin_vars_extra', [] );
		wp_enqueue_script( 'pum-admin-general' );

		PUM_Admin_Assets::maybe_localize_and_templates();

		$localized_data = wp_scripts()->get_data( 'pum-admin-general', 'data' );

		$this->assertIsString( $localized_data );
		$this->assertSame( 1, preg_match_all( '/(?:^|[;\r\n])\s*var\s+pum_admin_vars\s*=/m', $localized_data ) );
		$this->assertSame( 1, preg_match_all( '/(?:^|[;\r\n])\s*var\s+pum_admin\s*=/m', $localized_data ) );
	}

	/**
	 * Repeated print hooks add each legacy global at most once.
	 *
	 * @return void
	 */
	public function test_admin_vars_are_deduplicated_independently() {
		PUM_Admin_Assets::init();
		PUM_Admin_Assets::register_admin_scripts();

		wp_localize_script( 'pum-admin-general', 'pum_admin_vars', [ 'existing' => true ] );
		wp_enqueue_script( 'pum-admin-general' );

		PUM_Admin_Assets::maybe_localize_and_templates();
		PUM_Admin_Assets::maybe_localize_and_templates();

		$localized_data = wp_scripts()->get_data( 'pum-admin-general', 'data' );

		$this->assertIsString( $localized_data );
		$this->assertSame( 1, preg_match_all( '/(?:^|[;\r\n])\s*var\s+pum_admin_vars\s*=/m', $localized_data ) );
		$this->assertSame( 1, preg_match_all( '/(?:^|[;\r\n])\s*var\s+pum_admin\s*=/m', $localized_data ) );
	}

	/**
	 * Test cleanup preserves WordPress-owned script registrations.
	 *
	 * @return void
	 */
	public function test_wordpress_color_picker_registration_is_preserved() {
		$this->assertTrue( wp_script_is( 'wp-color-picker', 'registered' ) );
	}
}
