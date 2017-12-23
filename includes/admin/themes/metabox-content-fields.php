<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('popmake_popup_theme_content_meta_box_fields', 'popmake_popup_theme_content_meta_box_field_description', 0);
function popmake_popup_theme_content_meta_box_field_description( $popup_theme_id ) { ?>
	</tbody></table><p><?php _e( 'Theme the content inside the popups.', 'popup-maker' ); ?></p><table class="form-table"><tbody><?php
}

add_action('popmake_popup_theme_content_meta_box_fields', 'popmake_popup_theme_content_meta_box_field_font', 10);
function popmake_popup_theme_content_meta_box_field_font( $popup_theme_id ) { ?>
	<tr>
		<th scope="row">
			<label for="popup_theme_content_font_color"><strong class="title"><?php _e( 'Font', 'popup-maker' );?></strong></label>
		</th>
		<td></td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_content_font_color"><?php _e( 'Color', 'popup-maker' );?></label>
		</th>
		<td>
			<input type="text" name="popup_theme_content_font_color" id="popup_theme_content_font_color" value="<?php esc_attr_e(popmake_get_popup_theme_content( $popup_theme_id, 'font_color' ))?>" class="pum-color-picker" />
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_theme_content_font_family"><?php _e( 'Family', 'popup-maker' );?></label>
		</th>
		<td>
			<select name="popup_theme_content_font_family" id="popup_theme_content_font_family" class="font-family">
			<?php foreach( apply_filters( 'popmake_font_family_options', array() ) as $option => $value ) : ?>
				<option value="<?php echo $value;?>"
					<?php echo $value == popmake_get_popup_theme_content( $popup_theme_id, 'font_family' ) ? ' selected="selected"' : '';?>
					<?php echo $value == '' ? ' class="bold"' : '';?>
				><?php echo $option;?></option>
			<?php endforeach ?>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row font-weight-only">
			<label for="popup_theme_content_font_weight"><?php _e( 'Weight', 'popup-maker' );?></label>
		</th>
		<td>
			<select name="popup_theme_content_font_weight" id="popup_theme_content_font_weight" class="font-weight">
			<?php foreach(apply_filters('popmake_font_weight_options', array()) as $option => $value) : ?>
				<option
					value="<?php echo $value;?>"
					<?php echo $value == popmake_get_popup_theme_content( $popup_theme_id, 'font_weight') ? ' selected="selected"' : '';?>
					<?php echo $value == '' ? ' class="bold"' : '';?>
				><?php echo $option;?></option>
			<?php endforeach ?>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row font-style-only">
			<label for="popup_theme_content_font_style"><?php _e( 'Style', 'popup-maker' );?></label>
		</th>
		<td>
			<select name="popup_theme_content_font_style" id="popup_theme_content_font_style" class="font-style">
			<?php foreach(apply_filters('popmake_font_style_options', array()) as $option => $value) : ?>
				<option
					value="<?php echo $value;?>"
					<?php echo $value == popmake_get_popup_theme_content( $popup_theme_id, 'font_style') ? ' selected="selected"' : '';?>
					<?php echo $value == '' ? ' class="bold"' : '';?>
				><?php echo $option;?></option>
			<?php endforeach ?>
			</select>
		</td>
	</tr><?php
}