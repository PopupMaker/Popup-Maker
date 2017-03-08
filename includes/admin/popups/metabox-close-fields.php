<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders popup display fields
 * @since 1.0
 *
 * @param $post_id
 */

add_action( 'popmake_popup_close_meta_box_fields', 'popmake_popup_close_meta_box_field_text', 10 );
function popmake_popup_close_meta_box_field_text( $popup_id ) { ?>
	<tr>
	<th scope="row">
		<label for="popup_close_text">
			<?php _e( 'Close Text', 'popup-maker' ); ?>
		</label>
	<td>
		<input type="text" placeholder="<?php _e( 'CLOSE', 'popup-maker' ); ?>" name="popup_close_text" id="popup_close_text" value="<?php esc_attr_e( popmake_get_popup_close( $popup_id, 'text' ) ); ?>"/>

		<p class="description"><?php _e( 'Use this to override the default text set in the popup theme.', 'popup-maker' ); ?></p>
	</td>
	</tr><?php
}

add_action( 'popmake_popup_close_meta_box_fields', 'popmake_popup_close_meta_box_field_fi_extension_promotion', 10 );
function popmake_popup_close_meta_box_field_fi_extension_promotion( $popup_theme_id ) {
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

add_action( 'popmake_popup_close_meta_box_fields', 'popmake_popup_close_meta_box_field_button_delay', 20 );
function popmake_popup_close_meta_box_field_button_delay( $popup_id ) { ?>
	<tr>
	<th scope="row">
		<label for="popup_close_button_delay">
			<?php _e( 'Close Button Delay', 'popup-maker' ); ?>
		</label>
	<td>
		<input type="text"
		       value="<?php esc_attr_e( popmake_get_popup_close( $popup_id, 'button_delay' ) ) ?>"
		       name="popup_close_button_delay"
		       id="popup_close_button_delay"
		       class="popmake-range-manual"
		       step="<?php esc_html_e( apply_filters( 'popmake_popup_step_close_button_delay', 100 ) ); ?>"
		       min="<?php esc_html_e( apply_filters( 'popmake_popup_min_close_button_delay', 0 ) ); ?>"
		       max="<?php esc_html_e( apply_filters( 'popmake_popup_max_close_button_delay', 3000 ) ); ?>"
			/>
		<span class="range-value-unit regular-text">ms</span>

		<p class="description"><?php _e( 'This delays the display of the close button.', 'popup-maker' ); ?></p>
	</td>
	</tr><?php
}

add_action( 'popmake_popup_close_meta_box_fields', 'popmake_popup_close_meta_box_field_overlay_click', 30 );
function popmake_popup_close_meta_box_field_overlay_click( $popup_id ) { ?>
	<tr>
	<th scope="row"><?php _e( 'Click Overlay to Close', 'popup-maker' ); ?></th>
	<td>
		<input type="checkbox" value="true" name="popup_close_overlay_click" id="popup_close_overlay_click" <?php echo popmake_get_popup_close( $popup_id, 'overlay_click' ) ? 'checked="checked" ' : ''; ?>/>
		<label for="popup_close_overlay_click" class="description"><?php _e( 'Checking this will cause popup to close when user clicks on overlay.', 'popup-maker' ); ?></label>
	</td>
	</tr><?php
}


add_action( 'popmake_popup_close_meta_box_fields', 'popmake_popup_close_meta_box_field_esc_press', 40 );
function popmake_popup_close_meta_box_field_esc_press( $popup_id ) { ?>
	<tr>
	<th scope="row"><?php _e( 'Press ESC to Close', 'popup-maker' ); ?></th>
	<td>
		<input type="checkbox" value="true" name="popup_close_esc_press" id="popup_close_esc_press" <?php echo popmake_get_popup_close( $popup_id, 'esc_press' ) ? 'checked="checked" ' : ''; ?>/>
		<label for="popup_close_esc_press" class="description"><?php _e( 'Checking this will cause popup to close when user presses ESC key.', 'popup-maker' ); ?></label>
	</td>
	</tr><?php
}


add_action( 'popmake_popup_close_meta_box_fields', 'popmake_popup_close_meta_box_field_f4_press', 50 );
function popmake_popup_close_meta_box_field_f4_press( $popup_id ) { ?>
	<tr>
	<th scope="row"><?php _e( 'Press F4 to Close', 'popup-maker' ); ?></th>
	<td>
		<input type="checkbox" value="true" name="popup_close_f4_press" id="popup_close_f4_press" <?php echo popmake_get_popup_close( $popup_id, 'f4_press' ) ? 'checked="checked" ' : ''; ?>/>
		<label for="popup_close_f4_press" class="description"><?php _e( 'Checking this will cause popup to close when user presses F4 key.', 'popup-maker' ); ?></label>
	</td>
	</tr><?php
}