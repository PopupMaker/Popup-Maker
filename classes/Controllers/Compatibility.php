<?php
/**
 * Compatibility controller.
 *
 * @copyright (c) 2024, Code Atlantic LLC.
 *
 * @package PopupMaker
 */

namespace PopupMaker\Controllers;

use PopupMaker\Base\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Compatibility controller class.
 *
 * @package PopupMaker
 */
class Compatibility extends Controller {

	/**
	 * Initialize admin controller.
	 *
	 * @return void
	 */
	public function init() {
		$this->container->register_controllers( [
			// 'Compatibility\Backcompat\Filters' => new \PopupMaker\Controllers\Compatibility\BackCompat\Filters( $this->container ),
			// 'Compatibility\SEO\Yoast'          => new \PopupMaker\Controllers\Compatibility\SEO\Yoast( $this->container ),
		] );
	}
}
