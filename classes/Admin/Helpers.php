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
	 *
	 * PUM_Admin_Helpers::move_item($arr, 'move me', 'up'); //move it one up
	 * PUM_Admin_Helpers::move_item($arr, 'move me', 'down'); //move it one down
	 * PUM_Admin_Helpers::move_item($arr, 'move me', 'top'); //move it to top
	 * PUM_Admin_Helpers::move_item($arr, 'move me', 'bottom'); //move it to bottom
	 *
	 * PUM_Admin_Helpers::move_item($arr, 'move me', -1); //move it one up
	 * PUM_Admin_Helpers::move_item($arr, 'move me', 1); //move it one down
	 * PUM_Admin_Helpers::move_item($arr, 'move me', 2); //move it two down
	 *
	 * PUM_Admin_Helpers::move_item($arr, 'move me', 'up', 'b'); //move it before ['b']
	 * PUM_Admin_Helpers::move_item($arr, 'move me', -1, 'b'); //move it before ['b']
	 * PUM_Admin_Helpers::move_item($arr, 'move me', 'down', 'b'); //move it after ['b']
	 * PUM_Admin_Helpers::move_item($arr, 'move me', 1, 'b'); //move it after ['b']
	 * PUM_Admin_Helpers::move_item($arr, 'move me', 2, 'b'); //move it two positions after ['b']
	 *
	 * Special syntax, to swap two elements:
	 * PUM_Admin_Helpers::move_item($arr, 'a', 0, 'd'); //Swap ['a'] with ['d']
	 *
	 * @param array       $ref_arr
	 * @param string      $key1
	 * @param int|string  $move
	 * @param string|null $key2
	 *
	 * @return bool
	 */
	public static function move_item( &$ref_arr, $key1, $move, $key2 = null ) {
		$arr = $ref_arr;

		if ( $key2 == null ) {
			$key2 = $key1;
		}

		if ( ! isset( $arr[ $key1 ] ) || ! isset( $arr[ $key2 ] ) ) {
			return false;
		}

		$i = 0;
		foreach ( $arr as &$val ) {
			$val = array( 'sort' => ( ++ $i * 10 ), 'val' => $val );
		}

		if ( is_numeric( $move ) ) {
			if ( $move == 0 && $key1 == $key2 ) {
				return true;
			} elseif ( $move == 0 ) {
				$tmp                  = $arr[ $key1 ]['sort'];
				$arr[ $key1 ]['sort'] = $arr[ $key2 ]['sort'];
				$arr[ $key2 ]['sort'] = $tmp;
			} else {
				$arr[ $key1 ]['sort'] = $arr[ $key2 ]['sort'] + ( $move * 10 + ( $key1 == $key2 ? ( $move < 0 ? - 5 : 5 ) : 0 ) );
			}
		} else {
			switch ( $move ) {
				case 'up':
					$arr[ $key1 ]['sort'] = $arr[ $key2 ]['sort'] - ( $key1 == $key2 ? 15 : 5 );
					break;
				case 'down':
					$arr[ $key1 ]['sort'] = $arr[ $key2 ]['sort'] + ( $key1 == $key2 ? 15 : 5 );
					break;
				case 'top':
					$arr[ $key1 ]['sort'] = 5;
					break;
				case 'bottom':
					$arr[ $key1 ]['sort'] = $i * 10 + 5;
					break;
				default:
					return false;
			}
		}

		uasort( $arr, array( __CLASS__, 'sort_by_sort' ) );

		foreach ( $arr as &$val ) {
			$val = $val['val'];
		}

		$ref_arr = $arr;

		return true;
	}

	/**
	 * @param array $array
	 * @param bool  $string
	 *
	 * @return array
	 */
	public static function remove_keys_starting_with( $array, $string = false ) {

		foreach ( $array as $key => $value ) {
			if ( strpos( $key, $string ) === 0 ) {
				unset( $array[ $key ] );
			}
		}

		return $array;
	}

	public static function sort_by_sort( $a, $b ) {
		return $a['sort'] > $b['sort'];
	}

	public static function get_field_defaults( $fields = array() ) {
		$defaults = array();

		foreach ( $fields as $field_id => $field ) {
			$defaults[ $field_id ] = isset( $field['std'] ) ? $field['std'] : 'checkbox' === $field['type'] ? null : false;
		}

		return $defaults;

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
						$fields = self::replace_key( $fields, $field_id, $field['id'] );
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

		uasort( $fields, array( __CLASS__, 'sort_by_priority' ) );

		return $fields;
	}

	/**
	 * Sort array by priority value
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	public static function sort_by_priority( $a, $b ) {
		if ( ! isset( $a['priority'] ) || ! isset( $b['priority'] ) || $a['priority'] === $b['priority'] ) {
			return 0;
		}

		return ( $a['priority'] < $b['priority'] ) ? - 1 : 1;
	}


	/**
	 * @param $array
	 * @param $old_key
	 * @param $new_key
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function replace_key( $array, $old_key, $new_key ) {
		$keys = array_keys( $array );
		if ( false === $index = array_search( $old_key, $keys, true ) ) {
			throw new Exception( sprintf( 'Key "%s" does not exit', $old_key ) );
		}
		$keys[ $index ] = $new_key;
		return array_combine( $keys, array_values( $array ) );
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
	 * @param $obj
	 *
	 * @return array
	 */
	public static function object_to_array( $obj ) {
		if ( is_object( $obj ) ) {
			$obj = (array) $obj;
		}
		if ( is_array( $obj ) ) {
			$new = array();
			foreach ( $obj as $key => $val ) {
				$new[ $key ] = PUM_Admin_Helpers::object_to_array( $val );
			}
		} else {
			$new = $obj;
		}
		return $new;
	}

}

