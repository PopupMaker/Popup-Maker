<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Popmake_Fields
 *
 * @deprecated 1.4 Use PUM_Fields instead.
 */
class Popmake_Fields {

	/**
	 * @var string
	 */
	public $field_prefix = 'settings';

	/**
	 * @var string
	 */
	public $field_name_format = '{$prefix}[{$section}][{$field}]';

	/**
	 * @var string
	 */
	public $templ_value_format = '{$prefix}{$section}.{$field}';

	/**
	 * @var array
	 */
	public $fields = array();

	/**
	 * @var array
	 */
	public $sections = array();

	/**
	 * @var array
	 */
	public $args = array();

	/**
	 * @var array
	 */
	private static $instances = array();

	/**
	 * @param array $args
	 */
	public function __construct( $args = array() ) {

		$sections = isset( $args['sections'] ) ? $args['sections'] : array(
			'general' => array(
				'title' => __( 'General', 'popup-maker' ),
			),
		);

		$this->add_sections( $sections );

		if ( ! empty( $args['fields'] ) ) {
			$this->add_fields( $args['fields'] );
		}

		$this->args = $args;

		return $this;
	}

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public static function instance( $args = array() ) {
		$class = get_called_class();

		$class_key = md5( $class );

		if ( ! isset( self::$instances[ $class_key ] ) || ! self::$instances[ $class_key ] instanceof $class ) {
			self::$instances[ $class_key ] = new $class( $args );
		}

		return self::$instances[ $class_key ];
	}


	/**
	 * This function should no longer be used.
	 *
	 * @deprecated 1.4 Replace with add_section()
	 *
	 * @param $id
	 * @param $title
	 * @param null $callback
	 */
	public function register_section( $id, $title, $callback = null ) {
		$this->add_section( array(
			'id'       => $id,
			'title'    => $title,
			'callback' => $callback,
		) );
	}

	/**
	 * @param $sections
	 */
	public function add_sections( $sections ) {
		foreach ( $sections as $id => $section ) {
			if ( ! is_array( $section ) ) {
				$section = array(
					'title' => $section,
				);
			}

			if ( empty( $section['id'] ) ) {
				$section['id'] = $id;
			}

			$this->add_section( $section );
		}
	}

	/**
	 * @param $section
	 */
	public function add_section( $section ) {
		$section                          = wp_parse_args( $section, array(
			'id'       => null,
			'title'    => '',
			'hidden'   => false,
			'callback' => null,
		) );
		$this->sections[ $section['id'] ] = $section;
	}

	/**
	 * @param array $field
	 */
	public function add_field( $field = array() ) {

		$field = wp_parse_args( $field, array(
			'section'      => 'general',
			'type'         => 'text',
			'id'           => null,
			'label'        => '',
			'desc'         => '',
			'name'         => null,
			'templ_name'   => null,
			'size'         => 'regular',
			'options'      => array(),
			'std'          => null,
			'rows'         => 5,
			'cols'         => 50,
			'min'          => 0,
			'max'          => 50,
			'force_minmax' => false,
			'step'         => 1,
			'select2'      => null,
			'object_type'  => 'post_type',
			'object_key'   => 'post',
			'post_type'    => null,
			'taxonomy'     => null,
			'multiple'     => null,
			'as_array'     => false,
			'placeholder'  => null,
			'checkbox_val' => 1,
			'allow_blank'  => true,
			'readonly'     => false,
			'required'     => false,
			'disabled'     => false,
			'hook'         => null,
			'unit'         => __( 'ms', 'popup-maker' ),
			'priority'     => null,
			'doclink'      => '',
			'button_type'  => 'submit',
			'class'        => '',
		) );

		if ( ! $field['name'] ) {
			$field['name'] = $this->get_field_name( $field );
		}

		if ( ! $field['templ_name'] ) {
			$field['templ_name'] = $this->get_templ_name( $field );
		}

		$this->fields[ $field['section'] ][ $field['id'] ] = $field;
	}

	/**
	 * @param array $fields
	 * @param null $section
	 */
	public function add_fields( $fields = array(), $section = null ) {

		/**
		 * Switch the variables for backward compatibility with a
		 * select few extensions that started using the v1.3 Settings API
		 */
		if ( is_string( $fields ) && is_array( $section ) ) {
			$tmp     = $fields;
			$fields  = $section;
			$section = $tmp;
		}

		foreach ( $fields as $key => $field ) {

			// Either an undefined field or empty section. So lets skip it.
			if ( empty ( $field ) ) {
				continue;
			}

			$first_key = key( $field );

			if ( isset( $this->sections[ $key ] ) && is_array( $field[ $first_key ] ) ) {
				$this->add_fields( $field, $key );
			} // Process the fields.
			else {

				if ( $section ) {
					$field['section'] = $section;
				}

				if ( empty( $field['id'] ) && ! is_numeric( $key ) ) {
					$field['id'] = $key;
				}

				$this->add_field( $field );
			}
		}
	}

	/**
	 * @return array
	 */
	public function get_sections() {
		return $this->sections;
	}

