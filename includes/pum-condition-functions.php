<?php
/**
 * Conditions Functions
 *
 * @package     PUM
 * @subpackage  Functions/PUM_Conditions
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pum_generate_post_type_conditions() {
	$conditions = array();
	$post_types = get_post_types( array( 'public' => true ), 'objects' );

	foreach ( $post_types as $name => $post_type ) {

		$conditions[ $name . '_all' ]    = array(
			'group'  => 'content',
			'labels' => array(
				'name' => sprintf(
					_x( 'All %s', 'condition: post type plural label ie. Posts: All', 'popup-maker' ),
					$post_type->labels->name
				),
			),
		);
		$conditions[ $name . '_select' ] = array(
			'group'  => 'content',
			'labels' => array(
				'name' => sprintf(
					_x( '%s: Selected', 'condition: post type plural label ie. Posts: Selected', 'popup-maker' ),
					$post_type->labels->name
				),
			),
			'fields' => array(
				'selected' => array(
					'placeholder' => sprintf(
						_x( 'Select %s.', 'condition: post type plural label ie. Select Posts', 'popup-maker' ),
						strtolower( $post_type->labels->name )
					),
					'type'        => 'postselect',
					'post_type'   => $name,
					'multiple'    => true,
					'options'     => PUM_Helpers::post_type_selectlist( $name ),
				),
			)
		);

		$conditions = array_merge( $conditions, pum_generate_post_type_tax_conditions( $name ) );

	}

	return $conditions;
}

function pum_generate_post_type_tax_conditions( $name ) {
	$post_type  = get_post_type_object( $name );
	$taxonomies = get_object_taxonomies( $name, 'object' );
	$conditions = array();
	foreach ( $taxonomies as $tax_name => $taxonomy ) {


		$conditions[ $name . '_w_' . $tax_name ]  = array(
			'group'  => 'content',
			'labels' => array(
				'name' => sprintf(
					_x( '%1$s: With %2$s', 'condition: post type plural and taxonomy singular label ie. Posts: With Category', 'popup-maker' ),
					$post_type->labels->name,
					$taxonomy->labels->singular_name
				),
			),
			'fields' => array(
				'selected' => array(
					'placeholder' => sprintf(
						_x( 'Select %s.', 'condition: post type plural label ie. Select categories', 'popup-maker' ),
						strtolower( $taxonomy->labels->name )
					),
					'type'        => 'taxonomyselect',
					'taxonomy'    => $tax_name,
					'multiple'    => true,
					'options'     => PUM_Helpers::taxonomy_selectlist( $tax_name ),
				),
			)
		);
		$conditions[ $name . '_wo_' . $tax_name ] = array(
			'group'  => 'content',
			'labels' => array(
				'name' => sprintf(
					_x( '%1$s: Without %2$s', 'condition: post type plural and taxonomy singular label ie. Posts: Not Without Category', 'popup-maker' ),
					$post_type->labels->name,
					$taxonomy->labels->singular_name
				),
			),
			'fields' => array(
				'selected' => array(
					'placeholder' => sprintf(
						_x( 'Select %s.', 'condition: post type plural label ie. Select categories', 'popup-maker' ),
						strtolower( $taxonomy->labels->name )
					),
					'type'        => 'taxonomyselect',
					'taxonomy'    => $tax_name,
					'multiple'    => true,
					'options'     => PUM_Helpers::taxonomy_selectlist( $tax_name ),
				),
			)
		);
	}

	return $conditions;
}

function pum_generate_taxonomy_conditions() {
	$conditions = array();
	$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

	foreach ( $taxonomies as $tax_name => $taxonomy ) {

		$conditions[ 'tax_' . $tax_name . '_all' ]    = array(
			'group'  => 'content',
			'labels' => array(
				'name' => sprintf(
					_x( '%s: All', 'condition: taxonomy plural label ie. Categories: All', 'popup-maker' ),
					$taxonomy->labels->name
				),
			),
		);
		$conditions[ 'tax_' . $tax_name . '_select' ] = array(
			'group'  => 'content',
			'labels' => array(
				'name' => sprintf(
					_x( '%s: Selected', 'condition: taxonomy plural label ie. Categories: Selected', 'popup-maker' ),
					$taxonomy->labels->name
				),
			),
			'fields' => array(
				'selected' => array(
					'placeholder' => sprintf(
						_x( 'Select %s.', 'condition: taxonomy plural label ie. Select Categories', 'popup-maker' ),
						strtolower( $taxonomy->labels->name )
					),
					'type'        => 'taxonomyselect',
					'taxonomy'    => $tax_name,
					'multiple'    => true,
					'options'     => PUM_Helpers::taxonomy_selectlist( $tax_name ),
				),
			)
		);

	}

	return $conditions;
}

/**
 * Returns an array of args for registering coo0kies.
 *
 * @uses filter pum_get_conditions
 *
 * @return array
 */
function pum_get_conditions() {
	$conditions = array_merge(
		pum_generate_post_type_conditions(),
		pum_generate_taxonomy_conditions()
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

add_action( 'init', 'pum_register_conditions' );
