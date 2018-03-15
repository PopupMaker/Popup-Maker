<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Shortcode_PopupClose
 *
 * Registers the popup_close shortcode.
 */
class PUM_Shortcode_PopupClose extends PUM_Shortcode {

	public $version = 2;

	public $has_content = true;

	/**
	 * The shortcode tag.
	 */
	public function tag() {
		return 'popup_close';
	}

	public function label() {
		return __( 'Popup Close Button', 'popup-maker' );
	}

	public function description() {
		return __( 'Make text or html a close trigger for your popup.', 'popup-maker' );
	}

	public function inner_content_labels() {
		return array(
			'label'       => __( 'Content', 'popup-maker' ),
			'description' => __( 'Can contain other shortcodes, images, text or html content.' ),
		);
	}

	public function post_types() {
		return array( 'popup' );
	}

	public function fields() {
		return array(
			'general' => array(
				'main' => array(
					'tag'        => array(
						'label'       => __( 'HTML Tag', 'popup-maker' ),
						'placeholder' => __( 'HTML Tag', 'popup-maker' ) . ': button, span etc',
						'desc'        => __( 'The HTML tag used for this element.', 'popup-maker' ),
						'type'        => 'text',
						'std'         => '',
						'required'    => true,
					),

				),
			),
			'options' => array(
				'main' => array(
					'classes'    => array(
						'label'       => __( 'CSS Class', 'popup-maker' ),
						'placeholder' => 'my-custom-class',
						'type'        => 'text',
						'desc'        => __( 'Add additional classes for styling.', 'popup-maker' ),
						'std'         => '',
					),
					'do_default' => array(
						'type'     => 'checkbox',
						'label'    => __( 'Do not prevent the default click functionality.', 'popup-maker' ),
						'desc'     => __( 'This prevents us from disabling the browsers default action when a close button is clicked. It can be used to allow a link to a file to both close a popup and still download the file.', 'popup-maker' ),
					),
				),
			),
		);
	}

	/**
	 * Process shortcode attributes.
	 *
	 * Also remaps and cleans old ones.
	 *
	 * @param $atts
	 *
	 * @return array
	 */
	public function shortcode_atts( $atts ) {
		$atts = parent::shortcode_atts( $atts );

		if ( empty( $atts[''] ) ) {
			$atts['tag'] = 'span';
		}

		if ( ! empty( $atts['class'] ) ) {
			$atts['classes'] .= ' ' . $atts['class'];
			unset( $atts['class'] );
		}

		return $atts;
	}

	/**
	 * Shortcode handler
	 *
	 * @param  array  $atts    shortcode attributes
	 * @param  string $content shortcode content
	 *
	 * @return string
	 */
	public function handler( $atts, $content = null ) {
		$atts = $this->shortcode_atts( $atts );

		$do_default = $atts['do_default'] ? " data-do-default='" . esc_attr( $atts['do_default'] ) . "'" : '';

		$return = "<{$atts['tag']} class='pum-close popmake-close {$atts['classes']}' {$do_default}>";
		$return .= PUM_Helpers::do_shortcode( $content );
		$return .= "</{$atts['tag']}>";

		return $return;
	}

	public function template() { ?>
		<{{{attrs.tag}}} class="pum-close  popmake-close <# if (typeof attrs.classes !== 'undefined') print(attrs.classes); #>">{{{attrs._inner_content}}}</{{{attrs.tag}}}><?php
	}

}

