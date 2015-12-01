<?php
/**
 * Fields
 *
 * @package     PUM
 * @subpackage  Classes/PUM_Fields
 * @copyright   Copyright (c) 2015, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Fields extends Popmake_Fields {

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
	 * Text Callback
	 *
	 * Renders text fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function text_callback( $args, $value ) {

		$class = 'text ' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}

		$this->field_before( $class );

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		if ( ! empty( $args['label'] ) ) { ?>
			<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
		}

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular'; ?>

		<input type="text" placeholder="<?php esc_attr_e( $args['placeholder'] ); ?>" class="<?php esc_attr_e( $size ); ?>-text" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" value="<?php esc_attr_e( stripslashes( $value ) ); ?>"/><?php

		if ( $args['desc'] != '' ) { ?>
			<p class="desc"><?php esc_html_e( $args['desc'] ); ?></p><?php
		}

		$this->field_after();
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

		$class = 'hidden ' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		} ?>

		<input type="hidden" class="<?php esc_attr_e( implode( ',', $class ) ); ?>" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" value="<?php esc_attr_e( stripslashes( $value ) ); ?>"/><?php
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
	public function select_callback( $args, $value ) {

		$class = 'select ' . $args['id'];

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

		<select id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" >

		<?php if ( ! empty( $args['options'] ) ) {
			foreach ( $args['options'] as $label => $option ) { ?>
				<option value="<?php esc_attr_e( $option ); ?>" <?php selected( $option, $value ); ?>><?php esc_html_e( $label ); ?></option><?php
			}
		} ?>

		</select><?php

		if ( $args['desc'] != '' ) { ?>
			<p class="desc"><?php esc_html_e( $args['desc'] ); ?></p><?php
		}

		$this->field_after();
	}

	/**
	 * Checkbox Callback
	 *
	 * Renders checkboxes.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function checkbox_callback( $args, $value ) {
		$class = 'checkbox ' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}

		$this->field_before( $class );

		if ( ! empty( $args['label'] ) ) { ?>
			<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
		} ?>

		<input type="checkbox" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" value="1" <?php checked( 1, $value ); ?> /><?php

		if ( ! empty( $args['desc'] ) ) { ?>
			<label class="desc" for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['desc'] ); ?></label><?php
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
	 * @return void
	 */
	public function multicheck_callback( $args, $value = array() ) {
		if ( ! empty( $args['options'] ) ) {

			$class = 'multicheck ' . $args['id'];

			if ( ! empty ( $args['class'] ) ) {
				$class .= ' ' . $args['class'];
			}

			$this->field_before( $class );

			if ( ! empty( $args['label'] ) ) { ?>
				<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
			}

			foreach ( $args['options'] as $key => $option ) {
				$checked = false;
				if ( isset( $values[ $key ] ) ) {
					$checked = true;
				} ?>

				<input name="<?php esc_attr_e( $args['name'] ); ?>[<?php esc_attr_e( $key ); ?>]" id="<?php esc_attr_e( $args['id'] ); ?>[<?php esc_attr_e( $key ); ?>]" type="checkbox" value="<?php esc_html_e( $option ); ?>" <?php checked( true, $checked ); ?> />&nbsp;
				<label for="<?php esc_attr_e( $args['id'] ); ?>[<?php esc_attr_e( $key ); ?>]"><?php esc_html_e( $option ); ?></label><br/><?php
			}
			if ( $args['desc'] != '' ) { ?>
				<p class="desc"><?php esc_html_e( $args['desc'] ); ?></p><?php
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
	 * @return void
	 */
	public function rangeslider_callback( $args, $value ) {

		$class = 'rangeslider ' . $args['id'];

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
			/>
		<span class="range-value-unit regular-text"><?php esc_html_e( $args['unit'] ); ?></span><?php

		if ( $args['desc'] != '' ) { ?>
			<p class="desc"><?php esc_html_e( $args['desc'] ); ?></p><?php
		}

		$this->field_after();
	}


	public function heading_templ_callback( $args ) {
		$this->heading_callback( $args );
	}

	public function text_templ_callback( $args ) {
		$templ_name = $this->get_templ_name( $args );

		$class = 'text ' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}

		$this->field_before( $class );

		if ( ! empty( $args['label'] ) ) { ?>
			<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
		}

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular'; ?>

		<input type="text" placeholder="<?php esc_attr_e( $args['placeholder'] ); ?>" class="<?php esc_attr_e( $size ); ?>-text" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" value="<?php echo $templ_name; ?>"/><?php

		if ( $args['desc'] != '' ) { ?>
			<p class="desc"><?php esc_html_e( $args['desc'] ); ?></p><?php
		}

		$this->field_after();
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
	public function hidden_templ_callback( $args, $value ) {
		$templ_name = $this->get_templ_name( $args, false );

		$class = 'hidden ' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		} ?>

		<input type="hidden" class="<?php esc_attr_e( implode( ',', $class ) ); ?>" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" value="<?php echo $templ_name; ?>"/><?php
	}

	public function select_templ_callback( $args ) {
		$templ_name = $this->get_templ_name( $args, false );

		$class = 'select ' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}

		$this->field_before( $class );

		if ( ! empty( $args['label'] ) ) { ?>
			<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
		} ?>

		<select id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" >

		<?php if ( ! empty( $args['options'] ) ) {
			foreach ( $args['options'] as $label => $option ) { ?>
				<option value="<?php esc_attr_e( $option ); ?>"
				<% if(<?php esc_attr_e( $templ_name ); ?> === '<?php echo $option; ?>') { %>selected="selected"<% } %>
				><?php esc_html_e( $label ); ?></option><?php
			}
		} ?>

		</select><?php

		if ( $args['desc'] != '' ) { ?>
			<p class="desc"><?php esc_html_e( $args['desc'] ); ?></p><?php
		}

		$this->field_after();
	}

	public function checkbox_templ_callback( $args ) {
		$templ_name = $this->get_templ_name( $args, false );

		$class = 'checkbox ' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}

		$this->field_before( $class );

		if ( ! empty( $args['label'] ) ) { ?>
			<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
		} ?>

		<input type="checkbox" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" value="1" <% if (<?php esc_attr_e( $templ_name ); ?>) { %>checked="checked"<% } %> /><?php

		if ( ! empty( $args['desc'] ) ) { ?>
			<label class="desc" for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['desc'] ); ?></label><?php
		}

		$this->field_after();
	}

	public function multicheck_templ_callback( $args ) {
		if ( ! empty( $args['options'] ) ) {

			$templ_name = $this->get_templ_name( $args, false );

			$class = 'multicheck ' . $args['id'];

			if ( ! empty ( $args['class'] ) ) {
				$class .= ' ' . $args['class'];
			}

			$this->field_before( $class );

			if ( ! empty( $args['label'] ) ) { ?>
				<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
			}

			foreach ( $args['options'] as $key => $option ) { ?>
				<input name="<?php esc_attr_e( $args['name'] ); ?>[<?php esc_attr_e( $key ); ?>]" id="<?php esc_attr_e( $args['id'] ); ?>[<?php esc_attr_e( $key ); ?>]" type="checkbox" value="<?php esc_html_e( $option ); ?>" <% if (<?php esc_attr_e( $templ_name . '[' . $key . ']' ); ?> !== undefined) { %>checked="checked"<% } %> />&nbsp;
				<label for="<?php esc_attr_e( $args['id'] ); ?>[<?php esc_attr_e( $key ); ?>]"><?php esc_html_e( $option ); ?></label><br/><?php
			}
			if ( $args['desc'] != '' ) { ?>
				<p class="desc"><?php esc_html_e( $args['desc'] ); ?></p><?php
			}

			$this->field_after();
		}
	}

	public function rangeslider_templ_callback( $args ) {
		$value = $this->get_templ_name( $args );
		$this->rangeslider_callback( $args, $value );
	}











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

			echo '<p class="desc">' . $args['desc'] . '</p>';

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
	 * Textarea Callback
	 *
	 * Renders textarea fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 *
	public function textarea_callback( $args ) {

		$value = $this->get_option( $args['id'] );

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$html = '<textarea class="large-text" cols="50" rows="5" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
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
		do_action( 'pum_' . $hook, $args );
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


}

