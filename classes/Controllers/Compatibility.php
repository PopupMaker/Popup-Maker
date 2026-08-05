<?php
/**
 * Compatibility controller.
 *
 * @copyright (c) 2024, Code Atlantic LLC.
 *
 * @package PopupMaker
 */

namespace PopupMaker\Controllers;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Compatibility controller class.
 *
 * @since 1.21.0
 */
class Compatibility extends Controller {

	/**
	 * Initialize admin controller.
	 *
	 * @return void
	 */
	public function init() {
		$this->container->register_controllers( [
			'Compatibility\Backcompat\Filters'     => new \PopupMaker\Controllers\Compatibility\Backcompat\Filters( $this->container ),
			'Compatibility\SEO\Yoast'              => new \PopupMaker\Controllers\Compatibility\SEO\Yoast( $this->container ),
			'Compatibility\Builder\Divi'           => new \PopupMaker\Controllers\Compatibility\Builder\Divi( $this->container ),
			'Compatibility\Builder\Elementor'      => new \PopupMaker\Controllers\Compatibility\Builder\Elementor( $this->container ),
			'Compatibility\Plugin\ACF'             => new \PopupMaker\Controllers\Compatibility\Plugin\ACF( $this->container ),
			'Compatibility\Plugin\PostDuplication' => new \PopupMaker\Controllers\Compatibility\Plugin\PostDuplication( $this->container ),
		] );
	}
}
