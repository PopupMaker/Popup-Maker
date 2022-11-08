<?php
/**
 * KingComposer Builder for Integration
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */
class PUM_Integration_Builder_KingComposer extends PUM_Abstract_Integration {

	/**
	 * $key variable
	 *
	 * @var string
	 */
	public $key = 'kingcomposer';

	/**
	 * $type variable
	 *
	 * @var string
	 */
	public $type = 'builder';

	/**
	 * Label function.
	 *
	 * @return string
	 */
	public function label() {
		return 'King Composer';
	}

	/**
	 * Function enabled() for King Composer.
	 *
	 * @return bool
	 */
	public function enabled() {
		return class_exists( 'KingComposer' ) || defined( 'KC_VERSION' );
	}

}
