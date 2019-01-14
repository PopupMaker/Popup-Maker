<?php

class PUM_Types {

	/**
	 * Hook the initialize method to the WP init action.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 1 );
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 0 );
		add_filter( 'post_updated_messages', array( __CLASS__, 'updated_messages' ) );

		add_filter( 'wpseo_accessible_post_types', array( __CLASS__, 'yoast_sitemap_fix' ) );
	}

	/**
	 * Register post types
	 */
	public static function register_post_types() {
		if ( ! post_type_exists( 'popup' ) ) {
			$labels = PUM_Types::post_type_labels( __( 'Popup', 'popup-maker' ), __( 'Popups', 'popup-maker' ) );

			$labels['menu_name'] = __( 'Popup Maker', 'popup-maker' );

			$popup_args = apply_filters( 'popmake_popup_post_type_args', array(
				'labels'              => $labels,
				'public'              => true,
				'publicly_queryable'  => false,
				'query_var'           => false,
				'rewrite'             => false,
				'exclude_from_search' => true,
				'show_in_nav_menus'   => false,
				'show_ui'             => true,
				'menu_icon'           => POPMAKE_URL . '/assets/images/admin/dashboard-icon.png',
				'menu_position'       => 20.292892729,
				'supports'            => apply_filters( 'popmake_popup_supports', array(
					'title',
					'editor',
					'revisions',
					'author',
				) ),
				'show_in_rest'        => pum_get_option( 'gutenberg_support_enabled', false ), // Adds support for Gutenberg currently.
			) );

			// Temporary Yoast Fixes
			if ( is_admin() && isset( $_GET['page'] ) && $_GET['page'] === 'wpseo_titles' ) {
				$popup_args['public'] = false;
			}

			register_post_type( 'popup', apply_filters( 'pum_popup_post_type_args', $popup_args ) );
		}

		if ( ! post_type_exists( 'popup_theme' ) ) {
			$labels = PUM_Types::post_type_labels( __( 'Popup Theme', 'popup-maker' ), __( 'Popup Themes', 'popup-maker' ) );

			$labels['all_items'] = __( 'Popup Themes', 'popup-maker' );

			$labels = apply_filters( 'popmake_popup_theme_labels', $labels );

			register_post_type( 'popup_theme', apply_filters( 'popmake_popup_theme_post_type_args', array(
				'labels'            => $labels,
				'show_ui'           => true,
				'show_in_nav_menus' => false,
				'show_in_menu'      => 'edit.php?post_type=popup',
				'show_in_admin_bar' => false,
				'query_var'         => false,
				'rewrite'           => false,
				'supports'          => apply_filters( 'popmake_popup_theme_supports', array(
					'title',
					'revisions',
					'author',
				) ),
			) ) );
		}
	}

	/**
	 * @param $singular
	 * @param $plural
	 *
	 * @return mixed
	 */
	public static function post_type_labels( $singular, $plural ) {
		$labels = apply_filters( 'popmake_popup_labels', array(
			'name'               => '%2$s',
			'singular_name'      => '%1$s',
			'add_new_item'       => _x( 'Add New %1$s', 'Post Type Singular: "Popup", "Popup Theme"', 'popup-maker' ),
			'add_new'            => _x( 'Add %1$s', 'Post Type Singular: "Popup", "Popup Theme"', 'popup-maker' ),
			'edit_item'          => _x( 'Edit %1$s', 'Post Type Singular: "Popup", "Popup Theme"', 'popup-maker' ),
			'new_item'           => _x( 'New %1$s', 'Post Type Singular: "Popup", "Popup Theme"', 'popup-maker' ),
			'all_items'          => _x( 'All %2$s', 'Post Type Plural: "Popups", "Popup Themes"', 'popup-maker' ),
			'view_item'          => _x( 'View %1$s', 'Post Type Singular: "Popup", "Popup Theme"', 'popup-maker' ),
			'search_items'       => _x( 'Search %2$s', 'Post Type Plural: "Popups", "Popup Themes"', 'popup-maker' ),
			'not_found'          => _x( 'No %2$s found', 'Post Type Plural: "Popups", "Popup Themes"', 'popup-maker' ),
			'not_found_in_trash' => _x( 'No %2$s found in Trash', 'Post Type Plural: "Popups", "Popup Themes"', 'popup-maker' ),
		) );

		foreach ( $labels as $key => $value ) {
			$labels[ $key ] = sprintf( $value, $singular, $plural );
		}

		return $labels;
	}

	/**
	 * Register optional taxonomies.
	 *
	 * @param bool $force
	 */
	public static function register_taxonomies( $force = false ) {
		if ( ! $force && popmake_get_option( 'disable_popup_category_tag', false ) ) {
			return;
		}

		/** Categories */
		$category_labels = (array) get_taxonomy_labels( get_taxonomy( 'category' ) );

		$category_args = apply_filters( 'popmake_category_args', array(
			'hierarchical' => true,
			'labels'       => apply_filters( 'popmake_category_labels', $category_labels ),
			'public'       => false,
			'show_ui'      => true,
		) );
		register_taxonomy( 'popup_category', array( 'popup', 'popup_theme' ), $category_args );
		register_taxonomy_for_object_type( 'popup_category', 'popup' );
		register_taxonomy_for_object_type( 'popup_category', 'popup_theme' );

		/** Tags */

		$tag_labels = (array) get_taxonomy_labels( get_taxonomy( 'post_tag' ) );

		$tag_args = apply_filters( 'popmake_tag_args', array(
			'hierarchical' => false,
			'labels'       => apply_filters( 'popmake_tag_labels', $tag_labels ),
			'public'       => false,
			'show_ui'      => true,
		) );
		register_taxonomy( 'popup_tag', array( 'popup', 'popup_theme' ), $tag_args );
		register_taxonomy_for_object_type( 'popup_tag', 'popup' );
		register_taxonomy_for_object_type( 'popup_tag', 'popup_theme' );
	}

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
	public static function updated_messages( $messages ) {

		$labels = array(
			1 => _x( '%1$s updated.', 'Post Type Singular: Popup, Theme', 'popup-maker' ),
			4 => _x( '%1$s updated.', 'Post Type Singular: Popup, Theme', 'popup-maker' ),
			6 => _x( '%1$s published.', 'Post Type Singular: Popup, Theme', 'popup-maker' ),
			7 => _x( '%1$s saved.', 'Post Type Singular: Popup, Theme', 'popup-maker' ),
			8 => _x( '%1$s submitted.', 'Post Type Singular: Popup, Theme', 'popup-maker' ),
		);

		$messages['popup']       = array();
		$messages['popup_theme'] = array();

		$popup = __( 'Popup', 'popup-maker' );
		$theme = __( 'Popup Theme', 'popup-maker' );

		foreach ( $labels as $k => $string ) {
			$messages['popup'][ $k ]       = sprintf( $string, $popup );
			$messages['popup_theme'][ $k ] = sprintf( $string, $theme );
		}

		return $messages;
	}

	/**
	 * Remove popups from accessible post type list in Yoast.
	 *
	 * @param array $post_types
	 *
	 * @return array
	 */
	public static function yoast_sitemap_fix( $post_types = array() ) {
		unset( $post_types['popup'] );

		return $post_types;
	}


}