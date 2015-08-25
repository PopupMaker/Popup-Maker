<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Popmake_Fields {

	public $field_prefix = '';

	public $callback_prefix = 'popmake_field_';

	public $fields = array();

	public $sections = array();

	private static $instances = array();

	public static function instance() {
		$class = get_called_class();

		$class_key = md5( $class );

		if ( ! isset( self::$instances[ $class_key ] ) || ! self::$instances[ $class_key ] instanceof $class ) {
			self::$instances[ $class_key ] = new $class;
		}

		return self::$instances[ $class_key ];
	}

	public function register_section( $id, $title, $callback = null ) {
		$this->sections[ $id ] = array(
			'id'       => $id,
			'title'    => $title,
			'callback' => $callback
		);
	}

	/**
	 * @param $id
	 * @param $section
	 * @param $args
	 */
	public function add_field( $id, $section, $args ) {
		$this->fields[ $section ][ $id ] = $args;
	}

	public function add_fields( $section, $fields ) {
		foreach ( $fields as $field => $args ) {
			$this->add_field( $field, $section, $args );
		}
	}


	public function get_fields( $section = null ) {
		if ( ! $section ) {
			return $this->get_all_fields();
		}

		if ( ! isset( $this->fields[ $section ] ) ) {
			return array();
		}

		uasort( $this->fields[ $section ], array( $this, 'sort_by_priority' ) );

		return $this->fields[ $section ];
	}

	public function get_all_fields() {
		$all_fields = array();
		foreach ( $this->fields as $section => $fields ) {
			$all_fields[ $section ] = $this->get_fields( $section );
		}

		return $all_fields;
	}


	/**
	 * Sort array by priority value
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	protected function sort_by_priority( $a, $b ) {
		if ( ! isset( $a['priority'] ) || ! isset( $b['priority'] ) || $a['priority'] === $b['priority'] ) {
			return 0;
		}

		return ( $a['priority'] < $b['priority'] ) ? - 1 : 1;
	}

	public function get_field_names( $section ) {
		$names = array();

		foreach ( $this->get_fields( $section ) as $field => $args ) {
			$names[] = "{$this->field_prefix}_{$section}_{$field}";
		}

		return $names;
	}

}


class Popmake_Popup_Fields extends Popmake_Fields {
	public $field_prefix = 'popup_';

	public function __construct() {
		$this->fields = array(
			'display'     => array(
				'size'                      => array(
					'label'       => __( 'Size', 'popup-maker' ),
					'description' => __( 'Select the size of the popup.', 'popup-maker' ),
					'type'        => 'select',
					'std'         => 'medium',
					'priority'    => 1,
					'options'     => apply_filters( 'popmake_popup_display_size_options', array() ),
				),
				'responsive_min_width'      => array(
					'label'       => __( 'Min Width', 'popup-maker' ),
					'description' => __( 'Set a minimum width for the popup.', 'popup-maker' ),
					'type'        => 'measure',
					'std'         => '',
					'priority'    => 2,
					'units'       => apply_filters( 'popmake_size_unit_options', array() ),
				),
				'responsive_min_width_unit' => array(
					'std' => 'px'
				),
				'responsive_max_width'      => array(
					'label'       => __( 'Max Width', 'popup-maker' ),
					'description' => __( 'Set a maximum width for the popup.', 'popup-maker' ),
					'type'        => 'measure',
					'std'         => '',
					'priority'    => 3,
					'units'       => apply_filters( 'popmake_size_unit_options', array() ),
				),
				'responsive_max_width_unit' => array(
					'std' => 'px'
				),
				'custom_width'              => array(
					'label'       => __( 'Width', 'popup-maker' ),
					'description' => __( 'Set a custom width for the popup.', 'popup-maker' ),
					'type'        => 'measure',
					'std'         => 640,
					'priority'    => 4,
					'units'       => apply_filters( 'popmake_size_unit_options', array() ),
				),
				'custom_width_unit'         => array(
					'std' => 'px'
				),
				'custom_height_auto'        => array(
					'label'       => __( 'Auto Adjusted Height', 'popup-maker' ),
					'description' => __( 'Checking this option will set height to fit the content.', 'popup-maker' ),
					'type'        => 'checkbox',
					'std'         => false,
					'priority'    => 5,
				),
				'scrollable_content'        => array(
					'label'       => __( 'Scrollable Content', 'popup-maker' ),
					'description' => __( 'Checking this option will add a scroll bar to your content.', 'popup-maker' ),
					'type'        => 'checkbox',
					'std'         => false,
					'priority'    => 6,
				),
				'custom_height'             => array(
					'label'       => __( 'Height', 'popup-maker' ),
					'description' => __( 'Set a custom height for the popup.', 'popup-maker' ),
					'type'        => 'measure',
					'std'         => 380,
					'priority'    => 7,
					'units'       => apply_filters( 'popmake_size_unit_options', array() ),
				),
				'custom_height_unit'        => array(
					'std' => 'px'
				),
				'overlay_disabled'          => array(
					'label'       => __( 'Disable Overlay', 'popup-maker' ),
					'description' => __( 'Checking this will disable and hide the overlay for this popup.', 'popup-maker' ),
					'type'        => 'checkbox',
					'std'         => false,
					'priority'    => 8,
				),
				'animation_type'            => array(
					'label'       => __( 'Animation Type', 'popup-maker' ),
					'description' => __( 'Select an animation type for your popup.', 'popup-maker' ),
					'type'        => 'select',
					'std'         => 'fade',
					'priority'    => 9,
					'options'     => apply_filters( 'popmake_popup_display_animation_type_options', array() ),
				),
				'animation_speed'           => array(
					'label'       => __( 'Animation Speed', 'popup-maker' ),
					'description' => __( 'Set the animation speed for the popup.', 'popup-maker' ),
					'type'        => 'rangeslider',
					'std'         => 350,
					'priority'    => 10,
					'step'        => apply_filters( 'popmake_popup_display_animation_speed_step', 10 ),
					'min'         => apply_filters( 'popmake_popup_display_animation_speed_min', 50 ),
					'max'         => apply_filters( 'popmake_popup_display_animation_speed_max', 1000 ),
					'unit'        => __( 'ms', 'popup-maker' ),
				),
				'animation_origin'          => array(
					'label'       => __( 'Animation Origin', 'popup-maker' ),
					'description' => __( 'Choose where the animation will begin.', 'popup-maker' ),
					'type'        => 'select',
					'std'         => 'center top',
					'priority'    => 11,
					'options'     => apply_filters( 'popmake_popup_display_animation_origin_options', array() ),
				),
				'stackable'                 => array(
					'label'       => __( 'Stackable', 'popup-maker' ),
					'description' => __( 'This enables other popups to remain open.', 'popup-maker' ),
					'type'        => 'checkbox',
					'std'         => false,
					'priority'    => 12,
				),
				'position_fixed'            => array(
					'label'       => __( 'Fixed Postioning', 'popup-maker' ),
					'description' => __( 'Checking this sets the positioning of the popup to fixed.', 'popup-maker' ),
					'type'        => 'checkbox',
					'std'         => false,
					'priority'    => 13,
				),
				'location'                  => array(
					'label'       => __( 'Location', 'popup-maker' ),
					'description' => __( 'Choose where the popup will be displayed.', 'popup-maker' ),
					'type'        => 'select',
					'std'         => 'center top',
					'priority'    => 14,
					'options'     => apply_filters( 'popmake_popup_display_location_options', array() ),
				),
				'position_top'              => array(
					'label'       => __( 'Top', 'popup-maker' ),
					'description' => __( 'Distance from the top edge of the screen.', 'popup-maker' ),
					'type'        => 'rangeslider',
					'std'         => 100,
					'priority'    => 15,
					'step'        => apply_filters( 'popmake_popup_display_position_top_step', 1 ),
					'min'         => apply_filters( 'popmake_popup_display_position_top_min', 0 ),
					'max'         => apply_filters( 'popmake_popup_display_position_top_max', 500 ),
					'unit'        => __( 'px', 'popup-maker' ),
				),
				'position_bottom'           => array(
					'label'       => __( 'Bottom', 'popup-maker' ),
					'description' => __( 'Distance from the bottom edge of the screen.', 'popup-maker' ),
					'type'        => 'rangeslider',
					'std'         => 0,
					'priority'    => 14,
					'step'        => apply_filters( 'popmake_popup_display_position_bottom_step', 1 ),
					'min'         => apply_filters( 'popmake_popup_display_position_bottom_min', 0 ),
					'max'         => apply_filters( 'popmake_popup_display_position_bottom_max', 500 ),
					'unit'        => __( 'px', 'popup-maker' ),
				),
				'position_left'             => array(
					'label'       => __( 'Left', 'popup-maker' ),
					'description' => __( 'Distance from the left edge of the screen.', 'popup-maker' ),
					'type'        => 'rangeslider',
					'std'         => 0,
					'priority'    => 15,
					'step'        => apply_filters( 'popmake_popup_display_position_left_step', 1 ),
					'min'         => apply_filters( 'popmake_popup_display_position_left_min', 0 ),
					'max'         => apply_filters( 'popmake_popup_display_position_left_max', 500 ),
					'unit'        => __( 'px', 'popup-maker' ),
				),
				'position_right'            => array(
					'label'       => __( 'Right', 'popup-maker' ),
					'description' => __( 'Distance from the right edge of the screen.', 'popup-maker' ),
					'type'        => 'rangeslider',
					'std'         => 0,
					'priority'    => 15,
					'step'        => apply_filters( 'popmake_popup_display_position_right_step', 1 ),
					'min'         => apply_filters( 'popmake_popup_display_position_right_min', 0 ),
					'max'         => apply_filters( 'popmake_popup_display_position_right_max', 500 ),
					'unit'        => __( 'px', 'popup-maker' ),
				),
				'overlay_zindex'            => array(
					'label'       => __( 'Overlay Z-Index', 'popup-maker' ),
					'description' => __( 'Change the z-index layer level for the overlay.', 'popup-maker' ),
					'type'        => 'number',
					'std'         => 1999999998,
					'priority'    => 16,
					'min'         => 998,
					'max'         => 2147483646,
				),
				'zindex'                    => array(
					'label'       => __( 'Popup Z-Index', 'popup-maker' ),
					'description' => __( 'Change the z-index layer level for the popup.', 'popup-maker' ),
					'type'        => 'number',
					'std'         => 1999999999,
					'priority'    => 17,
					'min'         => 999,
					'max'         => 2147483647,
				),
			),
			'auto_open'   => array(
				'enabled'        => array(
					'label'       => __( 'Enable Auto Open Popups', 'popup-maker' ),
					'description' => __( 'Checking this will cause popup to open automatically.', 'popup-maker' ),
					'type'        => 'checkbox',
					'std'         => false,
					'priority'    => 1,
				),
				'delay'          => array(
					'label'       => __( 'Delay', 'popup-maker' ),
					'description' => __( 'The delay before the popup will open in milliseconds.', 'popup-maker' ),
					'type'        => 'rangeslider',
					'std'         => 500,
					'priority'    => 2,
					'step'        => apply_filters( 'popmake_popup_auto_open_delay_step', 500 ),
					'min'         => apply_filters( 'popmake_popup_auto_open_delay_min', 0 ),
					'max'         => apply_filters( 'popmake_popup_auto_open_delay_max', 10000 ),
					'unit'        => __( 'ms', 'popup-maker' ),
				),
				'cookie_trigger' => array(
					'label'       => __( 'Cookie Trigger', 'popup-maker' ),
					'description' => __( 'When do you want to create the cookie.', 'popup-maker' ),
					'type'        => 'select',
					'std'         => 'close',
					'priority'    => 3,
					'options'     => apply_filters( 'popmake_cookie_trigger_options', array() ),
				),
				'session_cookie' => array(
					'label'       => __( 'Use Session Cookie?', 'popup-maker' ),
					'description' => __( 'Session cookies expire when the user closes their browser.', 'popup-maker' ),
					'type'        => 'checkbox',
					'std'         => false,
					'priority'    => 4,
				),
				'cookie_time'    => array(
					'label'       => __( 'Cookie Time', 'popup-maker' ),
					'placeholder' => __( '364 days 23 hours 59 minutes 59 seconds', 'popup-maker' ),
					'description' => __( 'Enter a plain english time before cookie expires.', 'popup-maker' ),
					'std'         => '1 month',
					'priority'    => 5,
				),
				'cookie_path'    => array(
					'label'       => __( 'Sitewide Cookie', 'popup-maker' ),
					'description' => __( '	This will prevent the popup from auto opening on any page until the cookie expires.', 'popup-maker' ),
					'type'        => 'checkbox',
					'std'         => true,
					'priority'    => 6,
				),
				'cookie_key'     => array(
					'label'       => __( 'Cookie Key', 'popup-maker' ),
					'description' => __( 'Resetting this will cause all existing cookies to be invalid.', 'popup-maker' ),
					'std'         => '',
					'priority'    => 7,
				),
			),
			'close'       => array(
				'text'          => array(
					'label'       => __( 'Close Text', 'popup-maker' ),
					'placeholder' => __( 'CLOSE', 'popup-maker' ),
					'description' => __( 'Use this to override the default text set in the popup theme.', 'popup-maker' ),
					'std'         => '',
					'priority'    => 1,
				),
				'button_delay'  => array(
					'label'       => __( 'Close Button Delay', 'popup-maker' ),
					'description' => __( 'This delays the display of the close button.', 'popup-maker' ),
					'type'        => 'rangeslider',
					'std'         => 0,
					'priority'    => 2,
					'step'        => apply_filters( 'popmake_popup_close_button_delay_step', 100 ),
					'min'         => apply_filters( 'popmake_popup_close_button_delay_min', 0 ),
					'max'         => apply_filters( 'popmake_popup_close_button_delay_max', 3000 ),
					'unit'        => __( 'ms', 'popup-maker' ),
				),
				'overlay_click' => array(
					'label'       => __( 'Click Overlay to Close', 'popup-maker' ),
					'description' => __( 'Checking this will cause popup to close when user clicks on overlay.', 'popup-maker' ),
					'type'        => 'checkbox',
					'std'         => false,
					'priority'    => 3,
				),
				'esc_press'     => array(
					'label'       => __( 'Press ESC to Close', 'popup-maker' ),
					'description' => __( 'Checking this will cause popup to close when user presses ESC key.', 'popup-maker' ),
					'type'        => 'checkbox',
					'std'         => false,
					'priority'    => 4,
				),
				'f4_press'      => array(
					'label'       => __( 'Press F4 to Close', 'popup-maker' ),
					'description' => __( 'Checking this will cause popup to close when user presses F4 key.', 'popup-maker' ),
					'type'        => 'checkbox',
					'std'         => false,
					'priority'    => 5,
				),
			),
			'click_open'  => array(
				'extra_selectors' => array(
					'label'       => __( 'Extra CSS Selectors', 'popup-maker' ),
					'placeholder' => __( '.my-class, #button2', 'popup-maker' ),
					'description' => __( 'This allows custom css classes, ids or selector strings to trigger the popup when clicked. Seperate multiple selectors using commas.', 'popup-maker' ),
					'std'         => '',
					'priority'    => 1,
				),
			),
			'admin_debug' => array(
				'enabled' => array(
					'label'       => __( 'Enable Admin Debug', 'popup-maker' ),
					'description' => __( 'When Enabled, the popup will show immediately on the given page for admins.', 'popup-maker' ),
					'type'        => 'checkbox',
					'std'         => false,
					'priority'    => 1,
				),
			)
		);
	}
}

function popmake_register_popup_meta_fields( $section, $fields = array() ) {
	if ( ! empty( $fields ) ) {
		Popmake_Popup_Fields::instance()->add_fields( $section, $fields );
	}
}


class Popmake_Popup_Theme_Fields extends Popmake_Fields {
	public $field_prefix = 'popup_theme_';

	public function __construct() {
		$this->fields = array(
			'overlay'   => array(
				'background_color'   => array(
					'label'    => __( 'Color', 'popup-maker' ),
					'type'     => 'color',
					'std'      => '#ffffff',
					'priority' => 1,
				),
				'background_opacity' => array(
					'label'    => __( 'Opacity', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 100,
					'priority' => 2,
					'step'     => 1,
					'min'      => 0,
					'max'      => 100,
					'unit'     => __( '%', 'popup-maker' ),
				),
			),
			'container' => array(
				'padding'              => array(
					'label'    => __( 'Padding', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 18,
					'priority' => 3,
					'step'     => apply_filters( 'popmake_popup_theme_container_padding_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_container_padding_min', 1 ),
					'max'      => apply_filters( 'popmake_popup_theme_container_padding_max', 100 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'background_color'     => array(
					'label'    => __( 'Color', 'popup-maker' ),
					'type'     => 'color',
					'std'      => '#f9f9f9',
					'priority' => 2,
				),
				'background_opacity'   => array(
					'label'    => __( 'Opacity', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 100,
					'priority' => 3,
					'step'     => 1,
					'min'      => 0,
					'max'      => 100,
					'unit'     => __( '%', 'popup-maker' ),
				),
				'border_radius'        => array(
					'label'       => __( 'Radius', 'popup-maker' ),
					'description' => __( 'Choose a corner radius for your container button.', 'popup-maker' ),
					'type'        => 'rangeslider',
					'std'         => 0,
					'priority'    => 6,
					'step'        => apply_filters( 'popmake_popup_theme_container_border_radius_step', 1 ),
					'min'         => apply_filters( 'popmake_popup_theme_container_border_radius_min', 1 ),
					'max'         => apply_filters( 'popmake_popup_theme_container_border_radius_max', 80 ),
					'unit'        => __( 'px', 'popup-maker' ),
				),
				'border_style'         => array(
					'label'       => __( 'Style', 'popup-maker' ),
					'description' => __( 'Choose a border style for your container button.', 'popup-maker' ),
					'type'        => 'select',
					'std'         => 'none',
					'priority'    => 7,
					'options'     => apply_filters( 'popmake_border_style_options', array() ),
				),
				'border_color'         => array(
					'label'    => __( 'Color', 'popup-maker' ),
					'type'     => 'color',
					'std'      => '#000000',
					'priority' => 6,
				),
				'border_width'         => array(
					'label'    => __( 'Thickness', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 1,
					'priority' => 9,
					'step'     => apply_filters( 'popmake_popup_theme_container_border_width_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_container_border_width_min', 1 ),
					'max'      => apply_filters( 'popmake_popup_theme_container_border_width_max', 5 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'boxshadow_inset'      => array(
					'label'       => __( 'Inset', 'popup-maker' ),
					'description' => __( 'Set the box shadow to inset (inner shadow).', 'popup-maker' ),
					'type'        => 'select',
					'std'         => 'no',
					'priority'    => 10,
					'options'     => array(
						__( 'No', 'popup-maker' )  => 'no',
						__( 'Yes', 'popup-maker' ) => 'yes'
					),
				),
				'boxshadow_horizontal' => array(
					'label'    => __( 'Horizontal Position', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 1,
					'priority' => 11,
					'step'     => apply_filters( 'popmake_popup_theme_container_boxshadow_horizontal_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_container_boxshadow_horizontal_min', - 50 ),
					'max'      => apply_filters( 'popmake_popup_theme_container_boxshadow_horizontal_max', 50 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'boxshadow_vertical'   => array(
					'label'    => __( 'Vertical Position', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 1,
					'priority' => 12,
					'step'     => apply_filters( 'popmake_popup_theme_container_boxshadow_vertical_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_container_boxshadow_vertical_min', - 50 ),
					'max'      => apply_filters( 'popmake_popup_theme_container_boxshadow_vertical_max', 50 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'boxshadow_blur'       => array(
					'label'    => __( 'Blur Radius', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 3,
					'priority' => 13,
					'step'     => apply_filters( 'popmake_popup_theme_container_boxshadow_blur_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_container_boxshadow_blur_min', 0 ),
					'max'      => apply_filters( 'popmake_popup_theme_container_boxshadow_blur_max', 100 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'boxshadow_spread'     => array(
					'label'    => __( 'Spread', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 0,
					'priority' => 14,
					'step'     => apply_filters( 'popmake_popup_theme_container_boxshadow_spread_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_container_boxshadow_spread_min', - 100 ),
					'max'      => apply_filters( 'popmake_popup_theme_container_boxshadow_spread_max', 100 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'boxshadow_color'      => array(
					'label'    => __( 'Color', 'popup-maker' ),
					'type'     => 'color',
					'std'      => '#020202',
					'priority' => 13,
				),
				'boxshadow_opacity'    => array(
					'label'    => __( 'Opacity', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 23,
					'priority' => 14,
					'step'     => 1,
					'min'      => 0,
					'max'      => 100,
					'unit'     => __( '%', 'popup-maker' ),
				),
			),
			'title'     => array(
				'font_color'            => array(
					'label'    => __( 'Color', 'popup-maker' ),
					'type'     => 'color',
					'std'      => '#000000',
					'priority' => 15,
				),
				'line_height'           => array(
					'label'    => __( 'Line Height', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 36,
					'priority' => 2,
					'step'     => apply_filters( 'popmake_popup_theme_title_line_height_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_title_line_height_min', 8 ),
					'max'      => apply_filters( 'popmake_popup_theme_title_line_height_max', 54 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'font_size'             => array(
					'label'    => __( 'Spread', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 32,
					'priority' => 3,
					'step'     => apply_filters( 'popmake_popup_theme_title_font_size_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_title_font_size_min', 8 ),
					'max'      => apply_filters( 'popmake_popup_theme_title_font_size_max', 48 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'font_family'           => array(
					'label'    => __( 'Family', 'popup-maker' ),
					'type'     => 'select',
					'std'      => 'inherit',
					'priority' => 4,
					'options'  => apply_filters( 'popmake_font_family_options', array() ),
				),
				'font_weight'           => array(
					'label'    => __( 'Weight', 'popup-maker' ),
					'type'     => 'select',
					'std'      => 'inherit',
					'priority' => 5,
					'options'  => apply_filters( 'popmake_font_weight_options', array() ),
				),
				'font_style'            => array(
					'label'    => __( 'Style', 'popup-maker' ),
					'type'     => 'select',
					'std'      => 'normal',
					'priority' => 6,
					'options'  => apply_filters( 'popmake_font_style_options', array() ),
				),
				'text_align'            => array(
					'label'    => __( 'Align', 'popup-maker' ),
					'type'     => 'select',
					'std'      => 'left',
					'priority' => 7,
					'options'  => apply_filters( 'popmake_text_align_options', array() ),
				),
				'textshadow_horizontal' => array(
					'label'    => __( 'Horizontal Position', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 0,
					'priority' => 8,
					'step'     => apply_filters( 'popmake_popup_theme_title_textshadow_horizontal_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_title_textshadow_horizontal_min', - 50 ),
					'max'      => apply_filters( 'popmake_popup_theme_title_textshadow_horizontal_max', 50 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'textshadow_vertical'   => array(
					'label'    => __( 'Vertical Position', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 0,
					'priority' => 9,
					'step'     => apply_filters( 'popmake_popup_theme_title_textshadow_vertical_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_title_textshadow_vertical_min', - 50 ),
					'max'      => apply_filters( 'popmake_popup_theme_title_textshadow_vertical_max', 50 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'textshadow_blur'       => array(
					'label'    => __( 'Blur Radius', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 0,
					'priority' => 10,
					'step'     => apply_filters( 'popmake_popup_theme_title_textshadow_blur_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_title_textshadow_blur_min', 0 ),
					'max'      => apply_filters( 'popmake_popup_theme_title_textshadow_blur_max', 100 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'textshadow_color'      => array(
					'label'    => __( 'Color', 'popup-maker' ),
					'type'     => 'color',
					'std'      => '#020202',
					'priority' => 25,
				),
				'textshadow_opacity'    => array(
					'label'    => __( 'Opacity', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 23,
					'priority' => 12,
					'step'     => 1,
					'min'      => 0,
					'max'      => 100,
					'unit'     => __( '%', 'popup-maker' ),
				),
			),
			'content'   => array(
				'font_color'  => array(
					'label'    => __( 'Color', 'popup-maker' ),
					'type'     => 'color',
					'std'      => '#8c8c8c',
					'priority' => 1,
				),
				'font_family' => array(
					'label'    => __( 'Family', 'popup-maker' ),
					'type'     => 'select',
					'std'      => 'inherit',
					'priority' => 4,
					'options'  => apply_filters( 'popmake_font_family_options', array() ),
				),
				'font_weight' => array(
					'label'    => __( 'Weight', 'popup-maker' ),
					'type'     => 'select',
					'std'      => 'inherit',
					'priority' => 5,
					'options'  => apply_filters( 'popmake_font_weight_options', array() ),
				),
				'font_style'  => array(
					'label'    => __( 'Style', 'popup-maker' ),
					'type'     => 'select',
					'std'      => 'inherit',
					'priority' => 6,
					'options'  => apply_filters( 'popmake_font_style_options', array() ),
				),
			),
			'close'     => array(
				'text'                  => array(
					'label'       => __( 'Text', 'popup-maker' ),
					'placeholder' => __( 'CLOSE', 'popup-maker' ),
					'description' => __( 'Enter the close button text.', 'popup-maker' ),
					'std'         => __( 'CLOSE', 'popup-maker' ),
					'priority'    => 1,
				),
				'padding'               => array(
					'label'    => __( 'Padding', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 8,
					'priority' => 2,
					'step'     => apply_filters( 'popmake_popup_theme_close_padding_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_close_padding_min', 0 ),
					'max'      => apply_filters( 'popmake_popup_theme_close_padding_max', 100 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'height'                => array(
					'label'    => __( 'Height', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 0,
					'priority' => 2,
					'step'     => apply_filters( 'popmake_popup_theme_close_height_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_close_height_min', 0 ),
					'max'      => apply_filters( 'popmake_popup_theme_close_height_max', 100 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'width'                 => array(
					'label'    => __( 'Width', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 0,
					'priority' => 2,
					'step'     => apply_filters( 'popmake_popup_theme_close_width_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_close_width_min', 0 ),
					'max'      => apply_filters( 'popmake_popup_theme_close_width_max', 100 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'location'              => array(
					'label'       => __( 'Location', 'popup-maker' ),
					'description' => __( 'Choose which corner the close button will be positioned.', 'popup-maker' ),
					'type'        => 'select',
					'std'         => 'topright',
					'priority'    => 7,
					'options'     => apply_filters( 'popmake_theme_close_location_options', array() ),
				),
				'position_top'          => array(
					'label'    => __( 'Top', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 0,
					'priority' => 2,
					'step'     => apply_filters( 'popmake_popup_theme_close_position_top_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_close_position_top_min', - 100 ),
					'max'      => apply_filters( 'popmake_popup_theme_close_position_top_max', 100 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'position_left'         => array(
					'label'    => __( 'Left', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 0,
					'priority' => 2,
					'step'     => apply_filters( 'popmake_popup_theme_close_position_left_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_close_position_left_min', - 100 ),
					'max'      => apply_filters( 'popmake_popup_theme_close_position_left_max', 100 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'position_bottom'       => array(
					'label'    => __( 'Bottom', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 0,
					'priority' => 2,
					'step'     => apply_filters( 'popmake_popup_theme_close_position_bottom_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_close_position_bottom_min', - 100 ),
					'max'      => apply_filters( 'popmake_popup_theme_close_position_bottom_max', 100 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'position_right'        => array(
					'label'    => __( 'Right', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 0,
					'priority' => 2,
					'step'     => apply_filters( 'popmake_popup_theme_close_position_right_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_close_position_right_min', - 100 ),
					'max'      => apply_filters( 'popmake_popup_theme_close_position_right_max', 100 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'line_height'           => array(
					'label'    => __( 'Line Height', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 36,
					'priority' => 2,
					'step'     => apply_filters( 'popmake_popup_theme_close_line_height_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_close_line_height_min', 8 ),
					'max'      => apply_filters( 'popmake_popup_theme_close_line_height_max', 54 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'font_color'            => array(
					'label'    => __( 'Color', 'popup-maker' ),
					'type'     => 'color',
					'std'      => '#ffffff',
					'priority' => 11,
				),
				'font_size'             => array(
					'label'    => __( 'Size', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 12,
					'priority' => 3,
					'step'     => apply_filters( 'popmake_popup_theme_close_font_size_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_close_font_size_min', 8 ),
					'max'      => apply_filters( 'popmake_popup_theme_close_font_size_max', 32 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'font_family'           => array(
					'label'    => __( 'Family', 'popup-maker' ),
					'type'     => 'select',
					'std'      => 'inherit',
					'priority' => 4,
					'options'  => apply_filters( 'popmake_font_family_options', array() ),
				),
				'font_weight'           => array(
					'label'    => __( 'Weight', 'popup-maker' ),
					'type'     => 'select',
					'std'      => 'inherit',
					'priority' => 5,
					'options'  => apply_filters( 'popmake_font_weight_options', array() ),
				),
				'font_style'            => array(
					'label'    => __( 'Style', 'popup-maker' ),
					'type'     => 'select',
					'std'      => 'inherit',
					'priority' => 6,
					'options'  => apply_filters( 'popmake_font_style_options', array() ),
				),
				'background_color'      => array(
					'label'    => __( 'Color', 'popup-maker' ),
					'type'     => 'color',
					'std'      => '#00b7cd',
					'priority' => 16,
				),
				'background_opacity'    => array(
					'label'    => __( 'Opacity', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 100,
					'priority' => 17,
					'step'     => 1,
					'min'      => 0,
					'max'      => 100,
					'unit'     => __( '%', 'popup-maker' ),
				),
				'border_radius'         => array(
					'label'       => __( 'Radius', 'popup-maker' ),
					'description' => __( 'Choose a corner radius for your close button.', 'popup-maker' ),
					'type'        => 'rangeslider',
					'std'         => 0,
					'priority'    => 6,
					'step'        => apply_filters( 'popmake_popup_theme_close_border_radius_step', 1 ),
					'min'         => apply_filters( 'popmake_popup_theme_close_border_radius_min', 1 ),
					'max'         => apply_filters( 'popmake_popup_theme_close_border_radius_max', 28 ),
					'unit'        => __( 'px', 'popup-maker' ),
				),
				'border_style'          => array(
					'label'       => __( 'Style', 'popup-maker' ),
					'description' => __( 'Choose a border style for your close button.', 'popup-maker' ),
					'type'        => 'select',
					'std'         => 'none',
					'priority'    => 7,
					'options'     => apply_filters( 'popmake_border_style_options', array() ),
				),
				'border_color'          => array(
					'label'    => __( 'Color', 'popup-maker' ),
					'type'     => 'color',
					'std'      => '#ffffff',
					'priority' => 6,
				),
				'border_width'          => array(
					'label'    => __( 'Thickness', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 1,
					'priority' => 9,
					'step'     => apply_filters( 'popmake_popup_theme_close_border_width_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_close_border_width_min', 1 ),
					'max'      => apply_filters( 'popmake_popup_theme_close_border_width_max', 5 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'boxshadow_inset'       => array(
					'label'       => __( 'Inset', 'popup-maker' ),
					'description' => __( 'Set the box shadow to inset (inner shadow).', 'popup-maker' ),
					'type'        => 'select',
					'std'         => 'no',
					'priority'    => 10,
					'options'     => array(
						__( 'No', 'popup-maker' )  => 'no',
						__( 'Yes', 'popup-maker' ) => 'yes'
					),
				),
				'boxshadow_horizontal'  => array(
					'label'    => __( 'Horizontal Position', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 1,
					'priority' => 11,
					'step'     => apply_filters( 'popmake_popup_theme_close_boxshadow_horizontal_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_close_boxshadow_horizontal_min', - 50 ),
					'max'      => apply_filters( 'popmake_popup_theme_close_boxshadow_horizontal_max', 50 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'boxshadow_vertical'    => array(
					'label'    => __( 'Vertical Position', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 1,
					'priority' => 12,
					'step'     => apply_filters( 'popmake_popup_theme_close_boxshadow_vertical_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_close_boxshadow_vertical_min', - 50 ),
					'max'      => apply_filters( 'popmake_popup_theme_close_boxshadow_vertical_max', 50 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'boxshadow_blur'        => array(
					'label'    => __( 'Blur Radius', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 3,
					'priority' => 13,
					'step'     => apply_filters( 'popmake_popup_theme_close_boxshadow_blur_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_close_boxshadow_blur_min', 0 ),
					'max'      => apply_filters( 'popmake_popup_theme_close_boxshadow_blur_max', 100 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'boxshadow_spread'      => array(
					'label'    => __( 'Spread', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 0,
					'priority' => 14,
					'step'     => apply_filters( 'popmake_popup_theme_close_boxshadow_spread_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_close_boxshadow_spread_min', - 100 ),
					'max'      => apply_filters( 'popmake_popup_theme_close_boxshadow_spread_max', 100 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'boxshadow_color'       => array(
					'label'    => __( 'Color', 'popup-maker' ),
					'type'     => 'color',
					'std'      => '#020202',
					'priority' => 24,
				),
				'boxshadow_opacity'     => array(
					'label'    => __( 'Opacity', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 23,
					'priority' => 28,
					'step'     => 1,
					'min'      => 0,
					'max'      => 100,
					'unit'     => __( '%', 'popup-maker' ),
				),
				'textshadow_horizontal' => array(
					'label'    => __( 'Horizontal Position', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 0,
					'priority' => 8,
					'step'     => apply_filters( 'popmake_popup_theme_close_textshadow_horizontal_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_close_textshadow_horizontal_min', - 50 ),
					'max'      => apply_filters( 'popmake_popup_theme_close_textshadow_horizontal_max', 50 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'textshadow_vertical'   => array(
					'label'    => __( 'Vertical Position', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 0,
					'priority' => 9,
					'step'     => apply_filters( 'popmake_popup_theme_close_textshadow_vertical_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_close_textshadow_vertical_min', - 50 ),
					'max'      => apply_filters( 'popmake_popup_theme_close_textshadow_vertical_max', 50 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'textshadow_blur'       => array(
					'label'    => __( 'Blur Radius', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 0,
					'priority' => 10,
					'step'     => apply_filters( 'popmake_popup_theme_close_textshadow_blur_step', 1 ),
					'min'      => apply_filters( 'popmake_popup_theme_close_textshadow_blur_min', 0 ),
					'max'      => apply_filters( 'popmake_popup_theme_close_textshadow_blur_max', 100 ),
					'unit'     => __( 'px', 'popup-maker' ),
				),
				'textshadow_color'      => array(
					'label'    => __( 'Color', 'popup-maker' ),
					'type'     => 'color',
					'std'      => '#000000',
					'priority' => 29,
				),
				'textshadow_opacity'    => array(
					'label'    => __( 'Opacity', 'popup-maker' ),
					'type'     => 'rangeslider',
					'std'      => 23,
					'priority' => 34,
					'step'     => 1,
					'min'      => 0,
					'max'      => 100,
					'unit'     => __( '%', 'popup-maker' ),
				),
			)
		);
	}
}

function popmake_register_popup_theme_meta_fields( $section, $fields = array() ) {
	if ( ! empty( $fields ) ) {
		Popmake_Popup_Theme_Fields::instance()->add_fields( $section, $fields );
	}
}


