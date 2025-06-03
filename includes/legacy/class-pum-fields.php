<?php
/**
 * Fields
 *
 * @package     PUM
 * @subpackage  Classes/PUM_Fields
 * @copyright   Copyright (c) 2023, Code Atlantic LLC
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PUM_Fields Class
 */
class PUM_Fields extends Popmake_Fields {

	// region Non Fields

	/**
	 * Hook Callback
	 *
	 * Renders the heading.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function hook_callback( $args ) {
		do_action( $args['hook'], $args );
	}

	/**
	 * Heading Callback
	 *
	 * Renders the heading.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function heading_callback( $args ) {
		?>
		</td></tr></tbody></table>
		<h2 class="pum-setting-heading"><?php echo esc_html( $args['desc'] ); ?></h2>
		<hr/>
		<table class="form-table"><tbody><tr style="display:none;"><td colspan="2">
		<?php
	}


	// endregion Non Fields

	// region Standard Fields

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

		$this->field_before( $args );
		?>

		<button type="<?php echo esc_attr( $args['button_type'] ); ?>" class="pum-button-<?php echo esc_attr( $args['size'] ); ?>" id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>"><?php echo esc_html( $args['label'] ); ?></button>
								<?php

									$this->field_description( $args );

									$this->field_after();
	}

	/**
	 * Text Callback
	 *
	 * Renders text fields.
	 *
	 * @param array  $args Arguments passed by the setting
	 *
	 * @param string $value
	 */
	public function text_callback( $args, $value = null ) {

		if ( 'text' !== $args['type'] ) {
			$args['class'] .= '  pum-field-text';
		}

		$this->field_before( $args );

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$this->field_label( $args );
		?>

		<input type="<?php echo esc_attr( $args['type'] ); ?>" placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>" class="<?php echo esc_attr( $args['size'] ); ?>-text" id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" value="<?php echo esc_attr( stripslashes( $value ) ); ?>" 
								<?php
								if ( $args['required'] ) {
									echo 'required'; }
								?>
		/>
								<?php

								$this->field_description( $args );

								$this->field_after();
	}

