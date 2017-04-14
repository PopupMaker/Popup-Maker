<?php
/**
 * Conditions Functions
 *
 * @package     PUM
 * @subpackage  Functions/PUM_Conditions
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * @return array
 */
function pum_generate_post_type_conditions() {
	$conditions = array();
	$post_types = get_post_types( array( 'public' => true ), 'objects' );

	foreach ( $post_types as $name => $post_type ) {

		if ( $name == 'popup' || $name == 'popup_theme' ) {
			continue;
		}

		if ( $post_type->has_archive ) {
			$conditions[ $name . '_index' ] = array(
				'group'    => __( 'General', 'popup-maker' ),
				'name'     => sprintf( _x( '%s Archive', 'condition: post type plural label ie. Posts: All', 'popup-maker' ), $post_type->labels->name ),
				'callback' => array( 'PUM_Condition_Callbacks', 'post_type' ),
				'priority' => 5,
			);
		}

		$conditions[ $name . '_all' ] = array(
			'group'    => $post_type->labels->name,
			'name'     => sprintf( _x( 'All %s', 'condition: post type plural label ie. Posts: All', 'popup-maker' ), $post_type->labels->name ),
			'callback' => array( 'PUM_Condition_Callbacks', 'post_type' ),
		);

		$conditions[ $name . '_selected' ] = array(
			'group'    => $post_type->labels->name,
			'name'     => sprintf( _x( '%s: Selected', 'condition: post type plural label ie. Posts: Selected', 'popup-maker' ), $post_type->labels->name ),
			'fields'   => array(
				'selected' => array(
					'placeholder' => sprintf( _x( 'Select %s.', 'condition: post type plural label ie. Select Posts', 'popup-maker' ), strtolower( $post_type->labels->name ) ),
					'type'        => 'postselect',
					'post_type'   => $name,
					'multiple'    => true,
					'as_array'    => true,
					'options'     => is_admin() && popmake_is_admin_popup_page() ? PUM_Helpers::post_type_selectlist( $name ) : array(),
				),
			),
			'callback' => array( 'PUM_Condition_Callbacks', 'post_type' ),
		);

		$conditions[ $name . '_ID' ] = array(
			'group'    => $post_type->labels->name,
			'name'     => sprintf( _x( '%s: ID', 'condition: post type plural label ie. Posts: ID', 'popup-maker' ), $post_type->labels->name ),
			'fields'   => array(
				'selected' => array(
					'placeholder' => sprintf( _x( '%s IDs: 128, 129', 'condition: post type singular label ie. Posts IDs', 'popup-maker' ), strtolower( $post_type->labels->singular_name ) ),
					'type'        => 'text',
				),
			),
			'callback' => array( 'PUM_Condition_Callbacks', 'post_type' ),
		);

		if ( is_post_type_hierarchical( $name ) ) {
			$conditions[ $name . '_children' ] = array(
				'group'    => $post_type->labels->name,
				'name'     => sprintf( _x( '%s: Child Of', 'condition: post type plural label ie. Posts: ID', 'popup-maker' ), $post_type->labels->name ),
				'fields'   => array(
					'selected' => array(
						'placeholder' => sprintf( _x( 'Select %s.', 'condition: post type plural label ie. Select Posts', 'popup-maker' ), strtolower( $post_type->labels->name ) ),
						'type'        => 'postselect',
						'post_type'   => $name,
						'multiple'    => true,
						'as_array'    => true,
						'options'     => is_admin() && popmake_is_admin_popup_page() ? PUM_Helpers::post_type_selectlist( $name ) : array(),
					),
				),
				'callback' => array( 'PUM_Condition_Callbacks', 'post_type' ),
			);

			$conditions[ $name . '_ancestors' ] = array(
				'group'    => $post_type->labels->name,
				'name'     => sprintf( _x( '%s: Ancestor Of', 'condition: post type plural label ie. Posts: ID', 'popup-maker' ), $post_type->labels->name ),
				'fields'   => array(
					'selected' => array(
						'placeholder' => sprintf( _x( 'Select %s.', 'condition: post type plural label ie. Select Posts', 'popup-maker' ), strtolower( $post_type->labels->name ) ),
						'type'        => 'postselect',
						'post_type'   => $name,
						'multiple'    => true,
						'as_array'    => true,
						'options'     => is_admin() && popmake_is_admin_popup_page() ? PUM_Helpers::post_type_selectlist( $name ) : array(),
					),
				),
				'callback' => array( 'PUM_Condition_Callbacks', 'post_type' ),
			);

		}


		$templates = wp_get_theme()->get_page_templates();

		if ( $name == 'page' && ! empty( $templates ) ) {
			$conditions[ $name . '_template' ] = array(
				'group'    => $post_type->labels->name,
				'name'     => sprintf( _x( '%s: With Template', 'condition: post type plural label ie. Pages: With Template', 'popup-maker' ), $post_type->labels->name ),
				'fields'   => array(
					'selected' => array(
						'type'     => 'select',
						'select2'  => true,
						'multiple' => true,
						'as_array' => true,
						'options'  => array_flip( array_merge( array( 'default' => __( 'Default', 'popup-maker' ) ), $templates ) ),
					),
				),
				'callback' => array( 'PUM_Condition_Callbacks', 'post_type' ),
			);
		}

		$conditions = array_merge( $conditions, pum_generate_post_type_tax_conditions( $name ) );

	}

	return $conditions;
}

