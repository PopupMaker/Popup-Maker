<?php
/**
 * Debug class
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Controllers;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Debug controller class.
 *
 * @package PopupMaker\Controllers\Debug
 */
class Debug extends Controller {

	/**
	 * Initialize admin controller.
	 */
	public function init() {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		if ( ! defined( 'POPUP_MAKER_DEBUG' ) || ! POPUP_MAKER_DEBUG ) {
			return;
		}

		add_action( 'admin_head', [ $this, 'admin_head' ], 0 );
	}

	/**
	 * Enqueue admin assets.
	 */
	public function admin_head() {
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
		<script crossOrigin="anonymous" src="//unpkg.com/react-scan/dist/auto.global.js" />
		<?php
	}
}
