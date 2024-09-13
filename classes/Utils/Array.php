<?php
/**
 * Array Utility
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Utils_Array
 *
 * Various functions to help manipulating arrays.
 */
class PUM_Utils_Array {

	/**
	 * Filters out null values.
	 *
	 * @param array $arr
	 *
	 * @return array
	 */
	public static function filter_null( $arr = [] ) {
		return array_filter( $arr, [ __CLASS__, 'filter_null_callback' ] );
	}

	/**
	 * @param mixed $val
	 *
	 * @return bool
	 */
	public static function filter_null_callback( $val = null ) {
		return isset( $val );
	}

	/**
	 * Clean variables using sanitize_text_field.
	 *
	 * @param array|string $str_or_arr
	 *
	 * @return array|string
	 */
	public static function sanitize( $str_or_arr ) {
		if ( is_string( $str_or_arr ) ) {
			return sanitize_text_field( $str_or_arr );
		}

		return array_map( [ __CLASS__, 'sanitize' ], (array) $str_or_arr );
	}

	/**
	 * Helper function to move or swap array keys in various ways.
	 *
	 * PUM_Utils_Array::move_item($arr, 'move me', 'up'); //move it one up
	 * PUM_Utils_Array::move_item($arr, 'move me', 'down'); //move it one down
	 * PUM_Utils_Array::move_item($arr, 'move me', 'top'); //move it to top
	 * PUM_Utils_Array::move_item($arr, 'move me', 'bottom'); //move it to bottom
	 *
	 * PUM_Utils_Array::move_item($arr, 'move me', -1); //move it one up
	 * PUM_Utils_Array::move_item($arr, 'move me', 1); //move it one down
	 * PUM_Utils_Array::move_item($arr, 'move me', 2); //move it two down
	 *
	 * PUM_Utils_Array::move_item($arr, 'move me', 'before', 'b'); //move it before ['b']
	 * PUM_Utils_Array::move_item($arr, 'move me', 'up', 'b'); //move it before ['b']
	 * PUM_Utils_Array::move_item($arr, 'move me', -1, 'b'); //move it before ['b']
	 * PUM_Utils_Array::move_item($arr, 'move me', 'after', 'b'); //move it after ['b']
	 * PUM_Utils_Array::move_item($arr, 'move me', 'down', 'b'); //move it after ['b']
	 * PUM_Utils_Array::move_item($arr, 'move me', 1, 'b'); //move it after ['b']
	 * PUM_Utils_Array::move_item($arr, 'move me', 2, 'b'); //move it two positions after ['b']
	 *
	 * Special syntax, to swap two elements:
	 * PUM_Utils_Array::move_item($arr, 'a', 0, 'd'); //Swap ['a'] with ['d']
	 * PUM_Utils_Array::move_item($arr, 'a', 'swap', 'd'); //Swap ['a'] with ['d']
	 *
	 * @param array       $ref_arr
	 * @param string      $key1
	 * @param int|string  $move
	 * @param string|null $key2
	 *
	 * @return bool
	 */
	public static function move_item( &$ref_arr, $key1, $move, $key2 = null ) {
		$arr = $ref_arr;

		if ( null === $key2 ) {
			$key2 = $key1;
		}

		if ( ! isset( $arr[ $key1 ] ) || ! isset( $arr[ $key2 ] ) ) {
			return false;
		}

		$i = 0;
		foreach ( $arr as &$val ) {
			++$i;
			$val = [
				'sort' => ( $i * 10 ),
				'val'  => $val,
			];
		}

		// Add a quick keyword `swap` to make syntax simpler to remember.
		if ( 'swap' === $move ) {
			$move = 0;
		}

		if ( is_numeric( $move ) ) {
			if ( 0 === $move && $key1 === $key2 ) {
				return true;
			} elseif ( 0 === $move ) {
				$tmp                  = $arr[ $key1 ]['sort'];
				$arr[ $key1 ]['sort'] = $arr[ $key2 ]['sort'];
				$arr[ $key2 ]['sort'] = $tmp;
			} else {
				$arr[ $key1 ]['sort'] = $arr[ $key2 ]['sort'] + ( $move * 10 + ( $key1 === $key2 ? ( $move < 0 ? - 5 : 5 ) : 0 ) );
			}
		} else {
			switch ( $move ) {
				case 'up':
				case 'before':
					$arr[ $key1 ]['sort'] = $arr[ $key2 ]['sort'] - ( $key1 === $key2 ? 15 : 5 );
					break;
				case 'down':
				case 'after':
					$arr[ $key1 ]['sort'] = $arr[ $key2 ]['sort'] + ( $key1 === $key2 ? 15 : 5 );
					break;
				case 'top':
					$arr[ $key1 ]['sort'] = 5;
					break;
				case 'bottom':
					$arr[ $key1 ]['sort'] = $i * 10 + 5;
					break;
				default:
					return false;
			}
		}

		uasort( $arr, [ __CLASS__, 'sort_by_sort' ] );

		foreach ( $arr as &$val ) {
			$val = $val['val'];
		}

		$ref_arr = $arr;

		return true;
	}

