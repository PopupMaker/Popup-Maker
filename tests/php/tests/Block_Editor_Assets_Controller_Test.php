<?php
/**
 * Tests for block editor popup asset data.
 *
 * @package Popup_Maker
 */

/**
 * Verify block editor popup choices stay lightweight and compatible.
 */
class PUM_Block_Editor_Assets_Controller_Test extends WP_UnitTestCase {

	/**
	 * Existing block editor variable filters retain the original popup models.
	 *
	 * @return void
	 */
	public function test_block_editor_preserves_popup_models_for_existing_filters() {
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

		add_filter( 'popup_maker/block-editor_localized_vars', $filter, 0 );

		$assets   = \PopupMaker\plugin()->get_controller( 'Assets' );
		$packages = $assets->get_packages();
		$vars     = call_user_func( $packages['block-editor']['vars'] );

		remove_filter( 'popup_maker/block-editor_localized_vars', $filter, 0 );

		$this->assertSame( $expected, $vars['popups'] );
	}

	/**
	 * Authorized editors retain private popups in the viewer title data.
	 *
	 * @return void
	 */
	public function test_block_editor_preserves_private_popup_choices_for_authorized_editors() {
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
			$vars     = call_user_func( $packages['block-editor']['vars'] );
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
	 * Shared title-choice filters are retained in lightweight popup choices.
	 *
	 * @return void
	 */
	public function test_block_editor_preserves_filtered_popup_titles() {
		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Stored popup title',
			]
		);

		$filter = function ( $titles ) use ( $popup_id ) {
			$titles[ $popup_id ] = 'Filtered popup title';

			return $titles;
		};

		add_filter( 'popup_maker/popup_title_choices', $filter );

		$assets   = \PopupMaker\plugin()->get_controller( 'Assets' );
		$packages = $assets->get_packages();
		$vars     = call_user_func( $packages['block-editor']['vars'] );

		remove_filter( 'popup_maker/popup_title_choices', $filter );

		$this->assertContains(
			[
				'ID'         => $popup_id,
				'post_title' => 'Filtered popup title',
			],
			$vars['popups']
		);
	}
}
