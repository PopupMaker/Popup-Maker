<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'popmake_popup_theme_close_meta_box_fields', 'popmake_popup_theme_close_meta_box_field_description', 0 );
function popmake_popup_theme_close_meta_box_field_description( $popup_theme_id ) { ?>
	</tbody></table>
	<p><?php _e( 'Theme the close button for the popups.', 'popup-maker' ); ?></p><table class="form-table"><tbody><?php
}

add_action( 'popmake_popup_theme_close_meta_box_fields', 'popmake_popup_theme_close_meta_box_field_text', 10 );
function popmake_popup_theme_close_meta_box_field_text( $popup_theme_id ) { ?>
	<tr>
	<th scope="row">
		<label for="popup_theme_close_text"><?php _e( 'Text', 'popup-maker' ); ?></label>
	</th>
	<td>
		<input type="text" name="popup_theme_close_text" id="popup_theme_close_text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'text' ) ) ?>" />
		<p class="description"><?php _e( 'Enter the close button text.', 'popup-maker' ) ?></p>
	</td>
	</tr><?php
}

add_action( 'popmake_popup_theme_close_meta_box_fields', 'popmake_popup_theme_close_meta_box_field_fi_extension_promotion', 10 );
function popmake_popup_theme_close_meta_box_field_fi_extension_promotion( $popup_theme_id ) {
	if ( ! class_exists( 'Popup_Maker_Forced_Interaction' ) && ! class_exists( 'PUM_Forced_Interaction' ) ) :
		?>
		<tr>
		<th colspan="2" class="popmake-upgrade-tip">
			<img style="" src="<?php echo POPMAKE_URL; ?>/assets/images/upsell-icon-forced-interaction.png" />
			<?php printf(
				_x( 'Want to disable the close button? Check out %sForced Interaction%s!', '%s represent the opening & closing link html', 'popup-maker' ),
				'<a href="https://wppopupmaker.com/extensions/forced-interaction/?utm_source=plugin-theme-editor&utm_medium=text-link&utm_campaign=Upsell&utm_content=close-button-settings" target="_blank">',
				'</a>'
			); ?>
		</th>
		</tr><?php
	endif;
}

add_action( 'popmake_popup_theme_close_meta_box_fields', 'popmake_popup_theme_close_meta_box_field_padding', 20 );
function popmake_popup_theme_close_meta_box_field_padding( $popup_theme_id ) { ?>
	<tr>
	<th scope="row">
		<label for="popup_theme_close_padding"><?php _e( 'Padding', 'popup-maker' ); ?></label>
	</th>
	<td>
		<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'padding' ) ) ?>" name="popup_theme_close_padding" id="popup_theme_close_padding" class="popmake-range-manual" step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_close_padding', 1 ) ); ?>" min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_close_padding', 0 ) ); ?>" max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_close_padding', 100 ) ); ?>" />
		<span class="range-value-unit regular-text">px</span>
	</td>
	</tr><?php
}


add_action( 'popmake_popup_theme_close_meta_box_fields', 'popmake_popup_theme_close_meta_box_field_size', 30 );
function popmake_popup_theme_close_meta_box_field_size( $popup_theme_id ) { ?>
	<tr>
		<th scope="row">
			<label for="popup_theme_close_height"><?php _e( 'Height', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'height' ) ) ?>" name="popup_theme_close_height" id="popup_theme_close_height" class="popmake-range-manual" step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_close_height', 1 ) ); ?>" min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_close_height', 0 ) ); ?>" max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_close_height', 100 ) ); ?>" />
			<span class="range-value-unit regular-text">px</span>
		</td>
	</tr>
	<tr>
	<th scope="row">
		<label for="popup_theme_close_width"><?php _e( 'Width', 'popup-maker' ); ?></label>
	</th>
	<td>
		<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'width' ) ) ?>" name="popup_theme_close_width" id="popup_theme_close_width" class="popmake-range-manual" step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_close_width', 1 ) ); ?>" min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_close_width', 0 ) ); ?>" max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_close_width', 100 ) ); ?>" />
		<span class="range-value-unit regular-text">px</span>
	</td>
	</tr><?php
}


