<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class PUM_Model_Post {

	/**
	 * Used for compatibility testing.
	 *
	 * @var int
	 */
	public $version = 1;

	public static $instances = array();

	/**
	 * The post ID
	 */
	public $ID = 0;

	/**
	 * Declare the default properities in WP_Post as we can't extend it
	 */
	public $post_author = 0;
	public $post_date = '0000-00-00 00:00:00';
	public $post_date_gmt = '0000-00-00 00:00:00';
	public $post_content = '';
	public $post_title = '';
	public $post_excerpt = '';
	public $post_status = 'publish';
	public $comment_status = 'open';
	public $ping_status = 'open';
	public $post_password = '';
	public $post_name = '';
	public $post_type = '';
	public $to_ping = '';
	public $pinged = '';
	public $post_modified = '0000-00-00 00:00:00';
	public $post_modified_gmt = '0000-00-00 00:00:00';
	public $post_content_filtered = '';
	public $post_parent = 0;
	public $guid = '';
	public $menu_order = 0;
	public $post_mime_type = '';
	public $comment_count = 0;
	public $filter;

	/**
	 * @var WP_Post
	 */
	public $post;

	/**
	 * The post meta array.
	 */
	public $meta;

	/**
	 * The required post type of the object.
	 */
	protected $required_post_type = false;

	/**
	 * @var array Known meta keys for a post object.
	 */
	protected $_meta_keys = array();

	/**
	 * Whether the object is valid.
	 */
	protected $valid = true;

	/**
	 * Get things going
	 *
	 * @param int $post
	 * @param bool $autoload_meta
	 * @param array $_args
	 *
	 * @internal param bool $_id
	 */
	public function __construct( $post = null, $autoload_meta = true, $_args = array() ) {
		if ( ! isset( $post ) ) {
			$post = new WP_Post( $this );
		} elseif ( ! is_a( $post, 'WP_Post' ) ) {
			$post = WP_Post::get_instance( $post );
		}

		$this->_setup( $post, $autoload_meta );

		return $this;
	}

	/**
	 * Given the post data, let's set the variables
	 *
	 * @param  object $post The Post Object
	 * @param bool $autoload_meta
	 */
	protected function _setup( $post, $autoload_meta = true ) {
		if ( ! is_object( $post ) || ! is_a( $post, 'WP_Post' ) ) {
			$this->valid = false;

			return;
		}

		if ( $this->required_post_type ) {
			if ( is_array( $this->required_post_type ) && ! in_array( $post->post_type, $this->required_post_type ) ) {
				$this->valid = false;

				return;
			} else if ( is_string( $this->required_post_type ) && $this->required_post_type !== $post->post_type ) {
				$this->valid = false;

				return;
			}
		}

		$this->post = $post;

		foreach ( get_object_vars( $post ) as $key => $value ) {
			$this->$key = $value;
		}

		if ( $autoload_meta ) {
			$this->get_post_meta( true );
		}

		$this->setup();
	}

	/**
	 * Set/get the post meta for this object
	 *
	 * The $force parameter is in place to prevent hitting the database each time the method is called
	 * when we already have what we need in $this->meta
	 *
	 * @link    https://developer.wordpress.org/reference/functions/get_post_meta
	 *
	 * @param bool $force Whether to force load the post meta (helpful if $this->meta is already an array).
	 *
	 * @return array
	 */
	public function get_post_meta( $force = false ) {
		# make sure we have an ID
		if ( ! $this->ID ) {
			return array();
		}
		# if $this->meta is already an array
		if ( is_array( $this->meta ) ) {

			# return the array if we're not forcing the post meta to load
			if ( ! $force ) {
				return $this->meta;
			}
		} # if $this->meta isn't an array yet, initialize it as one
		else {
			$this->meta = array();
		}
		# get all post meta for the post
		$post_meta = get_post_meta( $this->ID );
		# if we found nothing
		if ( ! $post_meta ) {
			return $this->meta;
		}
		# loop through and clean up singleton arrays
		foreach ( $post_meta as $k => $v ) {
			# need to grab the first item if it's a single value
			if ( count( $v ) == 1 ) {
				$this->meta[ $k ] = maybe_unserialize( $v[0] );
			} # or store them all if there are multiple
			else {
				$this->meta[ $k ] = array_map( 'maybe_unserialize', $v );
			}
		}

		return $this->meta;
	}

	public function setup() {
	}

	public static function insert( $post_arr = array(), $meta = array(), $return_object = true ) {

		$id = wp_insert_post( $post_arr );

		if ( $id ) {
			if ( ! empty( $meta ) ) {
				foreach ( $meta as $key => $value ) {
					add_post_meta( $id, $key, $value, true );
				}
			}

			return $return_object ? self::instance( $id ) : $id;
		}

		return $id;
	}

	/**
	 * Retrieve WP_Post instance.
	 *
	 * @param int $post_id Post ID.
	 * @param bool $autoload_meta
	 * @param bool $force
	 *
	 * @return bool|PUM_Model_Post $post
	 * @internal param string $class
	 */
	public static function instance( $post_id, $autoload_meta = true, $force = false ) {

		// `model_post` passed to pum_cache_* will be appended with pum_ and stores a unique object for each post ID in any valid model.
		$cache_group = self::get_cache_group();

		/**
		 * @var PUM_Model_Post|false $post
		 */
		$post = pum_cache_get( $post_id, $cache_group );

		if ( $post === false || $force ) {
			if ( ! ( $_post = WP_Post::get_instance( $post_id ) ) ) {
				// Post doesn't exist.
				return false;
			}

			$class = get_called_class();

			/**
			 * @var PUM_Model_Post $post
			 */
			$post = new $class( $_post, $autoload_meta );

			if ( ! $post->is_valid() ) {
				// Post isn't correct post type for the called class. Do not cache it.
				return false;
			}

			$post->update_cache();
		}

		return $post->is_valid() ? $post : false;
	}

	/**
	 * @return string
	 */
	public static function get_cache_group() {
		return str_replace( 'pum_', '', strtolower( get_called_class() ) );
	}

	public function update_cache() {
		$cache_group = self::get_cache_group();
		pum_cache_set( $this->ID, $this, $cache_group );
	}

	public function clean_cache() {
		$cache_group = self::get_cache_group();
		pum_cache_delete( $this->ID, $cache_group );
	}

	public static function clear_cache() {
		$cache_group = self::get_cache_group();
		pum_cache_delete_group( $cache_group );
	}

	/**
	 * Get an array of new instances of this class (or an extension class) by meta_key value or values
	 *
	 * This method allows us to get posts via WP_Query, while also passing in key/value pairs and a 'meta_relation'
	 * argument to the same array
	 *
	 * The net effect is that we can easily get extended posts, complete with postmeta, by meta_key in a way
	 * that allows any arguments necessary from WP_Query
	 *
	 * If more control is needed over the meta_query item, you can
	 *
	 *        - use self::get() (a more basic wrapper for WP_Query) and pass in the meta_query manually
	 *        - use the '_get_posts_by' hook to access the query arguments
	 *        - use a normal WP_Query or get_posts; and then for each $post, create a new Helping_Friendly_Post( $post )
	 *
	 * @param    array $args {
	 *
	 *        Arguments for getting posts.  Besides the keys given, any arguments for WP_Query can also be included.
	 *
	 *        'meta_relation'
	 *        'meta' => array(
	 *            'meta_key_1' => 'value1',
	 *            'meta_key_2' => array( 'value2', 'value3' ),
	 *            ...
	 *        )
	 *    }
	 *
	 * @param    bool $autoload_post_meta Used when constructing the class instance
	 *
	 * @return    array
	 * @since    1.0.0
	 */
	public static function get_by( $args, $autoload_post_meta = true ) {
		$defaults = array(
			'posts_per_page' => - 1,
			'meta_relation'  => 'OR',
		);

		$args = wp_parse_args( $args, $defaults );

		$meta_query = array();
		if ( ! empty( $args['meta'] ) ) {
			foreach ( $args['meta'] as $k => $v ) {
				# if the key is not in our default array, we'll consider it a post meta key
				if ( ! in_array( $k, array_keys( $defaults ) ) ) {

					# the new item we'll add to meta_query
					$new_meta_query_item = array( 'key' => $k, 'value' => $v );

					# if we have an array of values
					if ( is_array( $v ) ) {
						$new_meta_query_item['compare'] = 'IN';
					} else {
						$new_meta_query_item['compare'] = '=';
					}
					$meta_query[] = $new_meta_query_item;
				}
			}
		}

		if ( ! empty( $meta_query ) ) {
			$meta_query['relation'] = $args['meta_relation'];
			$args['meta_query']     = $meta_query;
		}

		unset( $args['meta'], $args['meta_relation'] );

		return self::get( $args, $autoload_post_meta );
	}

	/**
	 * Get an array of new instances of this class (or an extension class), as a wrapper for a new WP_Query
	 *
	 * @param    array $wp_query_args Arguments to use for the WP_Query
	 * @param    bool $autoload_post_meta Used when constructing the class instance
	 *
	 * @return    array
	 */
	public static function get( $wp_query_args, $autoload_post_meta = true ) {
		$class = get_called_class();

		$defaults = array(
			'posts_per_page' => - 1,
		);

		$wp_query_args = wp_parse_args( $wp_query_args, $defaults );

		$query = new WP_Query( $wp_query_args );

		$out = array();

		foreach ( $query->posts as $post ) {

			$out[] = new $class( $post, $autoload_post_meta );
		}

		return $out;
	}

	/**
	 * Get an array of new instances of this class (or an extension class), as a wrapper for a new WP_Query
	 *
	 * @param    array $wp_query_args Arguments to use for the WP_Query
	 * @param    bool $autoload_post_meta Used when constructing the class instance
	 *
	 * @return    WP_Query
	 * @since    1.0.0
	 */
	public static function query( $wp_query_args, $autoload_post_meta = true ) {
		$class= get_called_class();

		$defaults = array(
			'posts_per_page' => - 1,
		);

		$wp_query_args = wp_parse_args( $wp_query_args, $defaults );

		$query = new WP_Query( $wp_query_args );

		foreach ( $query->posts as $key => $post ) {
			$query->posts[ $key ] = new $class( $post, $autoload_post_meta );
		}

		return $query;
	}


	/**
	 * Magic __get function to dispatch a call to retrieve a private property
	 *
	 * @since 1.0.0
	 *
	 * @param $key
	 *
	 * @return mixed|WP_Error
	 */
	public function __get( $key ) {

		if ( method_exists( $this, 'get_' . $key ) ) {

			return call_user_func( array( $this, 'get_' . $key ) );

		} else {

			$meta = get_post_meta( $this->ID, $key, true );

			if ( $meta ) {
				return $meta;
			}

			return new WP_Error( 'post-invalid-property', sprintf( __( 'Can\'t get property %s' ), $key ) );

		}

	}

	/**
	 * Is object valid.
	 *
	 * @return bool.
	 */
	public function is_valid() {
		return $this->valid;
	}

	/**
	 * @param $key
	 * @param bool $single
	 * @param bool $force
	 *
	 * @return mixed|false
	 */
	public function get_meta( $key, $single = true, $force = false ) {
		/**
		 * Checks for remapped meta values. This allows easily adding compatibility layers in the object meta.
		 */
		if ( ( $remapped_value = $this->remapped_meta( $key ) ) ) {
			return $remapped_value;
		}

		if ( ! isset ( $this->meta[ $key ] ) || $force ) {
			$this->meta[ $key ] = get_post_meta( $this->ID, $key, $single );

			$this->update_cache();
		}

		return isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : false;
	}

	public function update_meta( $key, $value ) {
		$updated = update_post_meta( $this->ID, $key, $value );

		if ( $updated ) {
			$this->meta[ $key ] = $value;
			$this->update_cache();
		}

		return $updated;
	}

	public function delete_meta( $key ) {
		$deleted = delete_post_meta( $this->ID, $key );

		if ( $deleted ) {
			unset( $this->meta[ $key ] );
			$this->update_cache();
		}

		return $deleted;
	}

	/**
	 * Allows for easy backward compatibility layer management in each child class.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function remapped_meta( $key = '' ) {
		return false;
	}

	/**
	 * @param int $size
	 *
	 * @return false|string
	 */
	public function author_avatar( $size = 35 ) {
		$author = $this->author();

		return is_object( $author ) ? $author->avatar( $size ) : false;
	}

	/**
	 * @return PUM_User|WP_Error
	 */
	public function author() {
		if ( ! isset( $this->_author ) ) {
			$this->_author = PUM_User::instance( $this->author_id() );
		}

		return $this->_author;
	}

	public function author_id() {
		return get_post_field( 'post_author', $this->ID );
	}

	public function save( $save_meta = false ) {
		$current  = $this->to_array();
		$original = $this->post->to_array();

		$new = array(
			'ID' => $this->ID,
		);

		// Only add changed values.
		foreach ( $current as $key => $value ) {
			if ( array_key_exists( $key, $original ) && $original[ $key ] != $value ) {
				$new[ $key ] = $value;
			}
		}

		if ( count( $new ) > 1 ) {
			$updated = wp_update_post( $new );

			if ( $updated ) {
				foreach( $new as $key => $value ) {
					$this->post->$key = $value;
				}
				$this->update_cache();
			}
		}

		if ( $save_meta ) {
			// TODO Implement this with a single query using $wpdb.
		}
	}

	/**
	 * Convert object to array.
	 *
	 * @return array Object as array.
	 */
	public function to_array() {
		$post = get_object_vars( $this );

		return $post;
	}

	/**
	 * @return bool
	 */
	public function is_trash() {
		return get_post_status( $this->ID ) == 'trash';
	}

	/**
	 * @return bool
	 */
	public function is_published() {
		return get_post_status( $this->ID ) == 'publish';
	}

	/**
	 * @return bool
	 */
	public function is_draft() {
		return get_post_status( $this->ID ) == 'draft';
	}

	/**
	 * @return bool
	 */
	public function is_private() {
		return get_post_status( $this->ID ) == 'private';
	}

	/**
	 * @return bool
	 */
	public function is_pending() {
		return get_post_status( $this->ID ) == 'pending';

	}

	public function set( $key, $value = false ) {

	}

}