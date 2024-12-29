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
class Admin extends Controller {

	/**
	 * Initialize admin controller.
	 */
	public function init() {
		$this->container->register_controllers( [
			'Admin\Toolbar'        => new \PopupMaker\Controllers\Admin\Toolbar( $this->container ),
			'Admin\WP\PluginsPage' => new \PopupMaker\Controllers\Admin\WP\PluginsPage( $this->container ),
			'Admin\CallToActions'  => new \PopupMaker\Controllers\Admin\CallToActions( $this->container ),
		] );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Enqueue admin assets.
	 */
	public function enqueue_admin_assets() {
		if ( ! is_admin() ) {
			return;
		}

		wp_enqueue_style( 'popup-maker-admin-marketing' );
		wp_enqueue_script( 'popup-maker-admin-marketing' );
	}
}
