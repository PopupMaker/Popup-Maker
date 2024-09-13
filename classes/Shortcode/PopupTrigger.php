<?php
/**
 * Shortcode for PopupTrigger
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

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
		return [
			'label'       => __( 'Trigger Content', 'popup-maker' ),
			'description' => __( 'Can contain other shortcodes, images, text or html content.', 'popup-maker' ),
		];
	}

	/**
	 * @return array
	 */
	public function post_types() {
		return [ 'post', 'page', 'popup' ];
	}

	/**
	 * @return array
	 */
	public function fields() {
		$select_args = [];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : null;

		if ( 'edit' === $action && is_int( $post ) ) {
			$select_args['post__not_in'] = wp_parse_id_list( [ get_the_ID(), $post ] );
		}

		return [
			'general' => [
				'main' => [
					'id'        => [
						'label'       => __( 'Targeted Popup', 'popup-maker' ),
						'placeholder' => __( 'Choose a Popup', 'popup-maker' ),
						'desc'        => __( 'Choose which popup will be targeted by this trigger.', 'popup-maker' ),
						'type'        => 'select',
						'post_type'   => 'popup',
						'priority'    => 5,
						'required'    => true,
						'options'     => PUM_Helpers::popup_selectlist( $select_args ) + [
							'custom' => __( 'Custom', 'popup-maker' ),
						],
						'std'         => 0,
					],
					'custom_id' => [
						'label'        => __( 'Custom Popup ID', 'popup-maker' ),
						'type'         => 'text',
						'dependencies' => [
							'id' => 'custom',
						],
						'std'          => '',
					],
				],
			],
			'options' => [
				'main' => [
					'tag'        => [
						'label'       => __( 'HTML Tag', 'popup-maker' ),
						'placeholder' => __( 'HTML Tags: button, span etc.', 'popup-maker' ),
						'desc'        => __( 'The HTML tag used to generate the trigger and wrap your text.', 'popup-maker' ),
						'type'        => 'text',
						'std'         => '',
						'priority'    => 10,
						'required'    => true,
					],
					'classes'    => [
						'label'       => __( 'CSS Class', 'popup-maker' ),
						'placeholder' => __( 'CSS Class', 'popup-maker' ),
						'type'        => 'text',
						'desc'        => __( 'Add additional classes for styling.', 'popup-maker' ),
						'priority'    => 15,
						'std'         => '',
					],
					'class'      => [
						'type' => 'hidden',
					],
					'do_default' => [
						'type'     => 'checkbox',
						'label'    => __( 'Do not prevent the default click functionality.', 'popup-maker' ),
						'desc'     => __( 'This prevents us from disabling the browsers default action when a trigger is clicked. It can be used to allow a link to a file to both trigger a popup and still download the file.', 'popup-maker' ),
						'priority' => 20,
						'std'      => false,
					],

				],
			],
		];
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

		$tag        = esc_attr( $atts['tag'] );
		$id         = esc_attr( $atts['id'] );
		$classes    = esc_attr( $atts['classes'] );
		$do_default = esc_attr( $atts['do_default'] );
		// Escaped using notes here: https://wordpress.stackexchange.com/a/357349/63942.
		$esc_content = PUM_Helpers::do_shortcode( force_balance_tags( wp_kses_post( $content ) ) );

		$return = "<$tag class='pum-trigger  popmake-$id  $classes' data-do-default='$do_default'>$esc_content</$tag>";

		PUM_Site_Popups::preload_popup_by_id_if_enabled( $atts['id'] );

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
		global $allowedtags;

		$atts = parent::shortcode_atts( $atts );

		// Add button to allowed tags.
		$tags_allowed = array_merge( array_keys( $allowedtags ), [ 'button' ] );

		if ( empty( $atts['tag'] ) || ! in_array( $atts['tag'], $tags_allowed, true ) ) {
			$atts['tag'] = 'span';
		}

		if ( 'custom' === $atts['id'] ) {
			$atts['id'] = $atts['custom_id'];
		}

		if ( ! empty( $atts['class'] ) ) {
			$atts['classes'] .= ' ' . $atts['class'];
			unset( $atts['class'] );
		}

		return $atts;
	}

	public function template() {
		global $allowedtags;
		?>
		<#
			const allowedTags = <?php echo wp_json_encode( array_keys( $allowedtags ) ); ?>;
			const tag = allowedTags.indexOf( attrs.tag ) >= 0 ? attrs.tag : 'span';
		#>
		<{{{tag}}} class="pum-trigger  popmake-{{{attrs.id}}} {{{attrs.classes}}}">{{{attrs._inner_content}}}</{{{tag}}}>
		<?php
	}
}
