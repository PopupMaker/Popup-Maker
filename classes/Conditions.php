<?php
/**
 * Conditions class
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Conditions
 */
class PUM_Conditions {

	/**
	 * @var PUM_Conditions
	 */
	public static $instance;

	/**
	 * @var bool
	 */
	public $preload_posts = false;

	/**
	 * @var array
	 */
	public $conditions;

	/**
	 * @var array
	 */
	public $condition_sort_order = [];

	/**
	 *
	 */
	public static function init() {
		self::instance();
	}

	/**
	 * @return self
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance                = new self();
			self::$instance->preload_posts = popmake_is_admin_popup_page();
		}

		return self::$instance;
	}

	/**
	 * @param array $conditions
	 */
	public function add_conditions( $conditions = [] ) {
		foreach ( $conditions as $key => $condition ) {
			if ( empty( $condition['id'] ) && ! is_numeric( $key ) ) {
				$condition['id'] = $key;
			}

			$this->add_condition( $condition );
		}
	}

	/**
	 * @param array $condition
	 */
	public function add_condition( $condition = [] ) {
		if ( ! empty( $condition['id'] ) && ! isset( $this->conditions[ $condition['id'] ] ) ) {
			$condition = wp_parse_args(
				$condition,
				[
					'id'       => '',
					'callback' => null,
					'group'    => '',
					'name'     => '',
					'priority' => 10,
					'fields'   => [],
					'advanced' => false,
				]
			);

			$this->conditions[ $condition['id'] ] = $condition;
		}
	}

	/**
	 * @return array
	 */
	public function get_conditions() {
		if ( ! isset( $this->conditions ) ) {
			$this->register_conditions();
		}

		return $this->conditions;
	}

	/**
	 * @return array|mixed
	 */
	public function condition_sort_order() {
		if ( ! $this->condition_sort_order ) {
			$order = [
				__( 'General', 'popup-maker' )    => 1,
				__( 'Pages', 'popup-maker' )      => 5,
				__( 'Posts', 'popup-maker' )      => 5,
				__( 'Categories', 'popup-maker' ) => 14,
				__( 'Tags', 'popup-maker' )       => 14,
				__( 'Format', 'popup-maker' )     => 16,
			];

			$post_types = get_post_types(
				[
					'public'   => true,
					'_builtin' => false,
				],
				'objects'
			);
			foreach ( $post_types as $name => $post_type ) {
				$order[ $post_type->labels->name ] = 10;
			}

			$taxonomies = get_taxonomies(
				[
					'public'   => true,
					'_builtin' => false,
				],
				'objects'
			);
			foreach ( $taxonomies as $tax_name => $taxonomy ) {
				$order[ $taxonomy->labels->name ] = 15;
			}

			$this->condition_sort_order = apply_filters( 'pum_condition_sort_order', $order );
		}

		return $this->condition_sort_order;
	}

	/**
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	public function sort_condition_groups( $a, $b ) {

		$order = $this->condition_sort_order();

		$ai = isset( $order[ $a ] ) ? intval( $order[ $a ] ) : 10;
		$bi = isset( $order[ $b ] ) ? intval( $order[ $b ] ) : 10;

		if ( $ai === $bi ) {
			return 0;
		}

		// Compare their positions in line.
		return $ai > $bi ? 1 : - 1;
	}

	/**
	 * @return array
	 */
	public function get_conditions_by_group() {
		static $groups;

		if ( ! isset( $groups ) ) {
			$groups = [];

			foreach ( $this->get_conditions() as $condition ) {
				$groups[ $condition['group'] ][ $condition['id'] ] = $condition;
			}

			uksort( $groups, [ $this, 'sort_condition_groups' ] );
		}

		return $groups;
	}

	/**
	 * @return array
	 */
	public function dropdown_list() {
		$groups = [];

		$conditions_by_group = $this->get_conditions_by_group();

		foreach ( $conditions_by_group as $group => $_conditions ) {
			$conditions = [];

			foreach ( $_conditions as $id => $condition ) {
				$conditions[ $id ] = $condition['name'];
			}

			$groups[ $group ] = $conditions;
		}

		return $groups;
	}

	/**
	 * @param null $condition
	 *
	 * @return mixed|null
	 */
	public function get_condition( $condition = null ) {
		$conditions = $this->get_conditions();

		return isset( $conditions[ $condition ] ) ? $conditions[ $condition ] : null;
	}

