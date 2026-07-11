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
	 * Version of react-scan to load. Pinned so the Subresource Integrity hash
	 * below stays valid; bump both together when updating.
	 *
	 * @var string
	 */
	const REACT_SCAN_VERSION = '0.5.7';

	/**
	 * SRI hash for react-scan REACT_SCAN_VERSION auto.global.js.
	 *
	 * @var string
	 */
	const REACT_SCAN_SRI = 'sha384-DDZCsimcjpG92OUulxf7DHi4rGS/fNIW7lC5DT8+5ftaTDiUKfzIq+pDTUbPjC86';

	/**
	 * Print the react-scan dev profiler (pinned + SRI over HTTPS).
	 */
	public function admin_head() {
		$src = sprintf(
			'https://unpkg.com/react-scan@%s/dist/auto.global.js',
			self::REACT_SCAN_VERSION
		);

		printf(
			// External script needs integrity/crossorigin, so it can't use wp_enqueue_script.
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
			'<script src="%s" integrity="%s" crossorigin="anonymous"></script>' . "\n",
			esc_url( $src ),
			esc_attr( self::REACT_SCAN_SRI )
		);
	}
}
