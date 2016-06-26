<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Shortcode_Popup
 *
 * Registers the popup_close shortcode.
 */
class PUM_Shortcode_Popup extends PUM_Shortcode {

	public $has_content = true;

	public $inner_content_priority = 15;

	/**
	 * The shortcode tag.
	 */
	public function tag() {
		return 'popup';
	}

	public function label() {
		return __( 'Popup', 'popup-maker' );
	}

	public function description() {
		return __( 'Insert a popup inline rather. Great for simple popups used for supporting content.', 'popup-maker' );
	}

	public function inner_content_labels() {
		return array(
			'label'       => __( 'Content', 'popup-maker' ),
			'description' => __( 'Can contain other shortcodes, images, text or html content.', 'popup-maker' ),
		);
	}

	public function post_types() {
		return array( 'post', 'page' );
	}

	public function sections() {
		return array(
			'general'   => __( 'General', 'popup-maker' ),
			'display'   => __( 'Display', 'popup-maker' ),
			'position'  => __( 'Position', 'popup-maker' ),
			'animation' => __( 'Animation', 'popup-maker' ),
			'close'     => __( 'Close', 'popup-maker' ),
		);
	}

	public function get_popup_themes() {
		$themes = popmake_get_all_popup_themes();

		$popup_themes = array();

		foreach ( $themes as $theme ) {
			$popup_themes[ $theme->post_title ] = $theme->ID;
		}

		return $popup_themes;
	}

