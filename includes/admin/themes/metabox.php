<?php
/**
 * Metabox Functions
 *
 * @package     POPMAKE
 * @subpackage  Admin/Themes
 * @copyright   Copyright (c) 2014, Wizard Internet Solutions
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** All Themes *****************************************************************/

/**
 * Register all the meta boxes for the Theme custom post type
 *
 * @since 1.0
 * @return void
 */
function popmake_add_popup_theme_meta_box() {

	$singular = popmake_get_label_singular( 'popup_theme' );
	$plural   = popmake_get_label_plural( 'popup_theme' );

	/** Preview Window **/
	add_meta_box( 'popmake_popup_theme_preview', __( 'Theme Preview', 'popup-maker' ), 'popmake_render_popup_theme_preview_meta_box', 'popup_theme', 'side', 'high' );

	/** Overlay Meta **/
	add_meta_box( 'popmake_popup_theme_overlay', __( 'Overlay Settings', 'popup-maker' ), 'popmake_render_popup_theme_overlay_meta_box', 'popup_theme', 'normal', 'high' );

	/** Container Meta **/
	add_meta_box( 'popmake_popup_theme_container', __( 'Container Settings', 'popup-maker' ), 'popmake_render_popup_theme_container_meta_box', 'popup_theme', 'normal', 'high' );

	/** Title Meta **/
	add_meta_box( 'popmake_popup_theme_title', __( 'Title Settings', 'popup-maker' ), 'popmake_render_popup_theme_title_meta_box', 'popup_theme', 'normal', 'high' );

	/** Content Meta **/
	add_meta_box( 'popmake_popup_theme_content', __( 'Content Settings', 'popup-maker' ), 'popmake_render_popup_theme_content_meta_box', 'popup_theme', 'normal', 'high' );

	/** Close Meta **/
	add_meta_box( 'popmake_popup_theme_close', __( 'Close Settings', 'popup-maker' ), 'popmake_render_popup_theme_close_meta_box', 'popup_theme', 'normal', 'high' );

	if ( ! popmake_get_option( 'disable_admin_support_widget', false ) ) {
		/** Support Meta **/
		add_meta_box( 'popmake_popup_support', __( 'Support', 'popup-maker' ), 'popmake_render_support_meta_box', 'popup_theme', 'side', 'default' );
	}
	if ( ! popmake_get_option( 'disable_admin_share_widget', false ) ) {
		/** Share Meta **/
		add_meta_box( 'popmake_popup_share', __( 'Share', 'popup-maker' ), 'popmake_render_share_meta_box', 'popup_theme', 'side', 'default' );
	}
}

add_action( 'add_meta_boxes', 'popmake_add_popup_theme_meta_box' );


function popmake_popup_theme_meta_fields() {
	$fields = array(
		'popup_theme_defaults_set'
	);
	foreach ( popmake_popup_theme_meta_field_groups() as $group ) {
		foreach ( apply_filters( 'popmake_popup_theme_meta_field_group_' . $group, array() ) as $field ) {
			$fields[] = 'popup_theme_' . $group . '_' . $field;
		}
	}

	return apply_filters( 'popmake_popup_theme_meta_fields', $fields );
}


function popmake_popup_theme_meta_field_groups() {
	$groups = array(
		'overlay',
		'container',
		'title',
		'content',
		'close'
	);

	return apply_filters( 'popmake_popup_theme_meta_field_groups', $groups );
}


function popmake_popup_theme_meta_field_group_overlay() {
	return array(
		'background_color',
		'background_opacity'
	);
}

add_filter( 'popmake_popup_theme_meta_field_group_overlay', 'popmake_popup_theme_meta_field_group_overlay', 0 );


function popmake_popup_theme_meta_field_group_container() {
	return array(
		'padding',
		'background_color',
		'background_opacity',
		'border_radius',
		'border_style',
		'border_color',
		'border_width',
		'boxshadow_inset',
		'boxshadow_horizontal',
		'boxshadow_vertical',
		'boxshadow_blur',
		'boxshadow_spread',
		'boxshadow_color',
		'boxshadow_opacity',
	);
}

add_filter( 'popmake_popup_theme_meta_field_group_container', 'popmake_popup_theme_meta_field_group_container', 0 );


function popmake_popup_theme_meta_field_group_title() {
	return array(
		'font_color',
		'line_height',
		'font_size',
		'font_family',
		'font_weight',
		'font_style',
		'text_align',
		'textshadow_horizontal',
		'textshadow_vertical',
		'textshadow_blur',
		'textshadow_color',
		'textshadow_opacity',
	);
}

add_filter( 'popmake_popup_theme_meta_field_group_title', 'popmake_popup_theme_meta_field_group_title', 0 );