add_action( 'popmake_popup_theme_close_meta_box_fields', 'popmake_popup_theme_close_meta_box_field_location', 40 );
function popmake_popup_theme_close_meta_box_field_location( $popup_theme_id ) { ?>
	<tr>
		<th scope="row">
			<label for="popup_theme_close_location"><?php _e( 'Location', 'popup-maker' ); ?></label>
		</th>
		<td>
			<select name="popup_theme_close_location" id="popup_theme_close_location">
				<?php foreach ( apply_filters( 'popmake_theme_close_location_options', array() ) as $option => $value ) : ?>
					<option value="<?php echo $value; ?>"
						<?php echo $value == popmake_get_popup_theme_close( $popup_theme_id, 'location' ) ? ' selected="selected"' : ''; ?>
					><?php echo $option; ?></option>
				<?php endforeach ?>
			</select>
			<p class="description"><?php _e( 'Choose which corner the close button will be positioned.', 'popup-maker' ) ?></p>
		</td>
	</tr>
	<tr class="title-divider">
		<th colspan="2"><h3 class="title"><?php _e( 'Position', 'popup-maker' ); ?></h3></th>
	</tr>
	<tr class="topright topleft">
		<th scope="row">
			<label for="popup_theme_close_position_top"><?php _e( 'Top', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'position_top' ) ) ?>" name="popup_theme_close_position_top" id="popup_theme_close_position_top" class="popmake-range-manual" step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_close_position_offset', 1 ) ); ?>" min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_close_position_offset', - 100 ) ); ?>" max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_close_position_offset', 100 ) ); ?>" />
			<span class="range-value-unit regular-text">px</span>
		</td>
	</tr>
	<tr class="topleft bottomleft">
		<th scope="row">
			<label for="popup_theme_close_position_left"><?php _e( 'Left', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'position_left' ) ) ?>" name="popup_theme_close_position_left" id="popup_theme_close_position_left" class="popmake-range-manual" step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_close_position_offset', 1 ) ); ?>" min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_close_position_offset', - 100 ) ); ?>" max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_close_position_offset', 100 ) ); ?>" />
			<span class="range-value-unit regular-text">px</span>
		</td>
	</tr>
	<tr class="bottomleft bottomright">
		<th scope="row">
			<label for="popup_theme_close_position_bottom"><?php _e( 'Bottom', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'position_bottom' ) ) ?>" name="popup_theme_close_position_bottom" id="popup_theme_close_position_bottom" class="popmake-range-manual" step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_close_position_offset', 1 ) ); ?>" min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_close_position_offset', - 100 ) ); ?>" max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_close_position_offset', 100 ) ); ?>" />
			<span class="range-value-unit regular-text">px</span>
		</td>
	</tr>
	<tr class="topright bottomright">
	<th scope="row">
		<label for="popup_theme_close_position_right"><?php _e( 'Right', 'popup-maker' ); ?></label>
	</th>
	<td>
		<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'position_right' ) ) ?>" name="popup_theme_close_position_right" id="popup_theme_close_position_right" class="popmake-range-manual" step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_close_position_offset', 1 ) ); ?>" min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_close_position_offset', - 100 ) ); ?>" max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_close_position_offset', 100 ) ); ?>" />
		<span class="range-value-unit regular-text">px</span>
	</td>
	</tr><?php
}


