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
				'url'               => [
					'type'     => 'link',
					'label'    => __( 'Enter a link for your call to action.', 'popup-maker' ),
					'priority' => 1.2,
				],
				// Will this be part of the link picker API?
				'link_target_blank' => [
					'type'     => 'checkbox',
					'label'    => __( 'Open in a new tab.', 'popup-maker' ),
					'priority' => 1.3,
				],
			],
			'appearance' => [],
		];
	}

	/**
	 * Output handler
	 *
	 * This will handle rendering for both shortcodes and blocks.
	 *
	 * @param array $atts Array of options / attributes.
	 *
	 * @return string
	 */
	public function render( $atts = [] ) {
		$atts = $this->parse_atts( $atts );

		$url     = $atts['url'];
		$target  = $atts['link_target_blank'] ? '_blank' : '';
		$text    = ! empty( $atts['cta_text'] ) ? $atts['cta_text'] : '';
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
