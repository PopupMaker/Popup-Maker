<?php
/**
 * Tests for context-aware legacy admin loading.
 *
 * @package Popup_Maker
 */

require_once dirname( __DIR__ ) . '/fixtures/class-pum-test-controller-container.php';

/**
 * Verify admin components load only in the request contexts that need them.
 */
class PUM_Admin_Loader_Test extends WP_UnitTestCase {

	/**
	 * Generic admin requests load shared components but defer screen components.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_generic_admin_loads_only_shared_admin_components() {
		global $pagenow;

		$pagenow = 'index.php';
		set_current_screen( 'dashboard' );
		remove_all_actions( 'admin_menu' );

		PUM_Admin::init();
		$this->assertSame( 20, has_action( 'admin_init', [ 'PUM_Admin_Shortcode_UI', 'init_editor' ] ) );
		$this->assertTrue( class_exists( 'PUM_Admin_Shortcode_UI', false ) );
		do_action( 'admin_menu' );

		$this->assertTrue( is_admin() );
		$this->assertSame( 10, has_action( 'admin_menu', [ 'PUM_Admin_Pages', 'register_pages' ] ) );
		$this->assertSame( 10, has_action( 'wp_ajax_pum_object_search', [ 'PUM_Admin_Ajax', 'object_search' ] ) );
		$this->assertSame( 10, has_action( 'save_post', [ 'PUM_Admin_Popups', 'save' ] ) );
		$this->assertSame( 10, has_action( 'save_post', [ 'PUM_Admin_Themes', 'save' ] ) );
		$this->assertFalse( has_action( 'admin_init', [ 'PUM_Admin_Settings', 'save' ] ) );
		$this->assertFalse( class_exists( 'PUM_Admin_Settings', false ) );
		$this->assertSame( 10, has_action( 'pum_save_enabled_betas', [ 'PUM_Admin_Tools', 'save_enabled_betas' ] ) );
		$this->assertSame( 10, has_action( 'pum_empty_error_log', [ 'PUM_Admin_Tools', 'error_log_empty' ] ) );
		$this->assertFalse( class_exists( 'PUM_Admin_Tools', false ) );
		$this->assertSame( 20, has_action( 'admin_init', [ 'PUM_Admin_Shortcode_UI', 'init_editor' ] ) );
		$this->assertTrue( class_exists( 'PUM_Admin_Shortcode_UI', false ) );
		$this->assertFalse( class_exists( 'PUM_Upsell', false ) );

		$container = new PUM_Test_Controller_Container();
		$admin     = new \PopupMaker\Controllers\Admin( $container );
		$admin->init();

		$this->assertArrayHasKey( 'Admin\Toolbar', $container->registered );
		$this->assertArrayHasKey( 'Admin\ToolbarNotifications', $container->registered );
		$this->assertArrayHasKey( 'Admin\WP\PluginsPage', $container->registered );
		$this->assertArrayHasKey( 'Admin\CallToActions', $container->registered );
	}

	/**
	 * Tools request callbacks exist before the init request dispatcher runs.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_tools_request_actions_register_before_init_dispatch() {
		global $pagenow;

		$pagenow = 'index.php';
		set_current_screen( 'dashboard' );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		PUM_Utils_Options::delete( 'enabled_betas' );

		remove_action( 'pum_save_enabled_betas', [ 'PUM_Admin_Tools', 'save_enabled_betas' ] );
		remove_action( 'pum_empty_error_log', [ 'PUM_Admin_Tools', 'error_log_empty' ] );

		PUM_Admin::init();

		$this->assertFalse( class_exists( 'PUM_Admin_Tools', false ) );
		$this->assertSame( 10, has_action( 'pum_save_enabled_betas', [ 'PUM_Admin_Tools', 'save_enabled_betas' ] ) );

		$_REQUEST['pum_action']        = 'save_enabled_betas';
		$_POST['pum_save_betas_nonce'] = wp_create_nonce( 'pum_save_betas_nonce' );
		$_POST['enabled_betas']        = [ 'example-extension' => 'true' ];

		PUM_Site::actions();

		$this->assertSame( [ 'example-extension' => true ], pum_get_option( 'enabled_betas' ) );
	}

	/**
	 * Popup save callbacks exist before earlier init callbacks can write posts.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_popup_save_hooks_register_before_init_writers() {
		global $pagenow;

		$pagenow = 'index.php';
		set_current_screen( 'dashboard' );
		remove_action( 'save_post', [ 'PUM_Admin_Popups', 'save' ], 10 );
		remove_filter( 'wp_insert_post_data', [ 'PUM_Admin_Popups', 'set_slug' ], 99 );
		remove_action( 'save_post', [ 'PUM_Admin_Themes', 'save' ], 10 );

		PUM_Admin::init();

		$this->assertSame( 10, has_action( 'save_post', [ 'PUM_Admin_Popups', 'save' ] ) );
		$this->assertSame( 99, has_filter( 'wp_insert_post_data', [ 'PUM_Admin_Popups', 'set_slug' ] ) );
		$this->assertSame( 10, has_action( 'save_post', [ 'PUM_Admin_Themes', 'save' ] ) );
	}

	/**
	 * Request components use the single filtered admin_menu definition pass.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_request_components_use_single_admin_menu_definition_pass() {
		global $pagenow;

		$pagenow = 'index.php';
		set_current_screen( 'dashboard' );
		remove_all_actions( 'admin_menu' );
		$filter_calls = 0;
		$page_filter  = function ( $pages ) use ( &$filter_calls ) {
			++$filter_calls;

			return $pages;
		};

		add_filter( 'pum_admin_pages', $page_filter );

		PUM_Admin::init();

		$this->assertSame( 0, $filter_calls );
		$this->assertFalse( has_action( 'init', [ 'PUM_Admin', 'init_request_components' ] ) );

		do_action( 'admin_menu' );

		$this->assertSame( 1, $filter_calls );
		$this->assertTrue( class_exists( 'PUM_Admin_Shortcode_UI', false ) );
	}

	/**
	 * Premium previews remain available when registries initialize before admin_menu.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_preview_registry_hooks_exist_before_screen_ui_initializes() {
		global $pagenow;

		$pagenow = 'index.php';
		set_current_screen( 'dashboard' );
		remove_all_actions( 'admin_menu' );
		remove_all_filters( 'pum_registered_triggers' );
		remove_all_filters( 'pum_registered_conditions' );

		PUM_Admin::init();

		$this->assertFalse( class_exists( 'PUM_Upsell', false ) );
		$this->assertSame( 10, has_filter( 'pum_registered_triggers', [ 'PUM_Upsell', 'register_preview_triggers' ] ) );
		$this->assertSame( 10, has_filter( 'pum_registered_conditions', [ 'PUM_Upsell', 'register_preview_conditions' ] ) );
		$this->assertFalse( has_action( 'in_admin_header', [ 'PUM_Upsell', 'notice_bar_display' ] ) );

		$triggers   = PUM_Triggers::instance()->get_triggers();
		$conditions = PUM_Conditions::instance()->get_conditions();

		$this->assertArrayHasKey( 'exit_intent', $triggers );
		$this->assertArrayHasKey( 'user_is_logged_in', $conditions );
		$this->assertTrue( $triggers['exit_intent']['pro_required'] );
		$this->assertTrue( $conditions['user_is_logged_in']['pro_required'] );
	}

	/**
	 * Request matching uses the same translated definitions as menu registration.
	 *
	 * @return void
	 */
	public function test_page_slug_resolution_uses_translated_filter_input() {
		$translate = function ( $translation, $text, $domain ) {
			return 'popup-maker' === $domain && 'Settings' === $text ? 'Einstellungen' : $translation;
		};
		$pages     = function ( $definitions ) {
			$definitions['settings']['menu_slug'] = sanitize_title( $definitions['settings']['page_title'] );

			return $definitions;
		};

		add_filter( 'gettext', $translate, 10, 3 );
		add_filter( 'pum_admin_pages', $pages );

		$page_slugs = PUM_Admin_Pages::get_page_slugs( PUM_Admin_Pages::get_page_definitions() );

		remove_filter( 'pum_admin_pages', $pages );
		remove_filter( 'gettext', $translate, 10 );

		$this->assertSame( 'einstellungen', $page_slugs['settings'] );
	}