	/**
	 * @return array
	 */
	public function generate_post_type_conditions() {
		$conditions = [];
		$post_types = get_post_types( [ 'public' => true ], 'objects' );

		foreach ( $post_types as $name => $post_type ) {
			if ( 'popup' === $name || 'popup_theme' === $name ) {
				continue;
			}

			$post_type_conditions = [];

			if ( $post_type->has_archive ) {
				$post_type_conditions[ $name . '_index' ] = [
					'group'    => $post_type->labels->name,
					'name'     => sprintf(
						/* translators: 1. Post type plural label. */
						_x( '%s Archive', 'condition: post type plural label ie. Posts: All', 'popup-maker' ),
						$post_type->labels->name
					),
					'callback' => [ 'PUM_ConditionCallbacks', 'post_type' ],
					'priority' => 5,
				];
			}

			$post_type_conditions[ $name . '_all' ] = [
				'group'    => $post_type->labels->name,
				'name'     => sprintf(
					/* translators: 1. Post type plural label. */
					_x( 'All %s', 'condition: post type plural label ie. Posts: All', 'popup-maker' ),
					$post_type->labels->name
				),
				'callback' => [ 'PUM_ConditionCallbacks', 'post_type' ],
			];

			$post_type_conditions[ $name . '_selected' ] = [
				'group'    => $post_type->labels->name,
				'name'     => sprintf(
					/* translators: 1. Post type plural label. */
					_x( '%s: Selected', 'condition: post type plural label ie. Posts: Selected', 'popup-maker' ),
					$post_type->labels->name
				),
				'fields'   => [
					'selected' => [
						'placeholder' => sprintf(
							/* translators: 1. Post type plural label. */
							_x( 'Select %s.', 'condition: post type plural label ie. Select Posts', 'popup-maker' ),
							strtolower( $post_type->labels->name )
						),
						'type'        => 'postselect',
						'post_type'   => $name,
						'multiple'    => true,
						'as_array'    => true,
						'std'         => [],
					],
				],
				'callback' => [ 'PUM_ConditionCallbacks', 'post_type' ],
			];

			$post_type_conditions[ $name . '_ID' ] = [
				'group'    => $post_type->labels->name,
				'name'     => sprintf(
					/* translators: 1. Post type plural label. */
					_x( '%s: ID', 'condition: post type plural label ie. Posts: ID', 'popup-maker' ),
					$post_type->labels->name
				),
				'fields'   => [
					'selected' => [
						'placeholder' => sprintf(
							/* translators: 1. Post type singular label. */
							_x( '%s IDs: 128, 129', 'condition: post type singular label ie. Posts IDs', 'popup-maker' ),
							strtolower( $post_type->labels->singular_name )
						),
						'type'        => 'text',
					],
				],
				'callback' => [ 'PUM_ConditionCallbacks', 'post_type' ],
			];

			if ( is_post_type_hierarchical( $name ) ) {
				$post_type_conditions[ $name . '_children' ] = [
					'group'    => $post_type->labels->name,
					'name'     => sprintf(
						/* translators: 1. Post type plural label. */
						_x( '%s: Child Of', 'condition: post type plural label ie. Posts: ID', 'popup-maker' ),
						$post_type->labels->name
					),
					'fields'   => [
						'selected' => [
							'placeholder' => sprintf(
								/* translators: 1. Post type plural label. */
								_x( 'Select %s.', 'condition: post type plural label ie. Select Posts', 'popup-maker' ),
								strtolower( $post_type->labels->name )
							),
							'type'        => 'postselect',
							'post_type'   => $name,
							'multiple'    => true,
							'as_array'    => true,
						],
					],
					'callback' => [ 'PUM_ConditionCallbacks', 'post_type' ],
				];

				$post_type_conditions[ $name . '_ancestors' ] = [
					'group'    => $post_type->labels->name,
					'name'     => sprintf(
						/* translators: 1. Post type plural label. */
						_x( '%s: Ancestor Of', 'condition: post type plural label ie. Posts: ID', 'popup-maker' ),
						$post_type->labels->name
					),
					'fields'   => [
						'selected' => [
							'placeholder' => sprintf(
								/* translators: 1. Post type plural label. */
								_x( 'Select %s.', 'condition: post type plural label ie. Select Posts', 'popup-maker' ),
								strtolower( $post_type->labels->name )
							),
							'type'        => 'postselect',
							'post_type'   => $name,
							'multiple'    => true,
							'as_array'    => true,
						],
					],
					'callback' => [ 'PUM_ConditionCallbacks', 'post_type' ],
				];
			}

			$templates = wp_get_theme()->get_page_templates();

			if ( 'page' === $name && ! empty( $templates ) ) {
				$post_type_conditions[ $name . '_template' ] = [
					'group'    => $post_type->labels->name,
					'name'     => sprintf(
						/* translators: 1. Post type plural label. */
						_x( '%s: With Template', 'condition: post type plural label ie. Pages: With Template', 'popup-maker' ),
						$post_type->labels->name
					),
					'fields'   => [
						'selected' => [
							'type'     => 'select',
							'select2'  => true,
							'multiple' => true,
							'as_array' => true,
							'options'  => array_merge( [ 'default' => __( 'Default', 'popup-maker' ) ], $templates ),
						],
					],
					'callback' => [ 'PUM_ConditionCallbacks', 'post_type' ],
				];
			}

			$post_type_conditions = array_merge( $post_type_conditions, $this->generate_post_type_tax_conditions( $name ) );

			/**
			 * Filters generated conditions for a specific post type.
			 *
			 * This runs once per post type during condition registration and allows
			 * extensions to append post type specific conditions without duplicating
			 * the core post type loop.
			 *
			 * @param array        $post_type_conditions Generated conditions for this post type.
			 * @param string       $name                 Post type name.
			 * @param \WP_Post_Type $post_type            Post type object.
			 */
			$post_type_conditions = apply_filters( 'popup_maker/post_type_conditions', $post_type_conditions, $name, $post_type );

			$conditions = array_merge( $conditions, $post_type_conditions );
		}

		return $conditions;
	}

