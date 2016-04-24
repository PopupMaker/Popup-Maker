<?php
/**
 * Fields
 *
 * @package     PUM
 * @subpackage  Classes/PUM_Fields
 * @copyright   Copyright (c) 2015, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Fields extends Popmake_Fields {

# region HTML Field Callbacks
	/**
	 * Heading Callback
	 *
	 * Renders the heading.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function heading_callback( $args ) { ?>
		</td></tr></tbody></table>
		<h2 class="pum-setting-heading"><?php esc_html_e( $args['desc'] ); ?></h2>
		<hr/>
		<table class="form-table"><tbody><tr style="display:none;"><td colspan="2"><?php
	}


	/**
	 * Button Callback
	 *
	 * Renders buttons.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function button_callback( $args ) {

		$args = wp_parse_args( $args, array(
			'id' => '',
			'button_type' => 'submit',
			'class' => '',
			'name' => '',
			'label' => __( 'Submit', 'popup-maker' ),
			'desc' => '',
			'size' => 'regular',
		) );

		$class = 'pum-field-button pum-field-' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}

		$this->field_before( $class ); ?>

		<button type="<?php esc_attr_e( $args['button_type'] ); ?>" class="pum-button-<?php esc_attr_e( $args['size'] ); ?>" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>"><?php esc_html_e( $args['label'] ); ?></button><?php

		if ( $args['desc'] != '' ) { ?>
			<p class="pum-desc"><?php esc_html_e( $args['desc'] ); ?></p><?php
		}

		$this->field_after();
	}


	/**
	 * Text Callback
	 *
	 * Renders text fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @param string $value
	 */
	public function text_callback( $args, $value = '' ) {

		$args = wp_parse_args( $args, array(
			'id' => '',
			'type' => 'text',
			'class' => '',
			'std' => '',
			'name' => '',
			'label' => '',
			'placeholder' => '',
			'desc' => '',
			'size' => 'regular',
			'required' => false,
			'disabled' => false,
			'readonly' => false,
		) );

		$class = 'pum-field-text';

		if ( $args['type'] != 'text' ) {
			$class .= ' pum-field-' . $args['type'];
		}

		$class .= ' pum-field-' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}

		$this->field_before( $class );

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		if ( ! empty( $args['label'] ) ) { ?>
			<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
		} ?>

		<input type="<?php esc_attr_e( $args['type'] ); ?>" placeholder="<?php esc_attr_e( $args['placeholder'] ); ?>" class="<?php esc_attr_e( $args['size'] ); ?>-text" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" value="<?php esc_attr_e( stripslashes( $value ) ); ?>" <?php if ( $args['required'] ) { echo 'required'; } ?>/><?php

		if ( $args['desc'] != '' ) { ?>
			<p class="pum-desc"><?php esc_html_e( $args['desc'] ); ?></p><?php
		}

		$this->field_after();
	}

#region html5 text fields
	/**
	 * Password Callback
	 *
	 * Renders password fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @param string $value
	 */
	public function password_callback( $args, $value = '' ) {
		$args['type'] = 'password';

		$this->text_callback( $args, $value );
	}

	/**
	 * Email Callback
	 *
	 * Renders email fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @param string $value
	 */
	public function email_callback( $args, $value = '' ) {
		$args['type'] = 'email';

		$this->text_callback( $args, $value );
	}

	/**
	 * Search Callback
	 *
	 * Renders search fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @param string $value
	 */
	public function search_callback( $args, $value = '' ) {
		$args['type'] = 'search';

		$this->text_callback( $args, $value );
	}

	/**
	 * URL Callback
	 *
	 * Renders url fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @param string $value
	 */
	public function url_callback( $args, $value = '' ) {
		$args['type'] = 'url';

		$this->text_callback( $args, $value );
	}

	/**
	 * Telephone Callback
	 *
	 * Renders telelphone number fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @param string $value
	 */
	public function tel_callback( $args, $value = '' ) {
		$args['type'] = 'tel';

		$this->text_callback( $args, $value );
	}

	/**
	 * Number Callback
	 *
	 * Renders number fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @param string $value
	 */
	public function number_callback( $args, $value = '' ) {
		$args['type'] = 'number';

		$this->text_callback( $args, $value );
	}

	/**
	 * Range Callback
	 *
	 * Renders range fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @param string $value
	 */
	public function range_callback( $args, $value = '' ) {
		$args['type'] = 'range';

		$this->text_callback( $args, $value );
	}
