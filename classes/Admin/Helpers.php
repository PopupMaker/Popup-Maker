<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Admin_Helpers {

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
			'priority'       => null,
			'doclink'        => '',
			'button_type'    => 'submit',
			'class'          => '',
			'messages'       => array(),
			'license_status' => '',
		) );
	}

	public static function parse_tab_fields( $fields, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'has_subtabs' => false,
			'name' => '%s',
		) );

		if ( $args['has_subtabs'] ) {
			foreach( $fields as $tab_id => $tab_sections ) {
				foreach( $tab_sections as $section_id => $section_fields ) {
					if ( self::is_field( $section_fields ) ) {
						// Allow for flat tabs with no sections.
						$section_id = 'main';
						$section_fields     = array(
							$section_id => $section_fields,
						);
					}

					$fields[ $tab_id ][ $section_id ] = self::parse_fields( $section_fields, $args['name'] );
				}
			}
		} else {
			foreach( $fields as $tab_id => $tab_fields ) {
				$fields[ $tab_id ] = self::parse_fields( $tab_fields, $args['name'] );
			}
		}



		return $fields;

	}

	public static function parse_fields( $fields, $name = '%' ) {
		foreach ( $fields as $field_id => $field ) {
			if ( ! is_array( $field ) || ! self::is_field( $field ) ) {
				continue;
			}

			if ( empty( $field['id'] ) ) {
				$field['id'] = $field_id;
			}

			if ( empty( $field['name'] ) ) {
				$field['name'] = sprintf( $name, $field_id );
			}

			$fields[ $field_id ] = self::parse_field( $field );
		}

		return $fields;
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
			isset( $array['id'] ),
			isset( $array['label'] ),
			isset( $array['type'] ),
			isset( $array['options'] ),
			isset( $array['desc'] ),
		);

		return in_array( true, $field_tests );
	}


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
    <div id="<?php echo $args['id']; ?>" class="pum-modal-background <?php esc_attr_e( $args['class'] ); ?>" role="dialog" aria-hidden="true" aria-labelledby="<?php echo $args['id']; ?>-title" <?php if ( $args['description'] != '' ) { ?>aria-describedby="<?php echo $args['id']; ?>-description"<?php } ?>>

		<div class="pum-modal-wrap">

            <form class="pum-form">

				<div class="pum-modal-header">

					<?php if ( $args['title'] != '' ) { ?>
                        <span id="<?php echo $args['id']; ?>-title" class="pum-modal-title"><?php echo $args['title']; ?></span>
					<?php } ?>
                    <button type="button" class="pum-modal-close" aria-label="<?php _e( 'Close', 'popup-maker' ); ?>"></button>
				</div>

                <?php if ( $args['description'] != '' ) { ?>
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
		</div><?php
	}

	public static function object_to_array( $obj ) {
		if ( is_object( $obj ) ) {
			$obj = ( array ) $obj;
		}
		if ( is_array( $obj ) ) {
			$new = array();
			foreach( $obj as $key => $val ) {
				$new[ $key ] = PUM_Admin_Helpers::object_to_array( $val );
			}
		}
		else {
			$new = $obj;
		}
		return $new;
	}

}

