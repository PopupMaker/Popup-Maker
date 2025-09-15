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
	 * @deprecated 1.21.0 Use PUM_Utils_Shortcodes::clean_do_shortcode
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
	 * @deprecated 1.14
	 *
	 * @param string $content Content potentially containing shortcodes.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_shortcodes_from_content( $content ) {
		return PUM_Utils_Shortcodes::get_shortcodes_from_content( $content );
	}

	/**
	 * Gets the directory caching should be stored in.
	 *
	 * Accounts for various adblock bypass options.
	 *
	 * @return string|false
	 */
	public static function get_cache_dir_url() {
		$upload_dir = \PopupMaker\get_upload_dir_url();
		if ( false === $upload_dir ) {
			return false;
		}

		if ( ! pum_get_option( 'bypass_adblockers', false ) ) {
			return trailingslashit( (string) $upload_dir ) . 'pum';
		}

		return (string) $upload_dir;
	}

	/**
	 * Gets the uploads directory path
	 *
	 * @since 1.10
	 * @deprecated 1.21.0 Use \PopupMaker\get_upload_dir_path instead.
	 *
	 * @param string $path A path to append to end of upload directory URL.
	 * @return bool|string The uploads directory path or false on failure
	 */
	public static function get_upload_dir_path( $path = '' ) {
		return \PopupMaker\get_upload_dir_path( $path );
	}

	/**
	 * Gets the uploads directory URL
	 *
	 * @since 1.10
	 * @deprecated 1.21.0 Use \PopupMaker\get_upload_dir_url instead.
	 *
	 * @param string $path A path to append to end of upload directory URL.
	 * @return bool|string The uploads directory URL or false on failure
	 */
	public static function get_upload_dir_url( $path = '' ) {
		return \PopupMaker\get_upload_dir_url( $path );
	}

	/**
	 * Gets the Uploads directory
	 *
	 * @since 1.10.0
	 * @deprecated 1.21.0 Use \PopupMaker\get_upload_dir instead.
	 *
	 * @return array{basedir: string, baseurl: string}|false An associated array with upload directory data or false on failure
	 */
	public static function get_upload_dir() {
		$result = \PopupMaker\get_upload_dir();
		return is_array( $result ) ? $result : false;
	}

	/**
	 * @deprecated 1.10.0 Use \PopupMaker\get_upload_dir_url instead.
	 *
	 * @param string $path A path to append to end of upload directory URL.
	 * @return string|false The uploads directory URL or false on failure
	 */
	public static function upload_dir_url( $path = '' ) {
		$result = \PopupMaker\get_upload_dir_url( $path );
		return false === $result ? false : (string) $result;
	}

	/**
	 * Sort array by priority value
	 *
	 * @param array{priority?: int} $a
	 * @param array{priority?: int} $b
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
	 * @param array<string,mixed> $arr
	 * @param string              $type
	 * @param bool                $reverse
	 *
	 * @return array<string,mixed>
	 * @deprecated 1.7.20
	 * @see        PUM_Utils_Array::sort instead.
	 */
	public static function sort_array( $arr = [], $type = 'key', $reverse = false ) {
		return PUM_Utils_Array::sort( $arr, $type, $reverse );
	}

	/**
	 * Query posts for selectlist options.
	 *
	 * @param string|string[]     $post_type Post type(s) to query.
	 * @param array<string,mixed> $args Query arguments.
	 * @param bool                $include_total Whether to include total count in results.
	 * @return ($include_total is true ? array{items: array<int,string>, total_count: int} : array<int,string>)
	 */
	public static function post_type_selectlist_query( $post_type, $args = [], $include_total = false ) {
		// Normalize post_type input - handles string, comma-separated string, or array
		$post_types = wp_parse_list( $post_type );

		// If only one post type, pass as string for consistency with WP_Query expectations
		$normalized_post_type = count( $post_types ) === 1 ? $post_types[0] : $post_types;

		$args = wp_parse_args(
			$args,
			[
				'posts_per_page'         => 10,
				'post_type'              => $normalized_post_type,
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

		$key = md5( wp_json_encode( $args ) ?: '' );

		if ( ! isset( $queries[ $key ] ) ) {
			$query = new WP_Query( $args );

			$posts = [];
			foreach ( $query->posts as $post ) {
				if ( $post instanceof WP_Post ) {
					$posts[ $post->ID ] = $post->post_title;
				}
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

	/**
	 * Query taxonomy terms for selectlist options.
	 *
	 * @param string[]|string     $taxonomies Taxonomy name(s) to query.
	 * @param array<string,mixed> $args Query arguments.
	 * @param bool                $include_total Whether to include total count in results.
	 * @return ($include_total is true ? array{items: array<int,string>, total_count: int} : array<int,string>)
	 */
	public static function taxonomy_selectlist_query( $taxonomies = [], $args = [], $include_total = false ) {
		if ( empty( $taxonomies ) ) {
			$taxonomies = [ 'category' ];
		}

		// Normalize taxonomy input - handles string, comma-separated string, or array
		$taxonomies = wp_parse_list( $taxonomies );

		// Ensure all taxonomy names are strings
		$taxonomies = array_map( 'strval', $taxonomies );

		$defaults = [
			'hide_empty' => false,
			'number'     => 10,
			'search'     => '',
			'include'    => null,
			'exclude'    => null,
			'offset'     => 0,
			'page'       => null,
			'taxonomy'   => $taxonomies,
		];

		$args = wp_parse_args( $args, $defaults );

		if ( $args['page'] ) {
			$args['offset'] = ( $args['page'] - 1 ) * $args['number'];
		}

		// Remove page parameter as it's not a valid get_terms argument
		unset( $args['page'] );

		// Query Caching.
		static $queries = [];

		$key = md5( wp_json_encode( $args ) ?: '' );

		if ( ! isset( $queries[ $key ] ) ) {
			$terms = [];

			$term_results = get_terms( $args );
			if ( ! is_wp_error( $term_results ) && is_array( $term_results ) ) {
				foreach ( $term_results as $term ) {
					if ( $term instanceof WP_Term ) {
						$terms[ $term->term_id ] = $term->name;
					}
				}
			}

			$total_args = [
				'taxonomy'   => $taxonomies,
				'hide_empty' => (bool) ( $args['hide_empty'] ?? false ),
			];

			if ( ! empty( $args['search'] ) ) {
				$total_args['search'] = (string) $args['search'];
			}

			if ( ! empty( $args['include'] ) ) {
				$total_args['include'] = $args['include'];
			}

			if ( ! empty( $args['exclude'] ) ) {
				$total_args['exclude'] = $args['exclude'];
			}

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
	 * Query users for selectlist options.
	 *
	 * @param array<string,mixed> $args Query arguments.
	 * @param bool                $include_total Whether to include total count in results.
	 *
	 * @return ($include_total is true ? array{items: array<int,string>, total_count: int} : array<int,string>)
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

		$key = md5( wp_json_encode( $args ) ?: '' );

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

	/**
	 * Get popup themes for selectlist options.
	 *
	 * @return array<int,string> Theme ID => title mapping
	 */
	public static function popup_theme_selectlist() {

		$themes = [];

		foreach ( pum_get_all_themes() as $theme ) {
			$themes[ $theme->ID ] = $theme->post_title;
		}

		return $themes;
	}

	/**
	 * Get popups for selectlist options.
	 *
	 * @param array<string,mixed> $args Query arguments.
	 * @return array<string,string> Popup ID => title mapping
	 */
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
