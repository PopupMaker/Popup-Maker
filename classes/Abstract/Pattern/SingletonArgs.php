<?php
/******************************************************************************
 * @Copyright (c) 2018, Code Atlantic                                        *
 ******************************************************************************/

namespace ForumWP\Abstracts\Pattern;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Singleton
 *
 * This is just a pattern so no new Singleton is allowed thanks to abstract definition
 *
 * Usage Notes:
 * 1. Must declare protected static $instance in each child class.
 * 2. init method should not be static, and is run in the context of $this.
 *
 * @property Singleton|null $instance
 * @package ForumWP\Abstracts\Pattern
 */
abstract class SingletonArgs {

	/**
	 * NOTE: no static $instance declaration this makes next declaration a must have for any extended class protected static $instance;
	 *
	 * protected static $instance;
	 */

	/**
	 * Singleton constructor.
	 *
	 * @param array $args
	 *
	 * @throws \Exception
	 */
	final private function __construct( $args = array() ) {
		// if called twice ....
		if ( isset( static::$instance ) ) {
			// throws an Exception
			throw new Exception( "An instance of " . get_called_class() . " already exists." );
		}

		// init method via magic static keyword ($this injected)
		static::init( $args );
	}

	/**
	 * by default there must be an inherited init method
	 * so an extended class could simply
	 * specify its own init
	 *
	 * @param array $args
	 */
	protected function init( $args = array() ) {
	}

	/**
	 * The common sense method to retrieve the instance
	 *
	 * @return static
	 */
	final public static function instance() {
		// ternary operator is that fast!
		try {
			return isset( static::$instance ) ? static::$instance : static::$instance = new static;
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
}
