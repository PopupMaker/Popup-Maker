<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pum_ajax_object_search() {
	$results = array(
		'items'       => array(),
		'total_count' => 0,
	);

	switch ( $_REQUEST['object_type'] ) {
		case 'post_type':
			$post_type = ! empty( $_REQUEST['object_key'] ) ? $_REQUEST['object_key'] : 'post';
			$args      = array(
				's'              => ! empty( $_REQUEST['s'] ) ? $_REQUEST['s'] : null,
				'post__in'       => ! empty( $_REQUEST['include'] ) ? array_map( 'intval', $_REQUEST['include'] ) : null,
				'page'           => ! empty( $_REQUEST['page'] ) ? absint( $_REQUEST['page'] ) : null,
				'posts_per_page' => 10,
			);

			$query = PUM_Helpers::post_type_selectlist( $post_type, $args, true );
			foreach ( $query['items'] as $name => $id ) {
				$results['items'][] = array(
					'id'   => $id,
					'text' => $name,
				);
			}
			$results['total_count'] = $query['total_count'];
			break;

		case 'taxonomy':
			$taxonomy = ! empty( $_REQUEST['object_key'] ) ? $_REQUEST['object_key'] : 'category';

			$args = array(
				'search'  => ! empty( $_REQUEST['s'] ) ? $_REQUEST['s'] : '',
				'include' => ! empty( $_REQUEST['include'] ) ? $_REQUEST['include'] : null,
				'page'    => ! empty( $_REQUEST['page'] ) ? absint( $_REQUEST['page'] ) : null,
				'number'  => 10,
			);

			$query = PUM_Helpers::taxonomy_selectlist( $taxonomy, $args, true );
			foreach ( $query['items'] as $name => $id ) {
				$results['items'][] = array(
					'id'   => $id,
					'text' => $name,
				);
			}
			$results['total_count'] = $query['total_count'];
			break;
	}

	echo json_encode( $results );
	die();
}

add_action( 'wp_ajax_pum_object_search', 'pum_ajax_object_search' );
//add_action( 'wp_ajax_nopriv_pum_object_search', 'pum_ajax_object_search' );
