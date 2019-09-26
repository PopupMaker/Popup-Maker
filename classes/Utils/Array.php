<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

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
	 * @param array $array
	 *
	 * @return array
	 */
	public static function filter_null( $array = array() ) {
		return array_filter( $array, array( __CLASS__, '_filter_null' ) );
	}

	/**
	 * @param null $val
	 *
	 * @return bool
	 */
	public static function _filter_null( $val = null ) {
		return isset( $val );
	}

	/**
	 * Clean variables using sanitize_text_field.
	 *
	 * @param $var
	 *
	 * @return array|string
	 */
	public static function sanitize( $var ) {
		if ( is_string( $var ) ) {
			return sanitize_text_field( $var );
		}

		return array_map( array( __CLASS__, 'sanitize' ), (array) $var );
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
	 * PUM_Utils_Array::move_item($arr, 'move me', 'up', 'b'); //move it before ['b']
	 * PUM_Utils_Array::move_item($arr, 'move me', -1, 'b'); //move it before ['b']
	 * PUM_Utils_Array::move_item($arr, 'move me', 'down', 'b'); //move it after ['b']
	 * PUM_Utils_Array::move_item($arr, 'move me', 1, 'b'); //move it after ['b']
	 * PUM_Utils_Array::move_item($arr, 'move me', 2, 'b'); //move it two positions after ['b']
	 *
	 * Special syntax, to swap two elements:
	 * PUM_Utils_Array::move_item($arr, 'a', 0, 'd'); //Swap ['a'] with ['d']
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

		if ( $key2 == null ) {
			$key2 = $key1;
		}

		if ( ! isset( $arr[ $key1 ] ) || ! isset( $arr[ $key2 ] ) ) {
			return false;
		}

		$i = 0;
		foreach ( $arr as &$val ) {
			$val = array( 'sort' => ( ++ $i * 10 ), 'val' => $val );
		}

		if ( is_numeric( $move ) ) {
			if ( $move == 0 && $key1 == $key2 ) {
				return true;
			} elseif ( $move == 0 ) {
				$tmp                  = $arr[ $key1 ]['sort'];
				$arr[ $key1 ]['sort'] = $arr[ $key2 ]['sort'];
				$arr[ $key2 ]['sort'] = $tmp;
			} else {
				$arr[ $key1 ]['sort'] = $arr[ $key2 ]['sort'] + ( $move * 10 + ( $key1 == $key2 ? ( $move < 0 ? - 5 : 5 ) : 0 ) );
			}
		} else {
			switch ( $move ) {
				case 'up':
					$arr[ $key1 ]['sort'] = $arr[ $key2 ]['sort'] - ( $key1 == $key2 ? 15 : 5 );
					break;
				case 'down':
					$arr[ $key1 ]['sort'] = $arr[ $key2 ]['sort'] + ( $key1 == $key2 ? 15 : 5 );
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

		uasort( $arr, array( __CLASS__, 'sort_by_sort' ) );

		foreach ( $arr as &$val ) {
			$val = $val['val'];
		}

		$ref_arr = $arr;

		return true;
	}

	/**
	 * Pluck all array keys beginning with string.
	 *
	 * @param array             $array
	 * @param bool|string|array $strings
	 *
	 * @return array
	 */
	public static function pluck_keys_starting_with( $array, $strings = array() ) {
		$to_be_removed = self::remove_keys_starting_with( $array, $strings );

		return array_diff_key( $array, $to_be_removed );
	}

	/**
	 * Pluck all array keys ending with string.
	 *
	 * @param array             $array
	 * @param bool|string|array $strings
	 *
	 * @return array
	 */
	public static function pluck_keys_ending_with( $array, $strings = array() ) {
		$to_be_removed = self::remove_keys_ending_with( $array, $strings );

		return array_diff_key( $array, $to_be_removed );
	}

	/**
	 * Pluck all array keys ending with string.
	 *
	 * @param array             $array
	 * @param bool|string|array $strings
	 *
	 * @return array
	 */
	public static function pluck_keys_containing( $array, $strings = array() ) {
		$to_be_removed = self::remove_keys_containing( $array, $strings );

		return array_diff_key( $array, $to_be_removed );
	}

	/**
	 * Remove all array keys beginning with string.
	 *
	 * @param array             $array
	 * @param bool|string|array $strings
	 *
	 * @return array
	 */
	public static function remove_keys_starting_with( $array, $strings = array() ) {
		if ( ! $strings ) {
			return $array;
		}

		if ( ! is_array( $strings ) ) {
			$strings = array( $strings );
		}

		foreach ( $array as $key => $value ) {
			foreach ( $strings as $string ) {
				if ( strpos( $key, $string ) === 0 ) {
					unset( $array[ $key ] );
				}
			}
		}

		return $array;
	}

	/**
	 * Remove all array keys ending with string.
	 *
	 * @param array             $array
	 * @param bool|string|array $strings
	 *
	 * @return array
	 */
	public static function remove_keys_ending_with( $array, $strings = array() ) {
		if ( ! $strings ) {
			return $array;
		}

		if ( ! is_array( $strings ) ) {
			$strings = array( $strings );
		}

		foreach ( $array as $key => $value ) {
			foreach ( $strings as $string ) {
				$length = strlen( $string );

				if ( substr( $key, - $length ) === $string ) {
					unset( $array[ $key ] );
				}
			}
		}

		return $array;
	}

	/**
	 * Remove all array keys containing string.
	 *
	 * @param array             $array
	 * @param bool|string|array $strings
	 *
	 * @return array
	 */
	public static function remove_keys_containing( $array, $strings = array() ) {

		if ( ! $strings ) {
			return $array;
		}

		if ( ! is_array( $strings ) ) {
			$strings = array( $strings );
		}

		foreach ( $array as $key => $value ) {
			foreach ( $strings as $string ) {
				if ( strpos( $key, $string ) !== false ) {
					unset( $array[ $key ] );
				}
			}
		}

		return $array;
	}

	/**
	 * Remove all array keys containing string.
	 *
	 * @param array        $array
	 * @param string|array $keys
	 *
	 * @return array
	 */
	public static function remove_keys( $array, $keys = array() ) {

		if ( empty( $keys ) ) {
			return $array;
		}

		if ( is_string( $keys ) ) {
			$keys = array( $keys );
		}

		foreach ( (array) $keys as $key ) {
			if ( is_string( $key ) && array_key_exists( $key, $array ) ) {
				unset( $array[ $key ] );
			}
		}

		return $array;
	}

	/**
	 * Sort nested arrays with various options.
	 *
	 * @param array  $array
	 * @param string $type
	 * @param bool   $reverse
	 *
	 * @return array
	 */
	public static function sort( $array = array(), $type = 'key', $reverse = false ) {
		if ( ! is_array( $array ) ) {
			return $array;
		}

		switch ( $type ) {
			case 'key':
				if ( ! $reverse ) {
					ksort( $array );
				} else {
					krsort( $array );
				}
				break;

			case 'natural':
				natsort( $array );
				break;

			case 'priority':
				if ( ! $reverse ) {
					uasort( $array, array( __CLASS__, 'sort_by_priority' ) );
				} else {
					uasort( $array, array( __CLASS__, 'rsort_by_priority' ) );
				}
				break;
		}

		return $array;
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
	 * @param $array
	 * @param $old_key
	 * @param $new_key
	 *
	 * @return array
	 */
	public static function replace_key( $array, $old_key, $new_key ) {
		$keys = array_keys( $array );
		if ( false === $index = array_search( $old_key, $keys, true ) ) {
			//throw new \Exception( sprintf( 'Key "%s" does not exit', $old_key ) );
		}
		$keys[ $index ] = $new_key;

		return array_combine( $keys, array_values( $array ) );
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
				if ( is_string( $value ) && in_array( $value, array( 'true', 'false' ) ) ) {
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
			$new = array();
			foreach ( $obj as $key => $val ) {
				$new[ $key ] = self::from_object( $val );
			}
		} else {
			$new = $obj;
		}

		return $new;
	}

	/**
	 * @param $array
	 *
	 * @return array
	 */
	public static function safe_json_decode( $array ) {
		if ( ! empty( $array ) && is_string( $array ) ) {
			if ( strpos( $array, '\"' ) >= 0 ) {
				$array = stripslashes( $array );
			}

			$array = json_decode( $array );
			$array = self::from_object( $array );
			$array = self::fix_json_boolean_values( $array );
		}

		return (array) $array;
	}

	/**
	 * Ensures proper encoding for strings before json_encode is used.
	 *
	 * @param array|string $data
	 *
	 * @return mixed|string
	 */
	public static function safe_json_encode( $data = array() ) {
		return wp_json_encode( self:: make_safe_for_json_encode( $data ) );
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
	public static function make_safe_for_json_encode( $data = array() ) {
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
		} else if ( is_string( $d ) ) {
			return utf8_encode( $d );
		}

		return $d;
	}


	/**
	 * @param      $value
	 * @param bool $encode
	 *
	 * @return string
	 */
	public static function maybe_json_attr( $value, $encode = false ) {
		if ( is_object( $value ) || is_array( $value ) ) {
			return $encode ? htmlspecialchars( json_encode( $value ) ) : json_encode( $value );
		}

		return $value;
	}
}

