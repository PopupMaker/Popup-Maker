<?php
/**
 * Post Type Functions
 *
 * @package        POPMAKE
 * @subpackage    Functions
 * @copyright    Copyright (c) 2014, Wizard Internet Solutions
 * @license        http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function popmake_setup_post_types() {
	global $popup_post_type, $popup_theme_post_type;

	$popup_labels = apply_filters( 'popmake_popup_labels', array(
		'name'               => '%2$s',
		'singular_name'      => '%1$s',
		'add_new'            => __( 'Add New', 'popup-maker' ),
		'add_new_item'       => __( 'Add New %1$s', 'popup-maker' ),
		'edit_item'          => __( 'Edit %1$s', 'popup-maker' ),
		'new_item'           => __( 'New %1$s', 'popup-maker' ),
		'all_items'          => __( 'All %2$s', 'popup-maker' ),
		'view_item'          => __( 'View %1$s', 'popup-maker' ),
		'search_items'       => __( 'Search %2$s', 'popup-maker' ),
		'not_found'          => __( 'No %2$s found', 'popup-maker' ),
		'not_found_in_trash' => __( 'No %2$s found in Trash', 'popup-maker' ),
		'parent_item_colon'  => '',
		'menu_name'          => __( POPMAKE_NAME, 'popup-maker' )
	) );

	foreach ( $popup_labels as $key => $value ) {
		$popup_labels[ $key ] = sprintf( $value, popmake_get_label_singular( 'popup' ), popmake_get_label_plural( 'popup' ) );
	}

	$popup_args      = array(
		'labels'        => $popup_labels,
		'show_ui'       => true,
		'query_var'     => false,
		'menu_icon'     => POPMAKE_URL . '/assets/images/admin/dashboard-icon.png',
		'menu_position' => 20.292892729,
		'supports'      => apply_filters( 'popmake_popup_supports', array( 'title', 'editor', 'revisions', 'author' ) ),
	);
	$popup_post_type = register_post_type( 'popup', apply_filters( 'popmake_popup_post_type_args', $popup_args ) );

	$popup_theme_labels = apply_filters( 'popmake_popup_theme_labels', array(
		'name'               => '%2$s',
		'singular_name'      => '%1$s',
		'add_new'            => __( 'Add New', 'popup-maker' ),
		'add_new_item'       => __( 'Add New %1$s', 'popup-maker' ),
		'edit_item'          => __( 'Edit %1$s', 'popup-maker' ),
		'new_item'           => __( 'New %1$s', 'popup-maker' ),
		'all_items'          => __( 'All %2$s', 'popup-maker' ),
		'view_item'          => __( 'View %1$s', 'popup-maker' ),
		'search_items'       => __( 'Search %2$s', 'popup-maker' ),
		'not_found'          => __( 'No %2$s found', 'popup-maker' ),
		'not_found_in_trash' => __( 'No %2$s found in Trash', 'popup-maker' ),
		'parent_item_colon'  => '',
	) );

	foreach ( $popup_theme_labels as $key => $value ) {
		$popup_theme_labels[ $key ] = sprintf( $value, popmake_get_label_singular( 'popup_theme' ), popmake_get_label_plural( 'popup_theme' ) );
	}

	$popup_theme_args      = array(
		'labels'            => $popup_theme_labels,
		'show_ui'           => true,
		'show_in_nav_menus' => false,
		'show_in_menu'      => 'edit.php?post_type=popup',
		'show_in_admin_bar' => false,
		'query_var'         => false,
		'supports'          => apply_filters( 'popmake_popup_theme_supports', array( 'title', 'revisions', 'author' ) ),
	);
	$popup_theme_post_type = register_post_type( 'popup_theme', apply_filters( 'popmake_popup_theme_post_type_args', $popup_theme_args ) );


}

add_action( 'init', 'popmake_setup_post_types', 1 );

/**
 * Get Default Labels
 *
 * @since 1.0
 * @return array $defaults Default labels
 */
function popmake_get_default_labels( $post_type = 'popup' ) {
	$defaults = apply_filters( 'popmake_default_post_type_name', array(
		'popup'       => array(
			'singular' => __( 'Popup', 'popup-maker' ),
			'plural'   => __( 'Popups', 'popup-maker' )
		),
		'popup_theme' => array(
			'singular' => __( 'Theme', 'popup-maker' ),
			'plural'   => __( 'Themes', 'popup-maker' )
		)
	) );

	return isset( $defaults[ $post_type ] ) ? $defaults[ $post_type ] : $defaults['popup'];
}

