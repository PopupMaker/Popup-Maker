<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly
	exit;
}

/**
 * Class PUM_Triggers
 */
class PUM_Triggers {

	/**
	 * @var PUM_Triggers
	 */
	public static $instance;

	/**
	 * @var array
	 */
	public $triggers;

	/**
	 *
	 */
	public static function init() {
		self::instance();
	}

	/**
	 * @return PUM_Triggers
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * @param array $triggers
	 */
	public function add_triggers( $triggers = array() ) {
		foreach ( $triggers as $key => $trigger ) {
			if ( empty( $trigger['id'] ) && ! is_numeric( $key ) ) {
				$trigger['id'] = $key;
			}

			$this->add_trigger( $trigger );
		}
	}

	/**
	 * @param array $trigger
	 */
	public function add_trigger( $trigger = array() ) {
		if ( ! empty( $trigger['id'] ) && ! isset ( $this->triggers[ $trigger['id'] ] ) ) {
			$trigger = wp_parse_args( $trigger, array(
				'id'              => '',
				'name'            => '',
				'modal_title'     => '',
				'settings_column' => '',
				'priority'        => 10,
				'tabs'            => $this->get_tabs(),
				'fields'          => array(),
			) );

			// Here for backward compatibility to merge in labels properly.
			$labels         = $this->get_labels();
			$trigger_labels = isset( $labels[ $trigger['id'] ] ) ? $labels[ $trigger['id'] ] : array();

			if ( ! empty( $trigger_labels ) ) {
				foreach ( $trigger_labels as $key => $value ) {
					if ( empty( $trigger[ $key ] ) ) {
						$trigger[ $key ] = $value;
					}
				}
			}

			// Remove cookie fields
			if ( ! empty( $trigger['fields']['cookie'] ) ) {
				unset( $trigger['fields']['cookie'] );
			}

			// Add cookie fields for all triggers automatically.
			if ( empty( $trigger['fields']['general']['cookie_name'] ) ) {
				$trigger['fields']['general'] = array_merge( $trigger['fields']['general'], $this->cookie_fields() );
			}

			$this->triggers[ $trigger['id'] ] = apply_filters( 'pum_trigger', $trigger );
		}

		return;
	}

	/**
	 * @return array
	 */
	public function get_triggers() {
		if ( ! isset( $this->triggers ) ) {
			$this->register_triggers();
		}

		return $this->triggers;
	}

	/**
	 * @param null $trigger
	 *
	 * @return mixed|null
	 */
	public function get_trigger( $trigger = null ) {
		$triggers = $this->get_triggers();

		return isset( $triggers[ $trigger ] ) ? $triggers[ $trigger ] : null;
	}

	/**
	 * @deprecated
	 *
	 * @param null  $trigger
	 * @param array $settings
	 *
	 * @return array
	 */
	public function validate_trigger( $trigger = null, $settings = array() ) {
		return $settings;
	}

