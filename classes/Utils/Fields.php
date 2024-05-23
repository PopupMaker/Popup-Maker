<?php
/**
 * Fields Utility
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Utils_Fields
 */
class PUM_Utils_Fields {

	/**
	 * @param $fields
	 * @param $field_id
	 *
	 * @return bool|mixed
	 */
	public static function get_field( $fields, $field_id ) {
		$fields = static::flatten_fields_array( $fields );

		return isset( $fields[ $field_id ] ) ? $fields[ $field_id ] : false;
	}

	/**
	 * @param array $fields
	 *
	 * @return array
	 */
	public static function get_form_default_values( $fields = [] ) {
		$fields = static::flatten_fields_array( $fields );

		return static::get_field_default_values( $fields );
	}

	/**
	 * @param array $fields
	 *
	 * @return array
	 */
	public static function get_field_default_values( $fields = [] ) {
		$defaults = [];

		foreach ( $fields as $field_id => $field ) {
			switch ( $field['type'] ) {
				case 'checkbox':
					$defaults[ $field_id ] = ! empty( $field['std'] ) ? $field['std'] : false;
					break;
				default:
					$defaults[ $field_id ] = isset( $field['std'] ) ? $field['std'] : null;
			}
		}

		return $defaults;
	}

	/**
	 * @param $tabs
	 *
	 * @return array
	 */
	public static function flatten_fields_array( $tabs ) {
		$fields = [];

		foreach ( $tabs as $tab_id => $tab_sections ) {

			if ( self::is_field( $tab_sections ) ) {
				$fields[ $tab_id ] = $tab_sections;
				continue;
			} else {
				foreach ( $tab_sections as $section_id => $section_fields ) {

					if ( self::is_field( $tab_sections ) ) {
						$fields[ $section_id ] = $section_fields;
						continue;
					}

					foreach ( $section_fields as $field_id => $field ) {
						$fields[ $field_id ] = $field;
						continue;
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * @param $field
	 *
	 * @return array
	 */
	public static function parse_field( $field ) {
		return wp_parse_args(
			$field,
			[
				'section'        => 'main',
				'type'           => 'text',
				'id'             => null,
				'label'          => '',
				'desc'           => '',
				'name'           => null,
				'templ_name'     => null,
				'size'           => 'regular',
				'options'        => [],
				'std'            => null,
				'rows'           => 5,
				'cols'           => 50,
				'min'            => 0,
				'max'            => 50,
				'force_minmax'   => false,
				'step'           => 1,
				'select2'        => null,
				'object_type'    => 'post_type',
				'object_key'     => 'post',
				'post_type'      => null,
				'taxonomy'       => null,
				'multiple'       => null,
				'as_array'       => false,
				'placeholder'    => null,
				'checkbox_val'   => 1,
				'allow_blank'    => true,
				'readonly'       => false,
				'required'       => false,
				'disabled'       => false,
				'hook'           => null,
				'unit'           => __( 'ms', 'popup-maker' ),
				'desc_position'  => 'bottom',
				'units'          => [
					'px'  => 'px',
					'%'   => '%',
					'em'  => 'em',
					'rem' => 'rem',
				],
				'priority'       => 10,
				'doclink'        => '',
				'button_type'    => 'submit',
				'class'          => '',
				'messages'       => [],
				'license_status' => '',
				'value'          => null,
				'private'        => false,
			]
		);
	}

	/**
	 * @param       $fields
	 * @param array  $args
	 *
	 * @return mixed
	 */
	public static function parse_tab_fields( $fields, $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'has_sections' => false,
				'name'         => '%s',
			]
		);

		if ( $args['has_sections'] ) {
			foreach ( $fields as $tab_id => $tab_sections ) {
				foreach ( $tab_sections as $section_id => $section_fields ) {
					if ( self::is_field( $section_fields ) ) {
						// Allow for flat tabs with no sections.
						$section_id     = 'main';
						$section_fields = [
							$section_id => $section_fields,
						];
					}

					$fields[ $tab_id ][ $section_id ] = self::parse_fields( $section_fields, $args['name'] );
				}
			}
		} else {
			foreach ( $fields as $tab_id => $tab_fields ) {
				$fields[ $tab_id ] = self::parse_fields( $tab_fields, $args['name'] );
			}
		}

		return $fields;
	}

	/**
	 * @param array  $fields
	 * @param string $name
	 *
	 * @return mixed
	 */
	public static function parse_fields( $fields, $name = '%' ) {
		if ( is_array( $fields ) && ! empty( $fields ) ) {
			foreach ( $fields as $field_id => $field ) {
				if ( ! is_array( $field ) || ! self::is_field( $field ) ) {
					continue;
				}

				// Remap old settings.
				if ( is_numeric( $field_id ) && ! empty( $field['id'] ) ) {
					try {
						$fields = PUM_Utils_Array::replace_key( $fields, $field_id, $field['id'] );
					} catch ( Exception $e ) {
					}

					$field_id = $field['id'];
				} elseif ( empty( $field['id'] ) && ! is_numeric( $field_id ) ) {
					$field['id'] = $field_id;
				}

				if ( empty( $field['name'] ) ) {
					$field['name'] = sprintf( $name, $field_id );
				}

				$fields[ $field_id ] = self::parse_field( $field );
			}
		}

		$fields = PUM_Utils_Array::sort( $fields, 'priority' );

		return $fields;
	}

	/**
	 * Checks if an array is a field.
	 *
	 * @param array $array
	 *
	 * @return bool
	 */
	public static function is_field( $array = [] ) {
		$field_tests = [
			! isset( $array['type'] ) && ( isset( $array['label'] ) || isset( $array['desc'] ) ),
			isset( $array['type'] ) && is_string( $array['type'] ),
		];

		return in_array( true, $field_tests );
	}

	/**
	 * Checks if an array is a section.
	 *
	 * @param array $array
	 *
	 * @return bool
	 */
	public static function is_section( $array = [] ) {
		return ! self::is_field( $array );
	}

	/**
	 * @param array $args
	 */
	public static function render_field( $args = [] ) {
		$args = static::parse_field( $args );

		// If no type default to text.
		$type = ! empty( $args['type'] ) ? $args['type'] : 'text';

		/**
		 * Check if any actions hooked to this type of field and load run those.
		 */
		if ( has_action( "pum_{$type}_field" ) ) {
			do_action( "pum_{$type}_field", $args );
		} else {
			if ( method_exists( 'PUM_Form_Fields', $type . '_callback' ) ) {
				/**
				 * Check if renderer method exists and load that.
				 */
				$function_name = [ 'PUM_Form_Fields', $type . '_callback' ];
			} elseif ( function_exists( "pum_{$type}_callback" ) ) {
				/**
				 * Check if function exists and load that.
				 */
				$function_name = "pum_{$type}_callback";
			} else {
				/**
				 * No method exists, lets notify them the field type doesn't exist.
				 */
				$function_name = [ 'PUM_Form_Fields', 'missing_callback' ];
			}

			/**
			 * Call the determined method, passing the field args & $value to the callback.
			 */
			call_user_func_array( $function_name, [ $args ] );
		}
	}

	/**
	 * @param PUM_Form $form
	 */
	public static function render_form_fields( $form ) {

		$tabs     = $form->get_tabs();
		$sections = $form->get_sections();
		$fields   = $form->get_fields();

		if ( $form->has_tabs() ) {
			if ( $form->has_sections() ) {
				foreach ( $tabs as $tab_id => $tab_label ) {
					foreach ( $sections as $section_id => $section_label ) {
						foreach ( $fields[ $tab_id ][ $section_id ] as $field_id => $field_args ) {
							static::render_field( $field_args );
						}
					}
				}
			} else {
				foreach ( $tabs as $tab_id => $label ) {
					foreach ( $fields[ $tab_id ] as $field_id => $field_args ) {
						static::render_field( $field_args );
					}
				}
			}
		} else {
			foreach ( $fields as $field_id => $field_args ) {
				static::render_field( $field_args );
			}
		}

	}

	/**
	 * Sanitizes an array of field values.
	 *
	 * @param $fields
	 * @param $values
	 *
	 * @return mixed
	 */
	public static function sanitize_fields( $values, $fields = [] ) {

		foreach ( $values as $key => $value ) {
			if ( is_string( $value ) ) {
				$values[ $key ] = sanitize_text_field( $value );
			}

			$field = self::get_field( $fields, $key );

			if ( $field ) {
				$values[ $key ] = self::sanitize_field( $field, $value );
			}
		}

		return $values;
	}

	/**
	 * @param array $args
	 * @param mixed $value
	 * @param array $fields
	 * @param array $values
	 *
	 * @return mixed|null
	 */
	public static function sanitize_field( $args, $value = null, $fields = [], $values = [] ) {

		// If no type default to text.
		$type = ! empty( $args['type'] ) ? $args['type'] : 'text';

		/**
		 * Check if any actions hooked to this type of field and load run those.
		 */
		if ( has_filter( "pum_{$type}_sanitize" ) ) {
			$value = apply_filters( "pum_{$type}_sanitize", $value, $args, $fields, $values );
		} else {
			/**
			 * Check if override or custom function exists and load that.
			 */
			if ( function_exists( "pum_{$type}_sanitize" ) ) {
				$function_name = "pum_{$type}_sanitize";
			} /**
			 * Check if core method exists and load that.
			 */ elseif ( method_exists( 'PUM_Utils_Sanitize', $type ) ) {
				$function_name = [ 'PUM_Utils_Sanitize', $type ];
			} else {
				$function_name = null;
			}

			if ( $function_name ) {
				/**
				 * Call the determined method, passing the field args & $value to the callback.
				 */
				$value = call_user_func_array( $function_name, [ $value, $args, $fields, $values ] );
			}
		}

		$value = apply_filters( 'pum_settings_sanitize', $value, $args, $fields, $values );

		return $value;
	}
}
