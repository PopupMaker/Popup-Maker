<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pum_ajax_object_search() {
	$results = array();

	switch ( $_REQUEST['object_type'] ) {
		case 'post_type':
			$post_type = ! empty( $_REQUEST['object_key'] ) ? $_REQUEST['object_key'] : 'post';
			$args      = array(
				's' => ! empty( $_REQUEST['s'] ) ? $_REQUEST['s'] : '',
				'post__in' => ! empty( $_REQUEST['include'] ) ? array_map( 'intval', $_REQUEST['include'] ) : null,
			);

			if ( isset( $_REQUEST['current_id'] ) ) {
				$args['post__not_in'] = array( intval( $_REQUEST['current_id'] ) );
			}

			foreach ( PUM_Helpers::post_type_selectlist( $post_type, $args ) as $name => $id ) {
				$results[] = array( 'id' => $id, 'name' => $name );
			}
			break;

		case 'taxonomy':
			$taxonomy = ! empty( $_REQUEST['object_key'] ) ? $_REQUEST['object_key'] : 'category';

			$args = array(
				'search' => ! empty( $_REQUEST['s'] ) ? $_REQUEST['s'] : '',
				'include' => ! empty( $_REQUEST['include'] ) ? $_REQUEST['include'] : null,
			);

			foreach ( PUM_Helpers::taxonomy_selectlist( $taxonomy, $args ) as $name => $id ) {
				$results[] = array( 'id' => $id, 'name' => $name );
			}
			break;
	}

	echo json_encode( $results );
	die();
}

add_action( 'wp_ajax_pum_object_search', 'pum_ajax_object_search' );
//add_action( 'wp_ajax_nopriv_pum_object_search', 'pum_ajax_object_search' );