	/**
	 * Pluck all array keys beginning with string.
	 *
	 * @param array             $arr
	 * @param bool|string|array $strings
	 *
	 * @return array
	 */
	public static function pluck_keys_starting_with( $arr, $strings = [] ) {
		$to_be_removed = self::remove_keys_starting_with( $arr, $strings );

		return array_diff_key( $arr, $to_be_removed );
	}

	/**
	 * Pluck all array keys ending with string.
	 *
	 * @param array             $arr
	 * @param bool|string|array $strings
	 *
	 * @return array
	 */
	public static function pluck_keys_ending_with( $arr, $strings = [] ) {
		$to_be_removed = self::remove_keys_ending_with( $arr, $strings );

		return array_diff_key( $arr, $to_be_removed );
	}

	/**
	 * Extract only allowed keys from an array.
	 *
	 * @param array    $arr Array to be extracted from.
	 * @param string[] $allowed_keys List of keys.
	 *
	 * @return array
	 */
	public static function allowed_keys( $arr, $allowed_keys = [] ) {
		return array_intersect_key( $arr, array_flip( $allowed_keys ) );
	}

	/**
	 * This works exactly the same as wp_parse_args, except we remove unused keys for sanitization.
	 *
	 * @param array $arr Array to be parsed.
	 * @param array $allowed_args Array of key=>defaultValue pairs for each allowed argument.
	 *
	 * @return array
	 */
	public static function parse_allowed_args( $arr, $allowed_args = [] ) {
		$arr = wp_parse_args( $arr, $allowed_args );

		return self::allowed_keys( $arr, array_keys( $allowed_args ) );
	}

	/**
	 * Pluck specified array keys.
	 *
	 * @param array    $arr
	 * @param string[] $keys
	 *
	 * @return array
	 */
	public static function pluck( $arr, $keys = [] ) {
		return self::pluck_keys_containing( $arr, $keys );
	}

	/**
	 * Pluck all array keys containing a string or strings.
	 *
	 * @param array    $arr
	 * @param string[] $strings
	 *
	 * @return array
	 */
	public static function pluck_keys_containing( $arr, $strings = [] ) {
		$to_be_removed = self::remove_keys_containing( $arr, $strings );

		return array_diff_key( $arr, $to_be_removed );
	}

	/**
	 * Remove all array keys beginning with string.
	 *
	 * @param array    $arr
	 * @param string[] $strings
	 *
	 * @return array
	 */
	public static function remove_keys_starting_with( $arr, $strings = [] ) {
		if ( ! $strings ) {
			return $arr;
		}

		if ( ! is_array( $strings ) ) {
			$strings = [ $strings ];
		}

		foreach ( $arr as $key => $value ) {
			foreach ( $strings as $string ) {
				if ( strpos( $key, $string ) === 0 ) {
					unset( $arr[ $key ] );
				}
			}
		}

		return $arr;
	}

	/**
	 * Remove all array keys ending with string.
	 *
	 * @param array             $arr
	 * @param bool|string|array $strings
	 *
	 * @return array
	 */
	public static function remove_keys_ending_with( $arr, $strings = [] ) {
		if ( ! $strings ) {
			return $arr;
		}

		if ( ! is_array( $strings ) ) {
			$strings = [ $strings ];
		}

		foreach ( $arr as $key => $value ) {
			foreach ( $strings as $string ) {
				$length = strlen( $string );

				if ( substr( $key, - $length ) === $string ) {
					unset( $arr[ $key ] );
				}
			}
		}

		return $arr;
	}

