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
				'menu_icon'           => 'data:image/svg+xml;base64,' . base64_encode('<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid meet" viewBox="0 0 16 16" width="16" height="16"><defs><path d="M0.14 9.69C0.99 13.84 4.65 14.46 6.18 14.29C7.71 14.12 9.36 13.37 10.42 12.42C11.48 11.47 12.18 10.98 13.3 10.13C14.42 9.29 15.28 8.24 15.57 7.78C15.86 7.32 16.37 5.41 15.57 3.71C14.76 2.02 12.92 1.69 11.88 1.7C10.84 1.72 5.83 2.42 4.19 2.76C2.54 3.1 -0.71 5.54 0.14 9.69Z" id="d2cKlvMGiG"></path><clipPath id="clipe1dqYdHPtO"><use xlink:href="#d2cKlvMGiG" opacity="1"></use></clipPath><path d="M9.35 8.59C9.35 10.57 7.82 12.19 5.95 12.19C4.07 12.19 2.54 10.57 2.54 8.59C2.54 6.6 4.07 4.99 5.95 4.99C7.82 4.99 9.35 6.6 9.35 8.59Z" id="cdfsLVOC"></path><clipPath id="clipb7EwfuaVEB"><use xlink:href="#cdfsLVOC" opacity="1"></use></clipPath><path d="M9.04 9.39L8.35 10.9L9.11 11.66C9.12 11.66 9.12 11.66 9.12 11.66C10.01 10.71 10.41 9.4 10.19 8.11C10.19 8.09 10.18 8.04 10.16 7.95L9.11 8.01L9.04 9.39Z" id="apNChgYiM"></path><path d="M3.74 11.08L2.82 9.69L1.78 9.94C1.78 9.94 1.78 9.94 1.78 9.94C2.1 11.2 3 12.24 4.2 12.74C4.23 12.75 4.28 12.77 4.36 12.8L4.87 11.89L3.74 11.08Z" id="a767A2LuY"></path><path d="M4.96 5.33L6.62 5.17L6.89 4.14C6.89 4.14 6.89 4.14 6.89 4.14C5.62 3.83 4.29 4.14 3.28 4.96C3.26 4.97 3.22 5.01 3.15 5.07L3.72 5.95L4.96 5.33Z" id="bhrbq8GS0"></path><path d="M14.12 5.67C14.12 6.78 13.27 7.68 12.23 7.68C11.19 7.68 10.34 6.78 10.34 5.67C10.34 4.57 11.19 3.67 12.23 3.67C13.27 3.67 14.12 4.57 14.12 5.67Z" id="d2hpPQXk8"></path><clipPath id="clipaHD8R803"><use xlink:href="#d2hpPQXk8" opacity="1"></use></clipPath><path d="M12.51 2.83L13.24 3.05L13.02 4.27L12.02 3.98L12.51 2.83Z" id="d2kQRXgvan"></path><path d="M14.65 4.57L14.87 5.29L13.73 5.78L13.42 4.79L14.65 4.57Z" id="e4K79bJBs4"></path><path d="M14.34 7.35L13.77 7.85L12.85 7.02L13.62 6.33L14.34 7.35Z" id="aedMpv11P"></path><path d="M11.95 8.41L11.2 8.27L11.3 7.03L12.32 7.22L11.95 8.41Z" id="gurBgUNT"></path><path d="M9.69 6.69L9.58 5.95L10.78 5.62L10.95 6.64L9.69 6.69Z" id="baWqWWOLp"></path><path d="M10.05 3.9L10.62 3.4L11.54 4.24L10.76 4.93L10.05 3.9Z" id="aEHM8bjkQ"></path></defs><g><g><g><g clip-path="url(#clipe1dqYdHPtO)"><use xlink:href="#d2cKlvMGiG" opacity="1" fill-opacity="0" stroke="black" stroke-width="1.2" stroke-opacity="1"></use></g></g><g><g><g clip-path="url(#clipb7EwfuaVEB)"><use xlink:href="#cdfsLVOC" opacity="1" fill-opacity="0" stroke="black" stroke-width="1.6" stroke-opacity="1"></use></g></g><g><use xlink:href="#apNChgYiM" opacity="1" fill="black" fill-opacity="1"></use><g><use xlink:href="#apNChgYiM" opacity="1" fill-opacity="0" stroke="black" stroke-width="1" stroke-opacity="1"></use></g></g><g><use xlink:href="#a767A2LuY" opacity="1" fill="black" fill-opacity="1"></use><g><use xlink:href="#a767A2LuY" opacity="1" fill-opacity="0" stroke="black" stroke-width="1" stroke-opacity="1"></use></g></g><g><use xlink:href="#bhrbq8GS0" opacity="1" fill="black" fill-opacity="1"></use><g><use xlink:href="#bhrbq8GS0" opacity="1" fill-opacity="0" stroke="black" stroke-width="1" stroke-opacity="1"></use></g></g></g><g><g><g clip-path="url(#clipaHD8R803)"><use xlink:href="#d2hpPQXk8" opacity="1" fill-opacity="0" stroke="black" stroke-width="2.8" stroke-opacity="1"></use></g></g><g><use xlink:href="#d2kQRXgvan" opacity="1" fill="black" fill-opacity="1"></use></g><g><use xlink:href="#e4K79bJBs4" opacity="1" fill="black" fill-opacity="1"></use></g><g><use xlink:href="#aedMpv11P" opacity="1" fill="black" fill-opacity="1"></use></g><g><use xlink:href="#gurBgUNT" opacity="1" fill="black" fill-opacity="1"></use></g><g><use xlink:href="#baWqWWOLp" opacity="1" fill="black" fill-opacity="1"></use></g><g><use xlink:href="#aEHM8bjkQ" opacity="1" fill="black" fill-opacity="1"></use></g></g></g></g></svg>'),
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