add_action( 'popmake_popup_theme_close_meta_box_fields', 'popmake_popup_theme_close_meta_box_field_font', 50 );
function popmake_popup_theme_close_meta_box_field_font( $popup_theme_id ) { ?>
	<tr class="title-divider">
		<th colspan="2"><h3 class="title"><?php _e( 'Font', 'popup-maker' ); ?></h3></th>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_close_font_color"><?php _e( 'Color', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" name="popup_theme_close_font_color" id="popup_theme_close_font_color" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'font_color' ) ) ?>" class="color-picker" />
		</td>
	</tr>

	<tr>
		<th scope="row">
			<label for="popup_theme_close_font_size"><?php _e( 'Size', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'font_size' ) ) ?>" name="popup_theme_close_font_size" id="popup_theme_close_font_size" class="popmake-range-manual" step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_close_font_size', 1 ) ); ?>" min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_close_font_size', 8 ) ); ?>" max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_close_font_size', 32 ) ); ?>" />
			<span class="range-value-unit regular-text">px</span>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_close_line_height"><?php _e( 'Line Height', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'line_height' ) ) ?>" name="popup_theme_close_line_height" id="popup_theme_close_line_height" class="popmake-range-manual" step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_close_line_height', 1 ) ); ?>" min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_close_line_height', 8 ) ); ?>" max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_close_line_height', 32 ) ); ?>" />
			<span class="range-value-unit regular-text">px</span>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_close_font_family"><?php _e( 'Family', 'popup-maker' ); ?></label>
		</th>
		<td>
			<select name="popup_theme_close_font_family" id="popup_theme_close_font_family" class="font-family">
				<?php foreach ( apply_filters( 'popmake_font_family_options', array() ) as $option => $value ) : ?>
					<option value="<?php echo $value; ?>"
						<?php echo $value == popmake_get_popup_theme_close( $popup_theme_id, 'font_family' ) ? ' selected="selected"' : ''; ?>
						<?php echo $value == '' ? ' class="bold"' : ''; ?>
					><?php echo $option; ?></option>
				<?php endforeach ?>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_close_font_weight"><?php _e( 'Weight', 'popup-maker' ); ?></label>
		</th>
		<td>
			<select name="popup_theme_close_font_weight" id="popup_theme_close_font_weight" class="font-weight">
				<?php foreach ( apply_filters( 'popmake_font_weight_options', array() ) as $option => $value ) : ?>
					<option value="<?php echo $value; ?>"
						<?php echo $value == popmake_get_popup_theme_close( $popup_theme_id, 'font_weight' ) ? ' selected="selected"' : ''; ?>
						<?php echo $value == '' ? ' class="bold"' : ''; ?>
					><?php echo $option; ?></option>
				<?php endforeach ?>
			</select>
		</td>
	</tr>
	<tr>
	<th scope="row font-style-only">
		<label for="popup_theme_close_font_style"><?php _e( 'Style', 'popup-maker' ); ?></label>
	</th>
	<td>
		<select name="popup_theme_close_font_style" id="popup_theme_close_font_style" class="font-style">
			<?php foreach ( apply_filters( 'popmake_font_style_options', array() ) as $option => $value ) : ?>
				<option value="<?php echo $value; ?>"
					<?php echo $value == popmake_get_popup_theme_close( $popup_theme_id, 'font_style' ) ? ' selected="selected"' : ''; ?>
					<?php echo $value == '' ? ' class="bold"' : ''; ?>
				><?php echo $option; ?></option>
			<?php endforeach ?>
		</select>
	</td>
	</tr><?php
}


add_action( 'popmake_popup_theme_close_meta_box_fields', 'popmake_popup_theme_close_meta_box_field_background', 60 );
function popmake_popup_theme_close_meta_box_field_background( $popup_theme_id ) { ?>
	<tr class="title-divider">
		<th colspan="2"><h3 class="title"><?php _e( 'Background', 'popup-maker' ); ?></ h3></th>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_close_background_color"><?php _e( 'Color', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" name="popup_theme_close_background_color" id="popup_theme_close_background_color" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'background_color' ) ) ?>" class="color-picker background-color" />
		</td>
	</tr>
	<tr class="background-opacity">
	<th scope="row">
		<label for="popup_theme_close_background_opacity"><?php _e( 'Opacity', 'popup-maker' ); ?></label>
	</th>
	<td>
		<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'background_opacity' ) ) ?>" name="popup_theme_close_background_opacity" id="popup_theme_close_background_opacity" class="popmake-range-manual" step="1" min="0" max="100" data-force-minmax=true />
		<span class="range-value-unit regular-text">%</span>
	</td>
	</tr><?php
}


add_action( 'popmake_popup_theme_close_meta_box_fields', 'popmake_popup_theme_close_meta_box_field_atb_extension_promotion', 70 );
function popmake_popup_theme_close_meta_box_field_atb_extension_promotion( $popup_theme_id ) { ?>
	<tr>
	<th colspan="2" class="popmake-upgrade-tip">
		<img style="" src="<?php echo POPMAKE_URL; ?>/assets/images/upsell-icon-advanted-theme-builder.png" /> <?php _e( 'Want to use background images?', 'popup-maker' ); ?>
		<a href="https://wppopupmaker.com/extensions/advanced-theme-builder/?utm_source=plugin-theme-editor&utm_medium=text-link&utm_campaign=Upsell&utm_content=close-button-settings" target="_blank"><?php _e( 'Check out Advanced Theme Builder!', 'popup-maker' ); ?></a>.
	</th>
	</tr><?php
}

