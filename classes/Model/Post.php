<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Model_Post
 */
abstract class PUM_Model_Post {

	/**
	 * Used for compatibility testing.
	 *
	 * @var int
	 */
	public $version = 1;

	/**
	 * @var array
	 */
	public static $instances = array();

	/**
	 * The post ID
	 */
	public $ID = 0;

	/**
	 * Declare the default properities in WP_Post as we can't extend it
	 */
	public $post_author = 0;
	/**
	 * @var string
	 */
	public $post_date = '0000-00-00 00:00:00';
	/**
	 * @var string
	 */
	public $post_date_gmt = '0000-00-00 00:00:00';
	/**
	 * @var string
	 */
	public $post_content = '';
	/**
	 * @var string
	 */
	public $post_title = '';
	/**
	 * @var string
	 */
	public $post_excerpt = '';
	/**
	 * @var string
	 */
	public $post_status = 'publish';
	/**
	 * @var string
	 */
	public $comment_status = 'open';
	/**
	 * @var string
	 */
	public $ping_status = 'open';
	/**
	 * @var string
	 */
	public $post_password = '';
	/**
	 * @var string
	 */
	public $post_name = '';
	/**
	 * @var string
	 */
	public $post_type = '';
	/**
	 * @var string
	 */
	public $to_ping = '';
	/**
	 * @var string
	 */
	public $pinged = '';
	/**
	 * @var string
	 */
	public $post_modified = '0000-00-00 00:00:00';
	/**
	 * @var string
	 */
	public $post_modified_gmt = '0000-00-00 00:00:00';
	/**
	 * @var string
	 */
	public $post_content_filtered = '';
	/**
	 * @var int
	 */
	public $post_parent = 0;
	/**
	 * @var string
	 */
	public $guid = '';
	/**
	 * @var int
	 */
	public $menu_order = 0;
	/**
	 * @var string
	 */
	public $post_mime_type = '';
	/**
	 * @var int
	 */
	public $comment_count = 0;
	/**
	 * @var
	 */
	public $filter;

	/**
	 * @var WP_Post
	 */
	public $post;

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
	 * @param int   $post
	 * @param array $_args
	 *
	 * @internal param bool $_id
	 */
	public function __construct( $post = null, $_args = array() ) {
		if ( ! isset( $post ) ) {
			$post = new WP_Post( $this );
		} elseif ( ! is_a( $post, 'WP_Post' ) ) {
			$post = WP_Post::get_instance( $post );
		}

		$this->_setup( $post );

		return $this;
	}

	/**
	 * Given the post data, let's set the variables
	 *
	 * @param  object $post The Post Object
	 */
	protected function _setup( $post ) {
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

		$this->setup();
	}

	/**
	 *
	 */
	public function setup() {
	}

	/**
	 * @param array $post_arr
	 * @param array $meta
	 * @param bool  $return_object
	 *
	 * @return bool|int|\PUM_Model_Post|\WP_Error
	 */
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
	 * @param int  $post_id Post ID.
	 * @param bool $force
	 *
	 * @return bool|PUM_Model_Post $post
	 * @internal param string $class
	 */
	public static function instance( $post_id, $force = false ) {

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
			$post = new $class( $_post );

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

	/**
	 *
	 */
	public function update_cache() {
		$cache_group = self::get_cache_group();
		pum_cache_set( $this->ID, $this, $cache_group );
	}

	/**
	 *
	 */
	public function clean_cache() {
		$cache_group = self::get_cache_group();
		pum_cache_delete( $this->ID, $cache_group );
	}

	/**
	 *
	 */
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
	 * @return    array
	 */
	public static function get_by( $args ) {
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

		return self::get( $args );
	}

	/**
	 * Get an array of new instances of this class (or an extension class), as a wrapper for a new WP_Query
	 *
	 * @param    array $wp_query_args Arguments to use for the WP_Query
	 *
	 * @return    array
	 */
	public static function get( $wp_query_args ) {
		$class = get_called_class();

		$defaults = array(
			'posts_per_page' => - 1,
		);

		$wp_query_args = wp_parse_args( $wp_query_args, $defaults );

		$query = new WP_Query( $wp_query_args );

		$out = array();

		foreach ( $query->posts as $post ) {
			$out[] = new $class( $post );
		}

		return $out;
	}

	/**
	 * Get an array of new instances of this class (or an extension class), as a wrapper for a new WP_Query
	 *
	 * @param    array $wp_query_args      Arguments to use for the WP_Query
	 *
	 * @return    WP_Query
	 */
	public static function query( $wp_query_args ) {
		$class = get_called_class();

		$defaults = array(
			'posts_per_page' => - 1,
		);

		$wp_query_args = wp_parse_args( $wp_query_args, $defaults );

		$query = new WP_Query( $wp_query_args );

		foreach ( $query->posts as $key => $post ) {
			$query->posts[ $key ] = new $class( $post );
		}

		return $query;
	}

	/**
	 * Magic __get function to dispatch a call to retrieve a private property
	 *
	 * @param $key
	 *
	 * @return mixed|WP_Error
	 */
	public function __get( $key ) {

		if ( method_exists( $this, 'get_' . $key ) ) {

			return call_user_func( array( $this, 'get_' . $key ) );

		} else {

			$meta = $this->get_meta( $key, true );

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
	 * @param      $key
	 * @param bool $single
	 *
	 * @return mixed|false
	 */
	public function get_meta( $key, $single = true ) {
		/**
		 * Checks for remapped meta values. This allows easily adding compatibility layers in the object meta.
		 */
		if ( ( $remapped_value = $this->remapped_meta( $key ) ) ) {
			return $remapped_value;
		}

		return get_post_meta( $this->ID, $key, $single );
	}

	/**
	 * @param $key
	 * @param $value
	 *
	 * @return bool|int
	 */
	public function update_meta( $key, $value ) {
		return update_post_meta( $this->ID, $key, $value );
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public function delete_meta( $key ) {
		return delete_post_meta( $this->ID, $key );
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
			$this->_author = PUM_Model_User::instance( $this->author_id() );
		}

		return $this->_author;
	}

	/**
	 * @return string
	 */
	public function author_id() {
		return get_post_field( 'post_author', $this->ID );
	}

	/**
	 *
	 */
	public function save() {
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
				foreach ( $new as $key => $value ) {
					$this->post->$key = $value;
				}
				$this->update_cache();
			}
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

	/**
	 * @param      $key
	 * @param bool $value
	 */
	public function set( $key, $value = false ) {

	}

}