<?php
/**
 * Condition
 *
 * @package     PUM
 * @subpackage  Classes/PUM_Condition
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Helpers {

	public static function post_type_selectlist( $post_type, $args = array(), $include_total = false ) {
		$args = wp_parse_args( $args, array(
			'posts_per_page' => 10,
			'post_type'      => $post_type,
			'post__in'       => null,
			'post__not_in'   => null,
			'post_status'    => null,
			'page'           => 1,
		) );

		if ( $post_type == 'attachment' ) {
			$args['post_status'] = 'inherit';
		}


		$query = new WP_Query( $args );

		$posts = array();
		foreach ( $query->get_posts() as $post ) {
			$posts[ $post->post_title ] = $post->ID;
		}

		return ! $include_total ? $posts : array(
			'items'       => $posts,
			'total_count' => $query->found_posts,
		);
	}

	public static function taxonomy_selectlist( $taxonomies = array(), $args = array(), $include_total = false ) {
		if ( empty ( $taxonomies ) ) {
			$taxonomies = array( 'category' );
		}

		$args = wp_parse_args( $args, array(
			'hide_empty' => false,
			'number'     => 10,
			'search'     => '',
			'include'    => null,
			'offset'     => 0,
			'page'       => null,
		) );

		if ( $args['page'] ) {
			$args['offset'] = ( $args['page'] - 1 ) * $args['number'];
		}

		$terms = array();

		foreach ( get_terms( $taxonomies, $args ) as $term ) {
			$terms[ $term->name ] = $term->term_id;
		}

		$total_args = $args;
		unset( $total_args['number'] );
		unset( $total_args['offset'] );

		return ! $include_total ? $terms : array(
			'items'       => $terms,
			'total_count' => count( get_terms( $taxonomies, $total_args ) ),
		);
	}


}
