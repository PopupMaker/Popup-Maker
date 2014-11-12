<?php
/**
 * Renders popup display fields
 * @since 1.0
 * @param $post_id
 */

add_action('popmake_popup_close_meta_box_fields', 'popmake_popup_close_meta_box_field_overlay_click', 10);
function popmake_popup_close_meta_box_field_overlay_click( $popup_id ) {
	?><tr>
		<th scope="row"><?php _e('Click Overlay to Close', 'popup-maker' );?></th>
		<td>
			<input type="checkbox" value="true" name="popup_close_overlay_click" id="popup_close_overlay_click" <?php echo popmake_get_popup_close( $popup_id, 'overlay_click' ) ? 'checked="checked" ' : '';?>/>
			<label for="popup_close_overlay_click" class="description"><?php _e('Checking this will cause popup to close when user clicks on overlay.', 'popup-maker' );?></label>
		</td>
	</tr><?php
}


add_action('popmake_popup_close_meta_box_fields', 'popmake_popup_close_meta_box_field_esc_press', 20);
function popmake_popup_close_meta_box_field_esc_press( $popup_id ) {
	?><tr>
		<th scope="row"><?php _e('Press ESC to Close', 'popup-maker' );?></th>
		<td>
			<input type="checkbox" value="true" name="popup_close_esc_press" id="popup_close_esc_press" <?php echo popmake_get_popup_close( $popup_id, 'esc_press' ) ? 'checked="checked" ' : '';?>/>
			<label for="popup_close_esc_press" class="description"><?php _e('Checking this will cause popup to close when user presses ESC key.', 'popup-maker' );?></label>
		</td>
	</tr><?php
}


add_action('popmake_popup_close_meta_box_fields', 'popmake_popup_close_meta_box_field_f4_press', 30);
function popmake_popup_close_meta_box_field_f4_press( $popup_id ) {
	?><tr>
		<th scope="row"><?php _e('Press F4 to Close', 'popup-maker' );?></th>
		<td>
			<input type="checkbox" value="true" name="popup_close_f4_press" id="popup_close_f4_press" <?php echo popmake_get_popup_close( $popup_id, 'f4_press' ) ? 'checked="checked" ' : '';?>/>
			<label for="popup_close_f4_press" class="description"><?php _e('Checking this will cause popup to close when user presses F4 key.', 'popup-maker' );?></label>
		</td>
	</tr><?php
}