#endregion html5 text fields

	/**

	/**
	 * Textarea Callback
	 *
	 * Renders textarea fields.
	 *
	 * @param array  $args Arguments passed by the setting
	 *
	 * @param string $value
	 */
	public function textarea_callback( $args, $value = '' ) {

		$args = wp_parse_args( $args, array(
			'id' => '',
			'class' => '',
			'std' => '',
			'name' => '',
			'label' => '',
			'placeholder' => '',
			'desc' => '',
			'size' => 'regular',
			'rows' => 5,
			'cols' => 50,
			'required' => false,
			'disabled' => false,
		) );

		$class = 'pum-field-textarea pum-field-' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}

		$this->field_before( $class );

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		if ( ! empty( $args['label'] ) ) { ?>
			<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
		} ?>

		<textarea placeholder="<?php esc_attr_e( $args['placeholder'] ); ?>" class="<?php esc_attr_e( $args['size'] ); ?>-text" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" cols="<?php esc_attr_e( $args['cols'] ); ?>" rows="<?php esc_attr_e( $args['rows'] ); ?>" <?php if ( $args['required'] ) { echo 'required'; } ?>><?php echo esc_textarea( stripslashes( $value ) ); ?></textarea><?php

		if ( $args['desc'] != '' ) { ?>
			<label for="<?php esc_attr_e( $args['id'] ); ?>" class="pum-desc desc"><?php esc_html_e( $args['desc'] ); ?></label><?php
		}
	}

	/**
	 * Hidden Callback
	 *
	 * Renders hidden fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @param $value
	*/
	public function hidden_callback( $args, $value ) {

		$args = wp_parse_args( $args, array(
			'id' => '',
			'class' => '',
			'std' => '',
			'name' => '',
		) );

		$class = 'pum-field-hidden pum-field-' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		if ( is_array( $class ) ) {
			$class = implode( ',', $class );
		} ?>

		<input type="hidden" class="<?php esc_attr_e( $class ); ?>" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" value="<?php esc_attr_e( stripslashes( $value ) ); ?>"/><?php
	}

	/**
	 * Select Callback
	 *
	 * Renders select fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @param $value
	 */
	public function select_callback( $args, $value ) {

		$args = wp_parse_args( $args, array(
			'id' => '',
			'class' => '',
			'std' => '',
			'name' => '',
			'select2' => '',
			'multiple' => '',
			'as_array' => '',
			'label' => '',
			'placeholder' => '',
			'options' => array(),
			'desc' => '',
			'required' => false,
			'disabled' => false,
		) );

		$class = 'pum-field-select pum-field-' . $args['id'];

        if ( isset ( $args['select2'] ) ) {
            $class .= ' pum-select2';
		}

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}


        if ( ! $value ) {
            $value = isset( $args['std'] ) ? $args['std'] : '';
        }

        $multiple = null;
        if ( isset ( $args['multiple'] ) && $args['multiple'] ) {
            $multiple = 'multiple';
            if ( $args['as_array'] ) {
                $args['name'] = $args['name'] . '[]';
            }

            if ( ! $value || empty( $value ) ) {
                $value = array();
            }

            if ( ! is_array( $value ) ) {
                $value = array( $value );
            }
        }

		$this->field_before( $class );

		if ( ! empty( $args['label'] ) ) { ?>
			<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
		} ?>

	    <select id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" data-placeholder="<?php esc_attr_e( $args['placeholder'] ); ?>" data-allow-clear="true" <?php echo $multiple; ?> <?php if ( $args['required'] ) { echo 'required'; } ?>>

		<?php if ( ! empty( $args['options'] ) ) {
			foreach ( $args['options'] as $label => $option ) {
			    $selected = 0;
			    if ( $multiple && in_array( $option, $value ) ) {
			        $selected = 1;
			    } elseif( ! $multiple && $option == $value ) {
			        $selected = 1;
			    } ?>
				<option value="<?php esc_attr_e( $option ); ?>" <?php selected( 1, $selected ); ?>><?php esc_html_e( $label ); ?></option><?php
			}
		} ?>

		</select><?php

		if ( $args['desc'] != '' ) { ?>
			<p class="pum-desc"><?php esc_html_e( $args['desc'] ); ?></p><?php
		}

		$this->field_after();
	}


	/**
	 * Select Callback
	 *
	 * Renders select fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @param null $value
	 */
	public function objectselect_callback( $args, $value = null ) {

		$args = wp_parse_args( $args, array(
			'id' => '',
			'class' => '',
			'std' => '',
			'name' => '',
			'select2' => '',
			'multiple' => '',
			'as_array' => '',
			'label' => '',
			'placeholder' => '',
			'options' => array(),
			'desc' => '',
			'object_type' => '',
			'object_key' => '',
			'required' => false,
			'disabled' => false,
		) );

		$class = 'pum-field-objectselect pum-select2 pum-field-' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}

         if ( ! $value ) {
            $value = isset( $args['std'] ) ? $args['std'] : '';
        }

        $multiple = null;
        if ( isset ( $args['multiple'] ) && $args['multiple'] ) {
            $multiple = 'multiple';
            if ( $args['as_array'] ) {
                $args['name'] = $args['name'] . '[]';
            }

            if ( ! $value || empty( $value ) ) {
                $value = array();
            }

            if ( ! is_array( $value ) ) {
                $value = array( $value );
            }

            $value = wp_parse_id_list( $value );
        }

		$this->field_before( $class );

		if ( ! empty( $args['label'] ) ) { ?>
			<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
		} ?>

	    <select id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" data-placeholder="<?php esc_attr_e( $args['placeholder'] ); ?>" data-allow-clear="true" <?php echo $multiple; ?> data-objecttype="<?php esc_attr_e( $args['object_type'] ); ?>" data-objectkey="<?php esc_attr_e( $args['object_key'] ); ?>" data-current="<?php echo maybe_json_attr( $value, true ); ?>" <?php if ( $args['required'] ) { echo 'required'; } ?>>

            <?php if ( ! empty( $args['options'] ) ) {
                foreach ( $args['options'] as $label => $option ) {
                    $selected = 0;
                    if ( $multiple && in_array( $option, $value ) ) {
                        $selected = 1;
                    } elseif( ! $multiple && $option == $value ) {
                        $selected = 1;
                    } ?>
                    <option value="<?php esc_attr_e( $option ); ?>" <?php selected( 1, $selected ); ?>><?php esc_html_e( $label ); ?></option><?php
                }
            } ?>

		</select><?php

		if ( $args['desc'] != '' ) { ?>
			<p class="pum-desc"><?php esc_html_e( $args['desc'] ); ?></p><?php
		}

		$this->field_after();
	}

	/**
	 * Select Callback
	 *
	 * Renders select fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @param $value
	 */
	public function postselect_callback( $args, $value ) {

		$args = wp_parse_args( $args, array(
			'id' => '',
			'class' => '',
			'std' => '',
			'name' => '',
			'select2' => '',
			'multiple' => '',
			'as_array' => '',
			'label' => '',
			'placeholder' => '',
			'options' => array(),
			'desc' => '',
			'post_type' => '',
			'object_type' => '',
			'object_key' => '',
			'required' => false,
			'disabled' => false,
		) );

		$args['object_type'] = 'post_type';
		$args['object_key'] = $args['post_type'];
		$args['class'] = ! empty( $args['class'] ) ? $args['class'] : '' . ' pum-postselect';

		$this->objectselect_callback( $args, $value );

	}

	/**
	 * Select Callback
	 *
	 * Renders select fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @param $value
	 */
	public function taxonomyselect_callback( $args, $value ) {

		$args = wp_parse_args( $args, array(
			'id' => '',
			'class' => '',
			'std' => '',
			'name' => '',
			'select2' => '',
			'multiple' => '',
			'as_array' => '',
			'label' => '',
			'placeholder' => '',
			'options' => array(),
			'desc' => '',
			'taxonomy' => '',
			'object_type' => '',
			'object_key' => '',
			'required' => false,
			'disabled' => false,
		) );

		$args['object_type'] = 'taxonomy';
		$args['object_key'] = $args['taxonomy'];
		$args['class'] = ! empty( $args['class'] ) ? $args['class'] : '' . ' pum-taxonomyselect';

		$this->objectselect_callback( $args, $value );

	}

	/**
	 * Checkbox Callback
	 *
	 * Renders checkboxes.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @param $value
	 */
	public function checkbox_callback( $args, $value ) {

		$args = wp_parse_args( $args, array(
			'id' => '',
			'class' => '',
			'name' => '',
			'label' => '',
			'desc' => '',
			'checkbox_val' => 1,
			'required' => false,
			'disabled' => false,
		) );

		$class = 'pum-field-checkbox pum-field-' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}

		$this->field_before( $class );

		if ( ! empty( $args['label'] ) ) { ?>
			<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
		} ?>

		<input type="checkbox" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" value="<?php esc_attr_e( $args['checkbox_val'] ); ?>" <?php checked( 1, $value ); ?> /><?php

		if ( ! empty( $args['desc'] ) ) { ?>
			<label class="pum-desc" for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['desc'] ); ?></label><?php
		}

		$this->field_after();
	}

	/**
	 * Multicheck Callback
	 *
	 * Renders multiple checkboxes.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @param array $values
	 */
	public function multicheck_callback( $args, $values = array() ) {

		$args = wp_parse_args( $args, array(
			'id' => '',
			'class' => '',
			'name' => '',
			'label' => '',
			'desc' => '',
			'options' => array(),
		) );

		if ( ! empty( $args['options'] ) ) {

			$class = 'pum-field-multicheck pum-field-' . $args['id'];

			if ( ! empty ( $args['class'] ) ) {
				$class .= ' ' . $args['class'];
			}

			$this->field_before( $class );

			if ( ! empty( $args['label'] ) ) { ?>
				<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
			}

			foreach ( $args['options'] as $key => $option ) {

				// TODO Pass $option through a wp_parse_args functions. should allow required & disabled

				$checked = false;
				if ( isset( $values[ $key ] ) ) {
					$checked = true;
				} ?>

				<input name="<?php esc_attr_e( $args['name'] ); ?>[<?php esc_attr_e( $key ); ?>]" id="<?php esc_attr_e( $args['id'] . '_' . $key ); ?>" type="checkbox" value="<?php esc_html_e( $option ); ?>" <?php checked( true, $checked ); ?> />&nbsp;
				<label for="<?php esc_attr_e( $args['id']. '_' . $key ); ?>"><?php esc_html_e( $option ); ?></label><br/><?php
			}
			if ( $args['desc'] != '' ) { ?>
				<p class="pum-desc"><?php esc_html_e( $args['desc'] ); ?></p><?php
			}

			$this->field_after();
		}
	}

	/**
	 * Rangeslider Callback
	 *
	 * Renders the rangeslider.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @param $value
	 */
	public function rangeslider_callback( $args, $value ) {

		$args = wp_parse_args( $args, array(
			'id' => '',
			'class' => '',
			'name' => '',
			'label' => '',
			'desc' => '',
			'std' => 0,
			'min' => 0,
			'max' => 50,
			'step' => 1,
			'unit' => 'px',
			'required' => false,
			'disabled' => false,
		) );

		$class = 'pum-field-rangeslider pum-field-' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}
		$this->field_before( $class );

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		if ( ! empty( $args['label'] ) ) { ?>
			<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
		} ?>

		<input type="text" readonly
		       value="<?php echo $value; ?>"
		       name="<?php esc_attr_e( $args['name'] ); ?>"
		       id="<?php esc_attr_e( $args['id'] ); ?>"
		       class="popmake-range-manual pum-range-manual"
		       step="<?php esc_attr_e( $args['step'] ); ?>"
		       min="<?php esc_attr_e( $args['min'] ); ?>"
		       max="<?php esc_attr_e( $args['max'] ); ?>"
			   <?php if ( $args['required'] ) { echo 'required'; } ?>
			/>
		<span class="range-value-unit regular-text"><?php esc_html_e( $args['unit'] ); ?></span><?php

		if ( $args['desc'] != '' ) { ?>
			<p class="pum-desc"><?php esc_html_e( $args['desc'] ); ?></p><?php
		}

		$this->field_after();
	}