/**
 * @param $name
 *
 * @return array
 */
function pum_generate_post_type_tax_conditions( $name ) {
	$post_type  = get_post_type_object( $name );
	$taxonomies = get_object_taxonomies( $name, 'object' );
	$conditions = array();
	foreach ( $taxonomies as $tax_name => $taxonomy ) {

		$conditions[ $name . '_w_' . $tax_name ] = array(
			'group'    => $post_type->labels->name,
			'name'     => sprintf( _x( '%1$s: With %2$s', 'condition: post type plural and taxonomy singular label ie. Posts: With Category', 'popup-maker' ), $post_type->labels->name, $taxonomy->labels->singular_name ),
			'fields'   => array(
				'selected' => array(
					'placeholder' => sprintf( _x( 'Select %s.', 'condition: post type plural label ie. Select categories', 'popup-maker' ), strtolower( $taxonomy->labels->name ) ),
					'type'        => 'taxonomyselect',
					'taxonomy'    => $tax_name,
					'multiple'    => true,
					'as_array'    => true,
					'options'     => is_admin() && popmake_is_admin_popup_page() ? PUM_Helpers::taxonomy_selectlist( $tax_name ) : array(),
				),
			),
			'callback' => array( 'PUM_Condition_Callbacks', 'post_type_tax' ),
		);
	}

	return $conditions;
}


/**
 * Generates conditions for all public taxonomies.
 *
 * @return array
 */
function pum_generate_taxonomy_conditions() {
	$conditions = array();
	$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

	foreach ( $taxonomies as $tax_name => $taxonomy ) {

		$conditions[ 'tax_' . $tax_name . '_all' ] = array(
			'group'    => $taxonomy->labels->name,
			'name'     => sprintf( _x( '%s: All', 'condition: taxonomy plural label ie. Categories: All', 'popup-maker' ), $taxonomy->labels->name ),
			'callback' => array( 'PUM_Condition_Callbacks', 'taxonomy' ),
		);

		$conditions[ 'tax_' . $tax_name . '_selected' ] = array(
			'group'    => $taxonomy->labels->name,
			'name'     => sprintf( _x( '%s: Selected', 'condition: taxonomy plural label ie. Categories: Selected', 'popup-maker' ), $taxonomy->labels->name ),
			'fields'   => array(
				'selected' => array(
					'placeholder' => sprintf( _x( 'Select %s.', 'condition: taxonomy plural label ie. Select Categories', 'popup-maker' ), strtolower( $taxonomy->labels->name ) ),
					'type'        => 'taxonomyselect',
					'taxonomy'    => $tax_name,
					'multiple'    => true,
					'as_array'    => true,
					'options'     => is_admin() && popmake_is_admin_popup_page() ? PUM_Helpers::taxonomy_selectlist( $tax_name ) : array(),
				),
			),
			'callback' => array( 'PUM_Condition_Callbacks', 'taxonomy' ),
		);

		$conditions[ 'tax_' . $tax_name . '_ID' ] = array(
			'group'    => $taxonomy->labels->name,
			'name'     => sprintf( _x( '%s: IDs', 'condition: taxonomy plural label ie. Categories: Selected', 'popup-maker' ), $taxonomy->labels->name ),
			'fields'   => array(
				'selected' => array(
					'placeholder' => sprintf( _x( '%s IDs: 128, 129', 'condition: taxonomy plural label ie. Category IDs', 'popup-maker' ), strtolower( $taxonomy->labels->singular_name ) ),
					'type'        => 'text',
				),
			),
			'callback' => array( 'PUM_Condition_Callbacks', 'taxonomy' ),
		);

	}

	return $conditions;
}

/**
 * Returns an array of args for registering conditions.
 *
 * @uses filter pum_get_conditions
 *
 * @return array
 */
function pum_get_conditions() {
	$conditions = array_merge( pum_generate_post_type_conditions(), pum_generate_taxonomy_conditions() );

	$conditions['is_front_page'] = array(
		'group'    => __( 'Pages' ),
		'name'     => __( 'Home Page', 'popup-maker' ),
		'callback' => 'is_front_page',
		'priority' => 2,
	);

	$conditions['is_home'] = array(
		'group'    => __( 'Posts' ),
		'name'     => __( 'Blog Index', 'popup-maker' ),
		'callback' => 'is_home',
		'priority' => 1,
	);

	$conditions['is_search'] = array(
		'group'    => __( 'Pages' ),
		'name'     => __( 'Search Pages', 'popup-maker' ),
		'callback' => 'is_search',
	);

	$conditions['is_404'] = array(
		'group'    => __( 'Pages' ),
		'name'     => __( '404 Pages', 'popup-maker' ),
		'callback' => 'is_404',
	);

	return apply_filters( 'pum_get_conditions', $conditions );
}

/**
 * Registers conditions on the WP `init` action.
 *
 * @uses function pum_get_conditions
 */
function pum_register_conditions() {
	$conditions = pum_get_conditions();
	PUM_Conditions::instance()->add_conditions( $conditions );
}

add_action( 'init', 'pum_register_conditions', 99999 );
