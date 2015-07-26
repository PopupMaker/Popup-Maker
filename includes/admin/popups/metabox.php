<?php
/**
 * Metabox Functions
 *
 * @package     POPMAKE
 * @subpackage  Admin/Popups
 * @copyright   Copyright (c) 2014, Wizard Internet Solutions
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** All Popups *****************************************************************/

/**
 * Register all the meta boxes for the Popup custom post type
 *
 * @since 1.0
 * @return void
 */
function popmake_add_popup_meta_box() {
	/** Display Meta **/
	add_meta_box( 'popmake_popup_display', __( 'Display Settings', 'popup-maker' ), 'popmake_render_popup_display_meta_box', 'popup', 'normal', 'high' );
	/** Close Meta **/
	add_meta_box( 'popmake_popup_close', __( 'Close Settings', 'popup-maker' ), 'popmake_render_popup_close_meta_box', 'popup', 'normal', 'high' );
	/** Loading Meta **/
	add_meta_box( 'popmake_popup_targeting_condition', __( 'Targeting Conditions', 'popup-maker' ), 'popmake_render_popup_targeting_condition_meta_box', 'popup', 'side', 'high' );

	/** Theme Meta **/
	add_meta_box( 'popmake_popup_themes', __( 'Theme Settings', 'popup-maker' ), 'popmake_render_popup_themes_meta_box', 'popup', 'side', 'high' );

	/** Click Open Meta **/
	add_meta_box( 'popmake_popup_click_open', __( 'Click Open Settings', 'popup-maker' ), 'popmake_render_popup_click_open_meta_box', 'popup', 'side', 'default' );

	/** Auto Open Popups Meta **/
	add_meta_box( 'popmake_popup_auto_open', __( 'Auto Open Settings', 'popup-maker' ), 'popmake_render_popup_auto_open_meta_box', 'popup', 'normal', 'high' );

	/** Admin Debug **/
	add_meta_box( 'popmake_popup_admin_debug', __( 'Admin Debug Settings', 'popup-maker' ), 'popmake_render_popup_admin_debug_meta_box', 'popup', 'normal', 'low' );

	if ( ! popmake_get_option( 'disable_admin_support_widget', false ) ) {
		/** Support Meta **/
		add_meta_box( 'popmake_popup_support', __( 'Support', 'popup-maker' ), 'popmake_render_support_meta_box', 'popup', 'side', 'default' );
	}
	if ( ! popmake_get_option( 'disable_admin_share_widget', false ) ) {
		/** Share Meta **/
		add_meta_box( 'popmake_popup_share', __( 'Share', 'popup-maker' ), 'popmake_render_share_meta_box', 'popup', 'side', 'default' );
	}
}

add_action( 'add_meta_boxes', 'popmake_add_popup_meta_box' );


function popmake_popup_meta_fields() {
	$fields = array(
		'popup_defaults_set',
		'popup_title',
		'popup_theme',
		'popup_targeting_condition_on_entire_site',
		'popup_targeting_condition_on_home',
		'popup_targeting_condition_exclude_on_home',
		'popup_targeting_condition_on_blog',
		'popup_targeting_condition_exclude_on_blog',
		'popup_targeting_condition_on_search',
		'popup_targeting_condition_exclude_on_search',
		'popup_targeting_condition_on_404',
		'popup_targeting_condition_exclude_on_404',
	);
	foreach ( popmake_popup_meta_field_groups() as $group ) {
		foreach ( apply_filters( 'popmake_popup_meta_field_group_' . $group, array() ) as $field ) {
			$fields[] = 'popup_' . $group . '_' . $field;
		}
	}
	foreach ( popmake_get_supported_types() as $pt ) {
		$labels   = get_post_type_object( $pt ) ? get_post_type_object( $pt ) : get_taxonomy( $pt );
		$plural   = $pt . 's';
		$fields[] = "popup_targeting_condition_on_{$plural}";
		$fields[] = "popup_targeting_condition_exclude_on_{$plural}";
		$fields[] = "popup_targeting_condition_on_specific_{$plural}";
		$fields[] = "popup_targeting_condition_exclude_on_specific_{$plural}";
	}

	return apply_filters( 'popmake_popup_meta_fields', $fields );
}

function popmake_popup_meta_field_groups() {
	$groups = array(
		'display',
		'close',
		'click_open',
		'auto_open',
		'admin_debug',
	);

	return apply_filters( 'popmake_popup_meta_field_groups', $groups );
}


function popmake_popup_meta_field_group_display() {
	return array(
		'stackable',
		'scrollable_content',
		'overlay_disabled',
		'size',
		'responsive_min_width',
		'responsive_min_width_unit',
		'responsive_max_width',
		'responsive_max_width_unit',
		'custom_width',
		'custom_width_unit',
		'custom_height',
		'custom_height_unit',
		'custom_height_auto',
		'location',
		'position_top',
		'position_left',
		'position_bottom',
		'position_right',
		'position_fixed',
		'animation_type',
		'animation_speed',
		'animation_origin',
		'overlay_zindex',
		'zindex',
	);
}

