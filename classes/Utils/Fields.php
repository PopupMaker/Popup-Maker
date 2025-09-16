<?php
/**
 * Fields Utility
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Utils_Fields
 */
class PUM_Utils_Fields {

	/**
	 * Get a specific field from a flattened fields array
	 *
	 * @param array<string, mixed> $fields Fields array or nested fields structure
	 * @param string               $field_id Field identifier to retrieve
	 * @return array{type: string, id?: string, label?: string, std?: mixed}|false Field configuration array or false if not found
	 */
	public static function get_field( $fields, $field_id ) {
		$fields = static::flatten_fields_array( $fields );

		return isset( $fields[ $field_id ] ) ? $fields[ $field_id ] : false;
	}

	/**
	 * Get default values for all fields in a form
	 *
	 * @param array<string, mixed> $fields Fields configuration array
	 * @return array<string, mixed> Field ID => default value mapping
	 */
	public static function get_form_default_values( $fields = [] ) {
		$fields = static::flatten_fields_array( $fields );

		return static::get_field_default_values( $fields );
	}

	/**
	 * Extract default values from field configurations
	 *
	 * @param array<string, mixed> $fields Flattened fields array
	 * @return array<string, mixed> Field ID => default value mapping
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
	 * Flatten a nested field structure into a single-level array
	 *
	 * @param array<string, mixed> $tabs Nested tabs/sections/fields structure
	 * @return array<string, mixed> Flattened field ID => field config mapping
	 */
	public static function flatten_fields_array( $tabs ) {
		$fields = [];

		foreach ( $tabs as $tab_id => $tab_sections ) {
			if ( self::is_field( $tab_sections ) ) {
				$fields[ $tab_id ] = $tab_sections;
				continue;
			} else {
				foreach ( $tab_sections as $section_id => $section_fields ) {
					if ( self::is_field( $section_fields ) ) {
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
	 * Parse and merge field configuration with defaults
	 *
	 * @param array<string, mixed> $field Field configuration array
	 * @return array<string, mixed> Complete field configuration with all defaults applied
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
	 * Parse tab-based field structure with optional sections
	 *
	 * @param array<string, mixed>                      $fields Tab-based fields structure
	 * @param array{has_sections?: bool, name?: string} $args Parsing configuration options
	 * @return array<string, mixed> Parsed fields structure
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
					if ( is_array( $section_fields ) && self::is_field( $section_fields ) ) {
						// Allow for flat tabs with no sections.
						$section_id     = 'main';
						$section_fields = [
							$section_id => $section_fields,
						];
					}

					if ( is_array( $section_fields ) ) {
						$fields[ $tab_id ][ $section_id ] = self::parse_fields( $section_fields, $args['name'] );
					}
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
	 * Parse and validate individual fields array
	 *
	 * @param array<string, mixed> $fields Fields array to parse
	 * @param string               $name Name format template for field naming
	 * @return array<string, mixed> Parsed and sorted fields array
	 */
	public static function parse_fields( $fields, $name = '%' ) {
		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field_id => $field ) {
				if ( ! is_array( $field ) || ! self::is_field( $field ) ) {
					continue;
				}

				// Remap old settings.
				if ( is_numeric( $field_id ) && ! empty( $field['id'] ) ) {
					try {
						$updated_fields = PUM_Utils_Array::replace_key( $fields, $field_id, $field['id'] );
						if ( is_array( $updated_fields ) ) {
							$fields = $updated_fields;
						}
					} catch ( Exception $e ) {
						// Exception handled by ignoring - field key replacement failed.
						unset( $e );
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
	 * Checks if an array is a field configuration
	 *
	 * @param array<string, mixed> $arr Array to test
	 * @return bool True if array represents a field configuration
	 */
	public static function is_field( $arr = [] ) {
		$field_tests = [
			! isset( $arr['type'] ) && ( isset( $arr['label'] ) || isset( $arr['desc'] ) ),
			isset( $arr['type'] ) && is_string( $arr['type'] ),
		];

		return in_array( true, $field_tests, true );
	}

	/**
	 * Checks if an array is a section (not a field)
	 *
	 * @param array<string, mixed> $arr Array to test
	 * @return bool True if array represents a section (not a field)
	 */
	public static function is_section( $arr = [] ) {
		return ! self::is_field( $arr );
	}

	/**
	 * Render a single field using appropriate callback
	 *
	 * @param array{type?: string} $args Field configuration arguments
	 * @return void
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
			$callback_method = $type . '_callback';
			// @phpstan-ignore-next-line class.notFound
			if ( class_exists( 'PUM_Form_Fields' ) && method_exists( 'PUM_Form_Fields', $callback_method ) ) {
				/**
				 * Check if renderer method exists and load that.
				 */
				$function_name = [ 'PUM_Form_Fields', $callback_method ];
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
			if ( is_callable( $function_name ) ) {
				call_user_func_array( $function_name, [ $args ] );
			}
		}
	}

	/**
	 * Render all fields within a form structure
	 *
	 * @param object $form Form object containing fields
	 * @return void
	 */
	public static function render_form_fields( $form ) {

		$tabs     = method_exists( $form, 'get_tabs' ) ? $form->get_tabs() : [];
		$sections = method_exists( $form, 'get_sections' ) ? $form->get_sections() : [];
		$fields   = method_exists( $form, 'get_fields' ) ? $form->get_fields() : [];

		if ( ! empty( $tabs ) ) {
			if ( ! empty( $sections ) ) {
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
	 * Sanitize an array of field values using field configurations
	 *
	 * @param array<string, mixed> $values Field values to sanitize (field_id => value mapping)
	 * @param array<string, mixed> $fields Field configurations for sanitization rules
	 * @return array<string, mixed> Sanitized field values
	 */
	public static function sanitize_fields( $values, $fields = [] ) {

		foreach ( $values as $key => $value ) {
			// Here to ensure undefined fields are still sanitized.
			if ( is_string( $value ) ) {
				$value = sanitize_text_field( $value );
			}

			$field = self::get_field( $fields, $key );

			if ( $field ) {
				$value = self::sanitize_field( $field, $value );
			}

			// Update the value.
			$values[ $key ] = $value;
		}

		return $values;
	}

	/**
	 * Sanitize a single field value using its configuration
	 *
	 * @param array{type?: string} $args Field configuration arguments
	 * @param mixed                $value Value to sanitize
	 * @param array<string, mixed> $fields All field configurations for context
	 * @param array<string, mixed> $values All field values for context
	 * @return mixed Sanitized field value
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
			} elseif ( method_exists( 'PUM_Utils_Sanitize', $type ) ) {
				/**
				 * Check if core method exists and load that.
				 */
				$function_name = [ 'PUM_Utils_Sanitize', $type ];
			} else {
				$function_name = null;
			}

			if ( $function_name && is_callable( $function_name ) ) {
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
