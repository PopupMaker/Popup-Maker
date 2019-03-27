<?php

/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Post_Types
 */
class PUM_Popups {

	/**
	 * Hook the initialize method to the WP init action.
	 */
	public static function init() {
	}


	/**
	 * @deprecated 1.8.0
	 * @remove 1.9.0
	 *
	 * @return \WP_Query
	 */
	public static function get_all() {
		static $query;

		if ( ! isset( $query ) ) {
			$query = self::query();
		}

		return $query;
	}

	/**
	 * @deprecated 1.8.0
	 * @remove 1.9.0
	 *
	 * @return \WP_Query
	 */
	public static function query( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'post_type'      => 'popup',
			'posts_per_page' => - 1,
		) );

		return new WP_Query( $args );
	}

}
