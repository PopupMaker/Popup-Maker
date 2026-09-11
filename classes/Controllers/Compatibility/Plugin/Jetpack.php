<?php
/**
 * Jetpack compatibility controller.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Controllers\Compatibility\Plugin;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Work around object-valued Jetpack form attributes causing rendering fatals.
 */
class Jetpack extends Controller {

	/**
	 * Check if Jetpack Forms is available.
	 *
	 * @return bool
	 */
	public function controller_enabled() {
		return class_exists( '\Automattic\Jetpack\Forms\ContactForm\Contact_Form' );
	}

	/**
	 * Initialize Jetpack compatibility hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'render_block_data', [ $this, 'normalize_popup_form_block_data' ] );
	}

	/**
	 * Normalize Jetpack field block data rendered from popup content.
	 *
	 * Jetpack Forms converts field block attributes back into shortcode
	 * attributes when rendering synced forms. Its converter supports arrays,
	 * but object-valued attributes cause a fatal error when escaped as strings.
	 *
	 * @param mixed $parsed_block Parsed block data.
	 * @return mixed Normalized block data.
	 */
	public function normalize_popup_form_block_data( $parsed_block ) {
		if ( ! doing_filter( 'pum_popup_content' ) || ! is_array( $parsed_block ) ) {
			return $parsed_block;
		}

		$block_name = isset( $parsed_block['blockName'] ) && is_string( $parsed_block['blockName'] ) ? $parsed_block['blockName'] : '';

		if ( 0 !== strpos( $block_name, 'jetpack/field-' ) ) {
			return $parsed_block;
		}

		return $this->convert_objects_to_arrays( $parsed_block );
	}

	/**
	 * Recursively convert stdClass values to arrays.
	 *
	 * @param mixed $value Value to normalize.
	 * @return mixed Normalized value.
	 */
	private function convert_objects_to_arrays( $value ) {
		if ( $value instanceof \stdClass ) {
			$value = (array) $value;
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = $this->convert_objects_to_arrays( $item );
			}
		}

		return $value;
	}
}
