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
add_action( 'popmake_popup_display_meta_box_fields', 'popmake_popup_display_meta_box_field_size', 10 );
function popmake_popup_display_meta_box_field_size( $popup_id ) {
	?>
	<tr>
	<th scope="row">
		<label for="popup_display_size">
			<?php _e( 'Size', 'popup-maker' ); ?>
		</label>
	</th>
	<td>
		<select name="popup_display_size" id="popup_display_size" required>
			<?php foreach ( apply_filters( 'popmake_popup_display_size_options', array() ) as $option => $value ) : ?>
				<option
					value="<?php echo $value; ?>"
					<?php echo $value == popmake_get_popup_display( $popup_id, 'size' ) ? ' selected="selected"' : ''; ?>
					<?php echo $value == '' ? ' class="bold"' : ''; ?>
					><?php echo $option; ?></option>
			<?php endforeach ?>
		</select>

		<p class="description"><?php _e( 'Select the size of the popup.', 'popup-maker' ); ?></p>
	</td>
	</tr><?php
}


add_action( 'popmake_popup_display_meta_box_fields', 'popmake_popup_display_meta_box_field_responsive_sizes', 20 );
function popmake_popup_display_meta_box_field_responsive_sizes( $popup_id ) {
	?>
	<tr class="responsive-size-only">
	<th scope="row">
		<label for="popup_display_responsive_min_width"><?php _e( 'Min Width', 'popup-maker' ); ?></label>
	</th>
	<td>
		<input type="text" value="<?php esc_html_e( popmake_get_popup_display( $popup_id, 'responsive_min_width' ) ); ?>" size="5" name="popup_display_responsive_min_width" id="popup_display_responsive_min_width"/>
		<select name="popup_display_responsive_min_width_unit" id="popup_display_responsive_min_width_unit">
			<?php foreach ( apply_filters( 'popmake_size_unit_options', array() ) as $option => $value ) : ?>
				<option
					value="<?php echo $value; ?>"
					<?php echo $value == popmake_get_popup_display( $popup_id, 'responsive_min_width_unit' ) ? ' selected="selected"' : ''; ?>
					<?php echo $value == '' ? ' class="bold"' : ''; ?>
					><?php echo $option; ?></option>
			<?php endforeach ?>
		</select>

		<p class="description"><?php _e( 'Set a minimum width for the popup.', 'popup-maker' ); ?></p>
	</td>
	</tr>
	<tr class="responsive-size-only">
	<th scope="row">
		<label for="popup_display_responsive_max_width"><?php _e( 'Max Width', 'popup-maker' ); ?></label>
	</th>
	<td>
		<input type="text" value="<?php esc_html_e( popmake_get_popup_display( $popup_id, 'responsive_max_width' ) ); ?>" size="5" name="popup_display_responsive_max_width" id="popup_display_responsive_max_width"/>
		<select name="popup_display_responsive_max_width_unit" id="popup_display_responsive_max_width_unit">
			<?php foreach ( apply_filters( 'popmake_size_unit_options', array() ) as $option => $value ) : ?>
				<option
					value="<?php echo $value; ?>"
					<?php echo $value == popmake_get_popup_display( $popup_id, 'responsive_max_width_unit' ) ? ' selected="selected"' : ''; ?>
					<?php echo $value == '' ? ' class="bold"' : ''; ?>
					><?php echo $option; ?></option>
			<?php endforeach ?>
		</select>

		<p class="description"><?php _e( 'Set a maximum width for the popup.', 'popup-maker' ); ?></p>
	</td>
	</tr><?php
}


