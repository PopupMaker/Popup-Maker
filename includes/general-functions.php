<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'maybe_json_attr' ) ) {
	function maybe_json_attr( $value, $encode = false ) {
		if ( is_object( $value ) || is_array( $value ) ) {
			return $encode ? htmlspecialchars( json_encode( $value ) ) : json_encode( $value );
		}
		return $value;
	}
}

/**
 * Returns array key from dot notated array key..
 *
 * @since 1.0
 *
 * @param string $a is the array you are searching.
 * @param string $path is the dot notated path.
 * @param string $default is the default returned if key empty or not found.
 *
 * @return mixed results of lookup
 */
function popmake_resolve( array $a, $path, $default = null ) {
	$current = $a;
	$p       = strtok( $path, '.' );
	while ( $p !== false ) {
		if ( ! isset( $current[ $p ] ) ) {
			return $default;
		}
		$current = $current[ $p ];
		$p       = strtok( '.' );
	}

	return $current;
}


if ( ! function_exists( "enqueue_popup" ) ) {
	function enqueue_popup( $id ) {
		if ( ! is_array( $id ) ) {
			PopMake_Popups::enqueue_popup( $id );
		} else {
			foreach ( $id as $i ) {
				PopMake_Popups::enqueue_popup( $i );
			}
		}
	}
}


function popmake_get_license( $key = null ) {
	$license = popmake_get_option( POPMAKE_SLUG . '-license' );
	if ( ! $license ) {
		$license = array(
			'valid'  => false,
			'key'    => '',
			'status' => array(
				'code'    => null,
				'message' => null,
				'expires' => null,
				'domains' => null
			)
		);
		popmake_update_option( POPMAKE_SLUG . '-license', $license );
	}

	return $license && $key ? emresolve( $license, $key ) : $license;
}


function popmake_get_site_option( $key ) {
	global $blog_id;
	if ( function_exists( 'is_multisite' ) && is_multisite() && $blog_id ) {
		return get_blog_option( $blog_id, $key );
	} else {
		return get_site_option( $key );
	}
}


function popmake_update_site_option( $key, $value ) {
	global $blog_id;
	if ( function_exists( 'is_multisite' ) && is_multisite() && $blog_id ) {
		return update_blog_option( $blog_id, $key, $value );
	} else {
		return update_site_option( $key, $value );
	}
}

function popmake_delete_site_option( $key ) {
	global $blog_id;
	if ( function_exists( 'is_multisite' ) && is_multisite() && $blog_id ) {
		return delete_blog_option( $blog_id, $key );
	} else {
		return delete_site_option( $key );
	}
}


function popmake_debug( $var ) {
	echo '<pre>';
	var_dump( $var );
	echo '</pre>';
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
				$array[ $key ] = array();
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