# endregion

# region Underscore.js Templ Field Callbacks
	public function heading_templ_callback( $args ) {
		$this->heading_callback( $args );
	}

	public function text_templ_callback( $args ) {
		$templ_name = $this->get_templ_name( $args );

		$class = 'pum-field-text pum-field-' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}

		$this->field_before( $class );

		if ( ! empty( $args['label'] ) ) { ?>
			<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
		}

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular'; ?>

		<input type="<?php esc_attr_e( $args['type'] ); ?>" placeholder="<?php esc_attr_e( $args['placeholder'] ); ?>" class="<?php esc_attr_e( $size ); ?>-text" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" value="<?php echo $templ_name; ?>"/><?php

		if ( $args['desc'] != '' ) { ?>
			<p class="pum-desc"><?php esc_html_e( $args['desc'] ); ?></p><?php
		}

		$this->field_after();
	}

	#region html5 text fields
	/**
	 * Password Callback
	 *
	 * Renders password fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function password_templ_callback( $args ) {
		$args['type'] = 'password';

		$this->text_templ_callback( $args );
	}

	/**
	 * Email Callback
	 *
	 * Renders email fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function email_templ_callback( $args ) {
		$args['type'] = 'email';

		$this->text_templ_callback( $args );
	}

	/**
	 * Search Callback
	 *
	 * Renders search fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function search_templ_callback( $args ) {
		$args['type'] = 'search';

		$this->text_templ_callback( $args );
	}

	/**
	 * URL Callback
	 *
	 * Renders url fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function url_templ_callback( $args ) {
		$args['type'] = 'url';

		$this->text_templ_callback( $args );
	}

	/**
	 * Telephone Callback
	 *
	 * Renders telelphone number fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function tel_templ_callback( $args ) {
		$args['type'] = 'tel';

		$this->text_templ_callback( $args );
	}

	/**
	 * Number Callback
	 *
	 * Renders number fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function number_templ_callback( $args ) {
		$args['type'] = 'number';

		$this->text_templ_callback( $args );
	}

	/**
	 * Range Callback
	 *
	 * Renders range fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function range_templ_callback( $args ) {
		$args['type'] = 'range';

		$this->text_templ_callback( $args );
	}
#endregion html5 text fields

	/**
	 * Hidden Callback
	 *
	 * Renders hidden fields.
	 *
	 * @param array $args Arguments passed by the setting
	 */
	public function hidden_templ_callback( $args ) {
		$templ_name = $this->get_templ_name( $args, false );

		$class = 'pum-field-hidden pum-field-' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		} ?>

		<input type="hidden" class="<?php esc_attr_e( implode( ',', $class ) ); ?>" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" value="<?php echo $templ_name; ?>"/><?php
	}

	public function select_templ_callback( $args ) {
		$templ_name = $this->get_templ_name( $args, false );

		$class = 'pum-field-select pum-field-' . $args['id'];

        if ( isset ( $args['select2'] ) ) {
            $class .= ' pum-select2';
		}

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}

        $multiple = '';
        if ( isset ( $args['multiple'] ) && $args['multiple'] ) {
            $multiple = 'multiple';
            if ( $args['as_array'] ) {
                $args['name'] = $args['name'] . '[]';
            }
        }

		$this->field_before( $class );

		if ( ! empty( $args['label'] ) ) { ?>
			<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
		} ?>

	    <select id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" data-placeholder="<?php esc_attr_e( $args['placeholder'] ); ?>" data-allow-clear="true" <?php echo $multiple; ?>>

		<?php if ( ! empty( $args['options'] ) ) {
			foreach ( $args['options'] as $label => $option ) { ?>
				<option value="<?php esc_attr_e( $option ); ?>" {{pumSelected(data.<?php esc_attr_e( $templ_name ); ?>, '<?php echo $option; ?>', true)}}>
				<?php esc_html_e( $label ); ?>
				</option><?php
			}
		} ?>

		</select><?php

		if ( $args['desc'] != '' ) { ?>
			<p class="pum-desc"><?php esc_html_e( $args['desc'] ); ?></p><?php
		}

		$this->field_after();
	}

	public function objectselect_templ_callback( $args ) {
		$templ_name = $this->get_templ_name( $args, false );

        $class = 'pum-field-objectselect pum-select2 pum-field-' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}

        $multiple = '';
        if ( isset ( $args['multiple'] ) && $args['multiple'] ) {
            $multiple = 'multiple';
            if ( $args['as_array'] ) {
                $args['name'] = $args['name'] . '[]';
            }
        }

		$this->field_before( $class );

		if ( ! empty( $args['label'] ) ) { ?>
			<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
		} ?>

		<# if (typeof data.<?php esc_attr_e( $templ_name ); ?> === 'undefined') {
			data.<?php esc_attr_e( $templ_name ); ?> = '';
		} #>

    <select id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" data-placeholder="<?php esc_attr_e( $args['placeholder'] ); ?>" data-allow-clear="true" <?php echo $multiple; ?> data-objecttype="<?php esc_attr_e( $args['object_type'] ); ?>" data-objectkey="<?php esc_attr_e( $args['object_key'] ); ?>">

		<?php if ( ! empty( $args['options'] ) ) {
			foreach ( $args['options'] as $label => $option ) { ?>
				<option value="<?php esc_attr_e( $option ); ?>" {{pumSelected(data.<?php esc_attr_e( $templ_name ); ?>, '<?php echo $option; ?>', true)}}>
				<?php esc_html_e( $label ); ?>
				</option><?php
			}
		} ?>

		</select><?php

		if ( $args['desc'] != '' ) { ?>
			<p class="pum-desc"><?php esc_html_e( $args['desc'] ); ?></p><?php
		}

		$this->field_after();
	}

	/**
	 * Select Callback
	 *
	 * Renders select fields.
	 *
	 * @param array $args Arguments passed by the setting
	 */
	public function postselect_templ_callback( $args ) {

		$args['object_type'] = 'post_type';
		$args['object_key'] = $args['post_type'];
		$args['class'] = ! empty( $args['class'] ) ? $args['class'] : '' . ' pum-postselect';

		$this->objectselect_templ_callback( $args );

	}

	/**
	 * Select Callback
	 *
	 * Renders select fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function taxonomyselect_templ_callback( $args ) {

		$args['object_type'] = 'taxonomy';
		$args['object_key'] = $args['taxonomy'];
		$args['class'] = ! empty( $args['class'] ) ? $args['class'] : '' . ' pum-taxonomyselect';

		$this->objectselect_templ_callback( $args );

	}


	public function checkbox_templ_callback( $args ) {
		$templ_name = $this->get_templ_name( $args, false );

		$class = 'pum-field-checkbox pum-field-' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}

		$this->field_before( $class );

		if ( ! empty( $args['label'] ) ) { ?>
			<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
		} ?>

		<input type="checkbox" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" value="<?php esc_attr_e( $args['checkbox_val'] ); ?>" <# if (data.<?php esc_attr_e( $templ_name ); ?>) { print('checked="checked"'); } #> /><?php

		if ( ! empty( $args['desc'] ) ) { ?>
			<label class="pum-desc" for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['desc'] ); ?></label><?php
		}

		$this->field_after();
	}

	public function multicheck_templ_callback( $args ) {
		if ( ! empty( $args['options'] ) ) {

			$templ_name = $this->get_templ_name( $args, false );

			$class = 'pum-field-multicheck pum-field-' . $args['id'];

			if ( ! empty ( $args['class'] ) ) {
				$class .= ' ' . $args['class'];
			}

			$this->field_before( $class );

			if ( ! empty( $args['label'] ) ) { ?>
				<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
			}

			foreach ( $args['options'] as $key => $option ) { ?>
				<input name="<?php esc_attr_e( $args['name'] ); ?>[<?php esc_attr_e( $key ); ?>]" id="<?php esc_attr_e( $args['id'] ); ?>[<?php esc_attr_e( $key ); ?>]" type="checkbox" value="<?php esc_html_e( $option ); ?>" <# if (data.<?php esc_attr_e( $templ_name . '[' . $key . ']' ); ?> !== undefined) { print('checked="checked"'); } #> />&nbsp;
				<label for="<?php esc_attr_e( $args['id'] ); ?>[<?php esc_attr_e( $key ); ?>]"><?php esc_html_e( $option ); ?></label><br/><?php
			}
			if ( $args['desc'] != '' ) { ?>
				<p class="pum-desc"><?php esc_html_e( $args['desc'] ); ?></p><?php
			}

			$this->field_after();
		}
	}

	public function rangeslider_templ_callback( $args ) {
		$value = $this->get_templ_name( $args );
		$this->rangeslider_callback( $args, $value );
	}
# endregion

# region General Field Callbacks
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
		$hook = ! empty ( $args['hook'] ) ? $args['hook'] : $args['id'];

		if ( ! has_action( 'pum_' . $hook ) && has_action( $hook ) ) {
			do_action( $hook, $args );
		} else {
			do_action( 'pum_' . $hook, $args );
		}
	}

	/**
	 * Missing Callback
	 *
	 * If a function is missing for settings callbacks alert the user.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function missing_callback( $args ) { ?>
		<div class="field">
			<?php printf( __( 'The callback function for "%s", used for the <strong>%s</strong> setting is missing.' ), $args['type'], $args['id'] ); ?>
		</div><?php
	}
# endregion

# region Sanitization Callbacks
    public function objectselect_sanitize( $value = array(), $args = array() ) {
        return wp_parse_id_list( $value );
    }

    public function postselect_sanitize( $value = array(), $args = array() ) {
        return $this->objectselect_sanitize( $value, $args );
    }

    public function taxonomyselect_sanitize( $value = array(), $args = array() ) {
        return $this->objectselect_sanitize( $value, $args );
    }
# endregion

/**
* TODO: Finish adding the following field types for HTML & underscore.js
 */