/**
 * Get Singular Label
 *
 * @since 1.0
 *
 * @param bool $lowercase
 *
 * @return string $defaults['singular'] Singular label
 */
function popmake_get_label_singular( $post_type = 'popup', $lowercase = false ) {
	$defaults = popmake_get_default_labels( $post_type );

	return ( $lowercase ) ? strtolower( $defaults['singular'] ) : $defaults['singular'];
}

/**
 * Get Plural Label
 *
 * @since 1.0
 * @return string $defaults['plural'] Plural label
 */
function popmake_get_label_plural( $post_type = 'popup', $lowercase = false ) {
	$defaults = popmake_get_default_labels( $post_type );

	return ( $lowercase ) ? strtolower( $defaults['plural'] ) : $defaults['plural'];
}

/**
 * Change default "Enter title here" input
 *
 * @since 1.0
 *
 * @param string $title Default title placeholder text
 *
 * @return string $title New placeholder text
 */
function popmake_change_default_title( $title ) {

	if ( ! is_admin() ) {
		return $title;
	}

	$screen = get_current_screen();

	if ( 'popup' == $screen->post_type || 'popup_theme' == $screen->post_type ) {
		$label = popmake_get_label_singular( $screen->post_type, false );
		if ( 'popup' == $screen->post_type ) {
			$title = sprintf( __( '%s Name', 'popup-maker' ), $label );
		} else {
			$title = sprintf( __( 'Enter %s name here', 'popup-maker' ), $label );
		}
	}

	return $title;
}

add_filter( 'enter_title_here', 'popmake_change_default_title' );

/**
 * Registers the custom taxonomies for the downloads custom post type
 *
 * @since 1.0
 *
 * @param bool $force_load
 */
function popmake_setup_taxonomies( $force_load = false ) {
	return;

	if ( ! $force_load && popmake_get_option( 'disable_popup_category_tag', false ) ) {
		return;
	}

	/** Categories */
	$category_labels = array(
		'name'                  => sprintf( _x( '%s Categories', 'taxonomy general name', 'popup-maker' ), popmake_get_label_singular() ),
		'singular_name'         => _x( 'Category', 'taxonomy singular name', 'popup-maker' ),
		'search_items'          => __( 'Search Categories', 'popup-maker' ),
		'all_items'             => __( 'All Categories', 'popup-maker' ),
		'parent_item'           => __( 'Parent Category', 'popup-maker' ),
		'parent_item_colon'     => __( 'Parent Category:', 'popup-maker' ),
		'edit_item'             => __( 'Edit Category', 'popup-maker' ),
		'update_item'           => __( 'Update Category', 'popup-maker' ),
		'add_new_item'          => sprintf( __( 'Add New %s Category', 'popup-maker' ), popmake_get_label_singular() ),
		'new_item_name'         => __( 'New Category Name', 'popup-maker' ),
		'menu_name'             => __( 'Categories', 'popup-maker' ),
		'choose_from_most_used' => sprintf( __( 'Choose from most used %s categories', 'popup-maker' ), popmake_get_label_singular() ),
	);

	$category_args = apply_filters( 'popmake_category_args', array(
			'hierarchical' => true,
			'labels'       => apply_filters( 'popmake_category_labels', $category_labels ),
			'public'       => false,
			'show_ui'      => true,
		)
	);
	register_taxonomy( 'popup_category', array( 'popup', 'popup_theme' ), $category_args );
	register_taxonomy_for_object_type( 'popup_category', 'popup' );
	register_taxonomy_for_object_type( 'popup_category', 'popup_theme' );

	/** Tags */
	$tag_labels = array(
		'name'                  => sprintf( _x( '%s Tags', 'taxonomy general name', 'popup-maker' ), popmake_get_label_singular() ),
		'singular_name'         => _x( 'Tag', 'taxonomy singular name', 'popup-maker' ),
		'search_items'          => __( 'Search Tags', 'popup-maker' ),
		'all_items'             => __( 'All Tags', 'popup-maker' ),
		'parent_item'           => __( 'Parent Tag', 'popup-maker' ),
		'parent_item_colon'     => __( 'Parent Tag:', 'popup-maker' ),
		'edit_item'             => __( 'Edit Tag', 'popup-maker' ),
		'update_item'           => __( 'Update Tag', 'popup-maker' ),
		'add_new_item'          => __( 'Add New Tag', 'popup-maker' ),
		'new_item_name'         => __( 'New Tag Name', 'popup-maker' ),
		'menu_name'             => __( 'Tags', 'popup-maker' ),
		'choose_from_most_used' => sprintf( __( 'Choose from most used %s tags', 'popup-maker' ), popmake_get_label_singular() ),
	);

	$tag_args = apply_filters( 'popmake_tag_args', array(
			'hierarchical' => false,
			'labels'       => apply_filters( 'popmake_tag_labels', $tag_labels ),
			'public'       => false,
			'show_ui'      => true,
		)
	);
	register_taxonomy( 'popup_tag', array( 'popup', 'popup_theme' ), $tag_args );
	register_taxonomy_for_object_type( 'popup_tag', 'popup' );
	register_taxonomy_for_object_type( 'popup_tag', 'popup_theme' );
}