	/**
	 * @param null $section
	 *
	 * @return array
	 */
	public function get_fields( $section = null ) {
		if ( ! $section ) {
			return $this->get_all_fields();
		}

		if ( ! isset( $this->fields[ $section ] ) ) {
			return array();
		}

		$non_priority_fields = array();
		$priority_fields     = array();

		foreach ( $this->fields[ $section ] as $field_id => $field ) {
			if ( ! isset( $field['priority'] ) || is_null( $field['priority'] ) ) {
				$non_priority_fields[ $field_id ] = $field;
			} else {
				$priority_fields[ $field_id ] = $field;
			}
		}

		uasort( $priority_fields, array( $this, 'sort_by_priority' ) );

		$fields = $priority_fields + $non_priority_fields;

		return $fields;
	}

	/**
	 * @return array
	 */
	public function get_all_fields() {
		$all_fields = array();
		foreach ( $this->fields as $section => $fields ) {
			$all_fields[ $section ] = $this->get_fields( $section );
		}

		return $all_fields;
	}

	/**
	 * Returns the a generated field name for given ID.
	 *
	 * Replaces {$prefix} with $field_prefix, {$section}
	 * with $section and {$field} with $field
	 *
	 * @param $field
	 *
	 * @return string $field_name
	 * @internal param $id
	 * @internal param $section
	 *
	 * @uses public $field_prefix
	 * @uses public $field_name_format
	 *
	 */
	public function get_field_name( $field ) {
		return str_replace( array(
			'{$prefix}',
			'{$section}',
			'{$field}',
		), array(
			$this->field_prefix,
			$field['section'],
			$field['id'],
		), $this->field_name_format );
	}

	/**
	 * @param $section
	 *
	 * @return array
	 */
	public function get_field_names( $section ) {
		$names = array();

		foreach ( $this->get_fields( $section ) as $id => $args ) {
			$names[] = $this->get_field_name( $args );
		}

		return $names;
	}

	/**
	 * @param $args
	 *
	 * @return mixed|string
	 */
	public function get_templ_name( $args ) {
		return str_replace( array(
			'{$prefix}',
			'{$section}',
			'{$field}',
		), array(
			$this->field_prefix,
			$args['section'] != 'general' ? ".{$args['section']}" : "",
			$args['id'],
		), $this->templ_value_format );
	}

	/**
	 * @param string $section
	 * @param array $values
	 */
	function render_fields_by_section( $section = 'general', $values = array() ) {
		foreach ( $this->get_fields( $section ) as $key => $args ) {
			$value = isset( $values[ $args['id'] ] ) ? $values[ $args['id'] ] : null;

			$this->render_field( $args, $value );
		}
	}

	/**
	 * @param array $values
	 */
	function render_fields( $values = array() ) {
		foreach ( $this->get_all_fields() as $section => $fields ) {
			foreach ( $fields as $id => $args ) {
				$value = isset( $values[ $args['id'] ] ) ? $values[ $args['id'] ] : null;

				$this->render_field( $args, $value );
			}
		}
	}

	/**
	 * @param array $args
	 * @param null $value
	 */
	public function render_field( $args = array(), $value = null ) {

		// If no type default to text.
		$type = ! empty( $args['type'] ) ? $args['type'] : 'text';

		/**
		 * Check if any actions hooked to this type of field and load run those.
		 */
		if ( has_action( "pum_{$type}_field" ) ) {
			do_action( "pum_{$type}_field", $args, $value );
		} else {
			/**
			 * Check if override or custom function exists and load that.
			 */
			if ( function_exists( "pum_{$type}_callback" ) ) {
				$function_name = "pum_{$type}_callback";
			} /**
			 * Check if core method exists and load that.
			 */ elseif ( method_exists( $this, $type . '_callback' ) ) {
				$function_name = array( $this, $type . '_callback' );
			} /**
			 * No method exists, lets notify them the field type doesn't exist.
			 */ else {
				$function_name = array( $this, 'missing_callback' );
			}

			/**
			 * Call the determined method, passing the field args & $value to the callback.
			 */
			call_user_func_array( $function_name, array( $args, $value ) );
		}

	}

	/**
	 */
	public function render_templ_fields() {
		foreach ( $this->get_all_fields() as $section => $fields ) {
			foreach ( $fields as $id => $args ) {
				$this->render_templ_field( $args );
			}
		}
	}

	/**
	 * @param string $section
	 */
	public function render_templ_fields_by_section( $section = 'general' ) {
		foreach ( $this->get_fields( $section ) as $key => $args ) {
			$this->render_templ_field( $args );
		}
	}

