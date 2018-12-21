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
