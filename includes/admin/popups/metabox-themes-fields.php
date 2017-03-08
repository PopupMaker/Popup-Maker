<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function popmake_popup_themes_meta_box_field_theme( $popup_id ) {
    $popup = pum_popup( $popup_id ); ?>
	<tr>
	<td scope="row">
		<label for="popup_theme"><strong><?php _e( 'Theme', 'popup-maker' ); ?></strong></label>
		<select name="popup_theme" id="popup_theme" class="input-large">
			<?php foreach ( popmake_get_all_popup_themes() as $theme ) : ?>
				<option value="<?php echo $theme->ID; ?>" <?php selected( $theme->ID, $popup->get_theme_id() ); ?>>
					<?php echo $theme->post_title; ?>
				</option>
			<?php endforeach ?>
		</select><br />
		<p class="description">
			<?php _e( 'Choose a theme for this popup.', 'popup-maker' ) ?><br />
			<a id="edit_theme_link" href="<?php echo admin_url( "post.php?action=edit&post={$popup->get_theme_id()}" ); ?>" data-baseurl="<?php echo admin_url( "post.php?action=edit&post=" ); ?>">
				<?php _e( 'Customize This Theme', 'popup-maker' ); ?>
			</a>
		</p>

	</td>
	</tr><?php
}

add_action( 'popmake_popup_themes_meta_box_fields', 'popmake_popup_themes_meta_box_field_theme', 5 );