<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pum_deprecated_v4_initialize() {

	// If not yet upgraded still show and process the old meta boxes.
	if ( pum_get_db_ver() < 4 ) {
		add_action( 'add_meta_boxes', 'popmake_deprecated_add_popup_meta_box' );
		add_filter( 'popmake_popup_meta_fields', 'popmake_deprecated_popup_meta_fields' );
		add_filter( 'popmake_popup_meta_field_groups', 'popmake_deprecated_popup_meta_field_groups' );
		add_filter( 'popmake_popup_meta_field_group_click_open', 'popmake_popup_meta_field_group_click_open', 0 );
		add_filter( 'popmake_popup_meta_field_group_auto_open', 'popmake_popup_meta_field_group_auto_open', 0 );
		add_filter( 'popmake_popup_meta_field_group_admin_debug', 'popmake_popup_meta_field_group_admin_debug', 0 );
		add_action( 'pum_save_popup', 'popmake_deprecated_popup_meta_box_save', 10, 2 );
		add_filter( 'popmake_metabox_save_popup_auto_open_cookie_key', 'popmake_metabox_save_popup_auto_open_cookie_key' );

		add_action( 'popmake_popup_click_open_meta_box_fields', 'popmake_popup_click_open_meta_box_field_extra_selectors', 10 );
		add_action( 'popmake_popup_auto_open_meta_box_fields', 'popmake_popup_auto_open_meta_box_field_enabled', 10 );
		add_action( 'popmake_popup_auto_open_meta_box_fields', 'popmake_popup_auto_open_meta_box_field_delay', 20 );
		add_action( 'popmake_popup_auto_open_meta_box_fields', 'popmake_popup_auto_open_meta_box_field_cookie_trigger', 30 );
		add_action( 'popmake_popup_auto_open_meta_box_fields', 'popmake_popup_auto_open_meta_box_field_session_cookie', 40 );
		add_action( 'popmake_popup_auto_open_meta_box_fields', 'popmake_popup_auto_open_meta_box_field_cookie_time', 50 );
		add_action( 'popmake_popup_auto_open_meta_box_fields', 'popmake_popup_auto_open_meta_box_field_cookie_path', 60 );
		add_action( 'popmake_popup_auto_open_meta_box_fields', 'popmake_popup_auto_open_meta_box_field_cookie_key', 70 );
		add_action( 'popmake_popup_admin_debug_meta_box_fields', 'popmake_popup_admin_debug_meta_box_field_extra_selectors', 10 );
		add_action( 'popmake_popup_targeting_condition_meta_box_fields', 'popmake_popup_targeting_condition_meta_box_fields', 10 );

        // Remove metaboxes.
        add_action( 'add_meta_boxes', 'pum_deprecated_v4_remove_metaboxes', 20 );

        /**
         * Popup Content Filtering
         * @deprecated 1.4 hooks & filters
         */
        add_filter( 'the_popup_content', array( $GLOBALS['wp_embed'], 'run_shortcode' ), 8 );
        add_filter( 'the_popup_content', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );
        add_filter( 'the_popup_content', 'wptexturize', 10 );
        add_filter( 'the_popup_content', 'convert_smilies', 10 );
        add_filter( 'the_popup_content', 'convert_chars', 10 );
        add_filter( 'the_popup_content', 'wpautop', 10 );
        add_filter( 'the_popup_content', 'shortcode_unautop', 10 );
        add_filter( 'the_popup_content', 'prepend_attachment', 10 );
        add_filter( 'the_popup_content', 'force_balance_tags', 10 );
        add_filter( 'the_popup_content', 'do_shortcode', 11 );
        add_filter( 'the_popup_content', 'capital_P_dangit', 11 );
        add_filter( 'the_popup_content', 'popmake_popup_content_container', 10000, 2 );
    }

}
add_action( 'pum_initialize_deprecated', 'pum_deprecated_v4_initialize' );

function pum_deprecated_v4_remove_metaboxes() {
	// Hide v1.4 Metaboxes if not yet upgraded.
	remove_meta_box( 'pum_popup_analytics', 'popup', 'side' );
	remove_meta_box( 'pum_popup_triggers', 'popup', 'normal' );
	remove_meta_box( 'pum_popup_cookies', 'popup', 'normal' );
	remove_meta_box( 'pum_popup_conditions', 'popup', 'side' );
}

#region Meta Boxes

/**
 * @deprecated 1.4
 */
function popmake_deprecated_add_popup_meta_box() {

	/** Loading Meta **/
	add_meta_box( 'popmake_popup_targeting_condition', __( 'Targeting Conditions', 'popup-maker' ), 'popmake_render_popup_targeting_condition_meta_box', 'popup', 'side', 'high' );

	/** Click Open Meta **/
	add_meta_box( 'popmake_popup_click_open', __( 'Click Open Settings', 'popup-maker' ), 'popmake_render_popup_click_open_meta_box', 'popup', 'side', 'default' );

	/** Auto Open Popups Meta **/
	add_meta_box( 'popmake_popup_auto_open', __( 'Auto Open Settings', 'popup-maker' ), 'popmake_render_popup_auto_open_meta_box', 'popup', 'normal', 'high' );

	/** Admin Debug **/
	add_meta_box( 'popmake_popup_admin_debug', __( 'Admin Debug Settings', 'popup-maker' ), 'popmake_render_popup_admin_debug_meta_box', 'popup', 'normal', 'low' );

}

/**
 * Popup Click Open Metabox
 *
 * Extensions (as well as the core plugin) can add items to the popup click_open
 * configuration metabox via the `popmake_popup_click_open_meta_box_fields` action.
 *
 * @since      1.1.0
 * @deprecated 1.4
 * @return void
 */
function popmake_render_popup_click_open_meta_box() {
	global $post, $popmake_options; ?>
	<div id="popmake_popup_click_open_fields" class="popmake_meta_table_wrap">
	<table class="form-table">
		<tbody>
		<?php do_action( 'popmake_popup_click_open_meta_box_fields', $post->ID ); ?>
		</tbody>
	</table>
	</div><?php
}


