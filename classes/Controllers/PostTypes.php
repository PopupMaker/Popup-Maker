<?php
/**
 * Post type setup.
 *
 * @copyright (c) 2024, Code Atlantic LLC.
 * @package PopupMaker
 */

namespace PopupMaker\Controllers;

use PopupMaker\Plugin\Controller;

use function PopupMaker\get_data_version;

/**
 * Post type controller.
 *
 * @since 1.21.0
 */
class PostTypes extends Controller {

	/**
	 * Init controller.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_post_types' ] );
		add_action( 'save_post_popup', [ $this, 'save_post' ], 10, 3 );
		add_filter( 'post_updated_messages', [ $this, 'updated_messages' ] );

		// Control block editor usage on per popup post type basis.
		add_filter( 'use_block_editor_for_post_type', [ $this, 'use_block_editor_for_post_type' ], 10, 2 );
		// Blanket disable block editor for popup post types if setting is enabled.
		add_action( 'replace_editor', [ $this, 'replace_editor' ], 10, 2 );
	}

	/**
	 * Get post type keys.
	 *
	 * @return array<string,string> The post type keys.
	 */
	public function get_type_keys() {
		return [
			'popup'          => 'popup',
			'popup_theme'    => 'popup_theme',
			'popup_category' => 'popup_category',
			'popup_tag'      => 'popup_tag',
			'pum_cta'        => 'pum_cta',
		];
	}

	/**
	 * Get post type key.
	 *
	 * @param string $type The post type.
	 *
	 * @return string
	 */
	public function get_type_key( $type ) {
		return $this->get_type_keys()[ $type ];
	}

	/**
	 * Register post types.
	 *
	 * @return void
	 */
	public function register_post_types() {
		$this->register_popup_post_type();
		$this->register_popup_theme_post_type();
		$this->register_cta_post_type();

		$this->register_popup_category_tax();
		$this->register_popup_tag_tax();

		do_action( 'popup_maker/register_post_types' );
	}

	/**
	 * Register `popup` post type.
	 *
	 * @return void
	 */
	public function register_popup_post_type() {
		$post_type_key = $this->get_type_key( 'popup' );

		$popup_labels = $this->post_type_labels(
			__( 'Popup', 'popup-maker' ),
			__( 'Popups', 'popup-maker' ),
			$post_type_key
		);

		$popup_args = [
			'label'               => __( 'Popup', 'popup-maker' ),
			'labels'              => array_merge( $popup_labels, [
				'menu_name' => __( 'Popup Maker', 'popup-maker' ),
			] ),
			'description'         => '',
			// Basic.
			'public'              => true, // TODO Test false.
			// Visibility.
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_in_nav_menus'   => false,
			'show_ui'             => true,
			'show_in_menu'        => true, // TODO Test true.
			'show_in_admin_bar'   => false, // TODO Test true.
			// Archive.
			'has_archive'         => false,
			// Urls.
			'query_var'           => false,
			'rewrite'             => false,
			// Menu.
			'menu_icon'           => pum_get_svg_icon( true ),
			'menu_position'       => 20.292892729,
			// Features.
			'supports'            => [
				'title',
				'excerpt',
				'editor',
				'revisions',
				'author',
			],
			// Rest.
			'show_in_rest'        => true,
			'rest_base'           => 'popups',
			'rest_namespace'      => 'popup-maker/v2',
			'show_in_graphql'     => false,
			// Permissions.
			'can_export'          => true,
			'map_meta_cap'        => true,
			'delete_with_user'    => false,
			'capabilities'        => [
				'create_posts' => $this->container->get_permission( 'edit_popups' ),
				'edit_posts'   => $this->container->get_permission( 'edit_popups' ),
				'delete_posts' => $this->container->get_permission( 'edit_popups' ),
			],
		];

		/**
		 * Filter: popup_maker/popup_post_type_args
		 *
		 * @param array<string,mixed> $args Popup post type args.
		 *
		 * @since 1.21.0
		 */
		$popup_args = apply_filters( 'popup_maker/popup_post_type_args', $popup_args );

		/**
		 * Filter: popmake_popup_post_type_args
		 *
		 * @param array<string,mixed> $args Popup post type args.
		 *`
		 * @deprecated 1.21.0
		 */
		$popup_args = apply_filters( 'popmake_popup_post_type_args', $popup_args );

		register_post_type( $this->get_type_key( 'popup' ), $popup_args );
	}

