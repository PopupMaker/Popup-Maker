<?php
/**
 * Sanitize utility.
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class PUM_Utils_Sanitize
 */
class PUM_Utils_Sanitize {

	/**
	 * Sanitizes value
	 *
	 * @param string $value Value to be sanitized
	 * @param array  $args Array of arguments
	 *
	 * @return string
	 */
	public static function text( $value = '', $args = [] ) {
		return sanitize_text_field( $value );
	}

	/**
	 * Checks value
	 *
	 * @param mixed|int $value Value to check - null by default.
	 * @param array     $args  Array of arguments.
	 *
	 * @return bool|int
	 */
	public static function checkbox( $value = null, $args = [] ) {
		if ( intval( $value ) === 1 ) {
			return 1;
		}

		return 0;
	}

	public static function measure( $value = '', $args = [], $fields = [], $values = [] ) {
		if ( isset( $values[ $args['id'] . '_unit' ] ) ) {
			$value .= $values[ $args['id'] . '_unit' ];
		}

		return sanitize_text_field( $value );
	}

}
