<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders popup auto open fields
 * @since 1.0
 *
 * @param $post_id
 */
add_action( 'popmake_popup_auto_open_meta_box_fields', 'popmake_popup_auto_open_meta_box_field_enabled', 10 );
function popmake_popup_auto_open_meta_box_field_enabled( $popup_id ) { ?>
	<tr>
	<th scope="row"><?php _e( 'Enable Auto Open Popups', 'popup-maker' ); ?></th>
	<td>
		<input type="checkbox" value="true" name="popup_auto_open_enabled" id="popup_auto_open_enabled" <?php echo popmake_get_popup_auto_open( $popup_id, 'enabled' ) ? 'checked="checked" ' : ''; ?>/>
		<label for="popup_auto_open_enabled" class="description"><?php _e( 'Checking this will cause popup to open automatically.', 'popup-maker' ); ?></label>
	</td>
	</tr><?php
}


add_action( 'popmake_popup_auto_open_meta_box_fields', 'popmake_popup_auto_open_meta_box_field_delay', 20 );
function popmake_popup_auto_open_meta_box_field_delay( $popup_id ) { ?>
	<tr class="auto-open-enabled">
	<th scope="row">
		<label for="popup_auto_open_delay"><?php _e( 'Delay', 'popup-maker' ); ?></label>
	</th>
	<td>
		<input type="text" readonly
		       value="<?php esc_attr_e( popmake_get_popup_auto_open( $popup_id, 'delay' ) ) ?>"
		       name="popup_auto_open_delay"
		       id="popup_auto_open_delay"
		       class="popmake-range-manual"
		       step="<?php esc_attr_e( apply_filters( 'popmake_popup_auto_open_delay_step', 500 ) ); ?>"
		       min="<?php esc_attr_e( apply_filters( 'popmake_popup_auto_open_delay_min', 0 ) ); ?>"
		       max="<?php esc_attr_e( apply_filters( 'popmake_popup_auto_open_delay_max', 10000 ) ); ?>"
			/>
		<span class="range-value-unit regular-text">ms</span>

		<p class="description"><?php _e( 'The delay before the popup will open in milliseconds.', 'popup-maker' ); ?></p>
	</td>
	</tr><?php
}


add_action( 'popmake_popup_auto_open_meta_box_fields', 'popmake_popup_auto_open_meta_box_field_cookie_trigger', 30 );
function popmake_popup_auto_open_meta_box_field_cookie_trigger( $popup_id ) { ?>
	<tr class="auto-open-enabled">
	<th scope="row">
		<label for="popup_auto_open_cookie_trigger">
			<?php _e( 'Cookie Trigger', 'popup-maker' ); ?>
		</label>
	</th>
	<td>
		<select name="popup_auto_open_cookie_trigger" id="popup_auto_open_cookie_trigger">
			<?php foreach ( apply_filters( 'popmake_cookie_trigger_options', array() ) as $option => $value ) : ?>
				<option
					value="<?php echo $value; ?>"
					<?php echo $value == popmake_get_popup_auto_open( $popup_id, 'cookie_trigger' ) ? ' selected="selected"' : ''; ?>
					><?php echo $option; ?></option>
			<?php endforeach ?>
		</select>

		<p class="description"><?php _e( 'When do you want to create the cookie.', 'popup-maker' ) ?></p>
	</td>
	</tr><?php
}


add_action( 'popmake_popup_auto_open_meta_box_fields', 'popmake_popup_auto_open_meta_box_field_session_cookie', 40 );
function popmake_popup_auto_open_meta_box_field_session_cookie( $popup_id ) { ?>
	<tr class="auto-open-enabled">
	<th scope="row">
		<label for="popup_auto_open_session_cookie">
			<?php _e( 'Use Session Cookie?', 'popup-maker' ); ?>
		</label>
	</th>
	<td>
		<input type="checkbox" value="true" name="popup_auto_open_session_cookie" id="popup_auto_open_session_cookie" <?php checked( popmake_get_popup_auto_open( $popup_id, 'session_cookie' ), 'true' ); ?>/>
		<label class="description" for="popup_auto_open_session_cookie"><?php _e( 'Session cookies expire when the user closes their browser.', 'popup-maker' ) ?></label>
	</td>
	</tr><?php
}

add_action( 'popmake_popup_auto_open_meta_box_fields', 'popmake_popup_auto_open_meta_box_field_cookie_time', 50 );
function popmake_popup_auto_open_meta_box_field_cookie_time( $popup_id ) { ?>
	<tr class="auto-open-enabled not-session-cookie">
	<th scope="row">
		<label for="popup_auto_open_cookie_time">
			<?php _e( 'Cookie Time', 'popup-maker' ); ?>
		</label>
	</th>
	<td>
		<input type="text" class="regular-text" name="popup_auto_open_cookie_time" id="popup_auto_open_cookie_time" value="<?php esc_attr_e( popmake_get_popup_auto_open( $popup_id, 'cookie_time' ) ) ?>"/>

		<p class="description"><?php _e( 'Enter a plain english time before cookie expires. <br/>Example "364 days 23 hours 59 minutes 59 seconds" will reset just before 1 year exactly.', 'popup-maker' ) ?></p>
	</td>
	</tr><?php
}


add_action( 'popmake_popup_auto_open_meta_box_fields', 'popmake_popup_auto_open_meta_box_field_cookie_path', 60 );
function popmake_popup_auto_open_meta_box_field_cookie_path( $popup_id ) { ?>
	<tr class="auto-open-enabled">
	<th scope="row"><?php _e( 'Sitewide Cookie', 'popup-maker' ); ?></th>
	<td>
		<input type="checkbox" value="/" name="popup_auto_open_cookie_path" id="popup_auto_open_cookie_path" <?php checked( popmake_get_popup_auto_open( $popup_id, 'cookie_path' ), '/' ); ?>/>
		<label for="popup_auto_open_cookie_path" class="description"><?php _e( 'This will prevent the popup from auto opening on any page until the cookie expires.', 'popup-maker' ); ?></label>
	</td>
	</tr><?php
}


add_action( 'popmake_popup_auto_open_meta_box_fields', 'popmake_popup_auto_open_meta_box_field_cookie_key', 70 );
function popmake_popup_auto_open_meta_box_field_cookie_key( $popup_id ) { ?>
	<tr class="auto-open-enabled">
	<th scope="row">
		<label for="popup_auto_open_cookie_key">
			<?php _e( 'Cookie Key', 'popup-maker' ); ?>
		</label>
	<td>
		<input type="text" value="<?php esc_attr_e( popmake_get_popup_auto_open( $popup_id, 'cookie_key' ) ) ?>" name="popup_auto_open_cookie_key" id="popup_auto_open_cookie_key"/>
		<button type="button" class="popmake-reset-cookie-key popmake-reset-auto-open-cookie-key button large-button"><?php _e( 'Reset', 'popup-maker' ); ?></button>
		<p class="description"><?php _e( 'This changes the key used when setting and checking cookies. Resetting this will cause all existing cookies to be invalid.', 'popup-maker' ); ?></p>
	</td>
	</tr><?php
}