add_action('popmake_popup_theme_close_meta_box_fields', 'popmake_popup_theme_close_meta_box_field_border', 80);
function popmake_popup_theme_close_meta_box_field_border( $popup_theme_id ) { ?>
	<tr class="title-divider">
		<th colspan="2">

			<h3 class="title"><?php _e( 'Border', 'popup-maker' ); ?></h3>
			<p
		</th>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_close_border_radius"><?php _e( 'Radius', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'border_radius' ) ) ?>" name="popup_theme_close_border_radius" id="popup_theme_close_border_radius" class="popmake-range-manual" step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_close_border_radius', 1 ) ); ?>" min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_close_border_radius', 0 ) ); ?>" max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_close_border_radius', 28 ) ); ?>" />
			<span class="range-value-unit regular-text">px</span>
			<p class="description"><?php _e( 'Choose a corner radius for your close button.', POPMAKE_SLUG ) ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_close_border_style"><?php _e( 'Style', 'popup-maker' ); ?></label>
		</th>
		<td>
			<select name="popup_theme_close_border_style" id="popup_theme_close_border_style" class="border-style">
				<?php foreach ( apply_filters( 'popmake_border_style_options', array() ) as $option => $value ) : ?>
					<option value="<?php echo $value; ?>"
						<?php echo $value == popmake_get_popup_theme_close( $popup_theme_id, 'border_style' ) ? ' selected="selected"' : ''; ?>
					><?php echo $option; ?></option>
				<?php endforeach ?>
			</select>
			<p class="description"><?php _e( 'Choose a border style for your close button.', 'popup-maker' ) ?></p>
		</td>
	</tr>
	<tr class="border-options">
		<th scope="row">
			<label for="popup_theme_close_border_color"><?php _e( 'Color', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" name="popup_theme_close_border_color" id="popup_theme_close_border_color" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'border_color' ) ) ?>" class="color-picker" />
		</td>
	</tr>
	<tr class="border-options">
	<th scope="row">
		<label for="popup_theme_close_border_width"><?php _e( 'Thickness', 'popup-maker' ); ?></label>
	</th>
	<td>
		<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'border_width' ) ) ?>" name="popup_theme_close_border_width" id="popup_theme_close_border_width" class="popmake-range-manual" step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_close_border_width', 1 ) ); ?>" min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_close_border_width', 0 ) ); ?>" max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_close_border_width', 10 ) ); ?>" />
		<span class="range-value-unit regular-text">px</span>
	</td>
	</tr><?php
}


add_action( 'popmake_popup_theme_close_meta_box_fields', 'popmake_popup_theme_close_meta_box_field_boxshadow', 90 );
function popmake_popup_theme_close_meta_box_field_boxshadow( $popup_theme_id ) { ?>
	<tr class="title-divider">
		<th colspan="2"><h3 class="title"><?php _e( 'Drop Shadow', 'popup-maker' ); ?></h3></th>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_close_boxshadow_inset"><?php _e( 'Inset', 'popup-maker' ); ?></label>
		</th>
		<td>
			<select name="popup_theme_close_boxshadow_inset" id="popup_theme_close_boxshadow_inset">
				<?php foreach (
					array(
						__( 'No', 'popup-maker' )  => 'no',
						__( 'Yes', 'popup-maker' ) => 'yes',
					) as $option => $value
				) : ?>
					<option value="<?php echo $value; ?>"
						<?php echo $value == popmake_get_popup_theme_close( $popup_theme_id, 'boxshadow_inset' ) ? ' selected="selected"' : ''; ?>
					><?php echo $option; ?></option>
				<?php endforeach ?>
			</select>
			<p class="description"><?php _e( 'Set the box shadow to inset (inner shadow).', 'popup-maker' ) ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_close_boxshadow_horizontal"><?php _e( 'Horizontal Position', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'boxshadow_horizontal' ) ) ?>" name="popup_theme_close_boxshadow_horizontal" id="popup_theme_close_boxshadow_horizontal" class="popmake-range-manual" step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_close_boxshadow_horizontal', 1 ) ); ?>" min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_close_boxshadow_horizontal', - 50 ) ); ?>" max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_close_boxshadow_horizontal', 50 ) ); ?>" />
			<span class="range-value-unit regular-text">px</span>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_close_boxshadow_vertical"><?php _e( 'Vertical Position', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'boxshadow_vertical' ) ) ?>" name="popup_theme_close_boxshadow_vertical" id="popup_theme_close_boxshadow_vertical" class="popmake-range-manual" step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_close_boxshadow_vertical', 1 ) ); ?>" min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_close_boxshadow_vertical', - 50 ) ); ?>" max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_close_boxshadow_vertical', 50 ) ); ?>" />
			<span class="range-value-unit regular-text">px</span>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_close_boxshadow_blur"><?php _e( 'Blur Radius', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'boxshadow_blur' ) ) ?>" name="popup_theme_close_boxshadow_blur" id="popup_theme_close_boxshadow_blur" class="popmake-range-manual" step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_close_boxshadow_blur', 1 ) ); ?>" min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_close_boxshadow_blur', 0 ) ); ?>" max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_close_boxshadow_blur', 100 ) ); ?>" />
			<span class="range-value-unit regular-text">px</span>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_close_boxshadow_spread"><?php _e( 'Spread', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'boxshadow_spread' ) ) ?>" name="popup_theme_close_boxshadow_spread" id="popup_theme_close_boxshadow_spread" class="popmake-range-manual" step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_close_boxshadow_spread', 1 ) ); ?>" min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_close_boxshadow_spread', 0 ) ); ?>" max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_close_boxshadow_spread', 100 ) ); ?>" />
			<span class="range-value-unit regular-text">px</span>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_close_boxshadow_color"><?php _e( 'Color', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" name="popup_theme_close_boxshadow_color" id="popup_theme_close_boxshadow_color" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'boxshadow_color' ) ) ?>" class="color-picker boxshadow-color" />
		</td>
	</tr>
	<tr>
	<th scope="row">
		<label for="popup_theme_close_boxshadow_opacity"><?php _e( 'Opacity', 'popup-maker' ); ?></label>
	</th>
	<td>
		<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'boxshadow_opacity' ) ) ?>" name="popup_theme_close_boxshadow_opacity" id="popup_theme_close_boxshadow_opacity" class="popmake-range-manual" step="1" min="0" max="100" data-force-minmax=true />
		<span class="range-value-unit regular-text">%</span>
	</td>
	</tr><?php
}

