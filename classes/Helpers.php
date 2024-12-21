<?php
/**
 * Helpers class
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Helpers
 */
class PUM_Helpers {

	/**
	 * Process do_shortcode without allowing printed side effects.
	 *
	 * @deprecated X.X.X Use PUM_Utils_Shortcodes::clean_do_shortcode
	 *
	 * @param string $shortcode_text Unprocessed string with shortcodes.
	 *
	 * @return string
	 */
	public static function do_shortcode( $shortcode_text = '' ) {
		return PUM_Utils_Shortcodes::clean_do_shortcode( $shortcode_text );
	}

	/**
	 * Get all shortcodes from given content.
	 *
	 * @deprecated X.X.X Use PUM_Utils_Shortcodes::get_shortcodes_from_content
	 *
	 * @param string $content Content potentially containing shortcodes.
	 *
	 * @return array
	 */
	public static function get_shortcodes_from_content( $content ) {
		return PUM_Utils_Shortcodes::get_shortcodes_from_content( $content );
	}

	/**
	 * Gets the directory caching should be stored in.
	 *
	 * Accounts for various adblock bypass options.
	 *
	 * @return bool|string
	 */
	public static function get_cache_dir_url() {
		$upload_dir = self::get_upload_dir_url();
		if ( false === $upload_dir ) {
			return false;
		}

		if ( ! pum_get_option( 'bypass_adblockers', false ) ) {
			return trailingslashit( $upload_dir ) . 'pum';
		}

		return $upload_dir;
	}

	/**
	 * Gets the uploads directory path
	 *
	 * @since 1.10
	 * @param string $path A path to append to end of upload directory URL.
	 * @return bool|string The uploads directory path or false on failure
	 */
	public static function get_upload_dir_path( $path = '' ) {
		$upload_dir = self::get_upload_dir();
		if ( false !== $upload_dir && isset( $upload_dir['basedir'] ) ) {
			$dir = $upload_dir['basedir'];
			if ( ! empty( $path ) ) {
				$dir = trailingslashit( $dir ) . $path;
			}
			return $dir;
		} else {
			return false;
		}
	}

	/**
	 * Gets the uploads directory URL
	 *
	 * @since 1.10
	 * @param string $path A path to append to end of upload directory URL.
	 * @return bool|string The uploads directory URL or false on failure
	 */
	public static function get_upload_dir_url( $path = '' ) {
		$upload_dir = self::get_upload_dir();
		if ( false !== $upload_dir && isset( $upload_dir['baseurl'] ) ) {
			$url = preg_replace( '/^https?:/', '', $upload_dir['baseurl'] );
			if ( null === $url ) {
				return false;
			}
			if ( ! empty( $path ) ) {
				$url = trailingslashit( $url ) . $path;
			}
			return $url;
		} else {
			return false;
		}
	}

	/**
	 * Gets the Uploads directory
	 *
	 * @since 1.10
	 * @return bool|array An associated array with baseurl and basedir or false on failure
	 */
	public static function get_upload_dir() {
		if ( defined( 'IS_WPCOM' ) && IS_WPCOM ) {
			$wp_upload_dir = wp_get_upload_dir();
		} else {
			$wp_upload_dir = wp_upload_dir();
		}

		if ( isset( $wp_upload_dir['error'] ) && false !== $wp_upload_dir['error'] ) {
			pum_log_message( sprintf( 'Getting uploads directory failed. Error given: %s', esc_html( $wp_upload_dir['error'] ) ) );
			return false;
		} else {
			return $wp_upload_dir;
		}
	}

	/**
	 * @deprecated Use get_upload_dir_url instead.
	 */
	public static function upload_dir_url( $path = '' ) {
		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['baseurl'];
		$upload_dir = preg_replace( '/^https?:/', '', $upload_dir );

		if ( ! empty( $path ) ) {
			$upload_dir = trailingslashit( $upload_dir ) . $path;
		}

		return $upload_dir;
	}

	/**
	 * Sort array by priority value
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 * @see        PUM_Utils_Array::sort_by_priority instead.
	 *
	 * @deprecated 1.7.20
	 */
	public static function sort_by_priority( $a, $b ) {
		return PUM_Utils_Array::sort_by_priority( $a, $b );
	}