	/**
	 * Remove all array keys containing string.
	 *
	 * @param array             $arr
	 * @param bool|string|array $strings
	 *
	 * @return array
	 */
	public static function remove_keys_containing( $arr, $strings = [] ) {

		if ( ! $strings ) {
			return $arr;
		}

		if ( ! is_array( $strings ) ) {
			$strings = [ $strings ];
		}

		foreach ( $arr as $key => $value ) {
			foreach ( $strings as $string ) {
				if ( strpos( $key, $string ) !== false ) {
					unset( $arr[ $key ] );
				}
			}
		}

		return $arr;
	}

	/**
	 * Remove all array keys containing string.
	 *
	 * @param array        $arr
	 * @param string|array $keys
	 *
	 * @return array
	 */
	public static function remove_keys( $arr, $keys = [] ) {

		if ( empty( $keys ) ) {
			return $arr;
		}

		if ( is_string( $keys ) ) {
			$keys = [ $keys ];
		}

		foreach ( (array) $keys as $key ) {
			if ( is_string( $key ) && array_key_exists( $key, $arr ) ) {
				unset( $arr[ $key ] );
			}
		}

		return $arr;
	}

	/**
	 * Sort nested arrays with various options.
	 *
	 * @param array  $arr
	 * @param string $type
	 * @param bool   $reverse
	 *
	 * @return array
	 */
	public static function sort( $arr = [], $type = 'key', $reverse = false ) {
		if ( ! is_array( $arr ) ) {
			return $arr;
		}

		switch ( $type ) {
			case 'key':
				if ( ! $reverse ) {
					ksort( $arr );
				} else {
					krsort( $arr );
				}
				break;

			case 'natural':
				natsort( $arr );
				break;

			case 'priority':
				if ( ! $reverse ) {
					uasort( $arr, [ __CLASS__, 'sort_by_priority' ] );
				} else {
					uasort( $arr, [ __CLASS__, 'rsort_by_priority' ] );
				}
				break;
		}

		return $arr;
	}

	/**
	 * @param $a
	 * @param $b
	 *
	 * @return bool
	 */
	public static function sort_by_sort( $a, $b ) {
		return $a['sort'] > $b['sort'];
	}

	/**
	 * Sort array by priority value
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	public static function sort_by_priority( $a, $b ) {
		$pri_a = isset( $a['pri'] ) ? $a['pri'] : ( isset( $a['priority'] ) ? $a['priority'] : false );
		$pri_b = isset( $b['pri'] ) ? $b['pri'] : ( isset( $b['priority'] ) ? $b['priority'] : false );

		if ( ! is_numeric( $pri_a ) || ! is_numeric( $pri_b ) || $pri_a === $pri_b ) {
			return 0;
		}

		return ( $pri_a < $pri_b ) ? - 1 : 1;
	}

	/**
	 * Sort array in reverse by priority value
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	public static function rsort_by_priority( $a, $b ) {
		$pri_a = isset( $a['pri'] ) ? $a['pri'] : ( isset( $a['priority'] ) ? $a['priority'] : false );
		$pri_b = isset( $b['pri'] ) ? $b['pri'] : ( isset( $b['priority'] ) ? $b['priority'] : false );

		if ( ! is_numeric( $pri_a ) || ! is_numeric( $pri_b ) || $pri_a === $pri_b ) {
			return 0;
		}

		return ( $pri_a < $pri_b ) ? 1 : - 1;
	}

	/**
	 * Replace array key with new key name in same order
	 *
	 * @param $arr
	 * @param $old_key
	 * @param $new_key
	 *
	 * @return array
	 */
	public static function replace_key( $arr, $old_key, $new_key ) {
		$keys  = array_keys( $arr );
		$index = array_search( $old_key, $keys, true );

		if ( false === $index ) {
			// throw new \Exception( sprintf( 'Key "%s" does not exit', $old_key ) );
		}

		$keys[ $index ] = $new_key;

		return array_combine( $keys, array_values( $arr ) );
	}

