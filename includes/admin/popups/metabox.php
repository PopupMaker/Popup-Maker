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

	/** Theme Meta **/
	add_meta_box( 'popmake_popup_themes', __( 'Theme Settings', 'popup-maker' ), 'popmake_render_popup_themes_meta_box', 'popup', 'side', 'high' );

}
add_action( 'add_meta_boxes', 'popmake_add_popup_meta_box' );


function popmake_popup_meta_fields() {
	$fields = array(
		'popup_title',
		'popup_theme',
	);
	foreach ( popmake_popup_meta_field_groups() as $group ) {
		foreach ( apply_filters( 'popmake_popup_meta_field_group_' . $group, array() ) as $field ) {
			$fields[] = 'popup_' . $group . '_' . $field;
		}
	}

	return apply_filters( 'popmake_popup_meta_fields', $fields );
}

function popmake_popup_meta_field_groups() {
	return apply_filters( 'popmake_popup_meta_field_groups', array() );
}


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


    // TODO Remove this and all other code here. This should be clean and all code more compartmentalized.
	foreach ( popmake_popup_meta_fields() as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			$new = apply_filters( 'popmake_metabox_save_' . $field, $_POST[ $field ] );
			update_post_meta( $post_id, $field, $new );
		} else {
			delete_post_meta( $post_id, $field );
		}
	}

	do_action( 'pum_save_popup', $post_id, $post );
}

add_action( 'save_post', 'popmake_popup_meta_box_save', 10, 2 );

/**
 * Ensures that the popups have unique slugs.
 *
 * @param $data
 * @param $postarr
 *
 * @return mixed
 */
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