/**
 * Popup Load Settings Metabox
 *
 * Extensions (as well as the core plugin) can add items to the popup targeting_condition
 * metabox via the `popmake_popup_targeting_condition_meta_box_fields` action.
 *
 * @since      1.0
 * @deprecated 1.4
 * @return void
 */
function popmake_render_popup_targeting_condition_meta_box() {
	global $post; ?>
	<div id="popmake_popup_targeting_condition_fields" class="popmake_meta_table_wrap">
	<?php do_action( 'popmake_popup_targeting_condition_meta_box_fields', $post->ID ); ?>
	</div><?php
}


/**
 * Popup Auto Open Popups Metabox
 *
 * Extensions (as well as the core plugin) can add items to the popup display
 * configuration metabox via the `popmake_popup_auto_open_meta_box_fields` action.
 *
 * @since      1.0
 * @deprecated 1.4
 * @return void
 */
function popmake_render_popup_auto_open_meta_box() {
	global $post, $popmake_options; ?>
	<div id="popmake_popup_auto_open_fields" class="popmake_meta_table_wrap">
	<table class="form-table">
		<tbody>
		<?php do_action( 'popmake_popup_auto_open_meta_box_fields', $post->ID ); ?>
		</tbody>
	</table>
	</div><?php
}


/**
 * Popup Admin Debug Metabox
 *
 * Extensions (as well as the core plugin) can add items to the popup debug
 * configuration metabox via the `popmake_popup_admin_debug_meta_box_fields` action.
 *
 * @since      1.0
 * @deprecated 1.4
 * @return void
 */
function popmake_render_popup_admin_debug_meta_box() {
	global $post, $popmake_options; ?>
	<div id="popmake_popup_admin_debug_fields" class="popmake_meta_table_wrap">
	<table class="form-table">
		<tbody>
		<?php do_action( 'popmake_popup_admin_debug_meta_box_fields', $post->ID ); ?>
		</tbody>
	</table>
	</div><?php
}

#endregion Meta Boxes

#region Meta Box Fields

/**
 *
 * @deprecated 1.4
 *
 * @param $fields
 *
 * @return array
 */
function popmake_deprecated_popup_meta_fields( $fields ) {
	$fields = array_merge( $fields, array(
		'popup_targeting_condition_on_entire_site',
		'popup_targeting_condition_on_home',
		'popup_targeting_condition_exclude_on_home',
		'popup_targeting_condition_on_blog',
		'popup_targeting_condition_exclude_on_blog',
		'popup_targeting_condition_on_search',
		'popup_targeting_condition_exclude_on_search',
		'popup_targeting_condition_on_404',
		'popup_targeting_condition_exclude_on_404',
	) );

	foreach ( popmake_get_supported_types() as $pt ) {
		$labels   = get_post_type_object( $pt ) ? get_post_type_object( $pt ) : get_taxonomy( $pt );
		$plural   = $pt . 's';
		$fields[] = "popup_targeting_condition_on_{$plural}";
		$fields[] = "popup_targeting_condition_exclude_on_{$plural}";
		$fields[] = "popup_targeting_condition_on_specific_{$plural}";
		$fields[] = "popup_targeting_condition_exclude_on_specific_{$plural}";
	}

	return $fields;
}

/**
 * @deprecated 1.4
 *
 * @param $groups
 *
 * @return array
 */
function popmake_deprecated_popup_meta_field_groups( $groups ) {
	return array_merge( $groups, array(
		'click_open',
		'auto_open',
		'admin_debug',
	) );
}

/**
 * @deprecated 1.4
 *
 * @return array
 */
function popmake_popup_meta_field_group_click_open() {
	return array(
		'extra_selectors',
	);
}

/**
 * @deprecated 1.4
 *
 * @param $fields
 *
 * @return array
 */
function popmake_popup_meta_field_group_auto_open( $fields ) {
	return array_merge( $fields, array(
		'enabled',
		'delay',
		'cookie_trigger',
		'session_cookie',
		'cookie_time',
		'cookie_path',
		'cookie_key',
	) );
}

/**
 * @deprecated 1.4
 *
 * @param $fields
 *
 * @return array
 */
function popmake_popup_meta_field_group_admin_debug( $fields ) {
	return array_merge( $fields, array(
		'enabled',
	) );
}

#endregion Meta Box Fields

#region Meta Box Saving

/**
 * Save post meta when the save_post action is called
 *
 * @since      1.0
 * @deprecated 1.4
 *
 * @param int   $post_id Popup (Post) ID
 * @param array $post    All the data of the the current post
 */
function popmake_deprecated_popup_meta_box_save( $post_id, $post ) {

	$includes = popmake_get_popup_targeting_condition_includes( $post_id );
	$excludes = popmake_get_popup_targeting_condition_excludes( $post_id );

	foreach ( popmake_get_supported_types() as $pt ) {

		foreach ( array( 'include', 'exclude' ) as $type ) {
			$prefix      = "popup_targeting_condition_" . ( $type == 'exclude' ? 'exclude_' : '' ) . "on_{$pt}";
			$current     = $type == 'include' ? ( ! empty( $includes[ $pt ] ) ? $includes[ $pt ] : array() ) : ( ! empty( $excludes[ $pt ] ) ? $excludes[ $pt ] : array() );
			$type_field  = $prefix;
			$type_prefix = $prefix . '_';

			$temp_ids = array();
			if ( ! empty( $_POST[ $type_field ] ) ) {
				foreach ( explode( ',', trim( $_POST[ $type_field ] ) ) as $id ) {
					if ( is_int( intval( $id ) ) ) {
						$temp_ids[] = intval( $id );
					}
				}
			}
			/**
			 * Remove existing meta that no longer exist in $_POST field.
			 */
			if ( ! empty( $current ) ) {
				foreach ( $current as $id ) {
					if ( ! in_array( $id, $temp_ids ) ) {
						delete_post_meta( $post_id, $type_prefix . $id );
					}
				}
			}
			/**
			 * Adds post meta for non existing post type ids in $_POST.
			 */
			foreach ( $temp_ids as $id ) {
				if ( ! in_array( $id, $current ) && $id > 0 ) {
					update_post_meta( $post_id, $type_prefix . $id, true );
				}
			}
		}
	}
}