add_action( 'init', 'popmake_setup_taxonomies', 0 );

/**
 * Registers Custom Post Statuses
 *
 * @since 1.0
 * @return void
 */
function popmake_register_post_type_statuses() {
	register_post_status( 'inactive', array(
		'label'                     => _x( 'Inactive', 'Inactive status', 'popup-maker' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', 'popup-maker' )
	) );
}

//add_action( 'init', 'popmake_register_post_type_statuses' );

/**
 * Updated Messages
 *
 * Returns an array of with all updated messages.
 *
 * @since 1.0
 *
 * @param array $messages Post updated message
 *
 * @return array $messages New post updated messages
 */
function popmake_updated_messages( $messages ) {
	global $post, $post_ID;

	$label             = popmake_get_label_singular();
	$messages['popup'] = array(
		1 => sprintf( __( '%1$s updated.', 'popup-maker' ), $label ),
		4 => sprintf( __( '%1$s updated.', 'popup-maker' ), $label ),
		6 => sprintf( __( '%1$s published.', 'popup-maker' ), $label ),
		7 => sprintf( __( '%1$s saved.', 'popup-maker' ), $label ),
		8 => sprintf( __( '%1$s submitted.', 'popup-maker' ), $label )
	);

	$label                   = popmake_get_label_singular( 'popup_theme' );
	$messages['popup_theme'] = array(
		1 => sprintf( __( '%1$s updated.', 'popup-maker' ), $label ),
		4 => sprintf( __( '%1$s updated.', 'popup-maker' ), $label ),
		6 => sprintf( __( '%1$s published.', 'popup-maker' ), $label ),
		7 => sprintf( __( '%1$s saved.', 'popup-maker' ), $label ),
		8 => sprintf( __( '%1$s submitted.', 'popup-maker' ), $label )
	);

	return $messages;
}

add_filter( 'post_updated_messages', 'popmake_updated_messages' );

function popmake_get_supported_types( $type = null, $collapse = true ) {
	$types = array(
		'post_type' => apply_filters( 'popmake_supported_post_types', array( 'post', 'page' ) ),
		'taxonomy'  => apply_filters( 'popmake_supported_taxonomies', array( 'category', 'post_tag' ) )
	);

	if ( $type ) {
		return $types[ $type ];
	} elseif ( $collapse ) {
		return array_merge( $types['post_type'], $types['taxonomy'] );
	}

	return $types;
}


function popmake_supported_post_types( $post_types = array() ) {
	global $popmake_options;
	if ( empty( $popmake_options['supported_post_types'] ) || ! is_array( $popmake_options['supported_post_types'] ) ) {
		return $post_types;
	}

	return array_merge( $post_types, array_values( $popmake_options['supported_post_types'] ) );
}

add_filter( 'popmake_supported_post_types', 'popmake_supported_post_types' );


function popmake_supported_taxonomies( $taxonomies = array() ) {
	global $popmake_options;
	if ( empty( $popmake_options['supported_taxonomies'] ) || ! is_array( $popmake_options['supported_taxonomies'] ) ) {
		return $taxonomies;
	}

	return array_merge( $taxonomies, array_values( $popmake_options['supported_taxonomies'] ) );
}

add_filter( 'popmake_supported_taxonomies', 'popmake_supported_taxonomies' );
