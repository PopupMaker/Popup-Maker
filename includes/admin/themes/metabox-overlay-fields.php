<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function popmake_popup_theme_overlay_meta_box_field_description( $popup_theme_id ) { ?>
	</tbody></table><p><?php _e( 'Theme the overlay behind the popups.', 'popup-maker' ); ?></p><table class="form-table"><tbody><?php
}
add_action('popmake_popup_theme_overlay_meta_box_fields', 'popmake_popup_theme_overlay_meta_box_field_description', 0);

function popmake_popup_theme_overlay_meta_box_field_background( $popup_theme_id ) { ?>
	<tr>
		<th scope="row">
			<label for="popup_theme_overlay_background_color"><?php _e( 'Color', 'popup-maker' );?></label>
		</th>
		<td>
			<input type="text" name="popup_theme_overlay_background_color" id="popup_theme_overlay_background_color" value="<?php esc_attr_e(popmake_get_popup_theme_overlay( $popup_theme_id, 'background_color'))?>" class="color-picker background-color" />
			<p class="description"><?php _e( 'Choose the overlay color.', 'popup-maker' )?></p>
		</td>
	</tr>
	<tr class="background-opacity">
		<th scope="row">
			<label for="popup_theme_overlay_background_opacity"><?php _e( 'Opacity', 'popup-maker' );?></label>
		</th>
		<td>
			<input type="text"
				value="<?php esc_attr_e(popmake_get_popup_theme_overlay( $popup_theme_id, 'background_opacity' ))?>"
				name="popup_theme_overlay_background_opacity"
				id="popup_theme_overlay_background_opacity"
				class="popmake-range-manual"
				step="1"
				min="0"
				max="100"
				data-force-minmax=true
			/>
			<span class="range-value-unit regular-text">%</span>
			<p class="description"><?php _e('The opacity value for the overlay.',POPMAKE_SLUG)?></p>
		</td>
	</tr><?php
}
add_action('popmake_popup_theme_overlay_meta_box_fields', 'popmake_popup_theme_overlay_meta_box_field_background', 10);

function popmake_popup_theme_overlay_meta_box_field_atb_extension_promotion( $popup_theme_id ) { ?>
	<tr>
		<th colspan="2" class="popmake-upgrade-tip">
			<img style="" src="<?php echo POPMAKE_URL;?>/assets/images/upsell-icon-advanted-theme-builder.png"/> <?php _e( 'Want to use background images?', 'popup-maker' ); ?> <a href="https://wppopupmaker.com/extensions/advanced-theme-builder/?utm_source=plugin-theme-editor&utm_medium=text-link&utm_campaign=Upsell&utm_content=overlay-settings" target="_blank"><?php _e( 'Check out Advanced Theme Builder!', 'popup-maker' ); ?></a>.
		</th>
	</tr><?php
}
add_action('popmake_popup_theme_overlay_meta_box_fields', 'popmake_popup_theme_overlay_meta_box_field_atb_extension_promotion', 20);