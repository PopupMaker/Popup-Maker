<?php
/**
 * Admin class
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Controllers;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Admin controller class.
 *
 * @package PopupMaker\Controllers\Admin
 */
class Frontend extends Controller {

	/**
	 * Initialize admin controller.
	 */
	public function init() {
		$this->container->register_controllers( [
			'Frontend\Popups' => new \PopupMaker\Controllers\Frontend\Popups( $this->container ),
		] );

		// add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Enqueue admin assets.
	 */
	public function enqueue_admin_assets() {
		if ( is_admin() ) {
			return;
		}

		// wp_enqueue_style( 'popup-maker-admin-marketing' );
		// wp_enqueue_script( 'popup-maker-admin-marketing' );
	}
}
