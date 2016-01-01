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

	public static function post_type_selectbox( $post_type, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'posts_per_page' => 30,
			'post_type'      => $post_type,
		) );

		$query = new WP_Query( $args );

		$posts = array();
		foreach ( $query->get_posts() as $post ) {
			$posts[ $post->post_title ] = $post->ID;
		}

		return $posts;
	}



	public static function post_selectbox( $args = array() ) {
		return static::post_type_selectbox( 'post', $args );
	}

	public static function taxonomy_selectbox( $taxonomies = array(), $args = array() ) {
		if ( empty ( $taxonomies ) ) {
			$taxonomies = array( 'category' );
		}

		$args = wp_parse_args( $args, array(
			'hide_empty' => false,
			'number'     => '',
			'fields'     => 'id=>name',
			'search'     => '',
		) );

		$terms = array();
		foreach ( get_terms( $taxonomies, $args ) as $id => $name ) {
			$terms[ $name ] = $id;
		}

		return $terms;
	}


}
