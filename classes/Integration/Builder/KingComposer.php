<?php
/**
 * Integration for KingComposer
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

class PUM_Integration_Builder_KingComposer extends PUM_Abstract_Integration {

	/**
	 * @var string
	 */
	public $key = 'kingcomposer';

	/**
	 * @var string
	 */
	public $type = 'builder';

	/**
	 * @return string
	 */
	public function label() {
		return 'King Composer';
	}

	/**
	 * @return bool
	 */
	public function enabled() {
		return class_exists( 'KingComposer' ) || defined( 'KC_VERSION' );
	}

}