add_action( 'popmake_popup_display_meta_box_fields', 'popmake_popup_display_meta_box_field_custom_sizes', 30 );
function popmake_popup_display_meta_box_field_custom_sizes( $popup_id ) {
	?>
	<tr class="custom-size-only">
	<th scope="row">
		<label for="popup_display_custom_width"><?php _e( 'Width', 'popup-maker' ); ?></label>
	</th>
	<td>
		<input type="text" value="<?php esc_html_e( popmake_get_popup_display( $popup_id, 'custom_width' ) ); ?>" size="5" name="popup_display_custom_width" id="popup_display_custom_width"/>
		<select name="popup_display_custom_width_unit" id="popup_display_custom_width_unit">
			<?php foreach ( apply_filters( 'popmake_size_unit_options', array() ) as $option => $value ) : ?>
				<option
					value="<?php echo $value; ?>"
					<?php echo $value == popmake_get_popup_display( $popup_id, 'custom_width_unit' ) ? ' selected="selected"' : ''; ?>
					<?php echo $value == '' ? ' class="bold"' : ''; ?>
					><?php echo $option; ?></option>
			<?php endforeach ?>
		</select>

		<p class="description"><?php _e( 'Set a custom width for the popup.', 'popup-maker' ); ?></p>
	</td>
	</tr>


	<tr class="custom-size-only">
		<th scope="row"><?php _e( 'Auto Adjusted Height', 'popup-maker' ); ?></th>
		<td>
			<input type="checkbox" value="true" name="popup_display_custom_height_auto" id="popup_display_custom_height_auto" <?php echo popmake_get_popup_display( $popup_id, 'custom_height_auto' ) ? 'checked="checked" ' : ''; ?>/>
			<label for="popup_display_custom_height_auto" class="description"><?php _e( 'Checking this option will set height to fit the content.', 'popup-maker' ); ?></label>
		</td>
	</tr>


	<tr class="custom-size-only custom-size-height-only">
		<th scope="row"><?php _e( 'Scrollable Content', 'popup-maker' ); ?></th>
		<td>
			<input type="checkbox" value="true" name="popup_display_scrollable_content" id="popup_display_scrollable_content" <?php echo popmake_get_popup_display( $popup_id, 'scrollable_content' ) ? 'checked="checked" ' : ''; ?>/>
			<label for="popup_display_scrollable_content" class="description"><?php _e( 'Checking this option will add a scroll bar to your content.', 'popup-maker' ); ?></label>
		</td>
	</tr>


	<tr class="custom-size-only custom-size-height-only"<?php echo popmake_get_popup_display( $popup_id, 'custom_height_auto' ) ? ' style="display:none"' : ''; ?>>
		<th scope="row">
			<?php _e( 'Height', 'popup-maker' ); ?>
		</th>
		<td>
			<input type="text" value="<?php esc_html_e( popmake_get_popup_display( $popup_id, 'custom_height' ) ); ?>" size="5" name="popup_display_custom_height" id="popup_display_custom_height"/>
			<select name="popup_display_custom_height_unit" id="popup_display_custom_height_unit">
				<?php foreach ( apply_filters( 'popmake_size_unit_options', array() ) as $option => $value ) : ?>
					<option
						value="<?php echo $value; ?>"
						<?php echo $value == popmake_get_popup_display( $popup_id, 'custom_height_unit' ) ? ' selected="selected"' : ''; ?>
						<?php echo $value == '' ? ' class="bold"' : ''; ?>
						><?php echo $option; ?></option>
				<?php endforeach ?>
			</select>

			<p class="description"><?php _e( 'Set a custom height for the popup.', 'popup-maker' ); ?></p>
		</td>
	</tr>
	<?php
}

add_action( 'popmake_popup_display_meta_box_fields', 'popmake_popup_display_meta_box_field_overlay_disabled', 40 );
function popmake_popup_display_meta_box_field_overlay_disabled( $popup_id ) {
	?>
	<tr class="title-divider">
	<th colspan="2"><h3 class="title"><?php _e( 'Overlay', 'popup-maker' ); ?></h3></th>
	</tr>
	<tr>
	<th scope="row"><?php _e( 'Disable Overlay', 'popup-maker' ); ?></th>
	<td>
		<input type="checkbox" value="true" name="popup_display_overlay_disabled" id="popup_display_overlay_disabled" <?php echo popmake_get_popup_display( $popup_id, 'overlay_disabled' ) ? 'checked="checked" ' : ''; ?>/>
		<label for="popup_display_overlay_disabled" class="description"><?php _e( 'Checking this will disable and hide the overlay for this popup.', 'popup-maker' ); ?></label>
	</td>
	</tr><?php
}

