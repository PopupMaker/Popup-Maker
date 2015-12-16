<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PUM_Post' ) ) {

	class PUM_Post {

		/**
		 * The required post type of the object.
		 *
		 * @since 1.0.0
		 */
		protected $required_post_type = false;

		/**
		 * Whether the object is valid.
		 *
		 * @since 1.0.0
		 */
		protected $valid = false;

		/**
		 * The original WP_Post object
		 *
		 * @since 1.0.0
		 */
		protected $post;

		/**
		 * The post ID
		 *
		 * @since 1.0.0
		 */
		public $ID = 0;

		/**
		 * Declare the default properties in WP_Post as we can't extend it
		 *
		 * @since 1.0.0
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
		 * Get things going
		 *
		 * @since 1.0.0
		 *
		 * @param bool $_id
		 * @param array $_args
		 *
		 * return boolean $valid
		 */
		public function __construct( $_id = false, $_args = array() ) {
			$post = WP_Post::get_instance( $_id );

			$this->valid = $this->setup( $post );

			return $this->valid;
		}

		/**
		 * Given the post data, let's set the variables
		 *
		 * @since  1.0.0
		 *
		 * @param  object $post The Post Object
		 *
		 * @return bool If the setup was successful or not
		 */
		private function setup( $post ) {
			if ( ! is_object( $post ) ) {
				return false;
			}

			if ( ! is_a( $post, 'WP_Post' ) ) {
				return false;
			}

			if ( $this->required_post_type && $this->required_post_type !== $post->post_type ) {
				return false;
			}

			foreach ( get_object_vars( $post ) as $key => $value ) {
				$this->$key = $value;
			}

			return true;
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

				return new WP_Error( 'pum-post-invalid-property', sprintf( __( 'Can\'t get property %s', 'popup-maker' ), $key ) );

			}

		}

		/**
		 * Convert object to array.
		 *
		 * @since 1.0.0
		 *
		 * @return array Object as array.
		 */
		public function to_array() {
			$post = get_object_vars( $this );

			return $post;
		}


		/**
		 * Is object valid.
		 *
		 * @since 1.0.0
		 *
		 * @return bool.
		 */
		public function is_valid() {
			return $this->valid;
		}


	}

}