add_filter( 'popmake_popup_meta_field_group_display', 'popmake_popup_meta_field_group_display', 0 );


function popmake_popup_meta_field_group_close() {
	return array(
		'text',
		'button_delay',
		'overlay_click',
		'esc_press',
		'f4_press',
	);
}

add_filter( 'popmake_popup_meta_field_group_close', 'popmake_popup_meta_field_group_close', 0 );


function popmake_popup_meta_field_group_click_open() {
	return array(
		'extra_selectors',
	);
}

add_filter( 'popmake_popup_meta_field_group_click_open', 'popmake_popup_meta_field_group_click_open', 0 );


function popmake_popup_meta_field_group_auto_open( $fields ) {
	return array_merge( $fields, array(
		'enabled',
		'delay',
		'cookie_trigger',
		'session_cookie',
		'cookie_time',
		'cookie_path',
		'cookie_key'
	) );
}

add_filter( 'popmake_popup_meta_field_group_auto_open', 'popmake_popup_meta_field_group_auto_open', 0 );


function popmake_popup_meta_field_group_admin_debug( $fields ) {
	return array_merge( $fields, array(
		'enabled',
	) );
}

add_filter( 'popmake_popup_meta_field_group_admin_debug', 'popmake_popup_meta_field_group_admin_debug', 0 );


/**
 * Save post meta when the save_post action is called
 *
 * @since 1.0
 *
 * @param int $post_id Popup (Post) ID
 *
 * @global array $post All the data of the the current post
 * @return void
 */
