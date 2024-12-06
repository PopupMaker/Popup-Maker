<?php
/**
 * Post type setup.
 *
 * @copyright (c) 2024, Code Atlantic LLC.
 * @package PopupMaker
 */

namespace PopupMaker\Controllers;

use PopupMaker\Base\Controller;

use function PopupMaker\get_data_version;

/**
 * Post type controller.
 */
class PostTypes extends Controller {

	/**
	 * Init controller.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_post_types' ] );
		add_action( 'init', [ $this, 'register_rest_fields' ] );
		add_action( 'save_post_popup', [ $this, 'save_post' ], 10, 3 );
		add_filter( 'rest_pre_dispatch', [ $this, 'rest_pre_dispatch' ], 10, 3 );
		add_filter( 'content_control/sanitize_popup_settings', [ $this, 'sanitize_popup_settings' ], 10, 2 );
		add_filter( 'content_control/validate_popup_settings', [ $this, 'validate_popup_settings' ], 10, 2 );
	}

	/**
	 * Get post type keys.
	 *
	 * @return array<string,string> The post type keys.
	 */
	public function get_type_keys() {
		static $keys = [
			'popup'          => 'popup',
			'popup_theme'    => 'popup_theme',
			'popup_category' => 'popup_category',
			'popup_tag'      => 'popup_tag',
		];

		return $keys;
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
		 * @since X.X.X
		 */
		$popup_args = apply_filters( 'popup_maker/popup_post_type_args', $popup_args );

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
		 * @since X.X.X
		 */
		$popup_theme_args = apply_filters( 'popup_maker/popup_theme_post_type_args', $popup_theme_args );

		register_post_type( $this->get_type_key( 'popup_theme' ), $popup_theme_args );
	}

	/**
	 * Register optional popup category taxonomy.
	 *
	 * @return void
	 */
	public function register_popup_category_tax() {
		if ( $this->container->get_option( 'disable_popup_category_tag', false ) ) {
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
		 * @since X.X.X
		 */
		$category_args = apply_filters( 'popup_maker/popup_category_tax_args', $category_args );

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
		if ( $this->container->get_option( 'disable_popup_category_tag', false ) ) {
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
		 * @since X.X.X
		 */
		$tag_args = apply_filters( 'popup_maker/popup_tag_tax_args', $tag_args );

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
		 *
		 * @since X.X.X
		 */
		$labels = apply_filters( 'popup_maker/post_type_labels', $labels, $singular, $plural, $post_type );

		// Map labels.
		$post_type_labels[ $post_type ] = array_map(
			static fn( $value ) => sprintf( $value, $singular, $plural ),
			$labels
		);

		return $post_type_labels[ $post_type ];
	}

	/**
	 * Registers custom REST API fields for popup post type.
	 *
	 * @return void
	 */
	public function register_rest_fields() {
		register_rest_field( 'popup', 'settings', [
			'get_callback'        => function ( $obj, $field, $request ) {
				$popup = pum_get_popup( $obj['id'] );

				// If edit context, return the current settings.
				if ( 'edit' === $request['context'] ) {
					$settings = $popup->get_settings();
				} else {
					// Otherwise, return the public settings.
					$settings = $popup->get_public_settings();
				}

				return $settings;
			},
			'update_callback'     => function ( $value, $obj ) {
				$popup = pum_get_popup( $obj->ID );
				$popup->update_settings( $value );
			},
			'schema'              => [
				'type'        => 'object',
				'arg_options' => [
					'sanitize_callback' => function ( $settings, $request ) {
						/**
						 * Sanitize the popup settings.
						 *
						 * @param array<string,mixed> $settings The settings to sanitize.
						 * @param int   $id       The popup ID.
						 * @param \WP_REST_Request $request The request object.
						 *
						 * @return array<string,mixed> The sanitized settings.
						 */
						return apply_filters( 'popup_maker/sanitize_popup_settings', $settings, $request->get_param( 'id' ), $request );
					},
					'validate_callback' => function ( $settings, $request ) {
						/**
						 * Validate the popup settings.
						 *
						 * @param array<string,mixed> $settings The settings to validate.
						 * @param int   $id       The popup ID.
						 * @param \WP_REST_Request $request The request object.
						 *
						 * @return bool|\WP_Error True if valid, WP_Error if not.
						 */
						return apply_filters( 'popup_maker/validate_popup_settings', $settings, $request->get_param( 'id' ), $request );
					},
				],
			],
			'permission_callback' => function () {
				return current_user_can( $this->container->get_permission( 'edit_popups' ) );
			},
		] );

		register_rest_field( 'popup', 'priority', [
			'get_callback'        => function ( $obj ) {
				return (int) get_post_field( 'menu_order', $obj['id'], 'raw' );
			},
			'update_callback'     => function ( $value, $obj ) {
				wp_update_post( [
					'ID'         => $obj->ID,
					'menu_order' => $value,
				] );
			},
			'permission_callback' => function () {
				return current_user_can( $this->container->get_permission( 'edit_popups' ) );
			},
			'schema'              => [
				'type'        => 'integer',
				'arg_options' => [
					'sanitize_callback' => function ( $priority ) {
						return absint( $priority );
					},
					'validate_callback' => function ( $priority ) {
						return is_int( $priority );
					},
				],
			],
		] );

		register_rest_field( 'popup', 'data_version', [
			'get_callback'        => function ( $obj ) {
				return get_post_meta( $obj['id'], 'data_version', true );
			},
			'update_callback'     => function ( $value, $obj ) {
				// Update the field/meta value.
				update_post_meta( $obj->ID, 'data_version', $value );
			},
			'permission_callback' => function () {
				return current_user_can( $this->container->get_permission( 'edit_popups' ) );
			},
		] );
	}

	/**
	 * Sanitize popup settings.
	 *
	 * @param array<string,mixed> $settings The settings to sanitize.
	 * @param int                 $id       The popup ID.
	 *
	 * @return array<string,mixed> The sanitized settings.
	 */
	public function sanitize_popup_settings( $settings, $id ) {
		return $settings;
	}

	/**
	 * Validate popup settings.
	 *
	 * @param array<string,mixed> $settings The settings to validate.
	 * @param int                 $id       The popup ID.
	 *
	 * @return bool|\WP_Error True if valid, WP_Error if not.
	 */
	public function validate_popup_settings( $settings, $id ) {
		// TODO Validate all known settings by type.
		return true;
	}

	/**
	 * Add data version meta to new popups.
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

		$current_popup_data_version = get_data_version( 'popups' );

		add_post_meta( $post_id, 'data_version', $current_popup_data_version );
	}

	/**
	 * Prevent access to the popups endpoint.
	 *
	 * @param mixed                                 $result Response to replace the requested version with.
	 * @param \WP_REST_Server                       $server Server instance.
	 * @param \WP_REST_Request<array<string,mixed>> $request  Request used to generate the response.
	 * @return mixed
	 */
	public function rest_pre_dispatch( $result, $server, $request ) {
		// Get the route being requested.
		$route = $request->get_route();

		// Only proceed if we're creating a user.
		if ( false === strpos( $route, '/popup-maker/v2/popups' ) ) {
			return $result;
		}

		$current_user_can = current_user_can( $this->container->get_permission( 'edit_popups' ) );

		// Prevent discovery of the endpoints data from unauthorized users.
		if ( ! $current_user_can ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Access to this endpoint requires authorization.', 'popup-maker' ),
				[
					'status' => rest_authorization_required_code(),
				]
			);
		}

		// Return data to the client to parse.
		return $result;
	}
}