	/**
	 * @param $name
	 *
	 * @return array
	 */
	public function generate_post_type_tax_conditions( $name ) {
		$post_type  = get_post_type_object( $name );
		$taxonomies = get_object_taxonomies( $name, 'object' );
		$conditions = [];
		foreach ( $taxonomies as $tax_name => $taxonomy ) {
			if ( is_string( $taxonomy ) ) {
				$taxonomy = get_taxonomy( $tax_name );
			}

			$taxonomy_conditions = [];

			/* @var WP_Taxonomy[] $taxonomy */
			$taxonomy_conditions[ $name . '_w_' . $tax_name ] = [
				'group'    => $post_type->labels->name,
				'name'     => sprintf(
					/* translators: 1. Post type plural label, 2. Taxonomy singular label. */
					_x( '%1$s: With %2$s', 'condition: post type plural and taxonomy singular label ie. Posts: With Category', 'popup-maker' ),
					$post_type->labels->name,
					$taxonomy->labels->singular_name
				),
				'fields'   => [
					'selected' => [
						'placeholder' => sprintf(
							/* translators: 1. Taxonomy plural label. */
							_x( 'Select %s.', 'condition: post type plural label ie. Select categories', 'popup-maker' ),
							strtolower( $taxonomy->labels->name )
						),
						'type'        => 'taxonomyselect',
						'taxonomy'    => $tax_name,
						'multiple'    => true,
						'as_array'    => true,
					],
				],
				'callback' => [ 'PUM_ConditionCallbacks', 'post_type_tax' ],
			];

			/**
			 * Filters generated post type taxonomy conditions.
			 *
			 * @param array        $taxonomy_conditions Generated conditions for this taxonomy + post type pair.
			 * @param string       $name                Post type name.
			 * @param \WP_Post_Type $post_type           Post type object.
			 * @param string       $tax_name            Taxonomy name.
			 * @param \WP_Taxonomy $taxonomy            Taxonomy object.
			 */
			$taxonomy_conditions = apply_filters( 'popup_maker/post_type_tax_conditions', $taxonomy_conditions, $name, $post_type, $tax_name, $taxonomy );

			$conditions = array_merge( $conditions, $taxonomy_conditions );
		}

		return $conditions;
	}

	/**
	 * Generates conditions for all public taxonomies.
	 *
	 * @return array
	 */
	public function generate_taxonomy_conditions() {
		$conditions = [];
		$taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );

