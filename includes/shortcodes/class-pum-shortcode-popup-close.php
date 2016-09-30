<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Shortcode_Popup_Close
 *
 * Registers the popup_close shortcode.
 */
class PUM_Shortcode_Popup_Close extends PUM_Shortcode {

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
			'label' => __( 'Content', 'popup-maker' ),
			'description' => __( 'Can contain other shortcodes, images, text or html content.')
		);
	}

	public function post_types() {
		return array( 'popup' );
	}

	public function fields() {
		return array(
			'options' => array(
				'tag'   => array(
					'label'       => __( 'HTML Tag', 'popup-maker' ),
					'placeholder' => __( 'HTML Tags: button, span etc.', 'popup-maker' ),
					'desc'        => __( 'The HTML tag used to generate the trigger and wrap your text.', 'popup-maker' ),
					'type'        => 'text',
					'std'         => 'span',
					'priority'    => 10,
					'required'    => true,
				),
				'classes' => array(
					'label'       => __( 'CSS Class', 'popup-maker' ),
					'placeholder' => __( 'CSS Class', 'popup-maker' ),
					'type'        => 'text',
					'desc'        => __( 'Add additional classes for styling.', 'popup-maker' ),
					'priority'    => 15,
				),
				'do_default'      => array(
					'type'  => 'checkbox',
					'label' => __( 'Do not prevent the default click functionality.', 'popup-maker' ),
					'desc'  => __( 'This prevents us from disabling the browsers default action when a close button is clicked. It can be used to allow a link to a file to both close a popup and still download the file.', 'popup-maker' ),
					'priority'    => 20,
				),
			)
		);
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
		$atts = shortcode_atts( array(
			'tag'   => 'span',
			'do_default' => false,
			'class' => '',
			'classes' => '',
		), $atts, 'popup_close' );

		if ( ! empty( $atts['class'] ) ) {
			$atts['classes'] .= ' ' . $atts['class'];
			unset( $atts['class'] );
		}

		$return = '<' . $atts['tag'] . ' class="pum-close popmake-close' . ' ' . $atts['classes'] . '" data-do-default="' . esc_attr( $atts['do_default'] ) . '">';
		$return .= do_shortcode( $content );
		$return .= '</' . $atts['tag'] . '>';

		return $return;
	}

	public function _template() { ?>
		<script type="text/html" id="tmpl-pum-shortcode-view-popup_close">
			<{{{attr.tag}}} class="pum-close popmake-close <# if (typeof attr.classes !== 'undefined') print(attr.classes); #>">{{{attr._inner_content}}}</{{{attr.tag}}}>
		</script><?php
	}

}

new PUM_Shortcode_Popup_Close();
