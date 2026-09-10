<?php
/**
 * Tests for the assets controller.
 *
 * @package Popup_Maker
 */

/**
 * Verify expensive localized values are resolved only when needed.
 */
class Assets_Controller_Test extends WP_UnitTestCase {

	/**
	 * @return void
	 */
	public function test_block_editor_localized_values_are_deferred() {
		$assets   = \PopupMaker\plugin()->get_controller( 'Assets' );
		$packages = $assets->get_packages();

		$this->assertIsCallable( $packages['block-editor']['vars'] );

		$vars = call_user_func( $packages['block-editor']['vars'] );

		$this->assertSame( home_url(), $vars['homeUrl'] );
		$this->assertSame( pum_get_all_popups(), $vars['popups'] );
		$this->assertArrayHasKey( 'cta_types', $vars );
		$this->assertArrayHasKey( 'previewNonce', $vars );
		$this->assertArrayHasKey( 'popupTriggerExcludedBlocks', $vars );
	}

	/**
	 * @return void
	 */
	public function test_components_localize_only_popup_ids_and_titles() {
		$published_id = self::factory()->post->create(
			[
				'post_type'    => 'popup',
				'post_status'  => 'publish',
				'post_title'   => 'Published popup',
				'post_content' => 'Content must not be localized.',
			]
		);

		$draft_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
				'post_title'  => 'Draft popup',
			]
		);

		$assets   = \PopupMaker\plugin()->get_controller( 'Assets' );
		$packages = $assets->get_packages();
		$vars     = call_user_func( $packages['components']['vars'] );

		$this->assertContains(
			[
				'ID'         => $published_id,
				'post_title' => 'Published popup',
			],
			$vars['popups']
		);

		$this->assertNotContains(
			[
				'ID'         => $draft_id,
				'post_title' => 'Draft popup',
			],
			$vars['popups']
		);

		foreach ( $vars['popups'] as $popup ) {
			$this->assertSame( [ 'ID', 'post_title' ], array_keys( $popup ) );
		}
	}

	/**
	 * Authorized admins retain private popups that are valid on the frontend.
	 *
	 * @return void
	 */
	public function test_components_preserve_private_popup_choices_for_authorized_admins() {
		$previous_user_id = get_current_user_id();
		$admin_user_id    = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$private_id       = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'private',
				'post_title'  => 'Private popup',
			]
		);

		wp_set_current_user( $admin_user_id );

		try {
			$assets   = \PopupMaker\plugin()->get_controller( 'Assets' );
			$packages = $assets->get_packages();
			$vars     = call_user_func( $packages['components']['vars'] );
		} finally {
			wp_set_current_user( $previous_user_id );
		}

		$this->assertContains(
			[
				'ID'         => $private_id,
				'post_title' => 'Private popup',
			],
			$vars['popups']
		);
	}

	/**
	 * Existing localized-variable filters retain the original popup models.
	 *
	 * @return void
	 */
	public function test_components_preserve_popup_models_for_existing_filters() {
		self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		$expected = pum_get_all_popups();
		$filter   = function ( $vars ) {
			return $vars;
		};

		add_filter( 'popup_maker/components_localized_vars', $filter, 0 );

		$assets   = \PopupMaker\plugin()->get_controller( 'Assets' );
		$packages = $assets->get_packages();
		$vars     = call_user_func( $packages['components']['vars'] );

		remove_filter( 'popup_maker/components_localized_vars', $filter, 0 );

		$this->assertSame( $expected, $vars['popups'] );
	}

	/**
	 * Query-level title filters are retained in lightweight popup choices.
	 *
	 * @return void
	 */
	public function test_components_preserve_filtered_popup_titles() {
		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Stored popup title',
			]
		);

		$filter = function ( $posts ) use ( $popup_id ) {
			foreach ( $posts as $post ) {
				if ( $post instanceof WP_Post && $popup_id === (int) $post->ID ) {
					$post->post_title = 'Filtered popup title';
				}
			}

			return $posts;
		};

		add_filter( 'posts_results', $filter );

		$assets   = \PopupMaker\plugin()->get_controller( 'Assets' );
		$packages = $assets->get_packages();
		$vars     = call_user_func( $packages['components']['vars'] );

		remove_filter( 'posts_results', $filter );

		$this->assertContains(
			[
				'ID'         => $popup_id,
				'post_title' => 'Filtered popup title',
			],
			$vars['popups']
		);
	}

	/**
	 * Shared popup-title filters reach the components payload.
	 *
	 * @return void
	 */
	public function test_components_preserve_shared_popup_title_filters() {
		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Original popup title',
			]
		);

		$title_filter = static function ( $titles ) use ( $popup_id ) {
			$titles[ $popup_id ] = 'Translated popup title';

			return $titles;
		};
		$query_filter = static function ( $posts ) {
			return $posts;
		};

		add_filter( 'popup_maker/popup_title_choices', $title_filter );
		add_filter( 'posts_results', $query_filter );

		try {
			$assets   = \PopupMaker\plugin()->get_controller( 'Assets' );
			$packages = $assets->get_packages();
			$vars     = call_user_func( $packages['components']['vars'] );
		} finally {
			remove_filter( 'posts_results', $query_filter );
			remove_filter( 'popup_maker/popup_title_choices', $title_filter );
		}

		$this->assertContains(
			[
				'ID'         => $popup_id,
				'post_title' => 'Translated popup title',
			],
			$vars['popups']
		);
	}

	/**
	 * @return void
	 */
	public function test_package_vars_are_localized_once_after_late_admin_filters() {
		$filter_calls = 0;
		$assets       = \PopupMaker\plugin()->get_controller( 'Assets' );

		wp_deregister_script( 'popup-maker-components' );
		wp_register_script( 'popup-maker-components', 'https://example.com/components.js', [], '1.0.0', true );
		wp_enqueue_script( 'popup-maker-components' );
		$this->assertFalse( wp_scripts()->get_data( 'popup-maker-components', 'data' ) );

		$filter = function ( $vars ) use ( &$filter_calls ) {
			++$filter_calls;

			return $vars;
		};

		$register_filter = function () use ( $filter ) {
			add_filter( 'popup_maker/components_localized_vars', $filter );
		};

		$this->assertSame( 1, has_action( 'admin_print_scripts', [ $assets, 'autoload_styles_for_scripts' ] ) );
		$this->assertSame( 1, has_action( 'wp_print_scripts', [ $assets, 'autoload_styles_for_scripts' ] ) );

		add_action( 'admin_print_scripts', $register_filter, 10 );
		do_action( 'admin_print_scripts' );
		do_action( 'wp_print_scripts' );
		remove_action( 'admin_print_scripts', $register_filter, 10 );

		remove_filter( 'popup_maker/components_localized_vars', $filter );

		$this->assertSame( 1, $filter_calls );

		$localized_data = wp_scripts()->get_data( 'popup-maker-components', 'data' );

		$this->assertIsString( $localized_data );
		$this->assertSame( 1, substr_count( $localized_data, 'var popupMakerComponents' ) );
	}
}
