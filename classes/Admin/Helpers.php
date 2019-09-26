<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Admin_Helpers
 */
class PUM_Admin_Helpers {


	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public static function post_type_dropdown_options( $args = array(), $compare = 'and' ) {
		$args = wp_parse_args( $args, array(
			'public'              => null,
			'publicly_queryable'  => null,
			'exclude_from_search' => null,
			'show_ui'             => null,
			'capability_type'     => null,
			'hierarchical'        => null,
			'menu_position'       => null,
			'menu_icon'           => null,
			'permalink_epmask'    => null,
			'rewrite'             => null,
			'query_var'           => null,
			'_builtin'            => null,
		) );

		foreach( $args as $key => $value ) {
			if ( $value === null ) {
				unset( $args[ $key ] );
			}
		}

		$options = array();

		foreach ( get_post_types( $args, 'objects', $compare ) as $post_type ) {
			if ( in_array( $post_type->name, array( 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'popup_theme', 'nf_sub' ) ) ) {
				// continue;
			}

			$labels = get_post_type_labels( $post_type );

			$options[ esc_attr( $post_type->name ) ] = esc_html( $labels->name );
		}

		return $options;
	}


	/**
	 * @deprecated 1.7.20
	 * @see        PUM_Helper_Array::move_item
	 *
	 * @param array       $ref_arr
	 * @param string      $key1
	 * @param int|string  $move
	 * @param string|null $key2
	 *
	 * @return bool
	 */
	public static function move_item( &$ref_arr, $key1, $move, $key2 = null ) {
		return PUM_Utils_Array::move_item( $ref_arr, $key1, $move, $key2 );
	}

	/**
	 * @deprecated 1.7.20
	 * @see        PUM_Helper_Array::remove_keys_starting_with
	 *
	 * @param array $array
	 * @param bool  $string
	 *
	 * @return array
	 */
	public static function remove_keys_starting_with( $array, $string = false ) {
		return PUM_Utils_Array::remove_keys_starting_with( $array, $string );
	}

	/**
	 * @deprecated 1.7.20
	 * @see        PUM_Helper_Array::sort_by_sort
	 *
	 * @param array $a
	 * @param array  $b
	 *
	 * @return array
	 */
	public static function sort_by_sort( $a, $b ) {
		return PUM_Utils_Array::sort_by_sort( $a, $b );
	}

	/**
	 * @param array $fields
	 *
	 * @return array
	 */
	public static function get_field_defaults( $fields = array() ) {
		$defaults = array();

		foreach ( $fields as $field_id => $field ) {
			$defaults[ $field_id ] = isset( $field['std'] ) ? $field['std'] : 'checkbox' === $field['type'] ? null : false;
		}

		return $defaults;

	}

	/**
	 * @deprecated 1.7.20
	 * @see        PUM_Utils_Array::from_object instead.
	 *
	 * @param $array
	 * @param $old_key
	 * @param $new_key
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function replace_key( $array, $old_key, $new_key ) {
		return PUM_Utils_Array::replace_key( $array, $old_key, $new_key );
	}

	/**
	 * @param $tabs
	 *
	 * @return array
	 */
	public static function flatten_fields_array( $tabs ) {
		$fields = array();

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
		return wp_parse_args( $field, array(
			'section'        => 'main',
			'type'           => 'text',
			'id'             => null,
			'label'          => '',
			'desc'           => '',
			'name'           => null,
			'templ_name'     => null,
			'size'           => 'regular',
			'options'        => array(),
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
			'units'          => array(
				'px'  => 'px',
				'%'   => '%',
				'em'  => 'em',
				'rem' => 'rem',
			),
			'priority'       => 10,
			'doclink'        => '',
			'button_type'    => 'submit',
			'class'          => '',
			'messages'       => array(),
			'license_status' => '',
			'private'        => false,
		) );
	}

