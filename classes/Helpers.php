<?php
/**
 * Helpers class
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Helpers {

	public static function do_shortcode( $shortcode_text = '' ) {
		ob_start();

		$content = do_shortcode( $shortcode_text );

		$ob_content = ob_get_clean();

		if ( ! empty( $ob_content ) ) {
			$content .= $ob_content;
		}

		return $content;
	}

	public static function get_shortcodes_from_content( $content ) {
		$pattern    = get_shortcode_regex();
		$shortcodes = [];
		if ( preg_match_all( '/' . $pattern . '/s', $content, $matches ) ) {
			foreach ( $matches[0] as $key => $value ) {
				$shortcodes[ $key ] = [
					'full_text' => $value,
					'tag'       => $matches[2][ $key ],
					'atts'      => shortcode_parse_atts( $matches[3][ $key ] ),
					'content'   => $matches[5][ $key ],
				];

				if ( ! empty( $shortcodes[ $key ]['atts'] ) ) {
					foreach ( $shortcodes[ $key ]['atts'] as $attr_name => $attr_value ) {
						// Filter numeric keys as they are valueless/truthy attributes.
						if ( is_numeric( $attr_name ) ) {
							$shortcodes[ $key ]['atts'][ $attr_value ] = true;
							unset( $shortcodes[ $key ]['atts'][ $attr_name ] );
						}
					}
				}
			}
		}

		return $shortcodes;
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
	 * @param array  $array
	 * @param string $type
	 * @param bool   $reverse
	 *
	 * @return array
	 * @deprecated 1.7.20
	 * @see        PUM_Utils_Array::sort instead.
	 */
	public static function sort_array( $array = [], $type = 'key', $reverse = false ) {
		return PUM_Utils_Array::sort( $array, $type, $reverse );
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

		$key = md5( serialize( $args ) );

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

		if ( $args['page'] ) {
			$args['offset'] = ( $args['page'] - 1 ) * $args['number'];
		}

		// Query Caching.
		static $queries = [];

		$key = md5( serialize( $args ) );

		if ( ! isset( $queries[ $key ] ) ) {
			$terms = [];

			foreach ( get_terms( $taxonomies, $args ) as $term ) {
				$terms[ $term->term_id ] = $term->name;
			}

			$total_args = $args;
			unset( $total_args['number'] );
			unset( $total_args['offset'] );

			$results = [
				'items'       => $terms,
				'total_count' => $include_total ? wp_count_terms( $taxonomies, $total_args ) : null,
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

		$key = md5( serialize( $args ) );

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