function popmake_popup_theme_meta_field_group_content() {
	return array(
		'font_color',
		'font_family',
		'font_weight',
		'font_style',
	);
}

add_filter( 'popmake_popup_theme_meta_field_group_content', 'popmake_popup_theme_meta_field_group_content', 0 );


function popmake_popup_theme_meta_field_group_close() {
	return array(
		'text',
		'padding',
		'height',
		'width',
		'location',
		'position_top',
		'position_left',
		'position_bottom',
		'position_right',
		'line_height',
		'font_color',
		'font_size',
		'font_family',
		'font_weight',
		'font_style',
		'background_color',
		'background_opacity',
		'border_radius',
		'border_style',
		'border_color',
		'border_width',
		'boxshadow_inset',
		'boxshadow_horizontal',
		'boxshadow_vertical',
		'boxshadow_blur',
		'boxshadow_spread',
		'boxshadow_color',
		'boxshadow_opacity',
		'textshadow_horizontal',
		'textshadow_vertical',
		'textshadow_blur',
		'textshadow_color',
		'textshadow_opacity',
	);
}

add_filter( 'popmake_popup_theme_meta_field_group_close', 'popmake_popup_theme_meta_field_group_close', 0 );


/**
 * Save post meta when the save_post action is called
 *
 * @since 1.0
 *
 * @param int $post_id Theme (Post) ID
 *
 * @global array $post All the data of the the current post
 * @return void
 */
