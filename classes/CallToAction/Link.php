<?php
/**
 * Link Call To Action class.
 *
 * @since       1.14
 * @package     PUM
 * @copyright   Copyright (c) 2020, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_CallToAction_Link
 */
class PUM_CallToAction_Link extends PUM_Abstract_CallToAction {

	/**
	 * Key identifier.
	 *
	 * @var string
	 */
	protected $key = 'link';

	/**
	 * Version of this cta.
	 *
	 * @var int
	 */
	public $version = 1;

	/**
	 * Label for this cta.
	 *
	 * @return string
	 */
	public function label() {
		return __( 'Link', 'popup-maker' );
	}

	/**
	 * Array of options for this CTA.
	 *
	 * @return array
	 */
	public function fields() {
		return [
			'general'    => [
				// TODO This might best be handled as block textarea or shortcode inner content.
				'text'              => [
					'type'         => 'text',
					'label'        => __( 'Enter text for your call to action.', 'popup-maker' ),
					'std'          => __( 'Learn more', 'popup-maker' ),
					'dependencies' => [],
					'priority'     => 1.1,
				],
				'link'              => [
					'type'         => 'link',
					'label'        => __( 'Enter a link for your call to action.', 'popup-maker' ),
					'dependencies' => [],
					'priority'     => 1.2,
				],
				// Will this be part of the link picker API?
				'link_target_blank' => [
					'type'         => 'checkbox',
					'label'        => __( 'Open in a new tab.', 'popup-maker' ),
					'dependencies' => [],
					'priority'     => 1.3,
				],
			],
			'appearance' => [
				'element_type'    => [
					'type'         => 'radio',
					'label'        => __( 'Choose how this link appears.', 'popup-maker' ),
					'options'      => [
						'text'   => __( 'Text Link', 'popup-maker' ),
						'button' => __( 'Button', 'popup-maker' ),
					],
					'std'          => 'button',
					'dependencies' => [],
					'priority'     => 1.1,
				],
				'element_classes' => [
					'type'         => 'text',
					'label'        => __( 'Enter text for your call to action.', 'popup-maker' ),
					'std'          => __( 'Learn more', 'popup-maker' ),
					'dependencies' => [],
					'priority'     => 1.2,
				],
			],
		];
	}

	/**
	 * Output handler
	 *
	 * This will handle rendering for both shortcodes and blocks.
	 *
	 * @param  array  $atts    Array of options / attributes.
	 * @param  string $content Inner content.
	 *
	 * @return string
	 */
	public function handler( $atts, $content = null ) {
		$atts = $this->parse_atts( $atts );

		$url     = $atts['link'];
		$target  = $atts['link_target_blank'] ? '_blank' : '';
		$text    = ! empty( $atts['text'] ) ? $atts['text'] : $content;
		$classes = array_merge(
			[
				'pum-cta',
				'pum-cta--link',
				'button' === $atts['element_type'] ? 'pum-cta--button' : null,
			],
			explode( ',', $atts['element_classes'] )
		);

		return sprintf( "<a href='%s' class='%s' target='%s'>%s</a>", $url, implode( ' ', array_filter( $classes ) ), $target, $text );
	}

	/**
	 * Template used for rendering visual shortcodes in old editor.
	 */
	public function template() {
		?>
		<a
			class="pum-cta pum-cta--lin class='pum-cta pum-cta--link {{{'button' === attrs.element_type ? pum-cta--button}}} {{{attrs.element_classes}}}"
			target="{{{attrs.link_target_blank ? '_blank' : ''}}}"
		>
			{{{attrs.text || attrs._inner_content}}}
		</a>
		<?php
	}

}