function popmake_popup_meta_box_save( $post_id, $post ) {

	if ( isset( $post->post_type ) && 'popup' != $post->post_type ) {
		return;
	}

	if ( ! isset( $_POST['popmake_popup_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['popmake_popup_meta_box_nonce'], basename( __FILE__ ) ) ) {
		return;
	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
		return;
	}

	if ( isset( $post->post_type ) && 'revision' == $post->post_type ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$field_prefix = Popmake_Popup_Fields::instance()->field_prefix;

	foreach ( Popmake_Popup_Fields::instance()->get_all_fields() as $section => $fields ) {

		$section_prefix = "{$field_prefix}{$section}";

		$meta_values = array();

		foreach ( $fields as $field => $args ) {

			$field_name = "{$section_prefix}_{$field}";

			if ( isset( $_POST[ $field_name ] ) ) {
				$meta_values[ $field ] = apply_filters( 'popmake_metabox_save_' . $field_name, $_POST[ $field_name ] );
			}

		}

		update_post_meta( $post_id, "popup_{$section}", $meta_values );

	}


	foreach ( popmake_popup_meta_fields() as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			$new = apply_filters( 'popmake_metabox_save_' . $field, $_POST[ $field ] );
			update_post_meta( $post_id, $field, $new );
		} else {
			delete_post_meta( $post_id, $field );
		}
	}

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
	do_action( 'popmake_save_popup', $post_id, $post );
}

add_action( 'save_post', 'popmake_popup_meta_box_save', 10, 2 );


function popmake_metabox_save_popup_auto_open_cookie_key( $field = '' ) {
	if ( $field == '' ) {
		$field = uniqid();
	}

	return $field;
}

add_filter( 'popmake_metabox_save_popup_auto_open_cookie_key', 'popmake_metabox_save_popup_auto_open_cookie_key' );


function popmake_set_popup_slug( $data, $postarr ) {
	if ( $data['post_type'] == 'popup' ) {
		$popup_name        = popmake_post( 'popup_name' );
		$post_slug         = sanitize_title_with_dashes( $popup_name, null, 'save' );
		$data['post_name'] = wp_unique_post_slug( sanitize_title( popmake_post( 'popup_name' ) ), $postarr['ID'], $data['post_status'], $data['post_type'], $data['post_parent'] );
	}

	return $data;
}

add_filter( 'wp_insert_post_data', 'popmake_set_popup_slug', 99, 2 );


/** Popup Configuration *****************************************************************/

function popmake_popup_title_meta() {
	global $post;
	if ( popmake_is_admin_popup_page() ) { ?>
		<div id="popuptitlediv">
		<div id="popuptitlewrap">
			<label class="screen-reader-text" id="popuptitle-prompt-text" for="popuptitle"><?php _e( 'Enter popup title here', 'popup-maker' ); ?></label>
			<input type="text" tabindex="2" name="popup_title" size="30" value="<?php esc_attr_e( popmake_get_the_popup_title( $post->ID ) ); ?>" id="popuptitle" autocomplete="off" placeholder="<?php _e( 'Enter popup title here', 'popup-maker' ); ?>"/>
		</div>
		<div class="inside">
		</div>
		</div><?php
	}
}

add_action( 'edit_form_advanced', 'popmake_popup_title_meta' );
add_action( 'edit_page_form', 'popmake_popup_title_meta' );


/**
 * Popup Display Metabox
 *
 * Extensions (as well as the core plugin) can add items to the popup display
 * configuration metabox via the `popmake_popup_display_meta_box_fields` action.
 *
 * @since 1.0
 * @return void
 */
function popmake_render_popup_display_meta_box() {
	global $post, $popmake_options;
	wp_nonce_field( basename( __FILE__ ), 'popmake_popup_meta_box_nonce' ); ?>
	<input type="hidden" name="popup_defaults_set" value="true"/>
	<div id="popmake_popup_display_fields" class="popmake_meta_table_wrap">
	<table class="form-table">
		<tbody>
			<?php do_action( 'popmake_popup_display_meta_box_fields', $post->ID ); ?>
		</tbody>
	</table>
	</div><?php
}


/**
 * Popup Click Open Metabox
 *
 * Extensions (as well as the core plugin) can add items to the popup click_open
 * configuration metabox via the `popmake_popup_click_open_meta_box_fields` action.
 *
 * @since 1.1.0
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
 * Popup Close Metabox
 *
 * Extensions (as well as the core plugin) can add items to the popup close
 * configuration metabox via the `popmake_popup_close_meta_box_fields` action.
 *
 * @since 1.0
 * @return void
 */
function popmake_render_popup_close_meta_box() {
	global $post, $popmake_options; ?>
	<div id="popmake_popup_close_fields" class="popmake_meta_table_wrap">
	<table class="form-table">
		<tbody>
			<?php do_action( 'popmake_popup_close_meta_box_fields', $post->ID ); ?>
		</tbody>
	</table>
	</div><?php
}


/**
 * Popup Theme Metabox
 *
 * Extensions (as well as the core plugin) can add items to the popup display
 * configuration metabox via the `popmake_popup_themes_meta_box_fields` action.
 *
 * @since 1.0
 * @return void
 */
function popmake_render_popup_themes_meta_box() {
	global $post ?>
	<div id="popmake_popup_themes_fields" class="popmake_meta_table_wrap">
	<table class="form-table">
		<tbody>
			<?php do_action( 'popmake_popup_themes_meta_box_fields', $post->ID ); ?>
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
 * @since 1.0
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
 * @since 1.0
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
 * @since 1.0
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


/**
 * Adds Popup meta fields to revisions.
 *
 * @since 1.0
 * @return array $fields Array of fields.
 */
function popmake_popup_post_revision_fields( $fields ) {
	$popup_fields = popmake_popup_meta_fields();
	foreach ( $popup_fields as $field ) {
		$fields[ $field ] = __( ucwords( str_replace( '_', ' ', str_replace( 'popup_', '', $field ) ) ), 'popup-maker' );
	}

	return $fields;
}

add_filter( '_wp_post_revision_fields', 'popmake_popup_post_revision_fields' );


function popmake_popup_revision_field( $value, $field, $revision ) {
	return get_metadata( 'post', $revision->ID, $field, true );
}


function popmake_add_popup_revision_fields() {
	foreach ( popmake_popup_meta_fields() as $field ) {
		add_filter( '_wp_post_revision_field_' . $field, 'popmake_popup_revision_field', 10, 3 );
	}
}

add_action( 'plugins_loaded', 'popmake_add_popup_revision_fields' );


function popmake_popup_meta_restore_revision( $post_id, $revision_id ) {
	$post = get_post( $post_id );
	if ( $post->post_type != 'popup' ) {
		return;
	}
	$revision = get_post( $revision_id );
	foreach ( popmake_popup_meta_fields() as $field ) {
		$meta = get_metadata( 'post', $revision->ID, $field, true );
		if ( false === $meta ) {
			delete_post_meta( $post_id, $field );
		} else {
			update_post_meta( $post_id, $field, $meta );
		}
	}
}

add_action( 'wp_restore_post_revision', 'popmake_popup_meta_restore_revision', 10, 2 );

function popmake_popup_meta_save_revision( $post_id, $post ) {
	if ( $post->post_type != 'popup' ) {
		return;
	}
	if ( $parent_id = wp_is_post_revision( $post_id ) ) {
		foreach ( popmake_popup_meta_fields() as $field ) {
			$meta = get_post_meta( $parent_id, $field, true );
			if ( false !== $meta ) {
				add_metadata( 'post', $post_id, $field, $meta );
			}

		}
	}
}

add_action( 'save_post', 'popmake_popup_meta_save_revision', 11, 2 );
