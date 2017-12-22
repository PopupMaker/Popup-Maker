<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class PUM_Admin_Ajax {

	public static function init() {
		add_action( 'wp_ajax_pum_object_search', array( __CLASS__, 'object_search' ) );
	}

	public static function object_search() {
		$results = array(
			'items'       => array(),
			'total_count' => 0,
		);

		switch ( $_REQUEST['object_type'] ) {
			case 'post_type':
				$post_type = ! empty( $_REQUEST['object_key'] ) ? $_REQUEST['object_key'] : 'post';

				if ( ! empty( $_REQUEST['include'] ) ) {
					$query = PUM_Helpers::post_type_selectlist( $post_type, array(
						'post__in' => ! empty( $_REQUEST['include'] ) ? wp_parse_id_list( (array) $_REQUEST['include'] ) : null,
					), true );

					foreach ( $query['items'] as $name => $id ) {
						$results['items'][] = array(
							'id'   => $id,
							'text' => $name,
						);
					}
				} else {
					$query = PUM_Helpers::post_type_selectlist( $post_type, array(
						's'              => ! empty( $_REQUEST['s'] ) ? $_REQUEST['s'] : null,
						'paged'           => ! empty( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : null,
						'posts_per_page' => 10,
					), true );

					foreach ( $query['items'] as $name => $id ) {
						$results['items'][] = array(
							'id'   => $id,
							'text' => $name,
						);
					}

					$results['total_count'] = $query['total_count'];
				}
				break;
			case 'taxonomy':
				$taxonomy = ! empty( $_REQUEST['object_key'] ) ? $_REQUEST['object_key'] : 'category';

				if ( ! empty( $_REQUEST['include'] ) ) {
					$query = PUM_Helpers::taxonomy_selectlist( $taxonomy, array(
						'include' => ! empty( $_REQUEST['include'] ) ? wp_parse_id_list( (array) $_REQUEST['include'] ) : null,
					), true );

					foreach ( $query['items'] as $name => $id ) {
						$results['items'][] = array(
							'id'   => $id,
							'text' => $name,
						);
					}
				} else {
					$query = PUM_Helpers::taxonomy_selectlist( $taxonomy, array(
						'search' => ! empty( $_REQUEST['s'] ) ? $_REQUEST['s'] : '',
						'paged'   => ! empty( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : null,
						'number' => 10,
					), true );

					foreach ( $query['items'] as $name => $id ) {
						$results['items'][] = array(
							'id'   => $id,
							'text' => $name,
						);
					}

					$results['total_count'] = $query['total_count'];
				}
				break;
		}
		echo json_encode( $results );
		die();
	}

}
