<?php
/**
 * Admin class
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Controllers;

use PopupMaker\Base\Controller;
use PopupMaker\Controllers\Admin\CallToActions;

defined( 'ABSPATH' ) || exit;

/**
 * Admin controller class.
 *
 * @package PopupMaker\Controllers\Admin
 */
class Admin extends Controller {

	public function init() {
		$this->container->register_controllers( [
			'Admin\Toolbar' => new \PopupMaker\Controllers\Admin\Toolbar( $this->container ),
			// 'Admin\CallToActions' => new \PopupMaker\Controllers\Admin\CallToActions( $this->container ),
		] );
	}
}