	/**
	 * Converts 'false' & 'true' string values in any array to proper boolean values.
	 *
	 * @param array|mixed $data
	 *
	 * @return array|mixed
	 */
	public static function fix_json_boolean_values( $data ) {

		if ( is_array( $data ) ) {
			foreach ( (array) $data as $key => $value ) {
				if ( is_string( $value ) && in_array( $value, [ 'true', 'false' ], true ) ) {
					$data[ $key ] = json_decode( $value );
				} elseif ( is_array( $value ) ) {
					$data[ $key ] = self::fix_json_boolean_values( $value );
				}
			}
		}

		return $data;
	}

	/**
	 * @param $obj
	 *
	 * @return array
	 */
	public static function from_object( $obj ) {
		if ( is_object( $obj ) ) {
			$obj = (array) $obj;
		}
		if ( is_array( $obj ) ) {
			$new = [];
			foreach ( $obj as $key => $val ) {
				$new[ $key ] = self::from_object( $val );
			}
		} else {
			$new = $obj;
		}

		return $new;
	}

	/**
	 * @param $arr
	 *
	 * @return array
	 */
	public static function safe_json_decode( $arr ) {
		if ( ! empty( $arr ) && is_string( $arr ) ) {
			if ( strpos( $arr, '\"' ) >= 0 ) {
				$arr = stripslashes( $arr );
			}

			$arr = json_decode( $arr );
			$arr = self::from_object( $arr );
			$arr = self::fix_json_boolean_values( $arr );
		}

		return (array) $arr;
	}

	/**
	 * Ensures proper encoding for strings before json_encode is used.
	 *
	 * @param array|string $data
	 *
	 * @return mixed|string
	 */
	public static function safe_json_encode( $data = [] ) {
		return wp_json_encode( self::make_safe_for_json_encode( $data ) );
	}

	/**
	 * json_encode only accepts valid UTF8 characters,  thus we need to properly convert translations and other data to proper utf.
	 *
	 * This function does that recursively.
	 *
	 * @param array|string $data
	 *
	 * @return array|string
	 */
	public static function make_safe_for_json_encode( $data = [] ) {
		if ( is_scalar( $data ) ) {
			return html_entity_decode( (string) $data, ENT_QUOTES, 'UTF-8' );
		}

		if ( is_array( $data ) ) {
			foreach ( (array) $data as $key => $value ) {
				if ( is_scalar( $value ) && ! is_bool( $value ) ) {
					$data[ $key ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
				} elseif ( is_array( $value ) ) {
					$data[ $key ] = self::make_safe_for_json_encode( $value );
				}
			}
		}

		return $data;
	}

	/**
	 * @param $d
	 *
	 * @return array|string
	 */
	public static function utf8_encode_recursive( $d ) {
		if ( is_array( $d ) ) {
			foreach ( $d as $k => $v ) {
				$d[ $k ] = self::utf8_encode_recursive( $v );
			}
		} elseif ( is_string( $d ) ) {
			return mb_convert_encoding( $d, 'UTF-8', 'ISO-8859-1' );
		}

		return $d;
	}


	/**
	 * @param      $value
	 * @param bool  $encode
	 *
	 * @return string
	 */
	public static function maybe_json_attr( $value, $encode = false ) {
		if ( is_object( $value ) || is_array( $value ) ) {
			return $encode ? htmlspecialchars( wp_json_encode( $value ) ) : wp_json_encode( $value );
		}

		return $value;
	}

	/**
	 * Remaps array keys.
	 *
	 * @param array $arr       an array values.
	 * @param array $remap_array an array of $old_key => $new_key values.
	 *
	 * @return array
	 */
	public static function remap_keys( $arr, $remap_array = [] ) {

		foreach ( $remap_array as $old_key => $new_key ) {
			$value = isset( $arr[ $old_key ] ) ? $arr[ $old_key ] : false;

			if ( ! empty( $value ) ) {
				$arr[ $new_key ] = $value;
			}

			unset( $arr[ $old_key ] );
		}

		return $arr;
	}
}