/**
 * @since      1.0
 * @deprecated 1.4
 *
 * @param string $field
 *
 * @return string
 */
function popmake_metabox_save_popup_auto_open_cookie_key( $field = '' ) {
	if ( $field == '' ) {
		$field = uniqid();
	}

	return $field;
}


#endregion Meta Box Saving

#region Meta Boxes Render Fields

/**
 * Deprecated Admin Popup Editor Functions
 */
/**
 * Renders popup click open fields
 *
 * @deprecated 1.4
 * @since      1.0
 *
 * @param $popup_id
 */
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

/**
 * Renders popup auto open fields
 *
 * @since      1.0
 * @deprecated 1.4
 *
 * @param $popup_id
 */
function popmake_popup_auto_open_meta_box_field_enabled( $popup_id ) { ?>
	<tr>
	<th scope="row"><?php _e( 'Enable Auto Open Popups', 'popup-maker' ); ?></th>
	<td>
		<input type="checkbox" value="true" name="popup_auto_open_enabled" id="popup_auto_open_enabled" <?php echo popmake_get_popup_auto_open( $popup_id, 'enabled' ) ? 'checked="checked" ' : ''; ?>/>
		<label for="popup_auto_open_enabled" class="description"><?php _e( 'Checking this will cause popup to open automatically.', 'popup-maker' ); ?></label>
	</td>
	</tr><?php
}

/**
 * @param $popup_id
 */
