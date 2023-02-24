<?php

// SPL can be disabled on PHP 5.2
if ( ! function_exists( 'spl_autoload_register' ) ) {
	$_wp_spl_autoloaders = [];

	/**
	 * Registers a function to be autoloaded.
	 *
	 * @since 4.6.0
	 *
	 * @param callable $autoload_function The function to register.
	 * @param bool     $throw Optional. Whether the function should throw an exception
	 *                                        if the function isn't callable. Default true.
	 * @param bool     $prepend Whether the function should be prepended to the stack.
	 *                                        Default false.
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
		if ( ! isset( $bt[ $l ] ) ) {
			throw new Exception( 'Cannot find called class -> stack level too deep.' );
		}
		if ( ! isset( $bt[ $l ]['type'] ) ) {
			throw new Exception( 'type not set' );
		} else {
			switch ( $bt[ $l ]['type'] ) {
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
						throw new Exception( 'Could not find caller class: originating method call is obscured.' );
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
								throw new Exception( 'Edge case fail. __get called on non object.' );
							}

							return get_class( $bt[ $l ]['object'] );
						default:
							return get_class( $bt[ $l ]['object'] );
					}
				default:
					throw new Exception( 'Unknown backtrace method type' );
			}
		}
	}
}

if ( ! function_exists( 'get_term_name' ) ) {
	function get_term_name( $term_id, $taxonomy ) {
		$term = get_term_by( 'id', absint( $term_id ), $taxonomy );

		return $term->name;
	}
}

// For WP versions before 3.6
if ( ! function_exists( 'has_shortcode' ) ) {
	function has_shortcode( $content, $tag ) {
		if ( false === strpos( $content, '[' ) ) {
			return false;
		}

		if ( shortcode_exists( $tag ) ) {
			preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER );
			if ( empty( $matches ) ) {
				return false;
			}

			foreach ( $matches as $shortcode ) {
				if ( $tag === $shortcode[2] ) {
					return true;
				} elseif ( ! empty( $shortcode[5] ) && has_shortcode( $shortcode[5], $tag ) ) {
					return true;
				}
			}
		}

		return false;
	}
}

if ( ! function_exists( 'shortcode_exists' ) ) {
	function shortcode_exists( $tag ) {
		global $shortcode_tags;

		return array_key_exists( $tag, $shortcode_tags );
	}
}

/**
 * Deprecated PHP v5.3 functions.
 */
if ( ! function_exists( 'array_replace_recursive' ) ) {
	function array_replace_recursive( $array, $array1 ) {
		// handle the arguments, merge one by one
		$args  = func_get_args();
		$array = $args[0];
		if ( ! is_array( $array ) ) {
			return $array;
		}
		for ( $i = 1; $i < count( $args ); $i ++ ) {
			if ( is_array( $args[ $i ] ) ) {
				$array = recurse( $array, $args[ $i ] );
			}
		}

		return $array;
	}
}

if ( ! function_exists( 'recurse' ) ) {
	function recurse( $array, $array1 ) {
		foreach ( $array1 as $key => $value ) {
			// create new key in $array, if it is empty or not an array
			if ( ! isset( $array[ $key ] ) || ( isset( $array[ $key ] ) && ! is_array( $array[ $key ] ) ) ) {
				$array[ $key ] = [];
			}

			// overwrite the value in the base array
			if ( is_array( $value ) ) {
				$value = recurse( $array[ $key ], $value );
			}
			$array[ $key ] = $value;
		}

		return $array;
	}
}

if ( ! function_exists( 'write_log' ) ) {
	function write_log( $log ) {
		if ( is_array( $log ) || is_object( $log ) ) {
			error_log( print_r( $log, true ) );
		} else {
			error_log( $log );
		}
	}
}

if ( ! function_exists( 'boolval' ) ) {
	function boolval( $val ) {
		return (bool) $val;
	}
}

if ( ! function_exists( 'maybe_json_attr' ) ) {
	function maybe_json_attr( $value, $encode = false ) {
		if ( is_object( $value ) || is_array( $value ) ) {
			return $encode ? htmlspecialchars( wp_json_encode( $value ) ) : wp_json_encode( $value );
		}
		return $value;
	}
}

if ( ! function_exists( 'has_blocks' ) ) {
	/**
	 * Determine whether a post or content string has blocks.
	 *
	 * This test optimizes for performance rather than strict accuracy, detecting
	 * the pattern of a block but not validating its structure. For strict accuracy,
	 * you should use the block parser on post content.
	 *
	 * @since 5.0.0
	 * @see parse_blocks()
	 *
	 * @param int|string|WP_Post|null $post Optional. Post content, post ID, or post object. Defaults to global $post.
	 * @return bool Whether the post has blocks.
	 */
	function has_blocks( $post = null ) {
		if ( ! is_string( $post ) ) {
			$wp_post = get_post( $post );
			if ( $wp_post instanceof WP_Post ) {
				$post = $wp_post->post_content;
			}
		}

		return false !== strpos( (string) $post, '<!-- wp:' );
	}
}

/**
 * Aweful hack for backward compatibility with WP <5.3.
 */
if ( ! function_exists( 'wp_date' ) ) {
	function wp_date( $format, $timestamp = null, $timezone = null ) {
		global $wp_locale;

		if ( null === $timestamp ) {
			$timestamp = time();
		} elseif ( ! is_numeric( $timestamp ) ) {
			return false;
		}

		if ( ! $timezone ) {
			if ( function_exists( 'wp_timezone' ) ) {
				$timezone = wp_timezone();
			} else {
				// Get the server timezone
				$server_timezone = get_option( 'timezone_string' );

				// If timezone_string is not set, get the default timezone of the server
				if ( empty( $server_timezone ) ) {
					$server_timezone = date_default_timezone_get();
				}

				$timezone = new DateTimeZone( $server_timezone );
			}
		}

		$datetime = date_create( '@' . $timestamp );
		$datetime->setTimezone( $timezone );

		if ( empty( $wp_locale->month ) || empty( $wp_locale->weekday ) ) {
			$date = $datetime->format( $format );
		} else {
			// We need to unpack shorthand `r` format because it has parts that might be localized.
			$format = preg_replace( '/(?<!\\\\)r/', DATE_RFC2822, $format );

			$new_format    = '';
			$format_length = strlen( $format );
			$month         = $wp_locale->get_month( $datetime->format( 'm' ) );
			$weekday       = $wp_locale->get_weekday( $datetime->format( 'w' ) );

			for ( $i = 0; $i < $format_length; $i++ ) {
				switch ( $format[ $i ] ) {
					case 'D':
						$new_format .= addcslashes( $wp_locale->get_weekday_abbrev( $weekday ), '\\A..Za..z' );
						break;
					case 'F':
						$new_format .= addcslashes( $month, '\\A..Za..z' );
						break;
					case 'l':
						$new_format .= addcslashes( $weekday, '\\A..Za..z' );
						break;
					case 'M':
						$new_format .= addcslashes( $wp_locale->get_month_abbrev( $month ), '\\A..Za..z' );
						break;
					case 'a':
						$new_format .= addcslashes( $wp_locale->get_meridiem( $datetime->format( 'a' ) ), '\\A..Za..z' );
						break;
					case 'A':
						$new_format .= addcslashes( $wp_locale->get_meridiem( $datetime->format( 'A' ) ), '\\A..Za..z' );
						break;
					case '\\':
						$new_format .= $format[ $i ];

						// If character follows a slash, we add it without translating.
						if ( $i < $format_length ) {
							$new_format .= $format[ ++$i ];
						}
						break;
					default:
						$new_format .= $format[ $i ];
						break;
				}
			}

			$date = $datetime->format( $new_format );
			$date = wp_maybe_decline_date( $date, $format );
		}

		/**
		 * Filters the date formatted based on the locale.
		 *
		 * @since 5.3.0
		 *
		 * @param string       $date      Formatted date string.
		 * @param string       $format    Format to display the date.
		 * @param int          $timestamp Unix timestamp.
		 * @param DateTimeZone $timezone  Timezone.
		 */
		$date = apply_filters( 'wp_date', $date, $format, $timestamp, $timezone );

		return $date;
	}
}