	public function fields() {
		return array(
			'general' => array(
				'id'    => array(
					'label'       => __( 'Unique Popup ID', 'popup-maker' ),
					'placeholder' => __( '`offer`, `more-info`', 'popup-maker' ),
					'desc'        => __( 'Used in popup triggers to target this popup', 'popup-maker' ),
					'priority'    => 5,
					'required'    => true,
				),
				'title' => array(
					'label'       => __( 'Popup Title', 'popup-maker' ),
					'placeholder' => __( 'Enter popup title text,', 'popup-maker' ),
					'desc'        => __( 'This will be displayed above the content. Leave it empty to disable it.', 'popup-maker' ),
					'priority'    => 10,
				),
			),
			'display' => array(
				'theme_id'         => array(
					'type'        => 'select',
					'label'       => __( 'Popup Theme', 'popup-maker' ),
					'placeholder' => __( 'Choose a theme,', 'popup-maker' ),
					'desc'        => __( 'Choose which popup theme will be used.', 'popup-maker' ),
					'std'         => popmake_get_default_popup_theme(),
					'select2'     => true,
					'options'     => $this->get_popup_themes(),
					'required'    => true,
					'priority'    => 5,
				),
				'overlay_disabled' => array(
					'label'       => __( 'Disable Overlay', 'popup-maker' ),
					'description' => __( 'Checking this will disable and hide the overlay for this popup.', 'popup-maker' ),
					'type'        => 'checkbox',
					'std'         => false,
					'priority'    => 10,
				),
				'size'                      => array(
					'label'       => __( 'Size', 'popup-maker' ),
					'description' => __( 'Select the size of the popup.', 'popup-maker' ),
					'type'        => 'select',
					'std'         => 'small',
					'options'     => apply_filters( 'popmake_popup_display_size_options', array() ),
					'priority'    => 15,
				),
				'width'            => array(
					'label'    => __( 'Width', 'popup-maker' ),
					'priority'    => 20,
				),
				'width_unit'       => array(
					'label'       => __( 'Width Unit', 'popup-maker' ),
					'type'        => 'select',
					'std'         => 'px',
					'options'     => apply_filters( 'popmake_size_unit_options', array() ),
					'priority'    => 25,
				),
				'height'           => array(
					'label'    => __( 'Height', 'popup-maker' ),
					'priority'    => 30,
				),
				'height_unit'       => array(
					'label'       => __( 'Height Unit', 'popup-maker' ),
					'type'        => 'select',
					'std'         => 'px',
					'options'     => apply_filters( 'popmake_size_unit_options', array() ),
					'priority'    => 35,
				),
			),
			'position'  => array(
				'location'        => array(
					'label'       => __( 'Location', 'popup-maker' ),
					'description' => __( 'Choose where the popup will be displayed.', 'popup-maker' ),
					'type'        => 'select',
					'std'         => 'center top',
					'priority'    => 4,
					'options'     => apply_filters( 'popmake_popup_display_location_options', array() ),
				),
				'position_top'    => array(
					'label'       => __( 'Top', 'popup-maker' ),
					'description' => sprintf( _x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ), strtolower( __( 'Top', 'popup-maker' ) ) ),
					'type'        => 'rangeslider',
					'std'         => 100,
					'priority'    => 10,
					'step'        => 1,
					'min'         => 0,
					'max'         => 500,
					'unit'        => 'px',
				),
				'position_bottom' => array(
					'label'       => __( 'Bottom', 'popup-maker' ),
					'description' => sprintf( _x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ), strtolower( __( 'Bottom', 'popup-maker' ) ) ),
					'type'        => 'rangeslider',
					'std'         => 0,
					'priority'    => 10,
					'step'        => 1,
					'min'         => 0,
					'max'         => 500,
					'unit'        => 'px',
				),
				'position_left'   => array(
					'label'       => __( 'Left', 'popup-maker' ),
					'description' => sprintf( _x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ), strtolower( __( 'Left', 'popup-maker' ) ) ),
					'type'        => 'rangeslider',
					'std'         => 0,
					'priority'    => 10,
					'step'        => 1,
					'min'         => 0,
					'max'         => 500,
					'unit'        => 'px',
				),
				'position_right'  => array(
					'label'       => __( 'Right', 'popup-maker' ),
					'description' => sprintf( _x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ), strtolower( __( 'Right', 'popup-maker' ) ) ),
					'type'        => 'rangeslider',
					'std'         => 0,
					'priority'    => 10,
					'step'        => 1,
					'min'         => 0,
					'max'         => 500,
					'unit'        => 'px',
				),
			),
			'animation' => array(
				'animation_type'            => array(
					'label'       => __( 'Animation Type', 'popup-maker' ),
					'description' => __( 'Select an animation type for your popup.', 'popup-maker' ),
					'type'        => 'select',
					'std'         => 'fade',
					'priority'    => 5,
					'options'     => apply_filters( 'popmake_popup_display_animation_type_options', array() ),
				),
				'animation_speed'           => array(
					'label'       => __( 'Animation Speed', 'popup-maker' ),
					'description' => __( 'Set the animation speed for the popup.', 'popup-maker' ),
					'type'        => 'rangeslider',
					'std'         => 350,
					'priority'    => 10,
					'step'        => 10,
					'min'         => 50,
					'max'         => 1000,
					'unit'        => __( 'ms', 'popup-maker' ),
				),
				'animation_origin'          => array(
					'label'       => __( 'Animation Origin', 'popup-maker' ),
					'description' => __( 'Choose where the animation will begin.', 'popup-maker' ),
					'type'        => 'select',
					'std'         => 'center top',
					'priority'    => 15,
					'options'     => apply_filters( 'popmake_popup_display_animation_origin_options', array() ),
				),
			),
			'close'     => array(
				'overlay_click' => array(
					'label'       => __( 'Click Overlay to Close', 'popup-maker' ),
					'description' => __( 'Checking this will cause popup to close when user clicks on overlay.', 'popup-maker' ),
					'type'        => 'checkbox',
					'std'         => false,
					'priority'    => 5,
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
		global $popup;

		$atts = shortcode_atts( apply_filters( 'pum_popup_shortcode_default_atts', array(

			'id'    => "",
			'title' => "",

			'theme_id'         => null,
			'theme'            => null,
			'overlay_disabled' => 0,
			'size'             => "small",
			'width'            => "",
			'width_unit'       => "px",
			'height'           => "",
			'height_unit'      => "px",

			'location'        => "center top",
			'position_top'    => 100,
			'position_left'   => 0,
			'position_bottom' => 0,
			'position_right'  => 0,
			'position_fixed'  => 0,

			'animation_type'   => "slide",
			'animation_speed'  => 350,
			'animation_origin' => 'top',

			'overlay_click' => 0,
			'esc_press'     => 1,
		) ), $atts, 'popup' );

		// We need to fake a popup using the PUM_Popup data model.
		$popup = new PUM_Popup;

		$popup->ID           = $atts['id'];
		$popup->title        = $atts['title'];
		$popup->post_content = $content;

		// Get Theme ID
		if ( ! $atts['theme_id'] ) {
			$atts['theme_id'] = $atts['theme'] ? $atts['theme'] : popmake_get_default_popup_theme();
		}

		// Theme ID
		$popup->theme_id = $atts['theme_id'];

		// Display Meta
		$popup->display = array(
			'size'               => $atts['size'],
			'overlay_disabled'   => $atts['overlay_disabled'],
			'custom_width'       => $atts['width'],
			'custom_width_unit'  => $atts['width_unit'],
			'custom_height'      => $atts['height'],
			'custom_height_unit' => $atts['height_unit'],
			'custom_height_auto' => $atts['width'] > 0 ? 0 : 1,
			'location'           => $atts['location'],
			'position_top'       => $atts['position_top'],
			'position_left'      => $atts['position_left'],
			'position_bottom'    => $atts['position_bottom'],
			'position_right'     => $atts['position_right'],
			'position_fixed'     => $atts['position_fixed'],
			'animation_type'     => $atts['animation_type'],
			'animation_speed'    => $atts['animation_speed'],
			'animation_origin'   => $atts['animation_origin'],
		);

		// Close Meta
		$popup->close = array(
			'overlay_click' => $atts['overlay_click'],
			'esc_press'     => $atts['esc_press'],
		);

		$popup->triggers = array(
			array(
				'type'     => 'click_open',
				'settings' => array(),
			),
		);

		ob_start();
		popmake_get_template_part( 'popup' );

		return ob_get_clean();
	}

	public function _template() { ?>
		<script type="text/html" id="tmpl-pum-shortcode-view-popup">
			<?php _e( 'Popup', 'popup-maker' ); ?>: ID "{{attr.id}}"
		</script><?php
	}

}

new PUM_Shortcode_Popup();
