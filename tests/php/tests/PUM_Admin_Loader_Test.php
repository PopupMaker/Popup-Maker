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
	 * Admin requests retain the complete admin bootstrap.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_admin_loads_all_admin_components() {
		set_current_screen( 'dashboard' );

		PUM_Admin::init();

		$this->assertTrue( is_admin() );
		$this->assertSame( 10, has_action( 'admin_menu', [ 'PUM_Admin_Pages', 'register_pages' ] ) );
		$this->assertSame( 10, has_action( 'wp_ajax_pum_object_search', [ 'PUM_Admin_Ajax', 'object_search' ] ) );
		$this->assertSame( 10, has_action( 'admin_init', [ 'PUM_Admin_Settings', 'save' ] ) );

		$container = new PUM_Test_Controller_Container();
		$admin     = new \PopupMaker\Controllers\Admin( $container );
		$admin->init();

		$this->assertArrayHasKey( 'Admin\Toolbar', $container->registered );
		$this->assertArrayHasKey( 'Admin\ToolbarNotifications', $container->registered );
		$this->assertArrayHasKey( 'Admin\WP\PluginsPage', $container->registered );
		$this->assertArrayHasKey( 'Admin\CallToActions', $container->registered );
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function test_frontend_loads_only_cross_context_admin_hooks() {
		$this->assertFalse( is_admin() );
		$this->assertSame( 10, has_action( 'save_post', [ 'PUM_Admin_Popups', 'save' ] ) );
		$this->assertSame( 10, has_action( 'save_post', [ 'PUM_Admin_Themes', 'save' ] ) );
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
