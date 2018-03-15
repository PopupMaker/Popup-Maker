<?php

// SPL can be disabled on PHP 5.2
if ( ! function_exists( 'spl_autoload_register' ) ) {
	$_wp_spl_autoloaders = array();

	/**
	 * Registers a function to be autoloaded.
	 *
	 * @since 4.6.0
	 *
	 * @param callable $autoload_function The function to register.
	 * @param bool $throw Optional. Whether the function should throw an exception
	 *                                    if the function isn't callable. Default true.
	 * @param bool $prepend Whether the function should be prepended to the stack.
	 *                                    Default false.
	 *
	 * @throws Exception
	 */
	function spl_autoload_register( $autoload_function, $throw = true, $prepend = false ) {
		if ( $throw && ! is_callable( $autoload_function ) ) {
			// String not translated to match PHP core.
			throw new Exception( 'Function not callable' );
		}

		global $_wp_spl_autoloaders;

		// Don't allow multiple registration.
		if ( in_array( $autoload_function, $_wp_spl_autoloaders ) ) {
			return;
		}

		if ( $prepend ) {
			array_unshift( $_wp_spl_autoloaders, $autoload_function );
		} else {
			$_wp_spl_autoloaders[] = $autoload_function;
		}
	}

	/**
	 * Unregisters an autoloader function.
	 *
	 * @since 4.6.0
	 *
	 * @param callable $function The function to unregister.
	 *
	 * @return bool True if the function was unregistered, false if it could not be.
	 */
	function spl_autoload_unregister( $function ) {
		global $_wp_spl_autoloaders;
		foreach ( $_wp_spl_autoloaders as &$autoloader ) {
			if ( $autoloader === $function ) {
				unset( $autoloader );

				return true;
			}
		}

		return false;
	}

	/**
	 * Retrieves the registered autoloader functions.
	 *
	 * @since 4.6.0
	 *
	 * @return array List of autoloader functions.
	 */
	function spl_autoload_functions() {
		return $GLOBALS['_wp_spl_autoloaders'];
	}
}

if ( ! function_exists( 'current_action' ) ) {
	function current_action() {
		return current_filter();
	}
}

if ( ! function_exists( 'get_called_class' ) ) {
	function get_called_class( $bt = false, $l = 1 ) {
		if ( ! $bt ) {
			$bt = debug_backtrace();
		}
		if ( ! isset ( $bt[ $l ] ) ) {
			throw new Exception ( "Cannot find called class -> stack level too deep." );
		}
		if ( ! isset( $bt[ $l ]['type'] ) ) {
			throw new Exception ( 'type not set' );
		} else switch ( $bt[ $l ]['type'] ) {
			case '::':
				$lines      = file( $bt[ $l ]['file'] );
				$i          = 0;
				$callerLine = '';
				do {
					$i ++;
					$callerLine = $lines[ $bt[ $l ]['line'] - $i ] . $callerLine;
				} while ( stripos( $callerLine, $bt[ $l ]['function'] ) === false );
				preg_match( '/([a-zA-Z0-9\_]+)::' . $bt[ $l ]['function'] . '/', $callerLine, $matches );
				if ( ! isset( $matches[1] ) ) {
					// must be an edge case.
					throw new Exception ( "Could not find caller class: originating method call is obscured." );
				}
				switch ( $matches[1] ) {
					case 'self':
					case 'parent':
						return get_called_class( $bt, $l + 1 );
					default:
						return $matches[1];
				}
			// won't get here.
			case '->':
				switch ( $bt[ $l ]['function'] ) {
					case '__get':
						// edge case -> get class of calling object
						if ( ! is_object( $bt[ $l ]['object'] ) ) {
							throw new Exception ( "Edge case fail. __get called on non object." );
						}

						return get_class( $bt[ $l ]['object'] );
					default:
						return get_class( $bt[ $l ]['object'] );
				}
			default:
				throw new Exception ( "Unknown backtrace method type" );
		}
	}
}

if ( ! function_exists( 'get_term_name' ) ) {
	function get_term_name( $term_id, $taxonomy ) {
		$term = get_term_by( 'id', absint( $term_id ), $taxonomy );

		return $term->name;
	}
}
