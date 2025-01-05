<?php
/**
 * WP class
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Controllers;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * WP controller class.
 *
 * @since X.X.X
 */
class WP extends Controller {

	/**
	 * Initialize admin controller.
	 */
	public function init() {
		$this->container->register_controllers( [
			'WP\Blocks' => new \PopupMaker\Controllers\WP\Blocks( $this->container ),
			'WP\I18n'   => new \PopupMaker\Controllers\WP\I18n( $this->container ),
		] );
	}
}
