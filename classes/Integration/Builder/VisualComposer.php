<?php
/**
 * VisualComposer Builder for Integration
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */
class PUM_Integration_Builder_VisualComposer extends PUM_Abstract_Integration {

	/**
	 * $key variable
	 *
	 * @var string
	 */
	public $key = 'visualcomposer';

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
		return 'Visual Composer';
	}

	/**
	 * Function enabled() for Visual Composer.
	 *
	 * @return bool
	 */
	public function enabled() {
		return defined( 'WPB_VC_VERSION' ) || defined( 'FL_BUILDER_VERSION' );
	}

}