function popmake_popup_auto_open_meta_box_field_delay( $popup_id ) { ?>
	<tr class="auto-open-enabled">
	<th scope="row">
		<label for="popup_auto_open_delay"><?php _e( 'Delay', 'popup-maker' ); ?></label>
	</th>
	<td>
		<input type="text"
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

/**
 * @param $popup_id
 */
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

/**
 * @param $popup_id
 */
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

/**
 * @param $popup_id
 */
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

/**
 * @param $popup_id
 */
function popmake_popup_auto_open_meta_box_field_cookie_path( $popup_id ) { ?>
	<tr class="auto-open-enabled">
	<th scope="row"><?php _e( 'Sitewide Cookie', 'popup-maker' ); ?></th>
	<td>
		<input type="checkbox" value="/" name="popup_auto_open_cookie_path" id="popup_auto_open_cookie_path" <?php checked( popmake_get_popup_auto_open( $popup_id, 'cookie_path' ), '/' ); ?>/>
		<label for="popup_auto_open_cookie_path" class="description"><?php _e( 'This will prevent the popup from auto opening on any page until the cookie expires.', 'popup-maker' ); ?></label>
	</td>
	</tr><?php
}

/**
 * @param $popup_id
 */
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

/**
 * Renders popup Admin Debug Metabox fields
 *
 * @since      1.0
 * @deprecated 1.4
 *
 * @param $popup_id
 */
function popmake_popup_admin_debug_meta_box_field_extra_selectors( $popup_id ) { ?>
	<tr>
	<th scope="row"><?php _e( 'Enable Admin Debug', 'popup-maker' ); ?></th>
	<td>
		<input type="checkbox" value="true" name="popup_admin_debug_enabled" id="popup_admin_debug_enabled" <?php echo popmake_get_popup_admin_debug( $popup_id, 'enabled' ) ? 'checked="checked" ' : ''; ?>/>
		<label for="popup_admin_debug_enabled" class="description"><?php _e( 'When Enabled, the popup will show immediately on the given page for admins.', 'popup-maker' ); ?></label>
	</td>
	</tr><?php
}


/**
 * Renders popup load settings fields
 *
 * @since      1.0
 * @deprecated 1.4
 *
 * @param $popup_id
 */
function popmake_popup_targeting_condition_meta_box_fields( $popup_id ) {
	$targeting_condition = popmake_get_popup_targeting_condition( $popup_id );
	/**
	 * Create nonce used for post type and taxonomy ajax searches. Copied from wp-admin/includes/nav-menu.php
	 */
	wp_nonce_field( 'add-menu_item', 'menu-settings-column-nonce' );

	/**
	 * Render Load on entire site toggle.
	 */ ?>
	<div id="targeting_condition-on_entire_site" class="targeting_condition form-table">
		<input type="checkbox"
		       id="popup_targeting_condition_on_entire_site"
		       name="popup_targeting_condition_on_entire_site"
		       value="true"
			<?php if ( ! empty( $targeting_condition['on_entire_site'] ) ) {
				echo 'checked="checked" ';
			} ?>
		/>
		<label for="popup_targeting_condition_on_entire_site"><?php _e( 'On Entire Site', 'popup-maker' ); ?></label>

		<div class="options">
			<?php do_action( "popmake_popup_targeting_condition_on_entire_site_options", $targeting_condition ); ?>
		</div>
	</div>
	<div id="targeting_condition-on_home" class="targeting_condition form-table">
		<input type="checkbox"
		       id="popup_targeting_condition_on_home"
		       name="popup_targeting_condition_on_home"
		       value="true"
			<?php if ( ! empty( $targeting_condition['on_home'] ) ) {
				echo 'checked="checked" ';
			} ?>
		/>
		<label for="popup_targeting_condition_on_home"><?php _e( 'On Home Page', 'popup-maker' ); ?></label>

		<div class="options">
			<?php do_action( "popmake_popup_targeting_condition_on_home_options", $targeting_condition ); ?>
		</div>
	</div>
	<div id="targeting_condition-exclude_on_home" class="targeting_condition form-table">
		<input type="checkbox"
		       id="popup_targeting_condition_exclude_on_home"
		       name="popup_targeting_condition_exclude_on_home"
		       value="true"
			<?php if ( ! empty( $targeting_condition['exclude_on_home'] ) ) {
				echo 'checked="checked" ';
			} ?>
		/>
		<label for="popup_targeting_condition_exclude_on_home"><?php _e( 'Exclude on Home Page', 'popup-maker' ); ?></label>

		<div class="options">
			<?php do_action( "popmake_popup_targeting_condition_exclude_on_home_options", $targeting_condition ); ?>
		</div>
	</div>
	<div id="targeting_condition-on_blog" class="targeting_condition form-table">
		<input type="checkbox"
		       id="popup_targeting_condition_on_blog"
		       name="popup_targeting_condition_on_blog"
		       value="true"
			<?php if ( ! empty( $targeting_condition['on_blog'] ) ) {
				echo 'checked="checked" ';
			} ?>
		/>
		<label for="popup_targeting_condition_on_blog"><?php _e( 'On Blog Index', 'popup-maker' ); ?></label>

		<div class="options">
			<?php do_action( "popmake_popup_targeting_condition_on_blog_options", $targeting_condition ); ?>
		</div>
	</div>
	<div id="targeting_condition-exclude_on_blog" class="targeting_condition form-table">
	<input type="checkbox"
	       id="popup_targeting_condition_exclude_on_blog"
	       name="popup_targeting_condition_exclude_on_blog"
	       value="true"
		<?php if ( ! empty( $targeting_condition['exclude_on_blog'] ) ) {
			echo 'checked="checked" ';
		} ?>
	/>
	<label for="popup_targeting_condition_exclude_on_blog"><?php _e( 'Exclude on Blog Index', 'popup-maker' ); ?></label>

	<div class="options">
		<?php do_action( "popmake_popup_targeting_condition_exclude_on_blog_options", $targeting_condition ); ?>
	</div>
	</div><?php

	do_action( 'popmake_before_post_type_targeting_conditions', $targeting_condition );

	$includes = popmake_get_popup_targeting_condition_includes( $popup_id );
	$excludes = popmake_get_popup_targeting_condition_excludes( $popup_id );

	foreach ( popmake_get_supported_types() as $pt ) {
		$is_post_type = get_post_type_object( $pt );
		$labels       = $is_post_type ? $is_post_type : get_taxonomy( $pt );
		if ( ! $labels ) {
			continue;
		}
		$plural = esc_attr( strtolower( $labels->labels->name ) );

		foreach ( array( 'include', 'exclude' ) as $include_exclude ) {
			$key     = ( $include_exclude != 'include' ? 'exclude_' : '' ) . "on_{$pt}s";
			$current = $include_exclude == 'include' ?
				( ! empty( $includes[ $pt ] ) ? $includes[ $pt ] : array() ) :
				( ! empty( $excludes[ $pt ] ) ? $excludes[ $pt ] : array() ); ?>
		<div id="targeting_condition-<?php echo $key; ?>" class="targeting_condition form-table">
			<input type="checkbox"
			       id="popup_targeting_condition_<?php echo $key; ?>"
			       name="popup_targeting_condition_<?php echo $key; ?>"
			       value="true"
				<?php if ( ! empty( $targeting_condition[ $key ] ) ) {
					echo 'checked="checked" ';
				} ?>
			/><?php
			$label = ( $include_exclude != 'include' ? 'Exclude ' : '' ) . 'On '; ?>
			<label for="popup_targeting_condition_<?php echo $key; ?>"><?php echo __( $label, 'popup-maker' ) . $labels->labels->name; ?></label>

			<div class="options">
				<p style="margin:0;"><?php
					$key = ( $include_exclude != 'include' ? 'exclude_' : '' ) . "on_specific_{$pt}s"; ?>
					<input type="checkbox" style="display:none" name="popup_targeting_condition_<?php echo $key; ?>" value="true" <?php if ( isset( $targeting_condition[ $key ] ) ) {
						echo 'checked';
					} ?>/>
					<label><?php
						$label = ( $include_exclude == 'include' ? 'Load' : 'Exclude' ) . ' on All ';
						echo __( $label, 'popup-maker' ) . $labels->labels->name; ?>
						<input type="radio"
						       name="<?php echo $key; ?>"
						       id="popup_targeting_condition_<?php echo $key; ?>"
						       value=""
							<?php if ( ! isset( $targeting_condition[ $key ] ) ) {
								echo 'checked';
							} ?>
						/>
					</label><br/>
					<label><?php
						$label = ( $include_exclude == 'include' ? 'Load' : 'Exclude' ) . ' on Specific ';
						echo __( $label, 'popup-maker' ) . $labels->labels->name; ?>
						<input type="radio"
						       name="<?php echo $key; ?>"
						       id="popup_targeting_condition_<?php echo $key; ?>"
						       value="true"
							<?php if ( isset( $targeting_condition[ $key ] ) ) {
								echo 'checked';
							} ?>
						/>
					</label>
				</p>

				<div id="<?php echo $key; ?>">
					<div class="nojs-tags hide-if-js">
							<textarea
								name="popup_targeting_condition_<?php echo $include_exclude == 'exclude' ? 'exclude_' : ''; ?>on_<?php echo $pt; ?>"
								rows="3" cols="20"
								id="popup_targeting_condition_<?php echo $include_exclude == 'exclude' ? 'exclude_' : ''; ?>on_<?php echo $pt; ?>"
							><?php esc_html_e( trim( implode( ',', $current ) ) ); ?></textarea>
					</div>
					<div class="hide-if-no-js"><?php
						if ( $is_post_type ) {
							popmake_post_type_item_metabox( $pt );
						} else {
							popmake_taxonomy_item_metabox( $pt );
						} ?>
						<div class="tagchecklist"><?php
							foreach ( $current as $post_id ) { ?>
								<span><a class="ntdelbutton" data-id="<?php echo $post_id; ?>">X</a>
								<?php echo $is_post_type ? get_the_title( $post_id ) : get_term_name( $post_id, $pt ); ?>
								</span><?php
							} ?>
						</div>
					</div>
					<hr/>
				</div>
			</div>
			</div><?php
		}
	} ?>
	<div id="targeting_condition-on_search" class="targeting_condition form-table">
		<input type="checkbox"
		       id="popup_targeting_condition_on_search"
		       name="popup_targeting_condition_on_search"
		       value="true"
			<?php if ( ! empty( $targeting_condition['on_search'] ) ) {
				echo 'checked="checked" ';
			} ?>
		/>
		<label for="popup_targeting_condition_on_search"><?php _e( 'On Search Pages', 'popup-maker' ); ?></label>

		<div class="options">
			<?php do_action( "popmake_popup_targeting_condition_on_search_options", $targeting_condition ); ?>
		</div>
	</div>
	<div id="targeting_condition-exclude_on_search" class="targeting_condition form-table">
		<input type="checkbox"
		       id="popup_targeting_condition_exclude_on_search"
		       name="popup_targeting_condition_exclude_on_search"
		       value="true"
			<?php if ( ! empty( $targeting_condition['exclude_on_search'] ) ) {
				echo 'checked="checked" ';
			} ?>
		/>
		<label for="popup_targeting_condition_exclude_on_search"><?php _e( 'Exclude on Search Pages', 'popup-maker' ); ?></label>

		<div class="options">
			<?php do_action( "popmake_popup_targeting_condition_exclude_on_search_options", $targeting_condition ); ?>
		</div>
	</div>
	<div id="targeting_condition-on_404" class="targeting_condition form-table">
		<input type="checkbox"
		       id="popup_targeting_condition_on_404"
		       name="popup_targeting_condition_on_404"
		       value="true"
			<?php if ( ! empty( $targeting_condition['on_404'] ) ) {
				echo 'checked="checked" ';
			} ?>
		/>
		<label for="popup_targeting_condition_on_404"><?php _e( 'On 404 Pages', 'popup-maker' ); ?></label>

		<div class="options">
			<?php do_action( "popmake_popup_targeting_condition_on_404_options", $targeting_condition ); ?>
		</div>
	</div>
	<div id="targeting_condition-exclude_on_404" class="targeting_condition form-table">
	<input type="checkbox"
	       id="popup_targeting_condition_exclude_on_404"
	       name="popup_targeting_condition_exclude_on_404"
	       value="true"
		<?php if ( ! empty( $targeting_condition['exclude_on_404'] ) ) {
			echo 'checked="checked" ';
		} ?>
	/>
	<label for="popup_targeting_condition_exclude_on_404"><?php _e( 'Exclude on 404 Pages', 'popup-maker' ); ?></label>

	<div class="options">
		<?php do_action( "popmake_popup_targeting_condition_exclude_on_404_options", $targeting_condition ); ?>
	</div>
	</div><?php
}

/**
 * Displays a metabox for a post type menu item.
 *
 * @since      1.0.0
 * @deprecated 1.4
 *
 * @param $post_type_name
 */
function popmake_post_type_item_metabox( $post_type_name ) {
	if ( ! function_exists( 'wp_nav_menu_item_post_type_meta_box' ) ) {
		include ABSPATH . 'wp-admin/includes/nav-menu.php';
	}
	global $_nav_menu_placeholder, $nav_menu_selected_id;

	$post_type = get_post_type_object( $post_type_name );


	// Paginate browsing for large numbers of post objects.
	$per_page = 50;
	$pagenum  = isset( $_REQUEST[ $post_type_name . '-tab' ] ) && isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 1;
	$offset   = 0 < $pagenum ? $per_page * ( $pagenum - 1 ) : 0;

	$args = array(
		'offset'                 => $offset,
		'order'                  => 'ASC',
		'orderby'                => 'title',
		'posts_per_page'         => $per_page,
		'post_type'              => $post_type_name,
		'suppress_filters'       => true,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
	);

	if ( isset( $post_type->_default_query ) ) {
		$args = array_merge( $args, (array) $post_type->_default_query );
	}

	$get_posts = new WP_Query;
	$posts     = $get_posts->query( $args );
	if ( ! $get_posts->post_count ) {
		echo '<p>' . __( 'No items.' ) . '</p>';

		return;
	}

	$num_pages = $get_posts->max_num_pages;

	$page_links = paginate_links( array(
		'base'      => add_query_arg(
			array(
				$post_type_name . '-tab' => 'all',
				'paged'                  => '%#%',
				'item-type'              => 'post_type',
				'item-object'            => $post_type_name,
			)
		),
		'format'    => '',
		'prev_text' => __( '&laquo;' ),
		'next_text' => __( '&raquo;' ),
		'total'     => $num_pages,
		'current'   => $pagenum,
	) );

	$db_fields = false;
	if ( is_post_type_hierarchical( $post_type_name ) ) {
		$db_fields = array( 'parent' => 'post_parent', 'id' => 'ID' );
	}

	$walker = new Walker_Nav_Menu_Checklist( $db_fields );

	$current_tab = 'most-recent';
	if ( isset( $_REQUEST[ $post_type_name . '-tab' ] ) && in_array( $_REQUEST[ $post_type_name . '-tab' ], array(
			'all',
			'search',
		) )
	) {
		$current_tab = $_REQUEST[ $post_type_name . '-tab' ];
	}

	if ( ! empty( $_REQUEST[ 'quick-search-posttype-' . $post_type_name ] ) ) {
		$current_tab = 'search';
	}

	$removed_args = array(
		'action',
		'customlink-tab',
		'edit-menu-item',
		'menu-item',
		'page-tab',
		'_wpnonce',
	);

	?>
	<div id="posttype-<?php echo $post_type_name; ?>" class="posttypediv">
		<ul id="posttype-<?php echo $post_type_name; ?>-tabs" class="posttype-tabs category-tabs add-menu-item-tabs">
			<li <?php echo( 'most-recent' == $current_tab ? ' class="tabs"' : '' ); ?>>
				<a class="nav-tab-link" data-type="tabs-panel-posttype-<?php echo esc_attr( $post_type_name ); ?>-most-recent" href="<?php if ( $nav_menu_selected_id ) {
					echo esc_url( add_query_arg( $post_type_name . '-tab', 'most-recent', remove_query_arg( $removed_args ) ) );
				} ?>#tabs-panel-posttype-<?php echo $post_type_name; ?>-most-recent">
					<?php _e( 'Most Recent' ); ?>
				</a>
			</li>
			<li <?php echo( 'all' == $current_tab ? ' class="tabs"' : '' ); ?>>
				<a class="nav-tab-link" data-type="<?php echo esc_attr( $post_type_name ); ?>-all" href="<?php if ( $nav_menu_selected_id ) {
					echo esc_url( add_query_arg( $post_type_name . '-tab', 'all', remove_query_arg( $removed_args ) ) );
				} ?>#<?php echo $post_type_name; ?>-all">
					<?php _e( 'View All' ); ?>
				</a>
			</li>
			<li <?php echo( 'search' == $current_tab ? ' class="tabs"' : '' ); ?>>
				<a class="nav-tab-link" data-type="tabs-panel-posttype-<?php echo esc_attr( $post_type_name ); ?>-search" href="<?php if ( $nav_menu_selected_id ) {
					echo esc_url( add_query_arg( $post_type_name . '-tab', 'search', remove_query_arg( $removed_args ) ) );
				} ?>#tabs-panel-posttype-<?php echo $post_type_name; ?>-search">
					<?php _e( 'Search' ); ?>
				</a>
			</li>
		</ul>
		<!-- .posttype-tabs -->

		<div id="tabs-panel-posttype-<?php echo $post_type_name; ?>-most-recent" class="tabs-panel <?php
		echo( 'most-recent' == $current_tab ? 'tabs-panel-active' : 'tabs-panel-inactive' );
		?>">
			<ul id="<?php echo $post_type_name; ?>checklist-most-recent" class="categorychecklist form-no-clear">
				<?php
				$recent_args    = array_merge( $args, array(
					'orderby'        => 'post_date',
					'order'          => 'DESC',
					'posts_per_page' => 15,
				) );
				$most_recent    = $get_posts->query( $recent_args );
				$args['walker'] = $walker;
				echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $most_recent ), 0, (object) $args );
				?>
			</ul>
		</div>
		<!-- /.tabs-panel -->

		<div class="tabs-panel <?php
		echo( 'search' == $current_tab ? 'tabs-panel-active' : 'tabs-panel-inactive' );
		?>" id="tabs-panel-posttype-<?php echo $post_type_name; ?>-search">
			<?php
			if ( isset( $_REQUEST[ 'quick-search-posttype-' . $post_type_name ] ) ) {
				$searched       = esc_attr( $_REQUEST[ 'quick-search-posttype-' . $post_type_name ] );
				$search_results = get_posts( array(
					's'         => $searched,
					'post_type' => $post_type_name,
					'fields'    => 'all',
					'order'     => 'DESC',
				) );
			} else {
				$searched       = '';
				$search_results = array();
			}
			?>
			<p class="quick-search-wrap">
				<input type="search" class="quick-search input-with-default-title" title="<?php esc_attr_e( 'Search' ); ?>" value="<?php echo $searched; ?>" name="quick-search-posttype-<?php echo $post_type_name; ?>"/>
				<span class="spinner"></span>
				<?php submit_button( __( 'Search' ), 'button-small quick-search-submit button-secondary hide-if-js', 'submit', false, array( 'id' => 'submit-quick-search-posttype-' . $post_type_name ) ); ?>
			</p>

			<ul id="<?php echo $post_type_name; ?>-search-checklist" data-wp-lists="list:<?php echo $post_type_name ?>" class="categorychecklist form-no-clear">
				<?php if ( ! empty( $search_results ) && ! is_wp_error( $search_results ) ) : ?>
					<?php
					$args['walker'] = $walker;
					echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $search_results ), 0, (object) $args );
					?>
				<?php elseif ( is_wp_error( $search_results ) ) : ?>
					<li><?php echo $search_results->get_error_message(); ?></li>
				<?php elseif ( ! empty( $searched ) ) : ?>
					<li><?php _e( 'No results found.' ); ?></li>
				<?php endif; ?>
			</ul>
		</div>
		<!-- /.tabs-panel -->

		<div id="<?php echo $post_type_name; ?>-all" class="tabs-panel tabs-panel-view-all <?php
		echo( 'all' == $current_tab ? 'tabs-panel-active' : 'tabs-panel-inactive' );
		?>">
			<?php if ( ! empty( $page_links ) ) : ?>
				<div class="add-menu-item-pagelinks">
					<?php echo $page_links; ?>
				</div>
			<?php endif; ?>
			<ul id="<?php echo $post_type_name; ?>checklist" data-wp-lists="list:<?php echo $post_type_name ?>" class="categorychecklist form-no-clear">
				<?php
				$args['walker'] = $walker;

				/*
				 * If we're dealing with pages, let's put a checkbox for the front
				 * page at the top of the list.
				 */
				if ( 'page' == $post_type_name ) {
					$front_page = 'page' == get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
					if ( ! empty( $front_page ) ) {
						$front_page_obj                = get_post( $front_page );
						$front_page_obj->front_or_home = true;
						array_unshift( $posts, $front_page_obj );
					} else {
						$_nav_menu_placeholder = ( 0 > $_nav_menu_placeholder ) ? intval( $_nav_menu_placeholder ) - 1 : - 1;
						array_unshift( $posts, (object) array(
							'front_or_home' => true,
							'ID'            => 0,
							'object_id'     => $_nav_menu_placeholder,
							'post_content'  => '',
							'post_excerpt'  => '',
							'post_parent'   => '',
							'post_title'    => _x( 'Home', 'nav menu home label' ),
							'post_type'     => 'nav_menu_item',
							'type'          => 'custom',
							'url'           => home_url( '/' ),
						) );
					}
				}

				/**
				 * Filter the posts displayed in the 'View All' tab of the current
				 * post type's menu items meta box.
				 *
				 * The dynamic portion of the hook name, $post_type_name,
				 * refers to the slug of the current post type.
				 *
				 * @since 3.2.0
				 *
				 * @see   WP_Query::query()
				 *
				 * @param array  $posts     The posts for the current post type.
				 * @param array  $args      An array of WP_Query arguments.
				 * @param object $post_type The current post type object for this menu item meta box.
				 */
				$posts          = apply_filters( "nav_menu_items_{$post_type_name}", $posts, $args, $post_type );
				$checkbox_items = walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $posts ), 0, (object) $args );

				if ( 'all' == $current_tab && ! empty( $_REQUEST['selectall'] ) ) {
					$checkbox_items = preg_replace( '/(type=(.)checkbox(\2))/', '$1 checked=$2checked$2', $checkbox_items );

				}

				echo $checkbox_items;
				?>
			</ul>
			<?php if ( ! empty( $page_links ) ) : ?>
				<div class="add-menu-item-pagelinks">
					<?php echo $page_links; ?>
				</div>
			<?php endif; ?>
		</div>
		<!-- /.tabs-panel -->

		<p class="button-controls">
			<span class="list-controls">
				<a href="<?php
				echo esc_url( add_query_arg(
					array(
						$post_type_name . '-tab' => 'all',
						'selectall'              => 1,
					),
					remove_query_arg( $removed_args )
				) );
				?>#posttype-<?php echo $post_type_name; ?>" class="select-all"><?php _e( 'Select All' ); ?></a>
			</span>

			<span class="add-to-list">
				<button type="button" <?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button-secondary submit-add-to-menu right" id="<?php echo esc_attr( 'submit-posttype-' . $post_type_name ); ?>"><?php esc_attr_e( 'Add Selected' ); ?></button>
				<span class="spinner"></span>
			</span>
		</p>

	</div><!-- /.posttypediv -->
	<?php
}