function popmake_popup_theme_meta_box_save( $post_id, $post ) {

	if ( isset( $post->post_type ) && 'popup_theme' != $post->post_type ) {
		return;
	}

	if ( ! isset( $_POST['popmake_popup_theme_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['popmake_popup_theme_meta_box_nonce'], basename( __FILE__ ) ) ) {
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

	$field_prefix = Popmake_Popup_Theme_Fields::instance()->field_prefix;

	foreach ( Popmake_Popup_Theme_Fields::instance()->get_all_fields() as $section => $fields ) {

		$section_prefix = "{$field_prefix}{$section}";

		$meta_values = array();

		foreach ( $fields as $field => $args ) {

			$field_name = "{$section_prefix}_{$field}";

			if ( isset( $_POST[ $field_name ] ) ) {
				$meta_values[ $field ] = apply_filters( 'popmake_metabox_save_' . $field_name, $_POST[ $field_name ] );
			}

		}

		update_post_meta( $post_id, "popup_theme_{$section}", $meta_values );

	}


	foreach ( popmake_popup_theme_meta_fields() as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			$new = apply_filters( 'popmake_metabox_save_' . $field, $_POST[ $field ] );
			update_post_meta( $post_id, $field, $new );
		} else {
			delete_post_meta( $post_id, $field );
		}
	}

	delete_transient( 'popmake_theme_styles' );

	do_action( 'popmake_save_popup_theme', $post_id, $post );
}

add_action( 'save_post', 'popmake_popup_theme_meta_box_save', 10, 2 );


/** Theme Configuration *****************************************************************/

/**
 * Theme Preview Metabox
 *
 * Extensions (as well as the core plugin) can add items to the theme preview
 * configuration metabox via the `popmake_popup_theme_preview_meta_box_fields` action.
 *
 * @since 1.0
 * @return void
 */
function popmake_render_popup_theme_preview_meta_box() { ?>
	<div class="empreview">
	<div id="PopMake-Preview">
		<div class="example-popup-overlay"></div>
		<div class="example-popup">
			<div class="title"><?php _e( 'Title Text', 'popup-maker' ); ?></div>
			<div class="content"><?php do_action( 'popmake_example_popup_content' ); ?></div>
			<a class="close-popup"><?php _e( '&#215;', 'popup-maker' ); ?></a>
		</div>
	</div>
	</div><?php
}


/**
 * Theme Overlay Metabox
 *
 * Extensions (as well as the core plugin) can add items to the theme overlay
 * configuration metabox via the `popmake_popup_theme_overlay_meta_box_fields` action.
 *
 * @since 1.0
 * @return void
 */
function popmake_render_popup_theme_overlay_meta_box() {
	global $post, $popmake_options;
	wp_nonce_field( basename( __FILE__ ), 'popmake_popup_theme_meta_box_nonce' ); ?>
	<input type="hidden" name="popup_theme_defaults_set" value="true"/>
	<div id="popmake_popup_theme_overlay_fields" class="popmake_meta_table_wrap">
	<table class="form-table">
		<tbody>
			<?php do_action( 'popmake_popup_theme_overlay_meta_box_fields', $post->ID ); ?>
		</tbody>
	</table>
	</div><?php
}


/**
 * Theme Container Metabox
 *
 * Extensions (as well as the core plugin) can add items to the theme container
 * configuration metabox via the `popmake_popup_theme_container_meta_box_fields` action.
 *
 * @since 1.0
 * @return void
 */
function popmake_render_popup_theme_container_meta_box() {
	global $post, $popmake_options; ?>
	<div id="popmake_popup_theme_container_fields" class="popmake_meta_table_wrap">
	<table class="form-table">
		<tbody>
			<?php do_action( 'popmake_popup_theme_container_meta_box_fields', $post->ID ); ?>
		</tbody>
	</table>
	</div><?php
}


/**
 * Theme Title Metabox
 *
 * Extensions (as well as the core plugin) can add items to the theme title
 * configuration metabox via the `popmake_popup_theme_title_meta_box_fields` action.
 *
 * @since 1.0
 * @return void
 */
function popmake_render_popup_theme_title_meta_box() {
	global $post, $popmake_options; ?>
	<div id="popmake_popup_theme_title_fields" class="popmake_meta_table_wrap">
	<table class="form-table">
		<tbody>
			<?php do_action( 'popmake_popup_theme_title_meta_box_fields', $post->ID ); ?>
		</tbody>
	</table>
	</div><?php
}


/**
 * Theme Content Metabox
 *
 * Extensions (as well as the core plugin) can add items to the theme content
 * configuration metabox via the `popmake_popup_theme_content_meta_box_fields` action.
 *
 * @since 1.0
 * @return void
 */
function popmake_render_popup_theme_content_meta_box() {
	global $post, $popmake_options; ?>
	<div id="popmake_popup_theme_content_fields" class="popmake_meta_table_wrap">
	<table class="form-table">
		<tbody>
			<?php do_action( 'popmake_popup_theme_content_meta_box_fields', $post->ID ); ?>
		</tbody>
	</table>
	</div><?php
}


/**
 * Theme Close Metabox
 *
 * Extensions (as well as the core plugin) can add items to the popup close
 * configuration metabox via the `popmake_popup_theme_close_meta_box_fields` action.
 *
 * @since 1.0
 * @return void
 */
function popmake_render_popup_theme_close_meta_box() {
	global $post, $popmake_options; ?>
	<div id="popmake_popup_theme_close_fields" class="popmake_meta_table_wrap">
	<table class="form-table">
		<tbody>
			<?php do_action( 'popmake_popup_theme_close_meta_box_fields', $post->ID ); ?>
		</tbody>
	</table>
	</div><?php
}


/**
 * Adds Popup Theme meta fields to revisions.
 *
 * @since 1.0
 * @return array $fields Array of fields.
 */
function popmake_popup_theme_post_revision_fields( $fields ) {
	$theme_fields = popmake_popup_theme_meta_fields();
	foreach ( $theme_fields as $field ) {
		$fields[ $field ] = __( 'Theme Overlay', ucwords( str_replace( '_', ' ', str_replace( 'popup_theme_', '', $field ) ) ), 'popup-maker' );
	}

	return $fields;
}

add_filter( '_wp_post_revision_fields', 'popmake_popup_theme_post_revision_fields' );


function popmake_popup_theme_revision_field( $value, $field, $revision ) {
	return get_metadata( 'post', $revision->ID, $field, true );
}


function popmake_add_popup_theme_revision_fields() {
	foreach ( popmake_popup_theme_meta_fields() as $field ) {
		add_filter( '_wp_post_revision_field_' . $field, 'popmake_popup_theme_revision_field', 10, 3 );
	}
}

add_action( 'plugins_loaded', 'popmake_add_popup_theme_revision_fields' );

function popmake_popup_theme_meta_restore_revision( $post_id, $revision_id ) {
	$post = get_post( $post_id );
	if ( $post->post_type != 'popup_theme' ) {
		return;
	}
	$revision = get_post( $revision_id );
	foreach ( popmake_popup_theme_meta_fields() as $field ) {
		$meta = get_metadata( 'post', $revision->ID, $field, true );
		if ( false === $meta ) {
			delete_post_meta( $post_id, $field );
		} else {
			update_post_meta( $post_id, $field, $meta );
		}
	}
}

add_action( 'wp_restore_post_revision', 'popmake_popup_theme_meta_restore_revision', 10, 2 );

function popmake_popup_theme_meta_save_revision( $post_id, $post ) {
	if ( $post->post_type != 'popup_theme' ) {
		return;
	}
	if ( $parent_id = wp_is_post_revision( $post_id ) ) {
		foreach ( popmake_popup_theme_meta_fields() as $field ) {
			$meta = get_post_meta( $parent_id, $field, true );
			if ( false !== $meta ) {
				add_metadata( 'post', $post_id, $field, $meta );
			}

		}
	}
}

add_action( 'save_post', 'popmake_popup_theme_meta_save_revision', 11, 2 );

