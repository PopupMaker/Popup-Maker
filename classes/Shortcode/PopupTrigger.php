<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Shortcode
 *
 * This is a base class for all popup maker & extension shortcodes.
 */
class PUM_Shortcode_PopupTrigger extends PUM_Shortcode {

	/**
	 * @var int
	 */
	public $version = 2;

	/**
	 * @var bool
	 */
	public $has_content = true;

	public $ajax_rendering = true;

	/**
	 * The shortcode tag.
	 */
	public function tag() {
		return 'popup_trigger';
	}

	/**
	 * @return string
	 */
	public function label() {
		return __( 'Popup Trigger', 'popup-maker' );
	}

	/**
	 * @return string
	 */
	public function description() {
		return __( 'Inserts a click-able popup trigger.', 'popup-maker' );
	}

	/**
	 * @return array
	 */
	public function inner_content_labels() {
		return array(
			'label'       => __( 'Trigger Content', 'popup-maker' ),
			'description' => __( 'Can contain other shortcodes, images, text or html content.' ),
		);
	}

	/**
	 * @return array
	 */
	public function post_types() {
		return array( 'post', 'page', 'popup' );
	}

	/**
	 * @return array
	 */
	public function fields() {
		$select_args = array();

		if ( isset( $_GET['post'] ) && is_int( (int) $_GET['post'] ) && isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {
			$select_args['post__not_in'] = wp_parse_id_list( array( get_the_ID(), $_GET['post'] ) );
		}

		return array(
			'general' => array(
				'main' => array(
					'id'        => array(
						'label'       => __( 'Targeted Popup', 'popup-maker' ),
						'placeholder' => __( 'Choose a Popup', 'popup-maker' ),
						'desc'        => __( 'Choose which popup will be targeted by this trigger.', 'popup-maker' ),
						'type'        => 'select',
						'post_type'   => 'popup',
						'priority'    => 5,
						'required'    => true,
						'options'     => PUM_Helpers::popup_selectlist( $select_args ) + array(
								'custom' => __( 'Custom', 'popup-maker' ),
							),
						'std'         => 0,
					),
					'custom_id' => array(
						'label'        => __( 'Custom Popup ID', 'popup-maker' ),
						'type'         => 'text',
						'dependencies' => array(
							'id' => 'custom',
						),
						'std'          => '',
					),
				),
			),
			'options' => array(
				'main' => array(
					'tag'        => array(
						'label'       => __( 'HTML Tag', 'popup-maker' ),
						'placeholder' => __( 'HTML Tags: button, span etc.', 'popup-maker' ),
						'desc'        => __( 'The HTML tag used to generate the trigger and wrap your text.', 'popup-maker' ),
						'type'        => 'text',
						'std'         => '',
						'priority'    => 10,
						'required'    => true,
					),
					'classes'    => array(
						'label'       => __( 'CSS Class', 'popup-maker' ),
						'placeholder' => __( 'CSS Class', 'popup-maker' ),
						'type'        => 'text',
						'desc'        => __( 'Add additional classes for styling.', 'popup-maker' ),
						'priority'    => 15,
						'std'         => '',
					),
					'class' => array(
					    'type' => 'hidden',
                    ),
					'do_default' => array(
						'type'     => 'checkbox',
						'label'    => __( 'Do not prevent the default click functionality.', 'popup-maker' ),
						'desc'     => __( 'This prevents us from disabling the browsers default action when a trigger is clicked. It can be used to allow a link to a file to both trigger a popup and still download the file.', 'popup-maker' ),
						'priority' => 20,
						'std'      => false,
					),

				),
			),
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
		$atts = $this->shortcode_atts( $atts );


		$return = '<' . $atts['tag'] . ' class="pum-trigger  popmake-' . $atts['id'] . ' ' . $atts['classes'] . '"  data-do-default="' . esc_attr( $atts['do_default'] ) . '">';
		$return .= PUM_Helpers::do_shortcode( $content );
		$return .= '</' . $atts['tag'] . '>';

		return $return;
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

		if ( empty( $atts['tag'] ) ) {
			$atts['tag'] = 'span';
		}

		if ( $atts['id'] == 'custom' ) {
			$atts['id'] = $atts['custom_id'];
		}

		if ( ! empty( $atts['class'] ) ) {
			$atts['classes'] .= ' ' . $atts['class'];
			unset( $atts['class'] );
		}


		return $atts;
	}

	/**
	 *
	 */
	public function template() { ?>
		<{{{attrs.tag}}} class="pum-trigger  popmake-{{{attrs.id}}} {{{attrs.classes}}}">{{{attrs._inner_content}}}</{{{attrs.tag}}}><?php
	}

}
