<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'popmake_popup_theme_title_meta_box_fields', 'popmake_popup_theme_title_meta_box_field_description', 0 );
function popmake_popup_theme_title_meta_box_field_description( $popup_theme_id ) { ?>
</tbody></table><p><?php _e( 'Theme the title of the popups.', 'popup-maker' ); ?></p>
<table class="form-table">
	<tbody><?php
		}

		add_action( 'popmake_popup_theme_title_meta_box_fields', 'popmake_popup_theme_title_meta_box_field_font', 10 );
		function popmake_popup_theme_title_meta_box_field_font( $popup_theme_id ) {
			?>
			<tr class="title-divider">
			<th colspan="2"><h3 class="title"><?php _e( 'Font', 'popup-maker' ); ?></h3></th>
			</tr>
			<tr>
				<th scope="row">
					<label for="popup_theme_title_font_color"><?php _e( 'Color', 'popup-maker' ); ?></label>
				</th>
				<td>
					<input type="text" name="popup_theme_title_font_color" id="popup_theme_title_font_color" value="<?php esc_attr_e( popmake_get_popup_theme_title( $popup_theme_id, 'font_color' ) ) ?>" class="color-picker"/>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="popup_theme_title_font_size"><?php _e( 'Size', 'popup-maker' ); ?></label>
				</th>
				<td>
					<input type="text"
					       value="<?php esc_attr_e( popmake_get_popup_theme_title( $popup_theme_id, 'font_size' ) ) ?>"
					       name="popup_theme_title_font_size"
					       id="popup_theme_title_font_size"
					       class="popmake-range-manual"
					       step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_title_font_size', 1 ) ); ?>"
					       min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_title_font_size', 8 ) ); ?>"
					       max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_title_font_size', 32 ) ); ?>"
						/>
					<span class="range-value-unit regular-text">px</span>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="popup_theme_title_line_height"><?php _e( 'Line Height', 'popup-maker' ); ?></label>
				</th>
				<td>
					<input type="text"
					       value="<?php esc_attr_e( popmake_get_popup_theme_title( $popup_theme_id, 'line_height' ) ) ?>"
					       name="popup_theme_title_line_height"
					       id="popup_theme_title_line_height"
					       class="popmake-range-manual"
					       step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_title_line_height', 1 ) ); ?>"
					       min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_title_line_height', 8 ) ); ?>"
					       max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_title_line_height', 32 ) ); ?>"
						/>
					<span class="range-value-unit regular-text">px</span>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="popup_theme_title_font_family"><?php _e( 'Family', 'popup-maker' ); ?></label>
				</th>
				<td>
					<select name="popup_theme_title_font_family" id="popup_theme_title_font_family" class="font-family">
						<?php foreach ( apply_filters( 'popmake_font_family_options', array() ) as $option => $value ) : ?>
							<option
								value="<?php echo $value; ?>"
								<?php echo $value == popmake_get_popup_theme_title( $popup_theme_id, 'font_family' ) ? ' selected="selected"' : ''; ?>
								<?php echo $value == '' ? ' class="bold"' : ''; ?>
								><?php echo $option; ?></option>
						<?php endforeach ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row font-weight-only">
					<label for="popup_theme_title_font_weight"><?php _e( 'Weight', 'popup-maker' ); ?></label>
				</th>
				<td>
					<select name="popup_theme_title_font_weight" id="popup_theme_title_font_weight" class="font-weight">
						<?php foreach ( apply_filters( 'popmake_font_weight_options', array() ) as $option => $value ) : ?>
							<option
								value="<?php echo $value; ?>"
								<?php echo $value == popmake_get_popup_theme_title( $popup_theme_id, 'font_weight' ) ? ' selected="selected"' : ''; ?>
								><?php echo $option; ?></option>
						<?php endforeach ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row font-style-only">
					<label for="popup_theme_title_font_style"><?php _e( 'Style', 'popup-maker' ); ?></label>
				</th>
				<td>
					<select name="popup_theme_title_font_style" id="popup_theme_title_font_style" class="font-style">
						<?php foreach ( apply_filters( 'popmake_font_style_options', array() ) as $option => $value ) : ?>
							<option
								value="<?php echo $value; ?>"
								<?php echo $value == popmake_get_popup_theme_title( $popup_theme_id, 'font_style' ) ? ' selected="selected"' : ''; ?>
								><?php echo $option; ?></option>
						<?php endforeach ?>
					</select>
				</td>
			</tr>
			<tr>
			<th scope="row">
				<label for="popup_theme_title_text_align"><?php _e( 'Align', 'popup-maker' ); ?></label>
			</th>
			<td>
				<select name="popup_theme_title_text_align" id="popup_theme_title_text_align">
					<?php foreach ( apply_filters( 'popmake_text_align_options', array() ) as $option => $value ) : ?>
						<option
							value="<?php echo $value; ?>"
							<?php echo $value == popmake_get_popup_theme_title( $popup_theme_id, 'text_align' ) ? ' selected="selected"' : ''; ?>
							><?php echo $option; ?></option>
					<?php endforeach ?>
				</select>
			</td>
			</tr><?php
		}

		add_action( 'popmake_popup_theme_title_meta_box_fields', 'popmake_popup_theme_title_meta_box_field_textshadow', 20 );
		function popmake_popup_theme_title_meta_box_field_textshadow( $popup_theme_id )
		{
		?>
		<tr class="title-divider">
			<th colspan="2"><h3 class="title"><?php _e( 'Text Shadow', 'popup-maker' ); ?></h3></th>
		</tr>
		<tr>
			<th scope="row">
				<label for="popup_theme_title_textshadow_horizontal"><?php _e( 'Horizontal Position', 'popup-maker' ); ?></label>
			</th>
			<td>
				<input type="text"
				       value="<?php esc_attr_e( popmake_get_popup_theme_title( $popup_theme_id, 'textshadow_horizontal' ) ) ?>"
				       name="popup_theme_title_textshadow_horizontal"
				       id="popup_theme_title_textshadow_horizontal"
				       class="popmake-range-manual"
				       step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_title_textshadow_horizontal', 1 ) ); ?>"
				       min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_title_textshadow_horizontal', - 50 ) ); ?>"
				       max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_title_textshadow_horizontal', 50 ) ); ?>"
					/>
				<span class="range-value-unit regular-text">px</span>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="popup_theme_title_textshadow_vertical"><?php _e( 'Vertical Position', 'popup-maker' ); ?></label>
			</th>
			<td>
				<input type="text"
				       value="<?php esc_attr_e( popmake_get_popup_theme_title( $popup_theme_id, 'textshadow_vertical' ) ) ?>"
				       name="popup_theme_title_textshadow_vertical"
				       id="popup_theme_title_textshadow_vertical"
				       class="popmake-range-manual"
				       step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_title_textshadow_vertical', 1 ) ); ?>"
				       min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_title_textshadow_vertical', - 50 ) ); ?>"
				       max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_title_textshadow_vertical', 50 ) ); ?>"
					/>
				<span class="range-value-unit regular-text">px</span>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="popup_theme_title_textshadow_blur"><?php _e( 'Blur Radius', 'popup-maker' ); ?></label>
			</th>
			<td>
				<input type="text"
				       value="<?php esc_attr_e( popmake_get_popup_theme_title( $popup_theme_id, 'textshadow_blur' ) ) ?>"
				       name="popup_theme_title_textshadow_blur"
				       id="popup_theme_title_textshadow_blur"
				       class="popmake-range-manual"
				       step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_title_textshadow_blur', 1 ) ); ?>"
				       min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_title_textshadow_blur', 0 ) ); ?>"
				       max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_title_textshadow_blur', 100 ) ); ?>"
					/>
				<span class="range-value-unit regular-text">px</span>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="popup_theme_title_textshadow_color"><?php _e( 'Color', 'popup-maker' ); ?></label>
			</th>
			<td>
				<input type="text" name="popup_theme_title_textshadow_color" id="popup_theme_title_textshadow_color" value="<?php esc_attr_e( popmake_get_popup_theme_title( $popup_theme_id, 'textshadow_color' ) ) ?>" class="color-picker textshadow-color"/>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="popup_theme_title_textshadow_opacity"><?php _e( 'Opacity', 'popup-maker' ); ?></label>
			</th>
			<td>
				<input type="text"
				       value="<?php esc_attr_e( popmake_get_popup_theme_title( $popup_theme_id, 'textshadow_opacity' ) ) ?>"
				       name="popup_theme_title_textshadow_opacity"
				       id="popup_theme_title_textshadow_opacity"
				       class="popmake-range-manual"
				       step="1"
				       min="0"
				       max="100"
				       data-force-minmax=true
					/>
				<span class="range-value-unit regular-text">%</span>
			</td>
		</tr><?php
}