# region Unfinished Callbacks
	/**
	 * Radio Callback
	 *
	 * Renders radio boxes.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 *
	public function radio_callback( $args, $value ) {
		if ( ! empty( $args['options'] ) ) {

			foreach ( $args['options'] as $key => $option ) {
				$checked = false;

				$value = $this->get_option( $args['id'] );

				if ( $value == $key || ( ! $value && isset( $args['std'] ) && $args['std'] == $key ) ) {
					$checked = true;
				}

				echo '<input name="<?php esc_attr_e( $args['name'] ); ?>"" id="<?php esc_attr_e( $args['id'] ); ?>[<?php esc_attr_e( $key ); ?>]" type="radio" value="<?php esc_attr_e( $key ); ?>" ' . checked( true, $checked, false ) . '/>&nbsp;';
				echo '<label for="<?php esc_attr_e( $args['id'] ); ?>[<?php esc_attr_e( $key ); ?>]">' . $option . '</label><br/>';
			}

			echo '<p class="pum-desc">' . $args['desc'] . '</p>';

		}
	}


	/**
	 * Number Callback
	 *
	 * Renders number fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 *
	public function number_callback( $args ) {

		$value = $this->get_option( $args['id'] );

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$max = isset( $args['max'] ) ? $args['max'] : 999999;
		$min = isset( $args['min'] ) ? $args['min'] : 0;
		$step = isset( $args['step'] ) ? $args['step'] : 1;

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
		$html .= '<label for="<?php esc_attr_e( $args['id'] ); ?>"> ' . $args['desc'] . '</label>';

		echo $html;
	}


	/**
	 * Password Callback
	 *
	 * Renders password fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 *
	public function password( $args ) {

		$value = $this->get_option( $args['id'] );

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="password" class="' . $size . '-text" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" value="' . esc_attr( $value ) . '"/>';
		$html .= '<label for="<?php esc_attr_e( $args['id'] ); ?>"> ' . $args['desc'] . '</label>';

		echo $html;
	}


	/**
	 * Color select Callback
	 *
	 * Renders color select fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 *
	public function color_select_callback( $args ) {

		$value = $this->get_option( $args['id'] );

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$html = '<select id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>"/>';

		if ( ! empty( $args['options'] ) ) {
			foreach ( $args['options'] as $option => $color ) {
				$selected = selected( $option, $value, false );
				$html .= '<option value="' . $option . '" ' . $selected . '>' . $color['label'] . '</option>';
			}
		}

		$html .= '</select>';
		$html .= '<label for="<?php esc_attr_e( $args['id'] ); ?>"> ' . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Rich Editor Callback
	 *
	 * Renders rich editor fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @global $wp_version WordPress Version
	 *
	public function rich_editor_callback( $args ) {
		global $wp_version;
		$value = $this->get_option( $args['id'] );

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$rows = isset( $args['size'] ) ? $args['size'] : 20;

		if ( $wp_version >= 3.3 && function_exists( 'wp_editor' ) ) {
			ob_start();
			wp_editor( stripslashes( $value ), $this->options_key . '_' . $args['id'], array(
				'textarea_name' => '' . $this->options_key . '[' . $args['id'] . ']',
				'textarea_rows' => $rows
			) );
			$html = ob_get_clean();
		} else {
			$html = '<textarea class="large-text" rows="10" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
		}

		$html .= '<br/><label for="<?php esc_attr_e( $args['id'] ); ?>"> ' . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Upload Callback
	 *
	 * Renders upload fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 *
	public function upload_callback( $args ) {
		$value = $this->get_option( $args['id'] );

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="text" class="' . $size . '-text" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
		$html .= '<span>&nbsp;<input type="button" class="' . $this->options_key . '_upload_button button-secondary" value="' . __( 'Upload File' ) . '"/></span>';
		$html .= '<label for="<?php esc_attr_e( $args['id'] ); ?>"> ' . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Color picker Callback
	 *
	 * Renders color picker fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 *
	public function color_callback( $args ) {
		$value = $this->get_option( $args['id'] );

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$default = isset( $args['std'] ) ? $args['std'] : '';

		$html = '<input type="text" class="di-color-picker" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $default ) . '" />';
		$html .= '<label for="<?php esc_attr_e( $args['id'] ); ?>"> ' . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Descriptive text callback.
	 *
	 * Renders descriptive text onto the settings field.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 *
	public function descriptive_text_callback( $args ) {
		echo esc_html( $args['desc'] );
	}

	/**
	 * Registers the license field callback for Software Licensing
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 *
	public function license_key_callback( $args ) {
		$value = $this->get_option( $args['id'] );

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';

		$html = '<input type="' . ( $value == '' ? 'text' : 'password' ) . '" class="' . $size . '-text" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" value="' . esc_attr( $value ) . '"/>';

		if ( 'valid' == get_option( $args['options']['is_valid_license_option'] ) ) {
			$html .= '<input type="submit" class="button-secondary" name="' . $args['id'] . '_deactivate" value="' . __( 'Deactivate License' ) . '"/>';
		}
		$html .= '<label for="<?php esc_attr_e( $args['id'] ); ?>"> ' . $args['desc'] . '</label>';

		echo $html;
	}
     *
     * */
# endregion

}
