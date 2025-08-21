<?php
/**
 * Sanitize Utility
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class PUM_Utils_Sanitize
 */
class PUM_Utils_Sanitize {

	/**
	 * Sanitize text field input
	 *
	 * @param string  $value Input value to sanitize
	 * @param mixed[] $args Configuration arguments (unused in current implementation)
	 * @return string Sanitized text field value
	 */
	public static function text( $value = '', $args = [] ) {
		return sanitize_text_field( $value );
	}

	/**
	 * Sanitize checkbox input to boolean integer values
	 *
	 * @param mixed   $value Input value to evaluate as checkbox
	 * @param mixed[] $args Configuration arguments (unused in current implementation)
	 * @return int<0, 1> Returns 1 for checked (truthy) values, 0 for unchecked
	 */
	public static function checkbox( $value = null, $args = [] ) {
		if ( intval( $value ) === 1 ) {
			return 1;
		}

		return 0;
	}

	/**
	 * Sanitize measurement value with optional unit suffix
	 *
	 * @param string               $value Base measurement value
	 * @param array{id?: string}   $args Configuration arguments containing optional field ID
	 * @param mixed[]              $fields Field definitions (unused in current implementation)
	 * @param array<string, mixed> $values Form values array containing potential unit suffix
	 * @return string Sanitized measurement value with unit suffix if available
	 */
	public static function measure( $value = '', $args = [], $fields = [], $values = [] ) {
		if ( isset( $args['id'] ) && isset( $values[ $args['id'] . '_unit' ] ) ) {
			$value .= $values[ $args['id'] . '_unit' ];
		}

		return sanitize_text_field( $value );
	}
}
