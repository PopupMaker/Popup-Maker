<?php
/**
 * Compatibility with Yoast SEO.
 *
 * @package PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Controllers\Compatibility\SEO;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Class Yoast
 *
 * @since 1.21.0
 */
class Yoast extends Controller {

	/**
	 * Is Yoast active?
	 *
	 * @return bool
	 */
	public function controller_enabled() {
		return function_exists( '\YoastSEO' );
	}

	/**
	 * Init controller.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'popup_maker/popup_post_type_args', [ $this, 'filter_post_type_args' ] );
		add_filter( 'wpseo_accessible_post_types', [ $this, 'yoast_sitemap_fix' ] );
	}

	/**
	 * Filter post type args to prevent Yoast from indexing popups.
	 *
	 * @param array $popup_args Popup args.
	 *
	 * @return array
	 */
	public function filter_post_type_args( $popup_args ) {
		// Temporary Yoast Fixes
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( is_admin() && isset( $_GET['page'] ) && 'wpseo_titles' === $_GET['page'] ) {
			$popup_args['public'] = false;
		}

		return $popup_args;
	}

	/**
	 * Remove popups from accessible post type list in Yoast.
	 *
	 * @param array $post_types Post types.
	 *
	 * @return array
	 */
	public function yoast_sitemap_fix( $post_types = [] ) {
		unset( $post_types['popup'] );

		return $post_types;
	}
}