	/**
	 * Textarea Callback
	 *
	 * Renders textarea fields.
	 *
	 * @param array  $args Arguments passed by the setting
	 *
	 * @param string $value
	 */
	public function textarea_callback( $args, $value = null ) {
		$this->field_before( $args );

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$this->field_label( $args );
		?>

		<textarea placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>" class="<?php echo esc_attr( $args['size'] ); ?>-text" id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" cols="<?php echo esc_attr( $args['cols'] ); ?>" rows="<?php echo esc_attr( $args['rows'] ); ?>" 
											<?php
											if ( $args['required'] ) {
												echo 'required'; }
											?>
		><?php echo esc_textarea( stripslashes( $value ) ); ?></textarea>
											<?php

											$this->field_description( $args );

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
	public function hidden_callback( $args, $value = null ) {

		$class = $this->field_classes( $args );

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}
		?>

		<input type="hidden" class="<?php echo esc_attr( $class ); ?>" id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" value="<?php echo esc_attr( stripslashes( $value ) ); ?>"/>
												<?php
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
	public function select_callback( $args, $value = null ) {

		if ( isset( $args['select2'] ) ) {
			$args['class'] .= '  pum-field-select2';
		}

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$multiple = null;
		if ( $args['multiple'] ) {
			$multiple       = 'multiple';
			$args['class'] .= '  pum-field-select--multiple';
			$args['name']  .= $args['as_array'] ? '[]' : '';
			$value          = ! is_array( $value ) ? [ $value ] : $value;
		}

		$this->field_before( $args );

		$this->field_label( $args );
		?>

		<select id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" data-placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>" data-allow-clear="true" <?php echo esc_attr( $multiple ); ?> <?php
		if ( $args['required'] ) {
			echo 'required'; }
		?>
		>
		<?php
		if ( ! empty( $args['options'] ) ) {
			foreach ( $args['options'] as $label => $option ) {
				$selected = ( ! $multiple && $option === $value ) || ( $multiple && in_array( $option, $value, true ) );
				?>
				<option value="<?php echo esc_attr( $option ); ?>" <?php selected( 1, $selected ); ?>><?php echo esc_html( $label ); ?></option>
											<?php
			}
		}
		?>
		</select>
		<?php

		$this->field_description( $args );

		$this->field_after();
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
		$this->field_before( $args );

		$this->field_label( $args );
		?>

		<input type="checkbox" id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" value="<?php echo esc_attr( $args['checkbox_val'] ); ?>" <?php checked( 1, $value ); ?> />
												<?php

												$this->field_description( $args );

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
	public function multicheck_callback( $args, $values = [] ) {
		$this->field_before( $args );

		$this->field_label( $args );

		if ( ! empty( $args['options'] ) ) {
			foreach ( $args['options'] as $key => $option ) {
				if ( ! is_array( $option ) ) {
					$option = [
						'label' => $option,
					];
				}

				$option = wp_parse_args(
					$option,
					[
						'label'    => '',
						'required' => false,
						'disabled' => false,
					]
				);

				$checked = isset( $values[ $key ] );
				?>
				<input name="<?php echo esc_attr( $args['name'] ); ?>[<?php echo esc_attr( $key ); ?>]" id="<?php echo esc_attr( $args['id'] . '_' . $key ); ?>" type="checkbox" value="<?php echo esc_html( $option ); ?>" <?php checked( true, $checked ); ?> <?php
				if ( $option['disabled'] ) {
					echo 'disabled="disabled"'; }
				?>
				<?php
				if ( $option['required'] ) {
									echo 'required'; }
				?>
/>&nbsp;
				<label for="<?php echo esc_attr( $args['id'] . '_' . $key ); ?>"><?php echo esc_html( $option['label'] ); ?></label><br/>
										<?php
			}
		}

		$this->field_description( $args );

		$this->field_after();
	}

	// endregion Standard Fields

	// region HTML5 Text Fields

	/**
	 * Password Callback
	 *
	 * Renders password fields.
	 *
	 * @param array  $args Arguments passed by the setting
	 *
	 * @param string $value
	 */
	public function password_callback( $args, $value = null ) {
		$args['type'] = 'password';

		$this->text_callback( $args, $value );
	}

	/**
	 * Email Callback
	 *
	 * Renders email fields.
	 *
	 * @param array  $args Arguments passed by the setting
	 *
	 * @param string $value
	 */
	public function email_callback( $args, $value = null ) {
		$args['type'] = 'email';

		$this->text_callback( $args, $value );
	}

	/**
	 * Search Callback
	 *
	 * Renders search fields.
	 *
	 * @param array  $args Arguments passed by the setting
	 *
	 * @param string $value
	 */
	public function search_callback( $args, $value = null ) {
		$args['type'] = 'search';

		$this->text_callback( $args, $value );
	}

	/**
	 * URL Callback
	 *
	 * Renders url fields.
	 *
	 * @param array  $args Arguments passed by the setting
	 *
	 * @param string $value
	 */
	public function url_callback( $args, $value = null ) {
		$args['type'] = 'url';

		$this->text_callback( $args, $value );
	}

	/**
	 * Telephone Callback
	 *
	 * Renders telelphone number fields.
	 *
	 * @param array  $args Arguments passed by the setting
	 *
	 * @param string $value
	 */
	public function tel_callback( $args, $value = null ) {
		$args['type'] = 'tel';

		$this->text_callback( $args, $value );
	}

	/**
	 * Number Callback
	 *
	 * Renders number fields.
	 *
	 * @param array  $args Arguments passed by the setting
	 *
	 * @param string $value
	 */
	public function number_callback( $args, $value = null ) {
		$args['type'] = 'number';

		$this->text_callback( $args, $value );
	}

	/**
	 * Range Callback
	 *
	 * Renders range fields.
	 *
	 * @param array  $args Arguments passed by the setting
	 *
	 * @param string $value
	 */
	public function range_callback( $args, $value = null ) {
		$args['type'] = 'range';

		$this->text_callback( $args, $value );
	}

	// endregion HTML5 Text Fields

	// region Custom Fields (post_type, taxonomy, object, rangeslider)

	/**
	 * Object Select Callback
	 *
	 * Renders select fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @param null  $value
	 */
	public function objectselect_callback( $args, $value = null ) {

		if ( 'objectselect' !== $args['type'] ) {
			$args['class'] .= '  pum-field-objectselect';
		}

		$args['class'] .= '  pum-field-select  pum-field-select2';

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$multiple = null;
		if ( $args['multiple'] ) {
			$multiple       = 'multiple';
			$args['class'] .= '  pum-field-select--multiple';
			$args['name']  .= $args['as_array'] ? '[]' : '';
			$value          = ! is_array( $value ) ? [ $value ] : $value;
			$value          = wp_parse_id_list( $value );
		}

		$this->field_before( $args );

		$this->field_label( $args );
		?>

		<select id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" data-placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>" data-allow-clear="true" <?php echo esc_attr( $multiple ); ?> data-objecttype="<?php echo esc_attr( $args['object_type'] ); ?>" data-objectkey="<?php echo esc_attr( $args['object_key'] ); ?>" data-current="
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo PUM_Utils_Array::maybe_json_attr( $value, true );
			?>
			" 
			<?php
			if ( $args['required'] ) {
				echo 'required'; }
			?>
		>
			<?php
			if ( ! empty( $args['options'] ) ) {
				foreach ( $args['options'] as $label => $option ) {
					$selected = ( $multiple && in_array( $option, $value, true ) ) || ( ! $multiple && $option === $value );
					?>
					<option value="<?php echo esc_attr( $option ); ?>" <?php selected( 1, $selected ); ?>><?php echo esc_html( $label ); ?></option>
												<?php
				}
			}
			?>
		</select>
		<?php

		$this->field_description( $args );

		$this->field_after();
	}

	/**
	 * Taxonomy Select Callback
	 *
	 * Renders select fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @param $value
	 */
	public function taxonomyselect_callback( $args, $value ) {
		$args['object_type'] = 'taxonomy';
		$args['object_key']  = $args['taxonomy'];
		$args['class']       = ! empty( $args['class'] ) ? $args['class'] : '  pum-taxonomyselect';

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
	public function postselect_callback( $args, $value = null ) {
		$args['object_type'] = 'post_type';
		$args['object_key']  = $args['post_type'];
		$args['class']       = ! empty( $args['class'] ) ? $args['class'] : '  pum-postselect';

		$this->objectselect_callback( $args, $value );
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
	public function rangeslider_callback( $args, $value = null ) {
		$this->field_before( $args );

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$this->field_label( $args );
		?>

		<input type="text"
				value="<?php echo esc_attr( $value ); ?>"
				name="<?php echo esc_attr( $args['name'] ); ?>"
				id="<?php echo esc_attr( $args['id'] ); ?>"
				class="pum-range-manual popmake-range-manual"
				step="<?php echo esc_attr( $args['step'] ); ?>"
				min="<?php echo esc_attr( $args['min'] ); ?>"
				max="<?php echo esc_attr( $args['max'] ); ?>"
				<?php
				if ( $args['required'] ) {
					echo 'required'; }
				?>
				data-force-minmax="<?php echo esc_attr( $args['force_minmax'] ); ?>"
			/>
		<span class="range-value-unit regular-text"><?php echo esc_html( $args['unit'] ); ?></span>
																<?php

																$this->field_description( $args );

																$this->field_after();
	}
	// endregion Custom Fields (post_type, taxonomy, object, rangeslider)

	// region Templ Non Fields

	/**
	 * Renders the heading.
	 */
	public function heading_templ_callback( $args ) {
		$this->heading_callback( $args );
	}

	// endregion Non Fields

	/**
	 * Renders the text field.
	 */
	public function text_templ_callback( $args ) {

		if ( 'text' !== $args['type'] ) {
			$args['class'] .= '  pum-field-text';
		}

		$this->field_before( $args );

		$this->field_label( $args );
		?>

		<input type="<?php echo esc_attr( $args['type'] ); ?>" placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>" class="<?php echo esc_attr( $args['size'] ); ?>-text" id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" value="{{data.<?php echo esc_attr( $args['templ_name'] ); ?>}}" />
								<?php

								$this->field_description( $args );

								$this->field_after();
	}

	/**
	 * Password Callback
	 *
	 * Renders password fields.
	 *
	 * @param array $args Arguments passed by the setting
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

	/**
	 * Hidden Callback
	 *
	 * Renders hidden fields.
	 *
	 * @param array $args Arguments passed by the setting
	 */
	public function hidden_templ_callback( $args ) {
		$class = $this->field_classes( $args );
		?>
		<input type="hidden" class="<?php echo esc_attr( $class ); ?>" id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" value="{{data.<?php echo esc_attr( $args['templ_name'] ); ?>}}"/>
												<?php
	}

	public function select_templ_callback( $args ) {
		if ( $args['select2'] ) {
			$args['class'] .= '  pum-field-select2';
		}

		$multiple = null;
		if ( $args['multiple'] ) {
			$multiple       = 'multiple';
			$args['class'] .= '  pum-field-select--multiple';
			$args['name']  .= $args['as_array'] ? '[]' : '';
		}

		$this->field_before( $args );

		$this->field_label( $args );
		?>

		<select id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" data-placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>" data-allow-clear="true" <?php echo esc_attr( $multiple ); ?>>

		<?php
		if ( ! empty( $args['options'] ) ) {
			foreach ( $args['options'] as $label => $option ) {
				?>
				<option value="<?php echo esc_attr( $option ); ?>" {{pumSelected(data.<?php echo esc_attr( $args['templ_name'] ); ?>, '<?php echo esc_attr( $option ); ?>', true)}}>
					<?php echo esc_html( $label ); ?>
				</option>
				<?php
			}
		}
		?>

		</select>
		<?php

		$this->field_description( $args );

		$this->field_after();
	}

	/**
	 * Posttype Select Callback
	 *
	 * Renders select fields.
	 *
	 * @param array $args Arguments passed by the setting
	 */
	public function postselect_templ_callback( $args ) {
		$args['object_type'] = 'post_type';
		$args['object_key']  = $args['post_type'];
		$args['class']      .= '  pum-postselect';

		$this->objectselect_templ_callback( $args );
	}

	public function objectselect_templ_callback( $args ) {
		if ( 'objectselect' !== $args['type'] ) {
			$args['class'] .= '  pum-field-objectselect';
		}

		$args['class'] .= '  pum-field-select  pum-field-select2';

		$multiple = null;
		if ( $args['multiple'] ) {
			$multiple       = 'multiple';
			$args['class'] .= '  pum-field-select--multiple';
			$args['name']  .= $args['as_array'] ? '[]' : '';
		}

		$this->field_before( $args );

		$this->field_label( $args );
		?>

		<# var templ_name = '<?php echo esc_attr( $args['templ_name'] ); ?>'; #>

		<# if (typeof data[templ_name] === 'undefined') {
			data[templ_name] = '';
		} #>

		<select id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" data-placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>" data-allow-clear="true" <?php echo esc_attr( $multiple ); ?> data-objecttype="<?php echo esc_attr( $args['object_type'] ); ?>" data-objectkey="<?php echo esc_attr( $args['object_key'] ); ?>">
			<?php
			if ( ! empty( $args['options'] ) ) {
				foreach ( $args['options'] as $label => $option ) {
					?>
					<option value="<?php echo esc_attr( $option ); ?>" {{pumSelected(data[templ_name], '<?php echo esc_attr( $option ); ?>', true)}}>
						<?php echo esc_html( $label ); ?>
					</option>
					<?php
				}
			}
			?>
		</select>
		<?php

		$this->field_description( $args );

		$this->field_after();
	}

	/**
	 * Select Callback
	 *
	 * Renders select fields.
	 *
	 * @param array $args Arguments passed by the setting
	 */
	public function taxonomyselect_templ_callback( $args ) {
		$args['object_type'] = 'taxonomy';
		$args['object_key']  = $args['taxonomy'];
		$args['class']      .= '  pum-field-taxonomyselect';
		$this->objectselect_templ_callback( $args );
	}

	public function checkbox_templ_callback( $args ) {
		$this->field_before( $args );

		$this->field_label( $args );
		?>

		<# var checked = data.<?php echo esc_attr( $args['templ_name'] ); ?> !== undefined && data.<?php echo esc_attr( $args['templ_name'] ); ?> ? true : false; #>

		<input type="checkbox" id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" value="<?php echo esc_attr( $args['checkbox_val'] ); ?>" {{pumChecked(checked, true, true)}} />
												<?php

												$this->field_description( $args );

												$this->field_after();
	}

	public function multicheck_templ_callback( $args ) {
			$this->field_before( $args );

			$this->field_label( $args );

			$this->field_description( $args );
		?>

			<# var checked = data.<?php echo esc_attr( $args['templ_name'] ); ?> !== undefined && data.<?php echo esc_attr( $args['templ_name'] ); ?> && typeof data.<?php echo esc_attr( $args['templ_name'] ); ?> === 'object' ? data.<?php echo esc_attr( $args['templ_name'] ); ?> : {}; #>

			<?php

			if ( ! empty( $args['options'] ) ) {
				foreach ( $args['options'] as $option => $label ) {
					?>
					<# if (checked.<?php echo esc_attr( $option ); ?> === undefined) {
						checked.<?php echo esc_attr( $option ); ?> = false;
					} #>

					<input name="<?php echo esc_attr( $args['name'] ); ?>[<?php echo esc_attr( $option ); ?>]" id="<?php echo esc_attr( $args['id'] ); ?>_<?php echo esc_attr( $option ); ?>" type="checkbox" value="<?php echo esc_attr( $option ); ?>" {{pumChecked(checked.<?php echo esc_attr( $option ); ?>, '<?php echo esc_attr( $option ); ?>', true)}} />&nbsp;
					<label for="<?php echo esc_attr( $args['id'] ); ?>_<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $label ); ?></label><br/>
											<?php
				}
			}

			$this->field_after();
	}

	public function rangeslider_templ_callback( $args ) {
		$value = '{{data.' . $args['templ_name'] . '}}';
		$this->rangeslider_callback( $args, $value );
	}

	public function postselect_sanitize( $value = [], $args = [] ) {
		return $this->objectselect_sanitize( $value, $args );
	}

	public function objectselect_sanitize( $value = [], $args = [] ) {
		return wp_parse_id_list( $value );
	}

	public function taxonomyselect_sanitize( $value = [], $args = [] ) {
		return $this->objectselect_sanitize( $value, $args );
	}

	/**
	 * TODO: Finish adding the following field types for HTML & underscore.js
	 */

	/*
	 * Radio Callback
	 *
	 * Renders radio boxes.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 *
	* public function radio_callback( $args, $value ) {
		* if ( ! empty( $args['options'] ) ) {
	*
	* foreach ( $args['options'] as $key => $option ) {
				* $checked = false;
	*
	* $value = $this->get_option( $args['id'] );
	*
	* if ( $value == $key || ( ! $value && isset( $args['std'] ) && $args['std'] == $key ) ) {
					* $checked = true;
				* }
	*
	* echo '<input name="<?php echo esc_attr( $args['name'] ); ?>"" id="<?php echo esc_attr( $args['id'] ); ?>[<?php echo esc_attr( $key ); ?>]" type="radio" value="<?php echo esc_attr( $key ); ?>" ' . checked( true, $checked, false ) . '/>&nbsp;';
				* echo '<label for="<?php echo esc_attr( $args['id'] ); ?>[<?php echo esc_attr( $key ); ?>]">' . $option . '</label><br/>';
			* }
	*
	* echo '<p class="pum-desc">' . $args['desc'] . '</p>';
	*
	* }
	* }
	*
	*
	*
	*
	*
	*
	*
	* /**
	 * Color select Callback
	 *
	 * Renders color select fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 *
	* public function color_select_callback( $args ) {
	*
	* $value = $this->get_option( $args['id'] );
	*
	* if ( ! $value ) {
			* $value = isset( $args['std'] ) ? $args['std'] : '';
		* }
	*
	* $html = '<select id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>"/>';
	*
	* if ( ! empty( $args['options'] ) ) {
			* foreach ( $args['options'] as $option => $color ) {
				* $selected = selected( $option, $value, false );
				* $html .= '<option value="' . $option . '" ' . $selected . '>' . $color['label'] . '</option>';
			* }
		* }
	*
	* $html .= '</select>';
		* $html .= '<label for="<?php echo esc_attr( $args['id'] ); ?>"> ' . $args['desc'] . '</label>';
	*
	* echo $html;
	* }
	*
	* /**
	 * Rich Editor Callback
	 *
	 * Renders rich editor fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @global $wp_version WordPress Version
	 *
	* public function rich_editor_callback( $args ) {
		* global $wp_version;
		* $value = $this->get_option( $args['id'] );
	*
	* if ( ! $value ) {
			* $value = isset( $args['std'] ) ? $args['std'] : '';
		* }
	*
	* $rows = isset( $args['size'] ) ? $args['size'] : 20;
	*
	* if ( $wp_version >= 3.3 && function_exists( 'wp_editor' ) ) {
			* ob_start();
			* wp_editor( stripslashes( $value ), $this->options_key . '_' . $args['id'], array(
				* 'textarea_name' => '' . $this->options_key . '[' . $args['id'] . ']',
				* 'textarea_rows' => $rows
			* ) );
			* $html = ob_get_clean();
		* } else {
			* $html = '<textarea class="large-text" rows="10" id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
		* }
	*
	* $html .= '<br/><label for="<?php echo esc_attr( $args['id'] ); ?>"> ' . $args['desc'] . '</label>';
	*
	* echo $html;
	* }
	*
	* /**
	 * Upload Callback
	 *
	 * Renders upload fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 *
	* public function upload_callback( $args ) {
		* $value = $this->get_option( $args['id'] );
	*
	* if ( ! $value ) {
			* $value = isset( $args['std'] ) ? $args['std'] : '';
		* }
	*
	* $size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		* $html = '<input type="text" class="' . $size . '-text" id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" value="' . echo esc_attr( stripslashes( $value ) ) . '"/>';
		* $html .= '<span>&nbsp;<input type="button" class="' . $this->options_key . '_upload_button button-secondary" value="' . __( 'Upload File' ) . '"/></span>';
		* $html .= '<label for="<?php echo esc_attr( $args['id'] ); ?>"> ' . $args['desc'] . '</label>';
	*
	* echo $html;
	* }
	*
	* /**
	 * Color picker Callback
	 *
	 * Renders color picker fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 *
	* public function color_callback( $args ) {
		* $value = $this->get_option( $args['id'] );
	*
	* if ( ! $value ) {
			* $value = isset( $args['std'] ) ? $args['std'] : '';
		* }
	*
	* $default = isset( $args['std'] ) ? $args['std'] : '';
	*
	* $html = '<input type="text" class="di-color-picker" id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" value="' . echo esc_attr( $value ) . '" data-default-color="' . echo esc_attr( $default ) . '" />';
		* $html .= '<label for="<?php echo esc_attr( $args['id'] ); ?>"> ' . $args['desc'] . '</label>';
	*
	* echo $html;
	* }
	*
	* /**
	 * Descriptive text callback.
	 *
	 * Renders descriptive text onto the settings field.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 *
	* public function descriptive_text_callback( $args ) {
		* echo esc_html( $args['desc'] );
	* }
	*
	* /**
	 * Registers the license field callback for Software Licensing
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 *
	* public function license_key_callback( $args ) {
		* $value = $this->get_option( $args['id'] );
	*
	* if ( ! $value ) {
			* $value = isset( $args['std'] ) ? $args['std'] : '';
		* }
	*
	* $size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	*
	* $html = '<input type="' . ( $value == '' ? 'text' : 'password' ) . '" class="' . $size . '-text" id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" value="' . echo esc_attr( $value ) . '"/>';
	*
	* if ( 'valid' == get_option( $args['options']['is_valid_license_option'] ) ) {
			* $html .= '<input type="submit" class="button-secondary" name="' . $args['id'] . '_deactivate" value="' . __( 'Deactivate License' ) . '"/>';
		* }
		* $html .= '<label for="<?php echo esc_attr( $args['id'] ); ?>"> ' . $args['desc'] . '</label>';
	*
	* echo $html;
	* }
	 */
}
