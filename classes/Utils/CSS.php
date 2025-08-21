<?php
/**
 * CSS Utility
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

class PUM_Utils_CSS {

	/**
	 * Converts hex color to RGB format
	 *
	 * @param mixed  $hex Color value in hex format (accepts string, numeric, or array)
	 * @param string $return_type Return format: 'array' for array<int, int>, 'rgb' for CSS string
	 * @return ($return_type is 'array' ? array<int, int> : string)
	 */
	public static function hex2rgb( $hex = '#ffffff', $return_type = 'rgb' ) {
		// Handle invalid input types (null, false, objects, etc.)
		if ( ! is_string( $hex ) && ! is_numeric( $hex ) && ! is_array( $hex ) ) {
			$hex = '#ffffff';
		}

		// Handle arrays by joining
		if ( is_array( $hex ) ) {
			$hex = implode( '', $hex );
		}

		// Convert to string and remove hash
		$hex = str_replace( '#', '', (string) $hex );

		// Validate hex format (3 or 6 valid hex characters only)
		if ( ! preg_match( '/^[0-9a-fA-F]{3}$|^[0-9a-fA-F]{6}$/', $hex ) ) {
			$hex = 'ffffff'; // Default to white for invalid hex
		}

		if ( strlen( $hex ) === 3 ) {
			$r = (int) hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
			$g = (int) hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
			$b = (int) hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
		} else {
			$r = (int) hexdec( substr( $hex, 0, 2 ) );
			$g = (int) hexdec( substr( $hex, 2, 2 ) );
			$b = (int) hexdec( substr( $hex, 4, 2 ) );
		}

		$rgb = [ $r, $g, $b ];

		if ( 'array' === $return_type ) {
			return $rgb; // returns an array with the rgb values
		}

		return 'rgb(' . implode( ',', $rgb ) . ')'; // returns the rgb values separated by commas
	}

	/**
	 * Converts hex color to RGBA format with opacity
	 *
	 * @param mixed $hex Color value in hex format
	 * @param int   $opacity Opacity percentage (0-100)
	 * @return string CSS RGBA color string
	 */
	public static function hex2rgba( $hex = '#ffffff', $opacity = 100 ) {
		$rgb     = self::hex2rgb( $hex, 'array' );
		$opacity = number_format( intval( $opacity ) / 100, 2 );

		return 'rgba( ' . implode( ', ', $rgb ) . ', ' . $opacity . ' )';
	}

	/**
	 * Generates CSS border style string
	 *
	 * @param int    $thickness Border thickness in pixels
	 * @param string $style Border style (solid, dashed, dotted, etc.)
	 * @param string $color Border color (hex, rgb, rgba, etc.)
	 * @return string CSS border style string
	 */
	public static function border_style( $thickness = 1, $style = 'solid', $color = '#cccccc' ) {
		return "{$thickness}px {$style} {$color}";
	}

	/**
	 * Generates CSS box-shadow style string
	 *
	 * @param int         $horizontal Horizontal offset in pixels
	 * @param int         $vertical Vertical offset in pixels
	 * @param int         $blur Blur radius in pixels
	 * @param int         $spread Spread radius in pixels
	 * @param string      $hex Shadow color in hex format
	 * @param int<0, 100> $opacity Shadow opacity percentage (0-100)
	 * @param 'yes'|'no'  $inset Whether shadow is inset ('yes' or 'no')
	 * @return string CSS box-shadow style string
	 */
	public static function box_shadow_style( $horizontal = 0, $vertical = 0, $blur = 0, $spread = 0, $hex = '#000000', $opacity = 50, $inset = 'no' ) {
		return "{$horizontal}px {$vertical}px {$blur}px {$spread}px " . self::hex2rgba( $hex, $opacity ) . ( 'yes' === $inset ? ' inset' : '' );
	}

	/**
	 * Generates CSS text-shadow style string
	 *
	 * @param int         $horizontal Horizontal offset in pixels
	 * @param int         $vertical Vertical offset in pixels
	 * @param int         $blur Blur radius in pixels
	 * @param string      $hex Shadow color in hex format
	 * @param int<0, 100> $opacity Shadow opacity percentage (0-100)
	 * @return string CSS text-shadow style string
	 */
	public static function text_shadow_style( $horizontal = 0, $vertical = 0, $blur = 0, $hex = '#000000', $opacity = 50 ) {
		return "{$horizontal}px {$vertical}px {$blur}px " . self::hex2rgba( $hex, $opacity );
	}

	/**
	 * Generates CSS font style string
	 *
	 * @param int|string       $size Font size (number for pixels, string for units)
	 * @param int|string       $weight Font weight (number or keyword like 'bold')
	 * @param float|int|string $line_height Line height (number for pixels, string for relative)
	 * @param string           $family Font family name
	 * @param string|null      $style Font style (italic, normal, etc.)
	 * @param string|null      $variant Font variant (small-caps, etc.)
	 * @return string CSS font shorthand property string
	 */
	public static function font_style( $size = 16, $weight = 300, $line_height = 1.2, $family = 'Times New Roman', $style = null, $variant = null ) {
		$size        = is_int( $size ) ? "{$size}px" : $size;
		$line_height = is_int( $line_height ) ? "{$line_height}px" : $line_height;

		return str_replace( '  ', ' ', trim( "$style $variant $weight {$size}/{$line_height} \"$family\"" ) );
	}
}
