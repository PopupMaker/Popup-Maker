<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function popmake_popup_themes_meta_box_field_theme( $popup_id ) {
    $popup = new PUM_Popup( $popup_id ); ?>
	<tr>
	<td scope="row">
		<label for="popup_theme"><strong><?php _e( 'Theme', 'popup-maker' ); ?></strong></label>
		<select name="popup_theme" id="popup_theme" class="input-large">
			<?php foreach ( popmake_get_all_popup_themes() as $theme ) : ?>
				<option value="<?php echo $theme->ID; ?>" <?php selected( $theme->ID, $popup->get_theme_id() ); ?>>
					<?php echo $theme->post_title; ?>
				</option>
			<?php endforeach ?>
		</select>

		<p class="description"><?php _e( 'Choose a theme for this popup.', 'popup-maker' ) ?></p>
	</td>
	</tr><?php
}

add_action( 'popmake_popup_themes_meta_box_fields', 'popmake_popup_themes_meta_box_field_theme', 5 );