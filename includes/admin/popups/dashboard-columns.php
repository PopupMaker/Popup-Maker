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
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Popups Columns
 *
 * Defines the custom columns and their order
 *
 * @since 1.0
 * @param array $popup_columns Array of popup columns
 * @return array $popup_columns Updated array of popup columns for Popups
 *  Post Type List Table
 */
function popmake_popup_columns( $popup_columns ) {
	$popup_columns = array(
		'cb'				=> '<input type="checkbox"/>',
		'title'				=> __( 'Name', 'popup-maker' ),
		'class'				=> __( 'CSS Classes', 'popup-maker' ),
		'popup_title'		=> __( 'Title', 'popup-maker' ),
		'popup_category'	=> __( 'Categories', 'popup-maker' ),
		'popup_tag'			=> __( 'Tags', 'popup-maker' ),
		//'date'				=> __( 'Date', 'popup-maker' )
	);
	return apply_filters( 'popmake_popup_columns', $popup_columns );
}
add_filter( 'manage_edit-popup_columns', 'popmake_popup_columns' );

/**
 * Render Popup Columns
 *
 * @since 1.0
 * @param string $column_name Column name
 * @param int $post_id Popup (Post) ID
 * @return void
 */
function popmake_render_popup_columns( $column_name, $post_id ) {
	if ( get_post_type( $post_id ) == 'popup' ) {
		global $popmake_options;

		$post = get_post( $post_id );
		setup_postdata( $post );

		$post_type_object = get_post_type_object( $post->post_type );
		$can_edit_post = current_user_can( $post_type_object->cap->edit_post, $post->ID );

		switch ( $column_name ) {
			case 'popup_title': echo '<strong>'. popmake_get_the_popup_title( $post_id ) .'</strong>';
				break;
			case 'popup_category':
				echo get_the_term_list( $post_id, 'popup_category', '', ', ', '');
				break;
			case 'popup_tag':
				echo get_the_term_list( $post_id, 'popup_tag', '', ', ', '');
				break;
			case 'class':
				echo '<pre style="display:inline-block;margin:0;"><code>popmake-' . absint( $post_id ) . '</code></pre>';
				if($post->post_name != $post->ID) {
					echo '|';
					echo '<pre style="display:inline-block;margin:0;"><code>popmake-' . $post->post_name . '</code></pre>';
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
 * @param array $columns Array of the columns
 * @return array $columns Array of sortable columns
 */
function popmake_sortable_popup_columns( $columns ) {
	$columns['popup_title']    = 'popup_title';
	return $columns;
}
add_filter( 'manage_edit-popup_sortable_columns', 'popmake_sortable_popup_columns' );

/**
 * Sorts Columns in the Popups List Table
 *
 * @since 1.0
 * @param array $vars Array of all the sort variables
 * @return array $vars Array of all the sort variables
 */
function popmake_sort_popups( $vars ) {
	// Check if we're viewing the "popup" post type
	if ( isset( $vars['post_type'] ) && 'popup' == $vars['post_type'] ) {
		// Check if 'orderby' is set to "name"
		if ( isset( $vars['orderby'] ) && 'popup_title' == $vars['orderby'] ) {
			$vars = array_merge(
				$vars,
				array(
					'meta_key' => 'popup_title',
					'orderby'  => 'meta_value',
				)
			);
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
	if ( $typenow == 'popup') {
		$terms = get_terms( 'popup_category' );
		if ( count( $terms ) > 0 ) {
			echo "<select name='popup_category' id='popup_category' class='postform'>";
				echo "<option value=''>" . __( 'Show all categories', 'popup-maker' ) . "</option>";
				foreach ( $terms as $term ) {
					$selected = isset( $_GET['popup_category'] ) && $_GET['popup_category'] == $term->slug ? ' selected="selected"' : '';
					echo '<option value="' . esc_attr( $term->slug ) . '"' . $selected . '>' . esc_html( $term->name ) .' (' . $term->count .')</option>';
				}
			echo "</select>";
		}

		$terms = get_terms( 'popup_tag' );
		if ( count( $terms ) > 0) {
			echo "<select name='popup_tag' id='popup_tag' class='postform'>";
				echo "<option value=''>" . __( 'Show all tags', 'popup-maker' ) . "</option>";
				foreach ( $terms as $term ) {
					$selected = isset( $_GET['popup_tag']) && $_GET['popup_tag'] == $term->slug ? ' selected="selected"' : '';
					echo '<option value="' . esc_attr( $term->slug ) . '"' . $selected . '>' . esc_html( $term->name ) .' (' . $term->count .')</option>';
				}
			echo "</select>";
		}
	}

}
add_action( 'restrict_manage_posts', 'popmake_add_popup_filters', 100 );

/**
 * Remove Popup Month Filter
 *
 * Removes the drop down filter for popups by date.
 *
 * @author Daniel Iser
 * @since 1.0
 * @param array $dates The preset array of dates
 * @global $typenow The post type we are viewing
 * @return array Empty array disables the dropdown
 */
function popmake_remove_month_filter( $dates ) {
	global $typenow;

	if ( $typenow == 'popup' ) {
		$dates = array();
	}

	return $dates;
}
add_filter( 'months_dropdown_results', 'popmake_remove_month_filter', 99 );

/**
 * Adds price field to Quick Edit options
 *
 * @since 1.0
 * @param string $column_name Name of the column
 * @param string $post_type Current Post Type (i.e. popup)
 * @return void
 */
function popmake_price_field_quick_edit( $column_name, $post_type ) {
	if ( $column_name != 'price' || $post_type != 'popup' ) return;
	?>
	<fieldset class="inline-edit-col-left">
		<div id="edd-popup-data" class="inline-edit-col">
			<h4><?php echo sprintf( __( '%s Configuration', 'popup-maker' ), popmake_get_label_singular() ); ?></h4>
			<label>
				<span class="title"><?php _e( 'Price', 'popup-maker' ); ?></span>
				<span class="input-text-wrap">
					<input type="text" name="_popmake_regprice" class="text regprice" />
				</span>
			</label>
			<br class="clear" />
		</div>
	</fieldset>
	<?php
}
add_action( 'quick_edit_custom_box', 'popmake_price_field_quick_edit', 10, 2 );
add_action( 'bulk_edit_custom_box', 'popmake_price_field_quick_edit', 10, 2 );

/**
 * Updates price when saving post
 *
 * @since 1.0
 * @param int $post_id Popup (Post) ID
 * @return void
 */
function popmake_price_save_quick_edit( $post_id ) {
	if ( ! isset( $_POST['post_type']) || 'popup' !== $_POST['post_type'] ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return $post_id;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;

	if ( isset( $_REQUEST['_popmake_regprice'] ) ) {
		update_post_meta( $post_id, 'popmake_price', strip_tags( stripslashes( $_REQUEST['_popmake_regprice'] ) ) );
	}
}
add_action( 'save_post', 'popmake_price_save_quick_edit' );

/**
 * Process bulk edit actions via AJAX
 *
 * @since 1.0
 * @return void
 */
function popmake_save_bulk_edit() {
	$post_ids = ( isset( $_POST[ 'post_ids' ] ) && ! empty( $_POST[ 'post_ids' ] ) ) ? $_POST[ 'post_ids' ] : array();

	if ( ! empty( $post_ids ) && is_array( $post_ids ) ) {
		$price = isset( $_POST['price'] ) ? strip_tags( stripslashes( $_POST['price'] ) ) : 0;
		foreach ( $post_ids as $post_id ) {
			if ( ! empty( $price ) ) {
				update_post_meta( $post_id, 'popmake_price', popmake_sanitize_amount( $price ) );
			}
		}
	}

	die();
}
add_action( 'wp_ajax_popmake_save_bulk_edit', 'popmake_save_bulk_edit' );