	/**
	 * Admin AJAX keeps the shortcode preview callback available without the editor UI.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_admin_ajax_registers_shortcode_preview_callback_without_editor_ui() {
		global $pagenow;

		$pagenow = 'admin-ajax.php';
		set_current_screen( 'admin-ajax' );
		$page_filter_calls = 0;
		$page_filter       = function ( $pages ) use ( &$page_filter_calls ) {
			++$page_filter_calls;

			return $pages;
		};

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'pum_admin_pages', $page_filter );

		PUM_Admin::init();

		remove_filter( 'pum_admin_pages', $page_filter );
		remove_filter( 'wp_doing_ajax', '__return_true' );

		$this->assertSame( 0, $page_filter_calls );
		$this->assertSame( 10, has_action( 'wp_ajax_pum_do_shortcode', [ 'PUM_Admin_Shortcode_UI', 'do_shortcode' ] ) );
		$this->assertSame( 10, has_action( 'wp_ajax_pum_get_css_styles', [ 'PUM_Admin_Settings', 'ajax_get_css_styles' ] ) );
		$this->assertFalse( class_exists( 'PUM_Admin_Shortcode_UI', false ) );
		$this->assertFalse( class_exists( 'PUM_Admin_Settings', false ) );
	}

	/**
	 * Popup Maker settings requests initialize their screen-specific callbacks.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_settings_request_loads_settings_and_upsell_components() {
		global $pagenow;

		$pagenow      = 'edit.php';
		$_GET['page'] = 'pum-settings';
		set_current_screen( 'popup_page_pum-settings' );
		remove_all_actions( 'admin_menu' );

		PUM_Admin::init();
		do_action( 'admin_menu' );

		$this->assertSame( 10, has_action( 'admin_init', [ 'PUM_Admin_Settings', 'save' ] ) );
		$this->assertSame( 10, has_action( 'in_admin_header', [ 'PUM_Upsell', 'notice_bar_display' ] ) );
		$this->assertTrue( class_exists( 'PUM_Admin_Settings', false ) );
		$this->assertTrue( class_exists( 'PUM_Upsell', false ) );
	}

	/**
	 * Filtered page slugs retain their screen-specific callbacks.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_filtered_settings_page_slug_loads_settings_and_upsell_components() {
		global $pagenow;

		add_filter(
			'pum_admin_pages',
			function ( $pages ) {
				$pages['settings']['menu_slug'] = 'custom-popup-settings';

				return $pages;
			}
		);

		$pagenow      = 'edit.php';
		$_GET['page'] = 'custom-popup-settings';
		set_current_screen( 'popup_page_custom-popup-settings' );
		remove_all_actions( 'admin_menu' );

		PUM_Admin::init();
		do_action( 'admin_menu' );

		$this->assertSame( 10, has_action( 'admin_init', [ 'PUM_Admin_Settings', 'save' ] ) );
		$this->assertSame( 10, has_action( 'in_admin_header', [ 'PUM_Upsell', 'notice_bar_display' ] ) );
		$this->assertTrue( class_exists( 'PUM_Admin_Settings', false ) );
		$this->assertTrue( class_exists( 'PUM_Upsell', false ) );
	}

	/**
	 * Late page filters initialize handlers before the normal admin_init pass.
	 *
	 * WordPress loads wp-admin/menu.php, which fires admin_menu, before firing
	 * admin_init in wp-admin/admin.php.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_admin_menu_page_filter_initializes_without_manual_request_replay() {
		global $pagenow;

		$pagenow           = 'edit.php';
		$_GET['page']      = 'late-popup-admin';
		$_GET['post_type'] = 'popup';
		set_current_screen( 'popup_page_late-popup-admin' );
		remove_all_actions( 'admin_menu' );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$_POST['pum_settings_nonce'] = wp_create_nonce( 'pum_settings_nonce' );
		$_POST['pum_settings']       = [ 'google_fonts_api_key' => 'late-filter-key' ];
		$save_calls                  = 0;
		$page_filter_calls           = 0;

		add_action(
			'pum_save_settings',
			function () use ( &$save_calls ) {
				++$save_calls;
			}
		);

		add_action(
			'admin_menu',
			function () use ( &$page_filter_calls ) {
				add_filter(
					'pum_admin_pages',
					function ( $pages ) use ( &$page_filter_calls ) {
						++$page_filter_calls;
						$pages['settings']['menu_slug'] = 'late-popup-admin';
						$pages['tools']['menu_slug']    = 'late-popup-admin';

						return $pages;
					}
				);
			},
			1
		);

		PUM_Admin::init();
		do_action( 'admin_menu' );

		$this->assertSame( 10, has_action( 'admin_init', [ 'PUM_Admin_Settings', 'save' ] ) );
		$this->assertSame( 10, has_action( 'admin_init', [ 'PUM_Admin_Tools', 'emodal_process_import' ] ) );
		$this->assertTrue( class_exists( 'PUM_Admin_Settings', false ) );
		$this->assertTrue( class_exists( 'PUM_Admin_Tools', false ) );
		$this->assertArrayHasKey( 'settings', PUM_Admin_Pages::$pages );
		$this->assertArrayHasKey( 'tools', PUM_Admin_Pages::$pages );
		$this->assertSame( 1, $page_filter_calls );
		$this->assertSame( 0, $save_calls );
		$this->assertNotSame( 'late-filter-key', pum_get_option( 'google_fonts_api_key' ) );

		do_action( 'admin_init' );

		$this->assertSame( 1, $save_calls );
		$this->assertSame( 'late-filter-key', pum_get_option( 'google_fonts_api_key' ) );
	}

	/**
	 * Shortcode editor integration remains available on non-post editor screens.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider editor_screen_provider
	 *
	 * @param string $screen_file Admin screen filename.
	 *
	 * @return void
	 */
	public function test_shortcode_ui_supports_nonstandard_editor_screens( $screen_file ) {
		global $pagenow;

		$pagenow = $screen_file;
		set_current_screen( sanitize_key( $screen_file ) );
		remove_all_actions( 'admin_menu' );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		update_user_option( get_current_user_id(), 'rich_editing', 'true' );

		PUM_Admin::init();
		do_action( 'admin_menu' );
		do_action( 'admin_init' );

		$this->assertSame( 10, has_filter( 'mce_buttons', [ 'PUM_Admin_Shortcode_UI', 'mce_buttons' ] ) );
		$this->assertSame( 10, has_filter( 'mce_external_plugins', [ 'PUM_Admin_Shortcode_UI', 'mce_external_plugins' ] ) );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public function editor_screen_provider() {
		return [
			'site editor'   => [ 'site-editor.php' ],
			'widgets'       => [ 'widgets.php' ],
			'plugin editor' => [ 'vendor-editor.php' ],
		];
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		unset( $_GET['page'] );
		unset( $_GET['post_type'] );
		unset( $_POST['pum_settings_nonce'], $_POST['pum_settings'] );

		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Frontend bootstrap defers the heavy editor classes until their save hooks run.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_frontend_loads_only_cross_context_admin_hooks() {
		$this->assertFalse( is_admin() );
		$this->assertSame( 10, has_action( 'save_post', [ 'PUM_Admin_Popups', 'save' ] ) );
		$this->assertSame( 10, has_action( 'save_post', [ 'PUM_Admin_Themes', 'save' ] ) );
		$this->assertSame( 99, has_filter( 'wp_insert_post_data', [ 'PUM_Admin_Popups', 'set_slug' ] ) );
		$this->assertSame( 10, has_action( 'enqueue_block_assets', [ 'PUM_Admin_BlockEditor', 'register_block_assets' ] ) );
		$this->assertFalse( has_action( 'admin_menu', [ 'PUM_Admin_Pages', 'register_pages' ] ) );
		$this->assertFalse( has_action( 'wp_ajax_pum_object_search', [ 'PUM_Admin_Ajax', 'object_search' ] ) );
		$this->assertSame( 10, has_filter( 'user_has_cap', [ 'PUM_Admin', 'prevent_default_theme_deletion' ] ) );
	}

	/**
	 * @return void
	 */
	public function test_frontend_admin_controllers_load_only_for_toolbar_capable_users() {
		$anonymous_container = new PUM_Test_Controller_Container();
		$anonymous_admin     = new \PopupMaker\Controllers\Admin( $anonymous_container );
		$anonymous_admin->init();

		$this->assertSame( [], $anonymous_container->registered );

		// Logged-in users without popup capabilities never see the toolbar.
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'subscriber' ] ) );

		$subscriber_container = new PUM_Test_Controller_Container();
		$subscriber_admin     = new \PopupMaker\Controllers\Admin( $subscriber_container );
		$subscriber_admin->init();

		$this->assertSame( [], $subscriber_container->registered );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$logged_in_container = new PUM_Test_Controller_Container();
		$logged_in_admin     = new \PopupMaker\Controllers\Admin( $logged_in_container );
		$logged_in_admin->init();

		$this->assertArrayHasKey( 'Admin\Toolbar', $logged_in_container->registered );
		$this->assertArrayHasKey( 'Admin\ToolbarNotifications', $logged_in_container->registered );
		$this->assertArrayNotHasKey( 'Admin\WP\PluginsPage', $logged_in_container->registered );
		$this->assertArrayNotHasKey( 'Admin\CallToActions', $logged_in_container->registered );
	}
}