	/**
	 * Register `popup_theme` post type.
	 *
	 * @return void
	 */
	public function register_popup_theme_post_type() {
		$post_type_key = $this->get_type_key( 'popup_theme' );

		$popup_theme_labels = $this->post_type_labels(
			__( 'Popup Theme', 'popup-maker' ),
			__( 'Popup Themes', 'popup-maker' ),
			$post_type_key
		);

		$popup_theme_args = [
			'label'               => __( 'Popup Theme', 'popup-maker' ),
			'labels'              => array_merge( $popup_theme_labels, [
				'all_items' => __( 'Popup Themes', 'popup-maker' ),
			] ),
			'description'         => '',
			// Basic.
			'public'              => true, // TODO Test false.
			'hierarchical'        => false,
			// Visibility.
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_in_nav_menus'   => false,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=popup',
			'show_in_admin_bar'   => false,
			'has_archive'         => false,
			// Urls.
			'query_var'           => false,
			'rewrite'             => false,
			// Features.
			'supports'            => [
				'title',
				'excerpt',
				'revisions',
				'author',
			],
			// Rest.
			'show_in_rest'        => true,
			'rest_base'           => 'popup-themes',
			'rest_namespace'      => 'popup-maker/v2',
			'show_in_graphql'     => false,
			// Permissions.
			'can_export'          => true,
			'map_meta_cap'        => true,
			'delete_with_user'    => false,
			'capabilities'        => [
				'create_posts' => $this->container->get_permission( 'edit_popup_themes' ),
				'edit_posts'   => $this->container->get_permission( 'edit_popup_themes' ),
				'delete_posts' => $this->container->get_permission( 'edit_popup_themes' ),
			],
		];

		/**
		 * Filter: popup_maker/popup_theme_post_type_args
		 *
		 * @param array<string,mixed> $args Popup theme post type args.
		 *
		 * @since 1.21.0
		 */
		$popup_theme_args = apply_filters( 'popup_maker/popup_theme_post_type_args', $popup_theme_args );

		/**
		 * Filter: popmake_popup_theme_post_type_args
		 *
		 * @param array<string,mixed> $args Popup theme post type args.
		 *`
		 * @deprecated 1.21.0
		 */
		$popup_theme_args = apply_filters( 'popmake_popup_theme_post_type_args', $popup_theme_args );

		register_post_type( $this->get_type_key( 'popup_theme' ), $popup_theme_args );
	}

	/**
	 * Register Call to Action post type.
	 *
	 * @return void
	 */
	public function register_cta_post_type() {
		$post_type_key = $this->get_type_key( 'pum_cta' );

		$cta_labels = $this->post_type_labels(
			__( 'Call to Action', 'popup-maker' ),
			__( 'Call to Actions', 'popup-maker' ),
			$post_type_key
		);

		$cta_args = [
			'label'             => __( 'Call to Action', 'popup-maker' ),
			'labels'            => array_merge( $cta_labels, [
				'all_items' => __( 'Call to Actions', 'popup-maker' ),
			] ),
			'description'       => '',
			// Basic.
			'public'            => false, // TODO Test false.
			// Visibility.
			'show_ui'           => false,
			// 'show_in_menu'      => 'edit.php?post_type=popup',
			'show_in_admin_bar' => false,
			// Features.
			'supports'          => [
				'title',
				'excerpt',
				'revisions',
				'author',
			],
			// Urls.
			'query_var'         => false,
			'rewrite'           => false,
			// Rest.
			'show_in_rest'      => true,
			'rest_base'         => 'ctas',
			'rest_namespace'    => 'popup-maker/v2',
			'show_in_graphql'   => false,
			// Permissions.
			'can_export'        => true,
			'map_meta_cap'      => true,
			'delete_with_user'  => false,
			'capabilities'      => [
				'create_posts' => $this->container->get_permission( 'edit_ctas' ),
				'edit_posts'   => $this->container->get_permission( 'edit_ctas' ),
				'delete_posts' => $this->container->get_permission( 'edit_ctas' ),
			],
		];

		/**
		 * Filter: popup_maker/cta_post_type_args
		 *
		 * @param array<string,mixed> $args CTA post type args.
		 *
		 * @since 1.21.0
		 */
		$cta_args = apply_filters( 'popup_maker/cta_post_type_args', $cta_args );

		register_post_type( $this->get_type_key( 'pum_cta' ), $cta_args );
	}