add_action( 'popmake_popup_display_meta_box_fields', 'popmake_popup_display_meta_box_field_animation', 50 );
function popmake_popup_display_meta_box_field_animation( $popup_id ) {
	?>
	<tr class="title-divider">
	<th colspan="2"><h3 class="title"><?php _e( 'Animation', 'popup-maker' ); ?></h3></th>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_display_animation_type">
				<?php _e( 'Animation Type', 'popup-maker' ); ?>
			</label>
		</th>
		<td>
			<select name="popup_display_animation_type" id="popup_display_animation_type">
				<?php foreach ( apply_filters( 'popmake_popup_display_animation_type_options', array() ) as $option => $value ) : ?>
					<option
						value="<?php echo $value; ?>"
						<?php echo $value == popmake_get_popup_display( $popup_id, 'animation_type' ) ? ' selected="selected"' : ''; ?>
						<?php echo $value == '' ? ' class="bold"' : ''; ?>
						><?php echo $option; ?></option>
				<?php endforeach ?>
			</select>

			<p class="description"><?php _e( 'Select an animation type for your popup.', 'popup-maker' ); ?></p>
		</td>
	</tr>
	<tr class="animation-speed">
		<th scope="row">
			<label for="popup_display_animation_speed">
				<?php _e( 'Animation Speed', 'popup-maker' ); ?>
			</label>
		</th>
		<td>
			<input type="text" readonly
			       value="<?php esc_attr_e( popmake_get_popup_display( $popup_id, 'animation_speed' ) ); ?>"
			       name="popup_display_animation_speed"
			       id="popup_display_animation_speed"
			       class="popmake-range-manual"
			       step="10"
			       min="<?php esc_html_e( apply_filters( 'popmake_admin_popup_min_animation_speed', 50 ) ); ?>"
			       max="<?php esc_html_e( apply_filters( 'popmake_admin_popup_max_animation_speed', 1000 ) ); ?>"
				/>
			<span class="range-value-unit regular-text">ms</span>

			<p class="description"><?php _e( 'Set the animation speed for the popup.', 'popup-maker' ); ?></p>
		</td>
	</tr>
	<tr class="animation-origin">
		<th scope="row">
			<label for="popup_display_animation_origin">
				<?php _e( 'Animation Origin', 'popup-maker' ); ?>
			</label>
		</th>
		<td>
			<select name="popup_display_animation_origin" id="popup_display_animation_origin">
				<?php foreach ( apply_filters( 'popmake_popup_display_animation_origin_options', array() ) as $option => $value ) : ?>
					<option
						value="<?php echo $value; ?>"
						<?php echo $value == popmake_get_popup_display( $popup_id, 'animation_origin' ) ? ' selected="selected"' : ''; ?>
						><?php echo $option; ?></option>
				<?php endforeach ?>
			</select>

			<p class="description"><?php _e( 'Choose where the animation will begin.', 'popup-maker' ); ?></p>
		</td>
	</tr>
	<?php
}


