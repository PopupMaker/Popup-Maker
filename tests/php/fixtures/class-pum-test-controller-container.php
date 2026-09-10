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

	/**
	 * Mirror the real container's permission lookup using plugin defaults.
	 *
	 * @param string $cap Permission key.
	 * @return string
	 */
	public function get_permission( $cap ) {
		$permissions = \PopupMaker\get_default_permissions();

		return isset( $permissions[ $cap ] ) ? $permissions[ $cap ] : 'manage_options';
	}
}
