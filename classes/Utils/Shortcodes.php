<?php
/**
 * Shortcodes helper class.
 *
 * @since       1.21.0
 * @package     PUM
 * @copyright   Copyright (c) 2020, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Utils_Shortcodes
 */
class PUM_Utils_Shortcodes {

	/**
	 * Process do_shortcode without allowing printed side effects.
	 *
	 * @param string $shortcode_text Unprocessed string with shortcodes.
	 *
	 * @return string Processed shortcode content
	 */
	public static function clean_do_shortcode( $shortcode_text = '' ) {
		ob_start();

		$content = do_shortcode( $shortcode_text );

		$ob_content = ob_get_clean();

		if ( ! empty( $ob_content ) ) {
			$content .= $ob_content;
		}

		return $content;
	}

	/**
	 * Extract shortcodes from content.
	 *
	 * @param string $content Content containing shortcodes.
	 *
	 * @return array<int, array{
	 *     full_text: string,
	 *     tag: string,
	 *     atts: array<string, string|bool>,
	 *     content: string,
	 *     token: string
	 * }> Array of shortcodes found with structured data
	 */
	public static function get_shortcodes_from_content( $content ) {
		$pattern    = get_shortcode_regex();
		$shortcodes = [];
		if ( preg_match_all( '/' . $pattern . '/s', $content, $matches ) ) {
			foreach ( $matches[0] as $key => $value ) {
				$tag     = $matches[2][ $key ];
				$atts    = shortcode_parse_atts( $matches[3][ $key ] );
				$content = $matches[5][ $key ];

				$shortcodes[ $key ] = [
					'full_text' => $value,
					'tag'       => $tag,
					'atts'      => $atts,
					'content'   => $content,
					'token'     => self::tokenize_shortcode( $tag, $atts, $content ),
				];

				if ( ! empty( $shortcodes[ $key ]['atts'] ) ) {
					foreach ( $shortcodes[ $key ]['atts'] as $attr_name => $attr_value ) {
						// Filter numeric keys as they are valueless/truthy attributes.
						if ( is_numeric( $attr_name ) ) {
							$shortcodes[ $key ]['atts'][ $attr_value ] = true;
							unset( $shortcodes[ $key ]['atts'][ $attr_name ] );
						}
					}
				}
			}
		}

		return $shortcodes;
	}

	/**
	 * Find specific shortcodes from given content.
	 *
	 * @param string          $content Content containing shortcodes.
	 * @param string|string[] $shortcode_tags Shortcode tags to look for.
	 *
	 * @return array<int, array{
	 *     full_text: string,
	 *     tag: string,
	 *     atts: array<string, string|bool>,
	 *     content: string,
	 *     token: string
	 * }> Array of matching shortcodes with structured data
	 */
	public static function find_shortcodes_in_content( $content, $shortcode_tags = [] ) {
		if ( ! is_array( $shortcode_tags ) ) {
			$shortcode_tags = array_map( 'trim', explode( ',', $shortcode_tags ) );
		}

		$shortcodes = self::get_shortcodes_from_content( $content );

		foreach ( $shortcodes as $key => $shortcode ) {
			if ( ! in_array( $shortcode['tag'], $shortcode_tags, true ) ) {
				unset( $shortcodes[ $key ] );
			}
		}

		return $shortcodes;
	}

	/**
	 * Returns a string token for a given shortcode.
	 *
	 * @param string                            $tag Shortcode tag.
	 * @param array<string, string|bool>|string $atts Array of shortcode attributes or attribute string.
	 * @param string                            $content Shortcodes inner content.
	 *
	 * @return string MD5 hash token for the shortcode
	 */
	public static function tokenize_shortcode( $tag, $atts = [], $content = '' ) {
		if ( ! is_array( $atts ) ) {
			$atts = shortcode_parse_atts( $atts );
		}

		/**
		 * Sort attributes so we get a uniform outcome.
		 */
		ksort( $atts );

		$atts = wp_json_encode( $atts );

		/**
		 * Stringify and hash the tag & atts.
		 */
		return md5( "$tag-$atts-$content" );
	}
}
