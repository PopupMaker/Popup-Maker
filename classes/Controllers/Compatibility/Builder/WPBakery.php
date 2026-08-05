<?php
/**
 * WPBakery Page Builder compatibility controller.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Controllers\Compatibility\Builder;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Keep WPBakery's backend editor available for popups.
 *
 * WPBakery uses the classic editor screen. When Popup Maker enables the block
 * editor for popups, WordPress bypasses that screen and WPBakery cannot load.
 *
 * @since 1.24.0
 */
class WPBakery extends Controller {

	/**
	 * Initialize compatibility hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'use_block_editor_for_post_type', [ $this, 'force_classic_editor' ], 999, 2 );
	}

	/**
	 * Use the classic editor for popups when WPBakery is active.
	 *
	 * @param bool   $use_block_editor Whether to use the block editor.
	 * @param string $post_type        The post type being edited.
	 * @return bool Whether to use the block editor.
	 */
	public function force_classic_editor( $use_block_editor, $post_type ) {
		if ( 'popup' !== $post_type || ! $this->should_use_wpbakery_editor() ) {
			return $use_block_editor;
		}

		return false;
	}

	/**
	 * Check whether WPBakery Page Builder should edit popups.
	 *
	 * @return bool
	 */
	protected function should_use_wpbakery_editor() {
		if ( ! $this->is_wpbakery_active() ) {
			return false;
		}

		$post_types = $this->get_wpbakery_editor_post_types();

		return null === $post_types || in_array( 'popup', $post_types, true );
	}

	/**
	 * Check whether WPBakery Page Builder is active.
	 *
	 * @return bool
	 */
	protected function is_wpbakery_active() {
		return defined( 'WPB_VC_VERSION' );
	}

	/**
	 * Get post types enabled in WPBakery.
	 *
	 * @return string[]|null Enabled post types, or null for legacy versions.
	 */
	protected function get_wpbakery_editor_post_types() {
		if ( ! function_exists( 'vc_editor_post_types' ) ) {
			return null;
		}

		$post_types = vc_editor_post_types();

		return is_array( $post_types ) ? $post_types : [];
	}
}