	/**
	 * Sort nested arrays with various options.
	 *
	 * @param array  $arr
	 * @param string $type
	 * @param bool   $reverse
	 *
	 * @return array
	 * @deprecated 1.7.20
	 * @see        PUM_Utils_Array::sort instead.
	 */
	public static function sort_array( $arr = [], $type = 'key', $reverse = false ) {
		return PUM_Utils_Array::sort( $arr, $type, $reverse );
	}

	public static function post_type_selectlist_query( $post_type, $args = [], $include_total = false ) {

		$args = wp_parse_args(
			$args,
			[
				'posts_per_page'         => 10,
				'post_type'              => $post_type,
				'post__in'               => null,
				'post__not_in'           => null,
				'post_status'            => null,
				'page'                   => 1,
				// Performance Optimization.
				'no_found_rows'          => ! $include_total ? true : false,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			]
		);

		if ( 'attachment' === $post_type ) {
			$args['post_status'] = 'inherit';
		}

		// Query Caching.
		static $queries = [];

		$key = md5( wp_json_encode( $args ) );

		if ( ! isset( $queries[ $key ] ) ) {
			$query = new WP_Query( $args );

			$posts = [];
			foreach ( $query->posts as $post ) {
				$posts[ $post->ID ] = $post->post_title;
			}

			$results = [
				'items'       => $posts,
				'total_count' => $query->found_posts,
			];

			$queries[ $key ] = $results;
		} else {
			$results = $queries[ $key ];
		}

		return ! $include_total ? $results['items'] : $results;
	}

	public static function taxonomy_selectlist_query( $taxonomies = [], $args = [], $include_total = false ) {
		if ( empty( $taxonomies ) ) {
			$taxonomies = [ 'category' ];
		}

		$args = wp_parse_args(
			$args,
			[
				'hide_empty' => false,
				'number'     => 10,
				'search'     => '',
				'include'    => null,
				'exclude'    => null,
				'offset'     => 0,
				'page'       => null,
			]
		);

		$args['taxonomy'] = $taxonomies;

		if ( $args['page'] ) {
			$args['offset'] = ( $args['page'] - 1 ) * $args['number'];
		}

		// Query Caching.
		static $queries = [];

		$key = md5( wp_json_encode( $args ) );

		if ( ! isset( $queries[ $key ] ) ) {
			$terms = [];

			foreach ( get_terms( $args ) as $term ) {
				$terms[ $term->term_id ] = $term->name;
			}

			$total_args = $args;
			unset( $total_args['number'] );
			unset( $total_args['offset'] );

			$total_args['taxonomy'] = $taxonomies;

			$results = [
				'items'       => $terms,
				'total_count' => $include_total ? wp_count_terms( $total_args ) : null,
			];

			$queries[ $key ] = $results;
		} else {
			$results = $queries[ $key ];
		}

		return ! $include_total ? $results['items'] : $results;
	}


	/**
	 * @param array $args
	 * @param bool  $include_total
	 *
	 * @return array|mixed
	 */
	public static function user_selectlist_query( $args = [], $include_total = false ) {

		$args = wp_parse_args(
			$args,
			[
				'role'        => null,
				'count_total' => ! $include_total ? true : false,
			]
		);

		// Query Caching.
		static $queries = [];

		$key = md5( wp_json_encode( $args ) );

		if ( ! isset( $queries[ $key ] ) ) {
			$query = new WP_User_Query( $args );

			$users = [];
			foreach ( $query->get_results() as $user ) {
				/** @var WP_User $user */
				$users[ $user->ID ] = $user->display_name;
			}

			$results = [
				'items'       => $users,
				'total_count' => $query->get_total(),
			];

			$queries[ $key ] = $results;
		} else {
			$results = $queries[ $key ];
		}

		return ! $include_total ? $results['items'] : $results;
	}

	public static function popup_theme_selectlist() {

		$themes = [];

		foreach ( pum_get_all_themes() as $theme ) {
			$themes[ $theme->ID ] = $theme->post_title;
		}

		return $themes;
	}

	public static function popup_selectlist( $args = [] ) {
		$popup_list = [];

		$popups = pum_get_all_popups( $args );

		foreach ( $popups as $popup ) {
			if ( $popup->is_published() ) {
				$popup_list[ (string) $popup->ID ] = $popup->post_title;
			}
		}

		return $popup_list;
	}
}
