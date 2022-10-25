<?php
/**
 * Post Model Handler
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Abstract_Model_Post
 */
abstract class PUM_Abstract_Model_Post {

	/**
	 * The current model version.
	 *
	 * Used for compatibility testing.
	 * 1 - v1.0.0
	 *
	 * @var int
	 */
	public $model_version = 1;

	/**
	 * The version of the data currently stored for the current item.
	 *
	 * 1 - v1.0.0
	 *
	 * @var int
	 */
	public $data_version;

	/**
	 * The post ID
	 *
	 * @var string
	 */
	public $ID = 0;

	/**
	 * Declare the default properties in WP_Post as we can't extend it
	 *
	 * @var string
	 */
	public $post_author = 0;

	/**
	 * Date of the post.
	 *
	 * @var string
	 */
	public $post_date = '0000-00-00 00:00:00';

	/**
	 * Date of the post in the GMT timezone.
	 *
	 * @var string
	 */
	public $post_date_gmt = '0000-00-00 00:00:00';

	/**
	 * The post content. Default empty.
	 *
	 * @var string
	 */
	public $post_content = '';

	/**
	 * The post title.
	 *
	 * @var string
	 */
	public $post_title = '';

	/**
	 * The post excerpt. Default empty.
	 *
	 * @var string
	 */
	public $post_excerpt = '';

	/**
	 * The post status.
	 *
	 * @var string
	 */
	public $post_status = 'publish';

	/**
	 * Whether the post can accept comments.
	 *
	 * @var string
	 */
	public $comment_status = 'open';

	/**
	 * Whether the post can accept pings.
	 *
	 * @var string
	 */
	public $ping_status = 'open';

	/**
	 * The password to access the post.
	 *
	 * @var string
	 */
	public $post_password = '';

	/**
	 * The post name. Default is the sanitized post title when creating a new post.
	 *
	 * @var string
	 */
	public $post_name = '';

	/**
	 * The post type.
	 *
	 * @var string
	 */
	public $post_type = '';

	/**
	 * URL to ping.
	 *
	 * @var string
	 */
	public $to_ping = '';

	/**
	 * URL that was pinged.
	 *
	 * @var string
	 */
	public $pinged = '';

	/**
	 * Date when the post was last modified.
	 *
	 * @var string
	 */
	public $post_modified = '0000-00-00 00:00:00';

	/**
	 * Date when the post was last modified in the GMT timezone.
	 *
	 * @var string
	 */
	public $post_modified_gmt = '0000-00-00 00:00:00';

	/**
	 * The filtered post content.
	 *
	 * @var string
	 */
	public $post_content_filtered = '';

	/**
	 * Set this for the post it belongs to, if any. Default 0.
	 *
	 * @var int
	 */
	public $post_parent = 0;

	/**
	 * Global Unique ID for referencing the post.
	 *
	 * @var string
	 */
	public $guid = '';

	/**
	 * Order the post should be displayed in.
	 *
	 * @var int
	 */
	public $menu_order = 0;

	/**
	 * Mime type of the post.
	 *
	 * @var string
	 */
	public $post_mime_type = '';

	/**
	 * Comment count for post.
	 *
	 * @var int
	 */
	public $comment_count = 0;

	/**
	 * Post filter.
	 *
	 * @var string
	 */
	public $filter;

	/**
	 * The post object for the current post.
	 *
	 * @var WP_Post
	 */
	public $post;

	/**
	 * The required post type of the object.
	 *
	 * @var bool
	 */
	protected $required_post_type = false;

	/**
	 * Whether the object is valid.
	 *
	 * @var bool
	 */
	protected $valid = true;

	/**
	 * Get things going.
	 *
	 * @param WP_Post|int $post  Post int.
	 */
	public function __construct( $post ) {
		if ( ! is_a( $post, 'WP_Post' ) ) {
			$post = get_post( $post );
		}

		$this->setup( $post );
	}

	/**
	 * Given the post data, let's set the variables.
	 *
	 * @param WP_Post $post  Validate post.
	 */
	protected function setup( $post ) {
		if ( ! is_a( $post, 'WP_Post' ) || ! $this->is_required_post_type( $post ) ) {
			$this->valid = false;

			return;
		}

		$this->post = $post;

		foreach ( get_object_vars( $post ) as $key => $value ) {
			$this->$key = $value;
		}
	}

	/**
	 * Function is_required_post_type - returns boolean.
	 *
	 * @param WP_Post $post  Post object.
	 *
	 * @return bool
	 */
	protected function is_required_post_type( $post ) {
		if ( $this->required_post_type ) {

			if ( is_array( $this->required_post_type ) && ! in_array( $post->post_type, $this->required_post_type ) ) {

				return false;
			} elseif ( is_string( $this->required_post_type ) && $this->required_post_type !== $post->post_type ) {

				return false;
			}
		}

		return true;
	}

	/**
	 * Is triggered when invoking inaccessible methods in an object context.
	 *
	 * @param string $name  The name of the method being called.
	 * @param array  $arguments  An enumerated array containing the parameters passed to the method.
	 *
	 * @return mixed
	 * @link http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.methods
	 */
	public function __call( $name, $arguments ) {
		if ( method_exists( $this, 'get_' . $name ) ) {
			return call_user_func_array( [ $this, 'get_' . $name ], $arguments );
		}
	}

	/**
	 * Magic __get function to dispatch a call to retrieve a private property.
	 *
	 * @param mixed $key Private property being retrieved.
	 *
	 * @return mixed|WP_Error
	 */
	public function __get( $key ) {

		if ( method_exists( $this, 'get_' . $key ) ) {

			return call_user_func( [ $this, 'get_' . $key ] );

		} else {

			$meta = $this->get_meta( $key );

			if ( $meta ) {
				return $meta;
			}

			/* translators: returns error. */
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
	 * Function get_meta.
	 *
	 * @param      $key
	 * @param bool $single  If true, return only the first value of the specified value.
	 *
	 * @return mixed|false
	 */
	public function get_meta( $key, $single = true ) {
		/**
		 * Checks for remapped meta values. This allows easily adding compatibility layers in the object meta.
		 */
		if ( false !== $remapped_value = $this->remapped_meta( $key ) ) {
			return $remapped_value;
		}

		return get_post_meta( $this->ID, $key, $single );
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param bool   $unique
	 *
	 * @return bool|int
	 */
	public function add_meta( $key, $value, $unique = false ) {
		return add_post_meta( $this->ID, $key, $value, $unique );
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return bool|int
	 */
	public function update_meta( $key, $value ) {
		return update_post_meta( $this->ID, $key, $value );
	}

	/**
	 * @param string $key
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
	 * @return int
	 */
	public function author_id() {
		return (int) $this->post_author;
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
}