	/**
	 * @param       $fields
	 * @param array $args
	 *
	 * @return mixed
	 */
	public static function parse_tab_fields( $fields, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'has_subtabs' => false,
			'name'        => '%s',
		) );

		if ( $args['has_subtabs'] ) {
			foreach ( $fields as $tab_id => $tab_sections ) {
				foreach ( $tab_sections as $section_id => $section_fields ) {
					if ( self::is_field( $section_fields ) ) {
						// Allow for flat tabs with no sections.
						$section_id     = 'main';
						$section_fields = array(
							$section_id => $section_fields,
						);
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

				if ( ! empty( $field['name'] ) && empty( $field['label'] ) ) {
					$field['label'] = $field['name'];
					unset( $field['name'] );
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
	 * Sort array by priority value
	 *
	 * @deprecated 1.7.20
	 * @see        PUM_Utils_Array::sort_by_priority instead.
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	public static function sort_by_priority( $a, $b ) {
		return PUM_Utils_Array::sort_by_priority( $a, $b );
	}

	/**
	 * Checks if an array is a field.
	 *
	 * @param array $array
	 *
	 * @return bool
	 */
	public static function is_field( $array = array() ) {
		$field_tests = array(
			! isset( $array['type'] ) && ( isset( $array['label'] ) || isset( $array['desc'] ) ),
			isset( $array['type'] ) && is_string( $array['type'] ),
		);

		return in_array( true, $field_tests );
	}

	/**
	 * Checks if an array is a section.
	 *
	 * @param array $array
	 *
	 * @return bool
	 */
	public static function is_section( $array = array() ) {
		return ! self::is_field( $array );
	}

	/**
	 * @deprecated 1.7.0
	 *
	 * @param array $args
	 */
	public static function modal( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'id'                 => 'default',
			'title'              => '',
			'description'        => '',
			'class'              => '',
			'cancel_button'      => true,
			'cancel_button_text' => __( 'Cancel', 'popup-maker' ),
			'save_button'        => true,
			'save_button_text'   => __( 'Add', 'popup-maker' ),
		) );
		?>
		<div id="<?php echo $args['id']; ?>" class="pum-modal-background <?php esc_attr_e( $args['class'] ); ?>" role="dialog" aria-hidden="true" aria-labelledby="<?php echo $args['id']; ?>-title"
			<?php if ( '' != $args['description'] ) { ?>aria-describedby="<?php echo $args['id']; ?>-description"<?php } ?>>

			<div class="pum-modal-wrap">

				<form class="pum-form">

					<div class="pum-modal-header">

						<?php if ( '' != $args['title'] ) { ?>
							<span id="<?php echo $args['id']; ?>-title" class="pum-modal-title"><?php echo $args['title']; ?></span>
						<?php } ?>
						<button type="button" class="pum-modal-close" aria-label="<?php _e( 'Close', 'popup-maker' ); ?>"></button>
					</div>

					<?php if ( '' != $args['description'] ) { ?>
						<span id="<?php echo $args['id']; ?>-description" class="screen-reader-text"><?php echo $args['description']; ?></span>
					<?php } ?>

					<div class="pum-modal-content">
						<?php echo $args['content']; ?>
					</div>

					<?php if ( $args['save_button'] || $args['cancel_button'] ) { ?>
						<div class="pum-modal-footer submitbox">
							<?php if ( $args['cancel_button'] ) { ?>
								<div class="cancel">
									<button type="button" class="submitdelete no-button" href="#"><?php echo $args['cancel_button_text']; ?></button>
								</div>
							<?php } ?>
							<?php if ( $args['save_button'] ) { ?>
								<div class="pum-submit">
									<span class="spinner"></span>
									<button class="button button-primary"><?php echo $args['save_button_text']; ?></button>
								</div>
							<?php } ?>
						</div>
					<?php } ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * @deprecated 1.7.20
	 * @see        PUM_Utils_Array::from_object instead.
	 *
	 * @param $obj
	 *
	 * @return array
	 */
	public static function object_to_array( $obj ) {
		return PUM_Utils_Array::from_object( $obj );
	}

}

