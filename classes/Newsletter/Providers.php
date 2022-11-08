<?php
/**
 * Newletter Providers
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Newsletter_Providers
 *
 * This class maintains a global set of all registered PUM newsletter providers.
 */
class PUM_Newsletter_Providers {

	/**
	 * $instance variable
	 *
	 * @var PUM_Newsletter_Providers
	 */
	public static $instance;

	/**
	 * $providers variable
	 *
	 * @var array
	 */
	public $providers = [];

	/**
	 * Function instance.
	 *
	 * @return PUM_Newsletter_Providers
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Adds provider.
	 */
	public function add_provider( PUM_Abstract_Provider $provider ) {
		$this->providers[ $provider->id ] = $provider;
	}

	/**
	 * Retrieves providers.
	 *
	 * @return array PUM_Shortcode
	 */
	public function get_providers() {
		return $this->providers;
	}

	/**
	 * Select list of Newsletter Providers.
	 */
	public static function selectlist() {
		$selectlist = [];

		foreach ( self::instance()->get_providers() as $id => $provider ) {
			$selectlist[ $provider->name ] = $id;
		}

		return $selectlist;
	}

	/**
	 * Dropdown list of Newsletter providers.
	 *
	 * @return array
	 */
	public static function dropdown_list() {
		$providers = self::instance()->get_providers();
		$list      = [];

		foreach ( $providers as $id => $provider ) {
			$list[ $id ] = $provider->name;
		}

		return $list;
	}


}
