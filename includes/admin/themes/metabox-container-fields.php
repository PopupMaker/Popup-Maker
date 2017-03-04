<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('popmake_popup_theme_container_meta_box_fields', 'popmake_popup_theme_container_meta_box_field_description', 0);
function popmake_popup_theme_container_meta_box_field_description( $popup_theme_id ) { ?>
	</tbody></table><p><?php _e( 'Theme the container inside the popups.', 'popup-maker' ); ?></p><table class="form-table"><tbody><?php
}

add_action('popmake_popup_theme_container_meta_box_fields', 'popmake_popup_theme_container_meta_box_field_padding', 10);
function popmake_popup_theme_container_meta_box_field_padding( $popup_theme_id ) { ?>
	<tr>
		<th scope="row">
			<label for="popup_theme_container_padding"><?php _e( 'Padding', 'popup-maker' );?></label> 
		</th>
		<td>
			<input type="text"
				value="<?php esc_attr_e(popmake_get_popup_theme_container( $popup_theme_id, 'padding' ))?>"
				name="popup_theme_container_padding"
				id="popup_theme_container_padding"
				class="popmake-range-manual"
				step="<?php esc_html_e(apply_filters('popmake_popup_theme_step_container_padding', 1));?>"
				min="<?php esc_html_e(apply_filters('popmake_popup_theme_min_container_padding', 0));?>"
				max="<?php esc_html_e(apply_filters('popmake_popup_theme_max_container_padding', 100));?>"
			/>
			<span class="range-value-unit regular-text">px</span>
		</td>
	</tr><?php
}


