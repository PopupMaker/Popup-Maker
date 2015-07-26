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

add_action( 'popmake_popup_click_open_meta_box_fields', 'popmake_popup_click_open_meta_box_field_extra_selectors', 10 );
function popmake_popup_click_open_meta_box_field_extra_selectors( $popup_id ) { ?>
	<tr>
	<th scope="row">
		<label for="popup_click_open_extra_selectors">
			<?php _e( 'Extra CSS Selectors', 'popup-maker' ); ?>
		</label>
	</th>
	<td>
		<input type="text" placeholder="<?php _e( '.my-class, #button2', 'popup-maker' ); ?>" name="popup_click_open_extra_selectors" id="popup_click_open_extra_selectors" value="<?php esc_attr_e( popmake_get_popup_click_open( $popup_id, 'extra_selectors' ) ); ?>"/>

		<p class="description"><?php _e( 'This allows custom css classes, ids or selector strings to trigger the popup when clicked. Seperate multiple selectors using commas.', 'popup-maker' ); ?></p>
	</td>
	</tr><?php
}