/**
 * Displays a metabox for a taxonomy menu item.
 *
 * @since      1.0.0
 * @deprecated 1.4
 *
 * @param string $taxonomy The taxonomy object.
 */
function popmake_taxonomy_item_metabox( $taxonomy_name ) {
	if ( ! function_exists( 'wp_nav_menu_item_post_type_meta_box' ) ) {
		include ABSPATH . 'wp-admin/includes/nav-menu.php';
	}
	global $nav_menu_selected_id;

	$taxonomy = get_taxonomy( $taxonomy_name );

	// Paginate browsing for large numbers of objects.
	$per_page = 50;
	$pagenum  = isset( $_REQUEST[ $taxonomy_name . '-tab' ] ) && isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 1;
	$offset   = 0 < $pagenum ? $per_page * ( $pagenum - 1 ) : 0;

	$args = array(
		'child_of'     => 0,
		'exclude'      => '',
		'hide_empty'   => false,
		'hierarchical' => 1,
		'include'      => '',
		'number'       => $per_page,
		'offset'       => $offset,
		'order'        => 'ASC',
		'orderby'      => 'name',
		'pad_counts'   => false,
	);

	$terms = get_terms( $taxonomy_name, $args );

	if ( ! $terms || is_wp_error( $terms ) ) {
		echo '<p>' . __( 'No items.' ) . '</p>';

		return;
	}

	$num_pages = ceil( wp_count_terms( $taxonomy_name, array_merge( $args, array(
			'number' => '',
			'offset' => '',
		) ) ) / $per_page );

	$page_links = paginate_links( array(
		'base'      => add_query_arg(
			array(
				$taxonomy_name . '-tab' => 'all',
				'paged'                 => '%#%',
				'item-type'             => 'taxonomy',
				'item-object'           => $taxonomy_name,
			)
		),
		'format'    => '',
		'prev_text' => __( '&laquo;' ),
		'next_text' => __( '&raquo;' ),
		'total'     => $num_pages,
		'current'   => $pagenum,
	) );

	$db_fields = false;
	if ( is_taxonomy_hierarchical( $taxonomy_name ) ) {
		$db_fields = array( 'parent' => 'parent', 'id' => 'term_id' );
	}

	$walker = new Walker_Nav_Menu_Checklist( $db_fields );

	$current_tab = 'most-used';
	if ( isset( $_REQUEST[ $taxonomy_name . '-tab' ] ) && in_array( $_REQUEST[ $taxonomy_name . '-tab' ], array(
			'all',
			'most-used',
			'search',
		) )
	) {
		$current_tab = $_REQUEST[ $taxonomy_name . '-tab' ];
	}

	if ( ! empty( $_REQUEST[ 'quick-search-taxonomy-' . $taxonomy_name ] ) ) {
		$current_tab = 'search';
	}

	$removed_args = array(
		'action',
		'customlink-tab',
		'edit-menu-item',
		'menu-item',
		'page-tab',
		'_wpnonce',
	);

	?>
	<div id="taxonomy-<?php echo $taxonomy_name; ?>" class="taxonomydiv">
		<ul id="taxonomy-<?php echo $taxonomy_name; ?>-tabs" class="taxonomy-tabs add-menu-item-tabs">
			<li <?php echo( 'most-used' == $current_tab ? ' class="tabs"' : '' ); ?>>
				<a class="nav-tab-link" data-type="tabs-panel-<?php echo esc_attr( $taxonomy_name ); ?>-pop" href="<?php if ( $nav_menu_selected_id ) {
					echo esc_url( add_query_arg( $taxonomy_name . '-tab', 'most-used', remove_query_arg( $removed_args ) ) );
				} ?>#tabs-panel-<?php echo $taxonomy_name; ?>-pop">
					<?php _e( 'Most Used' ); ?>
				</a>
			</li>
			<li <?php echo( 'all' == $current_tab ? ' class="tabs"' : '' ); ?>>
				<a class="nav-tab-link" data-type="tabs-panel-<?php echo esc_attr( $taxonomy_name ); ?>-all" href="<?php if ( $nav_menu_selected_id ) {
					echo esc_url( add_query_arg( $taxonomy_name . '-tab', 'all', remove_query_arg( $removed_args ) ) );
				} ?>#tabs-panel-<?php echo $taxonomy_name; ?>-all">
					<?php _e( 'View All' ); ?>
				</a>
			</li>
			<li <?php echo( 'search' == $current_tab ? ' class="tabs"' : '' ); ?>>
				<a class="nav-tab-link" data-type="tabs-panel-search-taxonomy-<?php echo esc_attr( $taxonomy_name ); ?>" href="<?php if ( $nav_menu_selected_id ) {
					echo esc_url( add_query_arg( $taxonomy_name . '-tab', 'search', remove_query_arg( $removed_args ) ) );
				} ?>#tabs-panel-search-taxonomy-<?php echo $taxonomy_name; ?>">
					<?php _e( 'Search' ); ?>
				</a>
			</li>
		</ul>
		<!-- .taxonomy-tabs -->

		<div id="tabs-panel-<?php echo $taxonomy_name; ?>-pop" class="tabs-panel <?php
		echo( 'most-used' == $current_tab ? 'tabs-panel-active' : 'tabs-panel-inactive' );
		?>">
			<ul id="<?php echo $taxonomy_name; ?>checklist-pop" class="categorychecklist form-no-clear">
				<?php
				$popular_terms  = get_terms( $taxonomy_name, array(
					'orderby'      => 'count',
					'order'        => 'DESC',
					'number'       => 10,
					'hierarchical' => false,
				) );
				$args['walker'] = $walker;
				echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $popular_terms ), 0, (object) $args );
				?>
			</ul>
		</div>
		<!-- /.tabs-panel -->

		<div id="tabs-panel-<?php echo $taxonomy_name; ?>-all" class="tabs-panel tabs-panel-view-all <?php
		echo( 'all' == $current_tab ? 'tabs-panel-active' : 'tabs-panel-inactive' );
		?>">
			<?php if ( ! empty( $page_links ) ) : ?>
				<div class="add-menu-item-pagelinks">
					<?php echo $page_links; ?>
				</div>
			<?php endif; ?>
			<ul id="<?php echo $taxonomy_name; ?>checklist" data-wp-lists="list:<?php echo $taxonomy_name ?>" class="categorychecklist form-no-clear">
				<?php
				$args['walker'] = $walker;
				echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $terms ), 0, (object) $args );
				?>
			</ul>
			<?php if ( ! empty( $page_links ) ) : ?>
				<div class="add-menu-item-pagelinks">
					<?php echo $page_links; ?>
				</div>
			<?php endif; ?>
		</div>
		<!-- /.tabs-panel -->

		<div class="tabs-panel <?php
		echo( 'search' == $current_tab ? 'tabs-panel-active' : 'tabs-panel-inactive' );
		?>" id="tabs-panel-search-taxonomy-<?php echo $taxonomy_name; ?>">
			<?php
			if ( isset( $_REQUEST[ 'quick-search-taxonomy-' . $taxonomy_name ] ) ) {
				$searched       = esc_attr( $_REQUEST[ 'quick-search-taxonomy-' . $taxonomy_name ] );
				$search_results = get_terms( $taxonomy_name, array(
					'name__like'   => $searched,
					'fields'       => 'all',
					'orderby'      => 'count',
					'order'        => 'DESC',
					'hierarchical' => false,
				) );
			} else {
				$searched       = '';
				$search_results = array();
			}
			?>
			<p class="quick-search-wrap">
				<input type="search" class="quick-search input-with-default-title" title="<?php esc_attr_e( 'Search' ); ?>" value="<?php echo $searched; ?>" name="quick-search-taxonomy-<?php echo $taxonomy_name; ?>"/>
				<span class="spinner"></span>
				<?php submit_button( __( 'Search' ), 'button-small quick-search-submit button-secondary hide-if-js', 'submit', false, array( 'id' => 'submit-quick-search-taxonomy-' . $taxonomy_name ) ); ?>
			</p>

			<ul id="<?php echo $taxonomy_name; ?>-search-checklist" data-wp-lists="list:<?php echo $taxonomy_name ?>" class="categorychecklist form-no-clear">
				<?php if ( ! empty( $search_results ) && ! is_wp_error( $search_results ) ) : ?>
					<?php
					$args['walker'] = $walker;
					echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $search_results ), 0, (object) $args );
					?>
				<?php elseif ( is_wp_error( $search_results ) ) : ?>
					<li><?php echo $search_results->get_error_message(); ?></li>
				<?php elseif ( ! empty( $searched ) ) : ?>
					<li><?php _e( 'No results found.' ); ?></li>
				<?php endif; ?>
			</ul>
		</div>
		<!-- /.tabs-panel -->

		<p class="button-controls">
			<span class="list-controls">
				<a href="<?php
				echo esc_url( add_query_arg(
					array(
						$taxonomy_name . '-tab' => 'all',
						'selectall'             => 1,
					),
					remove_query_arg( $removed_args )
				) );
				?>#taxonomy-<?php echo $taxonomy_name; ?>" class="select-all"><?php _e( 'Select All' ); ?></a>
			</span>

			<span class="add-to-menu">
				<input type="submit"<?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu' ); ?>" name="add-taxonomy-menu-item" id="<?php echo esc_attr( 'submit-taxonomy-' . $taxonomy_name ); ?>"/>
				<span class="spinner"></span>
			</span>
		</p>

	</div><!-- /.taxonomydiv -->
	<?php
}

#endregion Meta Boxes Render Fields
