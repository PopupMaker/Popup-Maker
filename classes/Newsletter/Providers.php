<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

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
	 * @var PUM_Newsletter_Providers
	 */
	public static $instance;

	/**
	 * @var array
	 */
	public $providers = array();

	/**
	 * @return PUM_Newsletter_Providers
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function add_provider( PUM_Abstract_Provider $provider ) {
		$this->providers[ $provider->id ] = $provider;
	}

	/**
	 * @return array PUM_Shortcode
	 */
	public function get_providers() {
		return $this->providers;
	}

	public static function selectlist() {
		$selectlist = array();

		foreach ( self::instance()->get_providers() as $id => $provider ) {
			$selectlist[ $provider->name ] = $id;
		}

		return $selectlist;
	}

	/**
	 * @return array
	 */
	public static function dropdown_list() {
		$providers = self::instance()->get_providers();
		$list  = array();

		foreach ( $providers as $id => $provider ) {
			$list[ $id ] = $provider->name;
		}

		return $list;
	}


}

