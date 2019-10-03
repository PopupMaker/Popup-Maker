<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class PUM_Utils_Sanitize
 */
class PUM_Utils_Sanitize {

	/**
	 * @param string $value
	 * @param array  $args
	 *
	 * @return string
	 */
	public static function text( $value = '', $args = array() ) {
		return sanitize_text_field( $value );
	}

	/**
	 * @param mixed|int $value
	 * @param array     $args
	 *
	 * @return bool|int
	 */
	public static function checkbox( $value = null, $args = array() ) {
		if ( intval( $value ) == 1 ) {
			return 1;
		}

		return 0;
	}

	public static function measure( $value = '', $args = array(), $fields = array(), $values = array() ) {
		if ( isset( $values[ $args['id'] . '_unit' ] ) ) {
			$value .= $values[ $args['id'] . '_unit' ];
		}

		return sanitize_text_field( $value );
	}

}