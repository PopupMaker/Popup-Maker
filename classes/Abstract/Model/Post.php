<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

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
	 */
	public $ID = 0;

	/**
	 * Declare the default properties in WP_Post as we can't extend it
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
	 * Whether the object is valid.
	 */
	protected $valid = true;

	/**
	 * Get things going
	 *
	 * @param WP_Post|int $post
	 */
	public function __construct( $post ) {
		if ( ! is_a( $post, 'WP_Post' ) ) {
			$post = get_post( $post );
		}

		$this->setup( $post );
	}

	/**
	 * Given the post data, let's set the variables
	 *
	 * @param WP_Post $post
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
	 * @param WP_Post $post
	 *
	 * @return bool
	 */
	protected function is_required_post_type( $post ) {
		if ( $this->required_post_type ) {

			if ( is_array( $this->required_post_type ) && ! in_array( $post->post_type, $this->required_post_type ) ) {

				return false;
			} else if ( is_string( $this->required_post_type ) && $this->required_post_type !== $post->post_type ) {

				return false;
			}
		}

		return true;
	}

	/**
	 * is triggered when invoking inaccessible methods in an object context.
	 *
	 * @param $name      string
	 * @param $arguments array
	 *
	 * @return mixed
	 * @link http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.methods
	 */
	public function __call( $name, $arguments ) {
		if ( method_exists( $this, 'get_' . $name ) ) {
			return call_user_func_array( array( $this, 'get_' . $name ), $arguments );
		}
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

			$meta = $this->get_meta( $key );

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