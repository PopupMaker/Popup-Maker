<?php
/**
 * Dashboard Columns
 *
 * @package POPMAKE
 * @subpackage  Admin/Popups
 * @copyright   Copyright (c) 2014, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Popups Columns
 *
 * Defines the custom columns and their order
 *
 * @since 1.0
 *
 * @param array $popup_columns Array of popup columns
 *
 * @return array $popup_columns Updated array of popup columns for Popups
 *  Post Type List Table
 */
function popmake_popup_columns( $popup_columns ) {
	$popup_columns = array(
		'cb'          => '<input type="checkbox"/>',
		'title'       => __( 'Name', 'popup-maker' ),
		'class'       => __( 'CSS Classes', 'popup-maker' ),
		'opens'       => __( 'Opened', 'popup-maker' ),
		'popup_title' => __( 'Title', 'popup-maker' ),
	);

	if ( get_taxonomy( 'popup_tag' ) ) {
		$popup_columns['popup_tag'] = __( 'Tags', 'popup-maker' );
	}

	if ( get_taxonomy( 'popup_category' ) ) {
		$popup_columns['popup_category'] = __( 'Categories', 'popup-maker' );
	}

	return apply_filters( 'popmake_popup_columns', $popup_columns );
}

add_filter( 'manage_edit-popup_columns', 'popmake_popup_columns' );

/**
 * Render Popup Columns
 *
 * @since 1.0
 *
 * @param string $column_name Column name
 * @param int $post_id Popup (Post) ID
 *
 * @return void
 */
function popmake_render_popup_columns( $column_name, $post_id ) {
	if ( get_post_type( $post_id ) == 'popup' ) {
		global $popmake_options;

		$post = get_post( $post_id );

		$popup = new PUM_Popup( $post_id );
		setup_postdata( $popup );

		/**
		 * Uncomment if need to check for permissions on certain columns.
		 *          *
		 * $post_type_object = get_post_type_object( $popup->post_type );
		 * $can_edit_post    = current_user_can( $post_type_object->cap->edit_post, $popup->ID );
		 */

		switch ( $column_name ) {
			case 'popup_title':
				echo '<strong>' . esc_html( $popup->get_title() ) . '</strong>';
				break;
			case 'popup_category':
				echo get_the_term_list( $post_id, 'popup_category', '', ', ', '' );
				break;
			case 'popup_tag':
				echo get_the_term_list( $post_id, 'popup_tag', '', ', ', '' );
				break;
			case 'class':
				echo '<pre style="display:inline-block;margin:0;"><code>popmake-' . absint( $post_id ) . '</code></pre>';
				if ( $popup->post_name != $popup->ID ) {
					echo '|';
					echo '<pre style="display:inline-block;margin:0;"><code>popmake-' . $popup->post_name . '</code></pre>';
				}
				break;
			case 'opens':
				if ( ! class_exists( 'PUM_Advanced_Analytics' ) && ! class_exists( 'PopMake_Popup_Analytics' ) ) {
					echo '<strong>' . $popup->get_open_count() . '</strong>';
				}
				break;
		}
	}
}

add_action( 'manage_posts_custom_column', 'popmake_render_popup_columns', 10, 2 );

/**
 * Registers the sortable columns in the list table
 *
 * @since 1.0
 *
 * @param array $columns Array of the columns
 *
 * @return array $columns Array of sortable columns
 */
function popmake_sortable_popup_columns( $columns ) {
	$columns['popup_title'] = 'popup_title';
	$columns['opens']       = 'opens';

	return $columns;
}

add_filter( 'manage_edit-popup_sortable_columns', 'popmake_sortable_popup_columns' );

/**
 * Sorts Columns in the Popups List Table
 *
 * @since 1.0
 *
 * @param array $vars Array of all the sort variables
 *
 * @return array $vars Array of all the sort variables
 */
function popmake_sort_popups( $vars ) {
	// Check if we're viewing the "popup" post type
	if ( isset( $vars['post_type'] ) && 'popup' == $vars['post_type'] ) {
		// Check if 'orderby' is set to "name"
		if ( isset( $vars['orderby'] ) ) {
			switch ( $vars['orderby'] ) {
				case 'popup_title':
					$vars = array_merge( $vars, array(
							'meta_key' => 'popup_title',
							'orderby'  => 'meta_value',
						) );
					break;
				case 'opens':
					if ( ! class_exists( 'PUM_Advanced_Analytics' ) && ! class_exists( 'PopMake_Popup_Analytics' ) ) {
						$vars = array_merge( $vars, array(
							'meta_key' => 'popup_open_count',
							'orderby'  => 'meta_value_num',
						) );
					}
					break;
			}
		}
	}

	return $vars;
}

/**
 * Popup Load
 *
 * Sorts the popups.
 *
 * @since 1.0
 * @return void
 */
function popmake_popup_load() {
	add_filter( 'request', 'popmake_sort_popups' );
}

add_action( 'load-edit.php', 'popmake_popup_load', 9999 );

/**
 * Add Popup Filters
 *
 * Adds taxonomy drop down filters for popups.
 *
 * @since 1.0
 * @return void
 */
function popmake_add_popup_filters() {
	global $typenow;

	// Checks if the current post type is 'popup'
	if ( $typenow == 'popup' ) {

		if ( get_taxonomy( 'popup_category' ) ) {
			$terms = get_terms( 'popup_category' );
			if ( count( $terms ) > 0 ) {
				echo "<select name='popup_category' id='popup_category' class='postform'>";
				echo "<option value=''>" . __( 'Show all categories', 'popup-maker' ) . "</option>";
				foreach ( $terms as $term ) {
					$selected = isset( $_GET['popup_category'] ) && $_GET['popup_category'] == $term->slug ? ' selected="selected"' : '';
					echo '<option value="' . esc_attr( $term->slug ) . '"' . $selected . '>' . esc_html( $term->name ) . ' (' . $term->count . ')</option>';
				}
				echo "</select>";
			}
		}

		if ( get_taxonomy( 'popup_tag' ) ) {
			$terms = get_terms( 'popup_tag' );
			if ( count( $terms ) > 0 ) {
				echo "<select name='popup_tag' id='popup_tag' class='postform'>";
				echo "<option value=''>" . __( 'Show all tags', 'popup-maker' ) . "</option>";
				foreach ( $terms as $term ) {
					$selected = isset( $_GET['popup_tag'] ) && $_GET['popup_tag'] == $term->slug ? ' selected="selected"' : '';
					echo '<option value="' . esc_attr( $term->slug ) . '"' . $selected . '>' . esc_html( $term->name ) . ' (' . $term->count . ')</option>';
				}
				echo "</select>";
			}
		}
	}

}

add_action( 'restrict_manage_posts', 'popmake_add_popup_filters', 100 );