		foreach ( $taxonomies as $tax_name => $taxonomy ) {
			$taxonomy_conditions = [];

			$taxonomy_conditions[ 'tax_' . $tax_name . '_all' ] = [
				'group'    => $taxonomy->labels->name,
				'name'     => sprintf(
					/* translators: 1. Taxonomy plural label. */
					_x( '%s: All', 'condition: taxonomy plural label ie. Categories: All', 'popup-maker' ),
					$taxonomy->labels->name
				),
				'callback' => [ 'PUM_ConditionCallbacks', 'taxonomy' ],
			];

			$taxonomy_conditions[ 'tax_' . $tax_name . '_selected' ] = [
				'group'    => $taxonomy->labels->name,
				'name'     => sprintf(
					/* translators: 1. Taxonomy plural label. */
					_x( '%s: Selected', 'condition: taxonomy plural label ie. Categories: Selected', 'popup-maker' ),
					$taxonomy->labels->name
				),
				'fields'   => [
					'selected' => [
						'placeholder' => sprintf(
							/* translators: 1. Taxonomy plural label. */
							_x( 'Select %s.', 'condition: taxonomy plural label ie. Select Categories', 'popup-maker' ),
							strtolower( $taxonomy->labels->name )
						),
						'type'        => 'taxonomyselect',
						'taxonomy'    => $tax_name,
						'multiple'    => true,
						'as_array'    => true,
					],
				],
				'callback' => [ 'PUM_ConditionCallbacks', 'taxonomy' ],
			];

			$taxonomy_conditions[ 'tax_' . $tax_name . '_ID' ] = [
				'group'    => $taxonomy->labels->name,
				'name'     => sprintf(
					/* translators: 1. Taxonomy plural label. */
					_x( '%s: IDs', 'condition: taxonomy plural label ie. Categories: Selected', 'popup-maker' ),
					$taxonomy->labels->name
				),
				'fields'   => [
					'selected' => [
						'placeholder' => sprintf(
							/* translators: 1. Taxonomy singular label. */
							_x( '%s IDs: 128, 129', 'condition: taxonomy plural label ie. Category IDs', 'popup-maker' ),
							strtolower( $taxonomy->labels->singular_name )
						),
						'type'        => 'text',
					],
				],
				'callback' => [ 'PUM_ConditionCallbacks', 'taxonomy' ],
			];

			/**
			 * Filters generated conditions for a specific taxonomy.
			 *
			 * @param array        $taxonomy_conditions Generated conditions for this taxonomy.
			 * @param string       $tax_name            Taxonomy name.
			 * @param \WP_Taxonomy $taxonomy            Taxonomy object.
			 */
			$taxonomy_conditions = apply_filters( 'popup_maker/taxonomy_conditions', $taxonomy_conditions, $tax_name, $taxonomy );

			$conditions = array_merge( $conditions, $taxonomy_conditions );
		}

		return $conditions;
	}

	/**
	 * Registers all known conditions when called.
	 */
	public function register_conditions() {
		$conditions = array_merge( $this->generate_post_type_conditions(), $this->generate_taxonomy_conditions() );

		$conditions['is_front_page'] = [
			'group'    => __( 'General', 'popup-maker' ),
			'name'     => __( 'Home Page', 'popup-maker' ),
			'callback' => 'is_front_page',
			'priority' => 2,
		];

		$conditions['is_home'] = [
			'group'    => __( 'Posts', 'popup-maker' ),
			'name'     => __( 'Blog Index', 'popup-maker' ),
			'callback' => 'is_home',
			'priority' => 1,
		];

		$conditions['is_search'] = [
			'group'    => __( 'General', 'popup-maker' ),
			'name'     => __( 'Search Result Page', 'popup-maker' ),
			'callback' => 'is_search',
		];

		$conditions['is_404'] = [
			'group'    => __( 'General', 'popup-maker' ),
			'name'     => __( '404 Error Page', 'popup-maker' ),
			'callback' => 'is_404',
		];

		$conditions = apply_filters( 'pum_registered_conditions', $conditions );

		// @deprecated filter.
		$old_conditions = apply_filters( 'pum_get_conditions', [] );

		foreach ( $old_conditions as $id => $condition ) {
			if ( ! empty( $condition['labels'] ) && ! empty( $condition['labels']['name'] ) ) {
				$condition['name'] = $condition['labels']['name'];
				unset( $condition['labels'] );
			}
			if ( ! isset( $conditions[ $id ] ) ) {
				$conditions[ $id ] = $condition;
			}
		}

		$this->add_conditions( $conditions );
	}

	/**
	 * Gets a filterable array of the allowed user roles.
	 *
	 * @return array
	 */
	public static function allowed_user_roles() {
		global $wp_roles;

		static $roles;

		if ( ! isset( $roles ) && is_object( $wp_roles ) ) {
			$roles = apply_filters( 'pum_user_roles', $wp_roles->role_names );

			if ( ! is_array( $roles ) || empty( $roles ) ) {
				$roles = [];
			}
		} else {
			return [];
		}

		return $roles;
	}
}