	/**
	 * Register optional popup category taxonomy.
	 *
	 * @return void
	 */
	public function register_popup_category_tax() {
		if ( pum_get_option( 'disable_popup_category_tag', false ) ) {
			return;
		}

		// Get labels from WP Core category taxonomy.
		$category_labels = (array) get_taxonomy_labels( get_taxonomy( 'category' ) );

		$category_args = [
			'hierarchical' => true,
			'labels'       => $category_labels,
			'public'       => false,
			'show_ui'      => true,
		];

		/**
		 * Filter: popup_maker/popup_category_tax_args
		 *
		 * @param array<string,mixed> $category_args Popup category taxonomy args.
		 *
		 * @since 1.21.0
		 */
		$category_args = apply_filters( 'popup_maker/popup_category_tax_args', $category_args );

		/**
		 * Filter: popmake_popup_category_tax_args
		 *
		 * @param array<string,mixed> $category_args Popup category taxonomy args.
		 *`
		 * @deprecated 1.21.0
		 */
		$category_args = apply_filters( 'popmake_category_args', $category_args );

		register_taxonomy( $this->get_type_key( 'popup_category' ), [ $this->get_type_key( 'popup' ), $this->get_type_key( 'popup_theme' ) ], $category_args );
		register_taxonomy_for_object_type( $this->get_type_key( 'popup_category' ), $this->get_type_key( 'popup' ) );
		register_taxonomy_for_object_type( $this->get_type_key( 'popup_category' ), $this->get_type_key( 'popup_theme' ) );
	}

	/**
	 * Register optional popup tag taxonomy.
	 *
	 * @return void
	 */
	public function register_popup_tag_tax() {
		if ( pum_get_option( 'disable_popup_category_tag', false ) ) {
			return;
		}

		$tag_labels = (array) get_taxonomy_labels( get_taxonomy( 'post_tag' ) );

		$tag_args = apply_filters(
			'popmake_tag_args',
			[
				'hierarchical' => false,
				'labels'       => $tag_labels,
				'public'       => false,
				'show_ui'      => true,
			]
		);

		/**
		 * Filter: popup_maker/popup_tag_tax_args
		 *
		 * @param array<string,mixed> $tag_args Popup tag taxonomy args.
		 *
		 * @since 1.21.0
		 */
		$tag_args = apply_filters( 'popup_maker/popup_tag_tax_args', $tag_args );

		/**
		 * Filter: popmake_tag_args
		 *
		 * @param array<string,mixed> $tag_args Popup tag taxonomy args.
		 *`
		 * @deprecated 1.21.0
		 */
		$tag_args = apply_filters( 'popmake_tag_args', $tag_args );

		register_taxonomy( $this->get_type_key( 'popup_tag' ), [ $this->get_type_key( 'popup' ), $this->get_type_key( 'popup_theme' ) ], $tag_args );
		register_taxonomy_for_object_type( $this->get_type_key( 'popup_tag' ), $this->get_type_key( 'popup' ) );
		register_taxonomy_for_object_type( $this->get_type_key( 'popup_tag' ), $this->get_type_key( 'popup_theme' ) );
	}

	/**
	 * Get post type labels.
	 *
	 * @param string $singular Singular label.
	 * @param string $plural Plural label.
	 * @param string $post_type Post type.
	 *
	 * @return array<string,string>
	 */
	public function post_type_labels( $singular, $plural, $post_type = null ) {
		static $post_type_labels = [];

		if ( isset( $post_type_labels[ $post_type ] ) ) {
			return $post_type_labels[ $post_type ];
		}

		$labels = [
			'name'               => '%2$s',
			'singular_name'      => '%1$s',
			/* translators: %1$s: Post Type Singular: "Popup", "Popup Theme" */
			'add_new_item'       => _x( 'Add New %1$s', 'Post Type Singular: "Popup", "Popup Theme"', 'popup-maker' ),
			/* translators: %1$s: Post Type Singular: "Popup", "Popup Theme" */
			'add_new'            => _x( 'Add New %1$s', 'Post Type Singular: "Popup", "Popup Theme"', 'popup-maker' ),
			/* translators: %1$s: Post Type Singular: "Popup", "Popup Theme" */
			'edit_item'          => _x( 'Edit %1$s', 'Post Type Singular: "Popup", "Popup Theme"', 'popup-maker' ),
			/* translators: %1$s: Post Type Singular: "Popup", "Popup Theme" */
			'new_item'           => _x( 'New %1$s', 'Post Type Singular: "Popup", "Popup Theme"', 'popup-maker' ),
			/* translators: %2$s: Post Type Plural: "Popups", "Popup Themes" */
			'all_items'          => _x( 'All %2$s', 'Post Type Plural: "Popups", "Popup Themes"', 'popup-maker' ),
			/* translators: %1$s: Post Type Singular: "Popup", "Popup Theme" */
			'view_item'          => _x( 'View %1$s', 'Post Type Singular: "Popup", "Popup Theme"', 'popup-maker' ),
			/* translators: %2$s: Post Type Plural: "Popups", "Popup Themes" */
			'search_items'       => _x( 'Search %2$s', 'Post Type Plural: "Popups", "Popup Themes"', 'popup-maker' ),
			/* translators: %2$s: Post Type Plural: "Popups", "Popup Themes" */
			'not_found'          => _x( 'No %2$s found', 'Post Type Plural: "Popups", "Popup Themes"', 'popup-maker' ),
			/* translators: %2$s: Post Type Plural: "Popups", "Popup Themes" */
			'not_found_in_trash' => _x( 'No %2$s found in Trash', 'Post Type Plural: "Popups", "Popup Themes"', 'popup-maker' ),
		];

		/**
		 * Filter: popup_maker/post_type_labels
		 *
		 * @param array<string,mixed> $labels Post type labels.
		 * @param string $singular Singular label.
		 * @param string $plural Plural label.
		 * @param string $post_type Post type.
		 *
		 * @since 1.21.0
		 */
		$labels = apply_filters( 'popup_maker/post_type_labels', $labels, $singular, $plural, $post_type );

		// Map labels.
		$post_type_labels[ $post_type ] = [];

		foreach ( $labels as $key => $value ) {
			$post_type_labels[ $post_type ][ $key ] = sprintf( $value, $singular, $plural );
		}

		return $post_type_labels[ $post_type ];
	}