add_action( 'popmake_popup_theme_close_meta_box_fields', 'popmake_popup_theme_close_meta_box_field_textshadow', 100 );
function popmake_popup_theme_close_meta_box_field_textshadow( $popup_theme_id ) { ?>
	<tr class="title-divider">
		<th colspan="2"><h3 class="title"><?php _e( 'Text Shadow', 'popup-maker' ); ?></h3></th>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_close_textshadow_horizontal"><?php _e( 'Horizontal Position', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'textshadow_horizontal' ) ) ?>" name="popup_theme_close_textshadow_horizontal" id="popup_theme_close_textshadow_horizontal" class="popmake-range-manual" step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_close_textshadow_horizontal', 1 ) ); ?>" min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_close_textshadow_horizontal', - 50 ) ); ?>" max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_close_textshadow_horizontal', 50 ) ); ?>" />
			<span class="range-value-unit regular-text">px</span>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_close_textshadow_vertical"><?php _e( 'Vertical Position', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'textshadow_vertical' ) ) ?>" name="popup_theme_close_textshadow_vertical" id="popup_theme_close_textshadow_vertical" class="popmake-range-manual" step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_close_textshadow_vertical', 1 ) ); ?>" min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_close_textshadow_vertical', - 50 ) ); ?>" max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_close_textshadow_vertical', 50 ) ); ?>" />
			<span class="range-value-unit regular-text">px</span>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_close_textshadow_blur"><?php _e( 'Blur Radius', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'textshadow_blur' ) ) ?>" name="popup_theme_close_textshadow_blur" id="popup_theme_close_textshadow_blur" class="popmake-range-manual" step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_close_textshadow_blur', 1 ) ); ?>" min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_close_textshadow_blur', 0 ) ); ?>" max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_close_textshadow_blur', 100 ) ); ?>" />
			<span class="range-value-unit regular-text">px</span>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_close_textshadow_color"><?php _e( 'Color', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" name="popup_theme_close_textshadow_color" id="popup_theme_close_textshadow_color" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'textshadow_color' ) ) ?>" class="color-picker textshadow-color" />
		</td>
	</tr>
	<tr>
	<th scope="row">
		<label for="popup_theme_close_textshadow_opacity"><?php _e( 'Opacity', 'popup-maker' ); ?></label>
	</th>
	<td>
		<input type="text" value="<?php esc_attr_e( popmake_get_popup_theme_close( $popup_theme_id, 'textshadow_opacity' ) ) ?>" name="popup_theme_close_textshadow_opacity" id="popup_theme_close_textshadow_opacity" class="popmake-range-manual" step="1" min="0" max="100" data-force-minmax=true />
		<span class="range-value-unit regular-text">%</span>
	</td>
	</tr><?php
}