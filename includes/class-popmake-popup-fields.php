<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
				'disable_reposition'           => array(
					'label'       => __( 'Disable Repositioning', 'popup-maker' ),
					'description' => __( 'This will disable automatic repositioning of the popup on window resizing.', 'popup-maker' ),
					'type'        => 'checkbox',
					'std'         => false,
					'priority'    => 13,
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
				'position_from_trigger' => array(
					'label'       => __( 'Position from Trigger', 'popup-maker' ),
					'description' => sprintf( __( 'This will position the popup in relation to the %sClick Trigger%s.', 'popup-maker' ), '<a target="_blank" href="http://docs.wppopupmaker.com/article/144-trigger-click-open?utm_medium=inline-doclink&utm_campaign=ContextualHelp&utm_source=plugin-popup-editor&utm_content=position-from-trigger">', '</a>' ),
					'type'        => 'checkbox',
					'std'         => false,
					'priority'    => 14.5,
				),
				'position_top'              => array(
					'label'       => __( 'Top', 'popup-maker' ),
					'description' => sprintf( _x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ), strtolower( __( 'Top', 'popup-maker' ) ) ),
					'type'        => 'rangeslider',
					'std'         => 100,
					'priority'    => 15,
					'step'        => apply_filters( 'popmake_popup_display_position_top_step', 1 ),
					'min'         => apply_filters( 'popmake_popup_display_position_top_min', 0 ),
					'max'         => apply_filters( 'popmake_popup_display_position_top_max', 500 ),
					'unit'        => 'px',
				),
				'position_bottom'           => array(
					'label'       => __( 'Bottom', 'popup-maker' ),
					'description' => sprintf( _x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ), strtolower( __( 'Bottom', 'popup-maker' ) ) ),
					'type'        => 'rangeslider',
					'std'         => 0,
					'priority'    => 14,
					'step'        => apply_filters( 'popmake_popup_display_position_bottom_step', 1 ),
					'min'         => apply_filters( 'popmake_popup_display_position_bottom_min', 0 ),
					'max'         => apply_filters( 'popmake_popup_display_position_bottom_max', 500 ),
					'unit'        => 'px',
				),
				'position_left'             => array(
					'label'       => __( 'Left', 'popup-maker' ),
					'description' => sprintf( _x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ), strtolower( __( 'Left', 'popup-maker' ) ) ),
					'type'        => 'rangeslider',
					'std'         => 0,
					'priority'    => 15,
					'step'        => apply_filters( 'popmake_popup_display_position_left_step', 1 ),
					'min'         => apply_filters( 'popmake_popup_display_position_left_min', 0 ),
					'max'         => apply_filters( 'popmake_popup_display_position_left_max', 500 ),
					'unit'        => 'px',
				),
				'position_right'            => array(
					'label'       => __( 'Right', 'popup-maker' ),
					'description' => sprintf( _x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ), strtolower( __( 'Right', 'popup-maker' ) ) ),
					'type'        => 'rangeslider',
					'std'         => 0,
					'priority'    => 15,
					'step'        => apply_filters( 'popmake_popup_display_position_right_step', 1 ),
					'min'         => apply_filters( 'popmake_popup_display_position_right_min', 0 ),
					'max'         => apply_filters( 'popmake_popup_display_position_right_max', 500 ),
					'unit'        => 'px',
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
		);
	}
}

function popmake_register_popup_meta_fields( $section, $fields = array() ) {
	if ( ! empty( $fields ) ) {
		Popmake_Popup_Fields::instance()->add_fields( $section, $fields );
	}
}
