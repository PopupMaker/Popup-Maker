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

	/** Preview Window **/
	add_meta_box( 'popmake_popup_theme_preview', __( 'Theme Preview', 'popup-maker' ), 'popmake_render_popup_theme_preview_meta_box', 'popup_theme', 'side', 'low' );

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

}

add_action( 'add_meta_boxes', 'popmake_add_popup_theme_meta_box' );


function popmake_popup_theme_meta_fields() {
	$fields = array(
		'popup_theme_defaults_set',
	);
	foreach ( popmake_popup_theme_meta_field_groups() as $group ) {
		foreach ( apply_filters( 'popmake_popup_theme_meta_field_group_' . $group, array() ) as $field ) {
			$fields[] = 'popup_theme_' . $group . '_' . $field;
		}
	}

	return apply_filters( 'popmake_popup_theme_meta_fields', $fields );
}


function popmake_popup_theme_meta_field_groups() {
	return apply_filters( 'popmake_popup_theme_meta_field_groups', array() );
}


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

	// If this is a built in theme and the user has modified it set a key so that we know not to make automatic upgrades to it in the future.
	if ( get_post_meta( $post_id, '_pum_built_in', true ) !== false ) {
		update_post_meta( $post_id, '_pum_user_modified', true );
	}

	pum_force_theme_css_refresh();

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
				<div class="content">
					<?php do_action( 'popmake_example_popup_content' ); ?>
				</div>
				<a class="close-popup">&#215;</a>
			</div>
			<p class="pum-desc"><?php
				$tips = array(
					__( 'If you move this theme preview to the bottom of your sidebar here it will follow you down the page?', 'popup-maker' ),
					__( 'Clicking on an element in this theme preview will take you to its relevant settings in the editor?', 'popup-maker' ),
				);
				$key  = array_rand( $tips, 1 ); ?>
				<i class="dashicons dashicons-info"></i> <?php echo '<strong>' . __( 'Did you know:', 'popup-maker' ) . '</strong>  ' . $tips[ $key ]; ?>
			</p>
		</div>
	</div>

	<?php
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
