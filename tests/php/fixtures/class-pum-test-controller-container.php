<?php
/**
 * Controller container test double.
 *
 * @package Popup_Maker
 */

/**
 * Minimal controller container used to inspect registration decisions.
 */
class PUM_Test_Controller_Container {

	/**
	 * @var array<string,object>
	 */
	public $registered = [];

	/**
	 * @param array<string,object> $controllers Registered controllers.
	 * @return void
	 */
	public function register_controllers( $controllers ) {
		$this->registered = $controllers;
	}
}