	/**
	 * Add data version meta to new Popup Maker post types.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an existing post being updated or not.
	 *
	 * @return void
	 */
	public function save_post( $post_id, $post, $update ) {
		if ( $update ) {
			return;
		}

		if ( ! in_array( $post->post_type, [ 'popup', 'pum_cta', 'popup_theme' ], true ) ) {
			return;
		}

		$current_popup_data_version = get_data_version( $post->post_type );

		add_post_meta( $post_id, 'data_version', $current_popup_data_version );
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

		$labels = [
			/* translators: %1$s: Post Type Singular: Popup, Theme */
			1 => _x( '%1$s updated.', 'Post Type Singular: Popup, Theme', 'popup-maker' ),
			/* translators: %1$s: Post Type Singular: Popup, Theme */
			4 => _x( '%1$s updated.', 'Post Type Singular: Popup, Theme', 'popup-maker' ),
			/* translators: %1$s: Post Type Singular: Popup, Theme */
			6 => _x( '%1$s published.', 'Post Type Singular: Popup, Theme', 'popup-maker' ),
			/* translators: %1$s: Post Type Singular: Popup, Theme */
			7 => _x( '%1$s saved.', 'Post Type Singular: Popup, Theme', 'popup-maker' ),
			/* translators: %1$s: Post Type Singular: Popup, Theme */
			8 => _x( '%1$s submitted.', 'Post Type Singular: Popup, Theme', 'popup-maker' ),
		];

		$messages['pum_cta']     = [];
		$messages['popup']       = [];
		$messages['popup_theme'] = [];

		$cta   = __( 'Call to Action', 'popup-maker' );
		$popup = __( 'Popup', 'popup-maker' );
		$theme = __( 'Popup Theme', 'popup-maker' );

		foreach ( $labels as $k => $string ) {
			$messages['pum_cta'][ $k ]     = sprintf( $string, $cta );
			$messages['popup'][ $k ]       = sprintf( $string, $popup );
			$messages['popup_theme'][ $k ] = sprintf( $string, $theme );
		}

		return $messages;
	}

	/**
	 * Control whether to use block editor for popup post types.
	 *
	 * @param bool   $use_block_editor Whether to use the block editor.
	 * @param string $post_type        The post type.
	 * @return bool Whether to use the block editor for this post type.
	 */
	public function use_block_editor_for_post_type( $use_block_editor, $post_type ) {
		// Only control our post types.
		if ( ! in_array( $post_type, [ 'popup', 'popup_theme' ], true ) ) {
			return $use_block_editor;
		}

		// If classic editor is enabled, disable block editor.
		if ( pum_get_option( 'enable_classic_editor', false ) ) {
			return false;
		}

		// Otherwise use block editor.
		return true;
	}

	/**
	 * Replace the editor interface when classic editor is forced.
	 *
	 * @param bool    $replace   Whether to replace the editor.
	 * @param WP_Post $post      The post object.
	 * @return void
	 */
	public function replace_editor( $replace, $post ) {
		// Only handle our post types.
		if ( ! in_array( $post->post_type, [ 'popup', 'popup_theme' ], true ) ) {
			return;
		}

		// If classic editor is enabled, ensure we're using classic interface.
		if ( pum_get_option( 'enable_classic_editor', false ) ) {
			// Remove block editor assets if they were enqueued.
			remove_action( 'admin_enqueue_scripts', 'wp_enqueue_editor' );
			remove_action( 'admin_footer', 'wp_enqueue_editor' );
		}
	}
}
