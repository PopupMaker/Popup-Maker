<?php
/**
 * CSS Utility
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

class PUM_Utils_CSS {

	/**
	 * @param string $hex
	 * @param string $return_type
	 *
	 * @return array|string
	 */
	public static function hex2rgb( $hex = '#ffffff', $return_type = 'rgb' ) {
		if ( is_array( $hex ) ) {
			$hex = implode( '', $hex );
		}
		$hex = str_replace( '#', '', $hex );

		if ( strlen( $hex ) === 3 ) {
			$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
			$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
			$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
		} else {
			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );
		}

		$rgb = [ $r, $g, $b ];

		if ( 'array' === $return_type ) {
			return $rgb; // returns an array with the rgb values
		}

		return 'rgb(' . implode( ',', $rgb ) . ')'; // returns the rgb values separated by commas
	}

	/**
	 * @param string $hex
	 * @param int    $opacity
	 *
	 * @return string
	 */
	public static function hex2rgba( $hex = '#ffffff', $opacity = 100 ) {
		$rgb     = self::hex2rgb( $hex, 'array' );
		$opacity = number_format( intval( $opacity ) / 100, 2 );

		return 'rgba( ' . implode( ', ', $rgb ) . ', ' . $opacity . ' )';
	}

	/**
	 * @param int    $thickness
	 * @param string $style
	 * @param string $color
	 *
	 * @return string
	 */
	public static function border_style( $thickness = 1, $style = 'solid', $color = '#cccccc' ) {
		return "{$thickness}px {$style} {$color}";
	}

	/**
	 * @param int    $horizontal
	 * @param int    $vertical
	 * @param int    $blur
	 * @param int    $spread
	 * @param string $hex
	 * @param int    $opacity
	 * @param string $inset
	 *
	 * @return string
	 */
	public static function box_shadow_style( $horizontal = 0, $vertical = 0, $blur = 0, $spread = 0, $hex = '#000000', $opacity = 50, $inset = 'no' ) {
		return "{$horizontal}px {$vertical}px {$blur}px {$spread}px " . self::hex2rgba( $hex, $opacity ) . ( 'yes' === $inset ? ' inset' : '' );
	}

	/**
	 * @param int    $horizontal
	 * @param int    $vertical
	 * @param int    $blur
	 * @param string $hex
	 * @param int    $opacity
	 *
	 * @return string
	 */
	public static function text_shadow_style( $horizontal = 0, $vertical = 0, $blur = 0, $hex = '#000000', $opacity = 50 ) {
		return "{$horizontal}px {$vertical}px {$blur}px " . self::hex2rgba( $hex, $opacity );
	}

	/**
	 * @param int|string       $size
	 * @param int|string       $weight
	 * @param float|int|string $line_height
	 * @param string           $family
	 * @param string|null      $style
	 * @param string|null      $variant
	 *
	 * @return mixed
	 */
	public static function font_style( $size = 16, $weight = 300, $line_height = 1.2, $family = 'Times New Roman', $style = null, $variant = null ) {
		$size        = is_int( $size ) ? "{$size}px" : $size;
		$line_height = is_int( $line_height ) ? "{$line_height}px" : $line_height;

		return str_replace( '  ', ' ', trim( "$style $variant $weight {$size}/{$line_height} \"$family\"" ) );
	}
}
