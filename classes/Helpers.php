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
			'paged'      => null,
			'taxonomy'   => $taxonomies,
		];

		$args = wp_parse_args( $args, $defaults );

		// Callers (e.g. the object-search AJAX handler) pass 'paged'; get_terms
		// has no paged concept, so treat it as an alias for 'page'. Without this
		// the offset never advances and every page returns the same terms,
		// causing duplicate and missing results in Select2. See issue #1206.
		if ( ! $args['page'] && $args['paged'] ) {
			$args['page'] = $args['paged'];
		}

		if ( $args['page'] ) {
			$args['offset'] = ( $args['page'] - 1 ) * $args['number'];
		}

		// Remove page parameters as they are not valid get_terms arguments.
		unset( $args['page'], $args['paged'] );

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
		if ( ! is_array( $args ) ) {
			return [];
		}

		$post_status = 'publish';

		if ( isset( $args['post_status'] ) ) {
			$statuses = array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) $args['post_status'] ) ) ) );

			if ( ! empty( $statuses ) && ! in_array( 'publish', $statuses, true ) && ! in_array( 'any', $statuses, true ) ) {
				return [];
			}

			if ( in_array( 'publish', $statuses, true ) && in_array( 'private', $statuses, true ) ) {
				$post_status = [ 'publish', 'private' ];
			}
		}

		if ( isset( $args['popups'] ) ) {
			$args['post__in'] = wp_parse_id_list( $args['popups'] );
			unset( $args['popups'] );
		}

		if ( ! isset( $args['orderby'] ) ) {
			$args['orderby'] = 'modified';
		} elseif ( 'name' === $args['orderby'] ) {
			$args['orderby'] = 'title';
			$args['order']   = isset( $args['order'] ) ? $args['order'] : 'ASC';
		} elseif ( 'activity' === $args['orderby'] ) {
			$args['orderby'] = 'modified';
		} elseif ( 'user_order' === $args['orderby'] && ! empty( $args['post__in'] ) ) {
			$args['orderby'] = 'post__in';
		}

		$query_args = array_merge(
			$args,
			[
				'post_type'        => 'popup',
				'post_status'      => $post_status,
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'order'            => isset( $args['order'] ) ? $args['order'] : 'DESC',
				'suppress_filters' => isset( $args['suppress_filters'] ) ? $args['suppress_filters'] : false,
			]
		);

		if ( is_array( $post_status ) ) {
			$query_args['perm'] = 'readable';
		}

		$use_filtered_posts = ! $query_args['suppress_filters'] && self::popup_query_result_filters_active();

		if ( $use_filtered_posts ) {
			$query_args['fields']                 = 'all';
			$query_args['update_post_meta_cache'] = false;
			$query_args['update_post_term_cache'] = false;
			$popup_list                           = [];

			foreach ( get_posts( $query_args ) as $popup ) {
				if ( ! $popup instanceof WP_Post || 'popup' !== $popup->post_type || ! in_array( $popup->post_status, (array) $post_status, true ) ) {
					continue;
				}

				if ( 'private' === $popup->post_status && ! current_user_can( 'read_post', $popup->ID ) ) {
					continue;
				}

				$popup_list[ (string) $popup->ID ] = (string) $popup->post_title;
			}

			$filtered_popup_list = apply_filters( 'popup_maker/popup_title_choices', $popup_list );

			if ( ! is_array( $filtered_popup_list ) ) {
				return $popup_list;
			}

			foreach ( $popup_list as $popup_id => $popup_title ) {
				if ( array_key_exists( $popup_id, $filtered_popup_list ) ) {
					$popup_list[ $popup_id ] = (string) $filtered_popup_list[ $popup_id ];
				} else {
					unset( $popup_list[ $popup_id ] );
				}
			}

			return $popup_list;
		}

		static $queries = [];

		$query_key = md5( wp_json_encode( $query_args ) . ':' . wp_cache_get_last_changed( 'posts' ) . ':' . get_current_user_id() );

		if ( isset( $queries[ $query_key ] ) ) {
			$popup_ids = $queries[ $query_key ];
		} else {
			$popup_ids             = array_map( 'absint', get_posts( $query_args ) );
			$queries[ $query_key ] = $popup_ids;
		}

		if ( empty( $popup_ids ) ) {
			return [];
		}
		$titles_by_id = \PopupMaker\plugin( 'popups' )->get_title_choices( $popup_ids );
		$popup_list   = [];

		foreach ( $popup_ids as $popup_id ) {
			if ( isset( $titles_by_id[ $popup_id ] ) ) {
				$popup_list[ (string) $popup_id ] = $titles_by_id[ $popup_id ];
			}
		}

		return $popup_list;
	}

	/**
	 * Check whether query-result filters can alter popup titles.
	 *
	 * WordPress registers its comment-status callback on `the_posts` by default;
	 * that callback does not alter popup titles and should not disable the fast
	 * projection path.
	 *
	 * @return bool
	 */
	private static function popup_query_result_filters_active() {
		global $wp_filter;

		if ( false !== has_filter( 'posts_results' ) ) {
			return true;
		}

		if ( false === has_filter( 'the_posts' ) ) {
			return false;
		}

		$the_posts_hook = isset( $wp_filter['the_posts'] ) ? $wp_filter['the_posts'] : null;

		if ( ! is_object( $the_posts_hook ) || ! isset( $the_posts_hook->callbacks ) || ! is_array( $the_posts_hook->callbacks ) ) {
			return true;
		}

		foreach ( $the_posts_hook->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = isset( $callback['function'] ) ? $callback['function'] : null;

				if ( '_close_comments_for_old_posts' !== $function ) {
					return true;
				}
			}
		}

		return false;
	}
}
