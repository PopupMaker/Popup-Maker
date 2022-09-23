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
						'desc'        => __( 'The HTML tag used for this element.', 'popup-maker' ),
						'type'         => 'select',
						'options'      => array(
							'a'      => 'a',
							'button' => 'button',
							'div'    => 'div',
							'img'    => 'img',
							'li'     => 'li',
							'p'      => 'p',
							'span'   => 'span',
						),
						'std'         => 'span',
						'required'    => true,
					),
					'href'   => array(
						'label'        => __( 'Value for href', 'popup-maker' ),
						'placeholder'  => '#',
						'desc'         => __( 'Enter the href value for your link. Leave blank if you do not want this link to take the visitor to a different page.', 'popup-maker' ),
						'type'         => 'text',
						'std'          => '',
						'dependencies' => array(
							'tag' => array( 'a' ),
						),
					),
					'target'   => array(
						'label'        => __( 'Target for the element', 'popup-maker' ),
						'placeholder'  => '',
						'desc'         => __( 'Enter the target value for your link. Can be left blank.', 'popup-maker' ),
						'type'         => 'text',
						'std'          => '',
						'dependencies' => array(
							'tag' => array( 'a' ),
						),
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
		global $allowedtags;

		$atts = parent::shortcode_atts( $atts );

		if ( empty( $atts['tag'] ) || ! in_array( $atts['tag'], array_keys( $allowedtags ) ) ) {
			$atts['tag'] = 'span';
		}

		if ( empty( $atts['href'] ) ) {
			$atts['href'] = '#';
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

		$tag = esc_attr( $atts['tag'] );

		$do_default = $atts['do_default'] ? " data-do-default='true'" : '';

		// Sets up our href and target, if the tag is an `a`.
		$href   = 'a' === $atts['tag'] ? "href='{" . esc_attr( $atts['href'] ) . "}'" : '';
		$target = 'a' === $atts['tag'] && ! empty( $atts['target'] ) ? "target='" . esc_attr( $atts['target'] ) . "'" : '';

		$return = "<{$tag} $href $target class='pum-close popmake-close " . esc_attr( $atts['classes'] ) . "' {$do_default}>";
		$return .= esc_html( PUM_Helpers::do_shortcode( $content ) );
		$return .= "</{$tag}>";

		return $return;
	}

	/**
	 * NOTE: Data comes here already filtered through shortcode_atts above.
	 */
	public function template() {
		global $allowedtags;
		?>
		<#
			const allowedTags = <?php echo json_encode( array_keys( $allowedtags ) ); ?>;
			const tag = allowedTags.indexOf( attrs.tag ) >= 0 ? attrs.tag : 'span';
		#>
		<{{{tag}}} class="pum-close  popmake-close <# if (typeof attrs.classes !== 'undefined') print(attrs.classes); #>">{{{attrs._inner_content}}}</{{{tag}}}><?php
	}

}
