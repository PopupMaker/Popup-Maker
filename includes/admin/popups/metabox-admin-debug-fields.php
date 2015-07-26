<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders popup click open fields
 * @since 1.0
 *
 * @param $post_id
 */

add_action( 'popmake_popup_admin_debug_meta_box_fields', 'popmake_popup_admin_debug_meta_box_field_extra_selectors', 10 );
function popmake_popup_admin_debug_meta_box_field_extra_selectors( $popup_id ) { ?>
	<tr>
	<th scope="row"><?php _e( 'Enable Admin Debug', 'popup-maker' ); ?></th>
	<td>
		<input type="checkbox" value="true" name="popup_admin_debug_enabled" id="popup_admin_debug_enabled" <?php echo popmake_get_popup_admin_debug( $popup_id, 'enabled' ) ? 'checked="checked" ' : ''; ?>/>
		<label for="popup_admin_debug_enabled" class="description"><?php _e( 'When Enabled, the popup will show immediately on the given page for admins.', 'popup-maker' ); ?></label>
	</td>
	</tr><?php
}
