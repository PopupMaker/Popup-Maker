<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Abstract_Pattern_Singleton
 *
 * This is just a pattern so no new PUM_Abstract_Pattern_Singleton is allowed thanks to abstract definition
 *
 * Usage Notes:
 * 1. Must declare protected static $instance in each child class.
 * 2. init method should not be static, and is run in the context of $this.
 *
 * @property PUM_Abstract_Pattern_Singleton|null $instance
 * @package ForumWP\Abstracts\Pattern
 */
abstract class PUM_Abstract_Pattern_Singleton {

	/**
	 * PUM_Abstract_Pattern_Singleton constructor.
	 *
	 * @throws Exception
	 */
	final private function __construct() {
		$class = get_called_class();

		// if called twice ....
		if ( isset( self::$instances[ $class ] ) ) {
			// throws an Exception
			throw new Exception( "An instance of " . $class . " already exists." );
		}

		// init method via magic static keyword ($this injected)
		static::init();
	}

	/**
	 * by default there must be an inherited init method
	 * so an extended class could simply
	 * specify its own init
	 */
	protected function init() {
	}

	/**
	 * The common sense method to retrieve the instance
	 *
	 * @return static
	 */
	final public static function instance() {
		$class = get_called_class();
		// ternary operator is that fast!
		try {
			return isset( self::$instances[ $class ] ) ? self::$instances[ $class ] : self::$instances[ $class ] = new static;
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * No clone allowed, both internally and externally
	 *
	 * @throws Exception
	 */
	final private function __clone() {
		throw new Exception( "An instance of " . get_called_class() . " cannot be cloned." );
	}

	/**
	 * NOTE: no static $instance declaration this makes next declaration a must have for any extended class protected static $instance;
	 */
	private static $instances = array();


}