add_action('popmake_popup_theme_container_meta_box_fields', 'popmake_popup_theme_container_meta_box_field_background', 20);
function popmake_popup_theme_container_meta_box_field_background( $popup_theme_id ) { ?>
	<tr class="title-divider">
		<th colspan="2">
			<h3 class="title"><?php _e( 'Background', 'popup-maker' );?></h3></th>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_container_background_color"><?php _e( 'Color', 'popup-maker' );?></label>
		</th>
		<td>
			<input type="text" name="popup_theme_container_background_color" id="popup_theme_container_background_color" value="<?php esc_attr_e(popmake_get_popup_theme_container( $popup_theme_id, 'background_color' ))?>" class="color-picker background-color" />
		</td>
	</tr>
	<tr class="background-opacity">
		<th scope="row">
			<label for="popup_theme_container_background_opacity"><?php _e( 'Opacity', 'popup-maker' );?></label> 
		</th>
		<td>
			<input type="text"
				value="<?php esc_attr_e(popmake_get_popup_theme_container( $popup_theme_id, 'background_opacity' ))?>"
				name="popup_theme_container_background_opacity"
				id="popup_theme_container_background_opacity"
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

add_action('popmake_popup_theme_container_meta_box_fields', 'popmake_popup_theme_container_meta_box_field_atb_extension_promotion', 30);
function popmake_popup_theme_container_meta_box_field_atb_extension_promotion( $popup_theme_id ) { ?>
	<tr>
		<th colspan="2" class="popmake-upgrade-tip">
			<img style="" src="<?php echo POPMAKE_URL;?>/assets/images/upsell-icon-advanted-theme-builder.png"/> <?php _e( 'Want to use background images?', 'popup-maker' ); ?> <a href="https://wppopupmaker.com/extensions/advanced-theme-builder/?utm_source=plugin-theme-editor&utm_medium=text-link&utm_campaign=Upsell&utm_content=container-settings" target="_blank"><?php _e( 'Check out Advanced Theme Builder!', 'popup-maker' ); ?></a>.
		</th>
	</tr><?php
}

add_action('popmake_popup_theme_container_meta_box_fields', 'popmake_popup_theme_container_meta_box_field_border', 40);
function popmake_popup_theme_container_meta_box_field_border( $popup_theme_id ) { ?>
	<tr class="title-divider">
		<th colspan="2"><h3 class="title"><?php _e( 'Border', 'popup-maker' );?></h3></th>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_container_border_radius"><?php _e( 'Radius', 'popup-maker' );?></label> 
		</th>
		<td>
			<input type="text"
				value="<?php esc_attr_e(popmake_get_popup_theme_container( $popup_theme_id, 'border_radius' ))?>"
				name="popup_theme_container_border_radius"
				id="popup_theme_container_border_radius"
				class="popmake-range-manual"
				step="<?php esc_html_e(apply_filters('popmake_popup_theme_step_container_border_radius', 1));?>"
				min="<?php esc_html_e(apply_filters('popmake_popup_theme_min_container_border_radius', 0));?>"
				max="<?php esc_html_e(apply_filters('popmake_popup_theme_max_container_border_radius', 80));?>"
			/>
			<span class="range-value-unit regular-text">px</span>
			<p class="description"><?php _e('Choose a corner radius for your container button.', 'popup-maker' )?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_container_border_style"><?php _e( 'Style', 'popup-maker' );?></label>
		</th>
		<td>
			<select name="popup_theme_container_border_style" id="popup_theme_container_border_style" class="border-style">
			<?php foreach(apply_filters('popmake_border_style_options', array()) as $option => $value) : ?>
				<option
					value="<?php echo $value;?>"
					<?php echo $value == popmake_get_popup_theme_container( $popup_theme_id, 'border_style') ? ' selected="selected"' : '';?>
				><?php echo $option;?></option>
			<?php endforeach ?>
			</select>
			<p class="description"><?php _e( 'Choose a border style for your container button.', 'popup-maker' )?></p>
		</td>
	</tr>
	<tr class="border-options">
		<th scope="row">
			<label for="popup_theme_container_border_color"><?php _e( 'Color', 'popup-maker' );?></label>
		</th>
		<td>
			<input type="text" name="popup_theme_container_border_color" id="popup_theme_container_border_color" value="<?php esc_attr_e(popmake_get_popup_theme_container( $popup_theme_id, 'border_color'))?>" class="color-picker" />
		</td>
	</tr>
	<tr class="border-options">
		<th scope="row">
			<label for="popup_theme_container_border_width"><?php _e( 'Thickness', 'popup-maker' );?></label> 
		</th>
		<td>
			<input type="text"
				value="<?php esc_attr_e(popmake_get_popup_theme_container( $popup_theme_id, 'border_width' ))?>"
				name="popup_theme_container_border_width"
				id="popup_theme_container_border_width"
				class="popmake-range-manual"
				step="<?php esc_html_e(apply_filters('popmake_popup_theme_step_container_border_width', 1));?>"
				min="<?php esc_html_e(apply_filters('popmake_popup_theme_min_container_border_width', 0));?>"
				max="<?php esc_html_e(apply_filters('popmake_popup_theme_max_container_border_width', 5));?>"
			/>
			<span class="range-value-unit regular-text">px</span>
		</td>
	</tr><?php
}

add_action('popmake_popup_theme_container_meta_box_fields', 'popmake_popup_theme_container_meta_box_field_boxshadow', 50);
function popmake_popup_theme_container_meta_box_field_boxshadow( $popup_theme_id ) { ?>
	<tr class="title-divider">
		<th colspan="2"><h3 class="title"><?php _e( 'Drop Shadow', 'popup-maker' );?></h3></th>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_container_boxshadow_inset"><?php _e( 'Inset', 'popup-maker' );?></label>
		</th>
		<td>
			<select name="popup_theme_container_boxshadow_inset" id="popup_theme_container_boxshadow_inset">
			<?php foreach(array(
				__('No', 'popup-maker' ) => 'no',
				__('Yes', 'popup-maker' ) => 'yes'
			) as $option => $value) : ?>
				<option
					value="<?php echo $value;?>"
					<?php echo $value == popmake_get_popup_theme_container( $popup_theme_id, 'boxshadow_inset') ? ' selected="selected"' : '';?>
				><?php echo $option;?></option>
			<?php endforeach ?>
			</select>
			<p class="description"><?php _e( 'Set the box shadow to inset (inner shadow).', 'popup-maker' )?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_container_boxshadow_horizontal"><?php _e( 'Horizontal Position', 'popup-maker' );?></label> 
		</th>
		<td>
			<input type="text"
				value="<?php esc_attr_e(popmake_get_popup_theme_container( $popup_theme_id, 'boxshadow_horizontal' ))?>"
				name="popup_theme_container_boxshadow_horizontal"
				id="popup_theme_container_boxshadow_horizontal"
				class="popmake-range-manual"
				step="<?php esc_html_e(apply_filters('popmake_popup_theme_step_container_boxshadow_horizontal', 1));?>"
				min="<?php esc_html_e(apply_filters('popmake_popup_theme_min_container_boxshadow_horizontal', -50));?>"
				max="<?php esc_html_e(apply_filters('popmake_popup_theme_max_container_boxshadow_horizontal', 50));?>"
			/>
			<span class="range-value-unit regular-text">px</span>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_container_boxshadow_vertical"><?php _e( 'Vertical Position', 'popup-maker' );?></label> 
		</th>
		<td>
			<input type="text"
				value="<?php esc_attr_e(popmake_get_popup_theme_container( $popup_theme_id, 'boxshadow_vertical' ))?>"
				name="popup_theme_container_boxshadow_vertical"
				id="popup_theme_container_boxshadow_vertical"
				class="popmake-range-manual"
				step="<?php esc_html_e(apply_filters('popmake_popup_theme_step_container_boxshadow_vertical', 1));?>"
				min="<?php esc_html_e(apply_filters('popmake_popup_theme_min_container_boxshadow_vertical', -50));?>"
				max="<?php esc_html_e(apply_filters('popmake_popup_theme_max_container_boxshadow_vertical', 50));?>"
			/>
			<span class="range-value-unit regular-text">px</span>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_container_boxshadow_blur"><?php _e( 'Blur Radius', 'popup-maker' );?></label> 
		</th>
		<td>
			<input type="text"
				value="<?php esc_attr_e( popmake_get_popup_theme_container( $popup_theme_id, 'boxshadow_blur' ) ); ?>"
				name="popup_theme_container_boxshadow_blur"
				id="popup_theme_container_boxshadow_blur"
				class="popmake-range-manual"
				step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_container_boxshadow_blur', 1 ) );?>"
				min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_container_boxshadow_blur', 0 ) );?>"
				max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_container_boxshadow_blur', 100 ) );?>"
			/>
			<span class="range-value-unit regular-text">px</span>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_container_boxshadow_spread"><?php _e( 'Spread', 'popup-maker' );?></label> 
		</th>
		<td>
			<input type="text"
				value="<?php esc_attr_e( popmake_get_popup_theme_container( $popup_theme_id, 'boxshadow_spread' ) ); ?>"
				name="popup_theme_container_boxshadow_spread"
				id="popup_theme_container_boxshadow_spread"
				class="popmake-range-manual"
				step="<?php esc_html_e( apply_filters( 'popmake_popup_theme_step_container_boxshadow_spread', 1 ) );?>"
				min="<?php esc_html_e( apply_filters( 'popmake_popup_theme_min_container_boxshadow_spread', -100 ) );?>"
				max="<?php esc_html_e( apply_filters( 'popmake_popup_theme_max_container_boxshadow_spread', 100 ) );?>"
			/>
			<span class="range-value-unit regular-text">px</span>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_container_boxshadow_color"><?php _e( 'Color', 'popup-maker' );?></label>
		</th>
		<td>
			<input type="text" name="popup_theme_container_boxshadow_color" id="popup_theme_container_boxshadow_color" value="<?php esc_attr_e(popmake_get_popup_theme_container( $popup_theme_id, 'boxshadow_color'))?>" class="color-picker boxshadow-color" />
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_container_boxshadow_opacity"><?php _e( 'Opacity', 'popup-maker' );?></label> 
		</th>
		<td>
			<input type="text"
				value="<?php esc_attr_e( popmake_get_popup_theme_container( $popup_theme_id, 'boxshadow_opacity' ) ); ?>"
				name="popup_theme_container_boxshadow_opacity"
				id="popup_theme_container_boxshadow_opacity"
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