	/**
	 * @param array $args
	 */
	public function render_templ_field( $args = array() ) {

		// If no type default to text.
		$type = ! empty( $args['type'] ) ? $args['type'] : 'text';

		/**
		 * Check if any actions hooked to this type of field and load run those.
		 */
		if ( has_action( "pum_{$type}_templ_field" ) ) {
			do_action( "pum_{$type}_templ_field", $args, $this );
		} else {
			/**
			 * Check if override or custom function exists and load that.
			 */
			if ( function_exists( "pum_{$type}_templ_callback" ) ) {
				$function_name = "pum_{$type}_templ_callback";
			} /**
			 * Check if core method exists and load that.
			 */ elseif ( method_exists( $this, $type . '_templ_callback' ) ) {
				$function_name = array( $this, $type . '_templ_callback' );
			} /**
			 * Check if the field type is hook.
			 */ elseif ( $type == 'hook' ) {
				$function_name = array( $this, 'hook_callback' );
			} /**
			 * No method exists, lets notify them the field type doesn't exist.
			 */ else {
				$function_name = array( $this, 'missing_callback' );
			}

			/**
			 * Call the determined method, passing the field args & $value to the callback.
			 */
			call_user_func_array( $function_name, array( $args, $this ) );
		}

	}

	/**
	 * @param array $args
	 */
	public function field_before( $args = array() ) {
		$classes = is_array( $args ) ? $this->field_classes( $args ) : ( is_string( $args ) ? $args : '' );
		?><div class="<?php echo esc_attr( $classes ); ?>"><?php
	}

	/**
	 *
	 */
	public function field_after() {
		?></div><?php
	}

	/**
	 * @param $args
	 * @param null $class
	 *
	 * @return string
	 */
	public function field_classes( $args, $class = null ) {

		$args = wp_parse_args( $args, array(
			'id'      => '',
			'class'   => '',
			'type'    => '',
			'desc'    => '',
			'doclink' => '',
		) );

		$classes = array(
			'pum-field',
			'pum-field-' . $args['id'],
			'pum-field-' . $args['type'],
		);

		if ( $args['doclink'] != '' ) {
			$classes[] = 'pum-field--has-doclink';
		}

		$classes[] = is_array( $args['class'] ) ? implode( '  ', $args['class'] ) : $args['class'];

		if ( isset( $class ) ) {
			$classes[] = is_array( $class ) ? implode( '  ', $class ) : $class;
		}

		return implode( '  ', $classes );
	}

	public function field_description( $args ) {
		if ( $args['desc'] != '' ) { ?>
			<p class="pum-desc"><?php echo esc_html( $args['desc'] ); ?></p><?php
		}
/*
		if ( $args['doclink'] != '' ) { ?>
			<a href="<?php echo esc_url( $args['doclink'] ); ?>" target="_blank" class="pum-doclink dashicons dashicons-editor-help"></a><?php
		}
*/
	}

	public function field_label( $args ) {
		if ( ! empty( $args['label'] ) ) { ?>
			<label for="<?php echo esc_attr( $args['id'] ); ?>"><?php
				echo esc_html( $args['label'] );
				if ( $args['doclink'] != '' ) { ?>
					<a href="<?php echo esc_url( $args['doclink'] ); ?>" target="_blank" class="pum-doclink dashicons dashicons-editor-help"></a><?php
				} ?>
			</label><?php
		}
	}


	public function sanitize_field( $args, $value = null ) {

		// If no type default to text.
		$type = ! empty( $args['type'] ) ? $args['type'] : 'text';

		/**
		 * Check if any actions hooked to this type of field and load run those.
		 */
		if ( has_filter( "pum_{$type}_sanitize" ) ) {
			$value = apply_filters( "pum_{$type}_sanitize", $value, $args );
		} else {
			/**
			 * Check if override or custom function exists and load that.
			 */
			if ( function_exists( "pum_{$type}_sanitize" ) ) {
				$function_name = "pum_{$type}_sanitize";
			} /**
			 * Check if core method exists and load that.
			 */ elseif ( method_exists( $this, $type . '_sanitize' ) ) {
				$function_name = array( $this, $type . '_sanitize' );
			} else {
				$function_name = null;
			}

			if ( $function_name ) {
				/**
				 * Call the determined method, passing the field args & $value to the callback.
				 */
				$value = call_user_func_array( $function_name, array( $value, $args ) );
			}

		}

		$value = apply_filters( 'pum_settings_sanitize', $value, $args );

		return $value;
	}

	/**
	 * Sanitize fields
	 *
	 * @param array $values
	 *
	 * @return array|mixed $input Sanitized value
	 * @internal param array $input The value inputted in the field
	 *
	 */
	public function sanitize_fields( $values = array() ) {

		$sanitized_values = array();

		foreach ( $this->get_all_fields() as $section => $fields ) {
			foreach ( $fields as $field ) {
				$value = isset( $values[ $section ][ $field['id'] ] ) ? $values[ $section ][ $field['id'] ] : null;

				$value = $this->sanitize_field( $field, $value );

				if ( ! is_null( $value ) ) {
					$sanitized_values[ $section ][ $field['id'] ] = $value;
				}
			}
		}

		return $sanitized_values;
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

	public function checkbox_sanitize( $value = null, $args = array() ) {
		if ( intval( $value ) == 1 ) {
			return 1;
		}

		return null;
	}

	/**
	 * Hook Callback
	 *
	 * Adds a do_action() hook in place of the field
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function hook_callback( $args ) {
		do_action( 'popmake_' . $args['id'] );
	}

}