	/**
	 * Registers all known triggers when called.
	 */
	public function register_triggers() {
		$triggers = apply_filters( 'pum_registered_triggers', array(
			'click_open' => array(
				'name'            => __( 'Click Open', 'popup-maker' ),
				'modal_title'     => __( 'Click Trigger Settings', 'popup-maker' ),
				'settings_column' => sprintf( '<strong>%1$s</strong>: %2$s', __( 'Extra Selectors', 'popup-maker' ), '{{data.extra_selectors}}' ),
				'fields'          => array(
					'general'  => array(
						'extra_selectors' => array(
							'label'       => __( 'Extra CSS Selectors', 'popup-maker' ),
							'desc'        => __( 'For more than one selector, separate by comma (,)', 'popup-maker' ) . '<br /><strong>eg:  </strong>' . __( ' .class-here, .class-2-here, #button_id', 'popup-maker' ),
							'placeholder' => __( '.class-here', 'popup-maker' ),
							'doclink'     => 'https://docs.wppopupmaker.com/article/147-getting-css-selectors?utm_source=plugin-popup-editor=&utm_medium=inline-doclink&utm_campaign=ContextualHelp&utm_content=extra-selectors',
						),
					),
					'advanced' => array(
						'do_default' => array(
							'type'  => 'checkbox',
							'label' => __( 'Do not prevent the default click functionality.', 'popup-maker' ),
							'desc'  => __( 'This prevents us from disabling the browsers default action when a trigger is clicked. It can be used to allow a link to a file to both trigger a popup and still download the file.', 'popup-maker' ),
						),
					),
				),
			),
			'auto_open'  => array(
				'name'            => __( 'Time Delay / Auto Open', 'popup-maker' ),
				'modal_title'     => __( 'Time Delay Settings', 'popup-maker' ),
				'settings_column' => sprintf( '<strong>%1$s</strong>: %2$s', __( 'Delay', 'popup-maker' ), '{{data.delay}}' ),
				'fields'          => array(
					'general' => array(
						'delay' => array(
							'type'  => 'rangeslider',
							'label' => __( 'Delay', 'popup-maker' ),
							'desc'  => __( 'The delay before the popup will open in milliseconds.', 'popup-maker' ),
							'std'   => 500,
							'min'   => 0,
							'max'   => 10000,
							'step'  => 500,
							'unit'  => 'ms',
						),
					),
				),
			),

		) );

		foreach ( $triggers as $key => $trigger ) {
			$triggers[ $key ]['fields'] = PUM_Admin_Helpers::parse_tab_fields( $triggers[ $key ]['fields'], array(
				'has_subtabs' => false,
				'name'        => '%s',
			) );
		}

		// @deprecated filter.
		$old_triggers = apply_filters( 'pum_get_triggers', array() );

		foreach ( $old_triggers as $type => $trigger ) {
			if ( isset( $triggers[ $type ] ) ) {
				continue;
			}

			if ( ! empty( $trigger['fields'] ) ) {
				foreach ( $trigger['fields'] as $tab_id => $tab_fields ) {
					foreach ( $tab_fields as $field_id => $field ) {
						if ( ! empty( $field['options'] ) ) {
							$trigger['fields'][ $tab_id ][ $field_id ]['options'] = array_flip( $trigger['fields'][ $tab_id ][ $field_id ]['options'] );
						}
					}
				}
			}

			$triggers[ $type ] = $trigger;
		}

		$this->add_triggers( $triggers );
	}

	/**
	 * @return array
	 */
	public function dropdown_list() {
		$_triggers = $this->get_triggers();
		$triggers  = array();

		foreach ( $_triggers as $id => $trigger ) {
			$triggers[ $id ] = $trigger['name'];
		}

		return $triggers;
	}

	/**
	 * Returns the cookie fields used for trigger options.
	 *
	 * @uses filter pum_trigger_cookie_fields
	 *
	 * @return array
	 */
	public function cookie_fields() {

		/**
		 * Filter the array of default trigger cookie fields.
		 *
		 * @param array $fields The list of trigger cookie fields.
		 */
		return apply_filters( 'pum_trigger_cookie_fields', array(
			'cookie_name' => $this->cookie_field(),
		) );
	}

	/**
	 * Returns the cookie field used for trigger options.
	 *
	 * @uses filter pum_trigger_cookie_field
	 *
	 * @return array
	 */
	public function cookie_field() {

		/**
		 * Filter the array of default trigger cookie field.
		 *
		 * @param array $fields The list of trigger cookie field.
		 */
		return apply_filters( 'pum_trigger_cookie_field', array(
			'label'    => __( 'Cookie Name', 'popup-maker' ),
			'desc'     => __( 'Choose which cookies will disable this trigger?', 'popup-maker' ),
			'type'     => 'select',
			'multiple' => true,
			'as_array' => true,
			'select2'  => true,
			'priority' => 99,
			'options'  => array(
				'add_new' => __( 'Add New Cookie', 'popup-maker' ),
			),
		) );
	}

	/**
	 * Returns an array of section labels for all triggers.
	 *
	 * Use the filter pum_get_trigger_section_labels to add or modify labels.
	 *
	 * @return array
	 */
	public function get_tabs() {
		/**
		 * Filter the array of trigger section labels.
		 *
		 * @param array $to_do The list of trigger section labels.
		 */
		return apply_filters( 'pum_get_trigger_tabs', array(
			'general'  => __( 'General', 'popup-maker' ),
			'cookie'   => __( 'Cookie', 'popup-maker' ),
			'advanced' => __( 'Advanced', 'popup-maker' ),
		) );
	}

	/**
	 * Returns an array of trigger labels.
	 *
	 * Use the filter pum_get_trigger_labels to add or modify labels.
	 *
	 * @return array
	 */
	public function get_labels() {
		static $labels;

		if ( ! isset( $labels ) ) {
			/**
			 * Filter the array of trigger labels.
			 *
			 * @param array $to_do The list of trigger labels.
			 */
			$labels = apply_filters( 'pum_get_trigger_labels', array() );
		}

		return $labels;
	}


}