add_action( 'popmake_popup_display_meta_box_fields', 'popmake_admin_popup_form_display_tab_settings_position', 60 );
function popmake_admin_popup_form_display_tab_settings_position( $popup_id ) {
	?>
	<tr class="title-divider">
	<th colspan="2"><h3 class="title"><?php _e( 'Position', 'popup-maker' ); ?></h3></th>
	</tr>
	<tr>
		<th scope="row"><?php _e( 'Stackable', 'popup-maker' ); ?></th>
		<td>
			<input type="checkbox" value="true" name="popup_display_stackable" id="popup_display_stackable" <?php echo popmake_get_popup_display( $popup_id, 'stackable' ) ? 'checked="checked" ' : ''; ?>/>
			<label for="popup_display_stackable" class="description"><?php _e( 'This enables other popups to remain open.', 'popup-maker' ); ?></label>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_display_location"><?php _e( 'Location', 'popup-maker' ); ?></label>
		</th>
		<td>
			<select name="popup_display_location" id="popup_display_location">
				<?php foreach ( apply_filters( 'popmake_popup_display_location_options', array() ) as $option => $value ) : ?>
					<option
						value="<?php echo $value; ?>"
						<?php echo $value == popmake_get_popup_display( $popup_id, 'location' ) ? ' selected="selected"' : ''; ?>
						><?php echo $option; ?></option>
				<?php endforeach ?>
			</select>

			<p class="description"><?php _e( 'Choose where the popup will be displayed.', 'popup-maker' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php _e( 'Fixed Postioning', 'popup-maker' ); ?></th>
		<td><?php
			$position_fixed = popmake_get_popup_display( $popup_id, 'position_fixed', false );
			switch( $position_fixed ) {
				case 'true':
				case true:
				case 1:
					$position_fixed = true;
					break;
				default:
					$position_fixed = false;
					break;
			} ?>
			<input type="checkbox" value="1" name="popup_display_position_fixed"
			       id="popup_display_position_fixed" <?php checked( $position_fixed, 1 ); ?>/>
			<label for="popup_display_position_fixed" class="description"><?php _e( 'Checking this sets the positioning of the popup to fixed.', 'popup-maker' ); ?></label>
		</td>
	</tr>
	<tr class="top">
		<th scope="row">
			<label for="popup_display_position_top"><?php _e( 'Top', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" readonly
			       value="<?php esc_attr_e( popmake_get_popup_display( $popup_id, 'position_top' ) ); ?>"
			       name="popup_display_position_top"
			       id="popup_display_position_top"
			       class="popmake-range-manual"
			       min="0"
			       max="500"
			       step="1"
				/>
			<span class="range-value-unit regular-text">px</span>

			<p class="description"><?php _e( 'Distance from the top edge of the screen.', 'popup-maker' ); ?></p>
		</td>
	</tr>
	<tr class="bottom">
		<th scope="row">
			<label for="popup_display_position_bottom"><?php _e( 'Bottom', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" readonly
			       value="<?php esc_attr_e( popmake_get_popup_display( $popup_id, 'position_bottom' ) ); ?>"
			       name="popup_display_position_bottom"
			       id="popup_display_position_bottom"
			       class="popmake-range-manual"
			       min="0"
			       max="500"
			       step="1"
				/>
			<span class="range-value-unit regular-text">px</span>

			<p class="description"><?php _e( 'Distance from the bottom edge of the screen.', 'popup-maker' ); ?></p>
		</td>
	</tr>
	<tr class="left">
		<th scope="row">
			<label for="popup_display_position_left"><?php _e( 'Left', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="text" readonly
			       value="<?php esc_attr_e( popmake_get_popup_display( $popup_id, 'position_left' ) ); ?>"
			       name="popup_display_position_left"
			       id="popup_display_position_left"
			       class="popmake-range-manual"
			       min="0"
			       max="500"
			       step="1"
				/>
			<span class="range-value-unit regular-text">px</span>

			<p class="description"><?php _e( 'Distance from the left edge of the screen.', 'popup-maker' ); ?></p>
		</td>
	</tr>
	<tr class="right">
	<th scope="row">
		<label for="popup_display_position_right"><?php _e( 'Right', 'popup-maker' ); ?></label>
	</th>
	<td>
		<input type="text" readonly
		       value="<?php esc_attr_e( popmake_get_popup_display( $popup_id, 'position_right' ) ); ?>"
		       name="popup_display_position_right"
		       id="popup_display_position_right"
		       class="popmake-range-manual"
		       min="0"
		       max="500"
		       step="1"
			/>
		<span class="range-value-unit regular-text">px</span>

		<p class="description"><?php _e( 'Distance from the right edge of the screen.', 'popup-maker' ); ?></p>
	</td>
	</tr><?php
}

add_action( 'popmake_popup_display_meta_box_fields', 'popmake_admin_popup_form_display_tab_settings_zindex', 70 );
function popmake_admin_popup_form_display_tab_settings_zindex( $popup_id ) {
	?>
	<tr class="title-divider">
	<th colspan="2"><h3 class="title"><?php _e( 'Z Index', 'popup-maker' ); ?></h3></th>
	</tr>
	<tr>
		<th scope="row">
			<label for="popup_display_overlay_zindex"><?php _e( 'Overlay Z-Index', 'popup-maker' ); ?></label>
		</th>
		<td>
			<input type="number" max="2147483647" min="0" name="popup_display_overlay_zindex" id="popup_display_overlay_zindex" value="<?php esc_attr_e( popmake_get_popup_display( $popup_id, 'overlay_zindex' ) ); ?>">

			<p class="description"><?php _e( 'Change the z-index layer level for the overlay.', 'popup-maker' ); ?></p>
		</td>
	</tr>
	<tr>
	<th scope="row">
		<label for="popup_display_zindex"><?php _e( 'Popup Z-Index', 'popup-maker' ); ?></label>
	</th>
	<td>
		<input type="number" max="2147483647" min="0" name="popup_display_zindex" id="popup_display_zindex" value="<?php esc_attr_e( popmake_get_popup_display( $popup_id, 'zindex' ) ); ?>">

		<p class="description"><?php _e( 'Change the z-index layer level for the popup.', 'popup-maker' ); ?></p>
	</td>
	</tr><?php
}