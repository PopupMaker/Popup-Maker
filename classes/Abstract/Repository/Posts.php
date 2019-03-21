<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Abstract_Repository_Posts
 *
 * Interface between WP_Query and our data needs. Essentially a query factory.
 */
abstract class PUM_Abstract_Repository_Posts implements PUM_Interface_Repository {

	/**
	 * WordPress query object.
	 *
	 * @var WP_Query
	 */
	protected $query;

	/**
	 * Array of hydrated object models.
	 *
	 * @var array
	 */
	protected $cache = array(
		'objects' => array(),
		'queries' => array(),
	);

	/**
	 * @var string
	 */
	protected $model;

	/**
	 * Should return a valid post type to test against.
	 *
	 * @return string
	 */
	protected function get_post_type() {
		return 'post';
	}

	/**
	 * Initialize the repository.
	 */
	protected function init() {
		$this->query = new WP_Query;
		$this->reset_strict_query_args();
	}

	public function __construct() {
		$this->init();
	}

	/**
	 * @return array
	 */
	public function default_query_args() {
		return array();
	}

	/**
	 * @var array
	 */
	protected $strict_query_args = array();

	/**
	 * Returns an array of default strict query args that can't be over ridden, such as post type.
	 *
	 * @return array
	 */
	protected function default_strict_query_args() {
		return array(
			'post_type' => $this->get_post_type(),
		);
	}

	/**
	 * Returns an array of enforced query args that can't be over ridden, such as post type.
	 *
	 * @return array
	 */
	protected function get_strict_query_args() {
		return $this->strict_query_args;
	}

	/**
	 * Sets a specific query arg to a strict value.
	 *
	 * @param      $key
	 * @param null $value
	 */
	protected function set_strict_query_arg( $key, $value = null ) {
		$this->strict_query_args[ $key ] = $value;
	}

	/**
	 * Returns an array of enforced query args that can't be over ridden, such as post type.
	 *
	 * @return array
	 */
	protected function reset_strict_query_args() {
		$this->strict_query_args = $this->default_strict_query_args();

		return $this->strict_query_args;
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	protected function _build_wp_query_args( $args = array() ) {
		$args = wp_parse_args( $args, $this->default_query_args() );

		$args = $this->build_wp_query_args( $args );

		return array_merge( $args, $this->get_strict_query_args() );
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	protected function build_wp_query_args( $args = array() ) {
		return $args;
	}

	/**
	 * @param int $id
	 *
	 * @return WP_Post|PUM_Abstract_Model_Post
	 * @throws \InvalidArgumentException
	 */
	public function get_item( $id ) {
		if ( ! $this->has_item( $id ) ) {
			throw new InvalidArgumentException( sprintf( __( 'No %s found with id %d.', 'popup-maker' ), $this->get_post_type(), $id ) );
		}

		return $this->get_model( $id );
	}

	/**
	 * @param $field
	 * @param $value
	 *
	 * @return PUM_Abstract_Model_Post|\WP_Post
	 */
	public function get_item_by( $field, $value ) {
		global $wpdb;

		$id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE %s = %s", $field, $value ) );

		if ( ! $id || ! $this->has_item( $id ) ) {
			throw new InvalidArgumentException( sprintf( __( 'No user found with %s %s.', 'popup-maker' ), $field, $value ) );
		}

		return $this->get_model( $id );
	}

	/**
	 * @param int $id
	 *
	 * @return bool
	 */
	public function has_item( $id ) {
		return get_post_type( $id ) === $this->get_post_type();
	}

	/**
	 * @param $args
	 *
	 * @return string
	 */
	protected function get_args_hash( $args ) {
		return md5( serialize( $args ) );
	}

	/**
	 * @param array $args
	 *
	 * @return WP_Post[]|PUM_Abstract_Model_Post[]
	 */
	public function get_items( $args = array() ) {
		/** Reset default strict query args. */
		$this->reset_strict_query_args();

		$args = $this->_build_wp_query_args( $args );

		$hash = $this->get_args_hash( $args );

		if ( ! isset( $this->cache['queries'][ $hash ] ) ) {
			/**
			 * Initialize a new query and return it.
			 *
			 * This also keeps the query cached for potential later usage via $this->get_last_query();
			 */
			$this->query->query( $args );

			$this->cache['queries'][ $hash ] = (array) $this->query->posts;

		}

		/** @var array $posts */
		$posts = $this->cache['queries'][ $hash ];

		/**
		 * Only convert to models if the model set is valid and not the WP_Post default.
		 */
		foreach ( $posts as $key => $post ) {
			$posts[ $key ] = $this->get_model( $post );
		}

		return $posts;
	}

	/**
	 * @param array $args
	 *
	 * @return int
	 */
	public function count_items( $args = array() ) {
		/** Reset default strict query args. */
		$this->reset_strict_query_args();

		/** Set several strict query arg overrides, no matter what args were passed. */
		$this->set_strict_query_arg( 'fields', 'ids' );
		$this->set_strict_query_arg( 'posts_per_page', 1 );

		/** We don't use  $this->query here to avoid returning count queries via $this->>get_last_query(); */
		$query = new WP_Query( $this->_build_wp_query_args( $args ) );

		return (int) $query->found_posts;
	}

	/**
	 * @return \WP_Query
	 */
	public function get_last_query() {
		return $this->query;
	}

	/**
	 * Assert that data is valid.
	 *
	 * @param array $data
	 *
	 * @throws InvalidArgumentException
	 *
	 * TODO Add better Exceptions via these guides:
	 * - https://www.brandonsavage.net/using-interfaces-for-exceptions/
	 * - https://www.alainschlesser.com/structuring-php-exceptions/
	 *
	 *  if ( isset( $data['subject'] ) && ! $data['subject'] ) {
	 *        throw new InvalidArgumentException( 'The subject is required.' );
	 *  }
	 */
	abstract protected function assert_data( $data );

	/**
	 * @param array $data
	 *
	 * @return WP_Post|PUM_Abstract_Model_Post
	 * @throws InvalidArgumentException
	 */
	public function create_item( $data ) {

		$data = wp_parse_args( $data, array(
			'content'    => '',
			'title'      => '',
			'meta_input' => array(),
		) );

		$this->assert_data( $data );

		$post_id = wp_insert_post( array(
			'post_type'    => $this->get_post_type(),
			'post_status'  => 'publish',
			'post_title'   => $data['title'],
			'post_content' => $data['content'],
			'meta_input'   => $data['meta_input'],
		), true );

		if ( is_wp_error( $post_id ) ) {
			throw new InvalidArgumentException( $post_id->get_error_message() );
		}

		return $this->get_item( $post_id );
	}

	/**
	 * @param int   $id
	 * @param array $data
	 *
	 * @return WP_Post|PUM_Abstract_Model_Post
	 * @throws Exception
	 */
	public function update_item( $id, $data ) {

		$this->assert_data( $data );

		/** @var WP_Post|PUM_Abstract_Model_Post $original */
		$original = $this->get_item( $id );

		$post_update = array();

		foreach ( $data as $key => $value ) {
			if ( $original->$key === $value ) {
				continue;
			}

			switch ( $key ) {
				default:
					$post_update[ $key ] = $value;
					break;
				case 'title':
					$post_update['post_title'] = $value;
					break;
				case 'content':
					$post_update['post_content'] = $value;
					break;

				case 'custom_meta_key':
					update_post_meta( $id, '_custom_meta_key', $value );
			}
		}

		if ( count( $post_update ) ) {
			$post_update['ID'] = $id;
			wp_update_post( $post_update );
		}

		return $this->get_item( $id );
	}

	/**
	 * @param $post
	 *
	 * @return string
	 */
	protected function get_post_hash( $post ) {
		return md5( serialize( $post ) );
	}

	/**
	 * @param $post
	 *
	 * @return bool
	 */
	protected function cached_model_exists( $post ) {
		return isset( $this->cache['objects'][ $post->ID ] ) && $this->get_post_hash( $post ) === $this->cache['objects'][ $post->ID ]['hash'];
	}

	/**
	 * @param int|WP_Post $id
	 *
	 * @return WP_Post|PUM_Abstract_Model_Post
	 */
	protected function get_model( $id ) {
		$post = is_a( $id, 'WP_Post' ) ? $id : get_post( $id );

		/**
		 * Only convert to models if the model set is valid and not the WP_Post default.
		 */
		$model = $this->model;
		if ( ! $model || 'WP_Post' === $model || ! class_exists( $model ) || is_a( $post, $model ) ) {
			return $post;
		}

		if ( ! $this->cached_model_exists( $post ) ) {
			$object = new $model( $post );

			$this->cache['objects'][ $post->ID ] = array(
				'object' => $object,
				'hash' => $this->get_post_hash( $post )
			);
		}

		return $this->cache['objects'][ $post->ID ]['object'];
	}

	/**
	 * @param int $id
	 *
	 * @return bool
	 */
	public function delete_item( $id ) {
		return EMPTY_TRASH_DAYS && (bool) wp_trash_post( $id );
	}

	/**
	 * @param int $id
	 *
	 * @return bool
	 */
	public function is_item_trashed( $id ) {
		return get_post_status( $id ) === 'trash';
	}

	/**
	 * @param int $id
	 *
	 * @return bool
	 */
	public function untrash_item( $id ) {
		return (bool) wp_untrash_post( $id );
	}

	/**
	 * @param int $id
	 *
	 * @return bool
	 */
	public function force_delete_item( $id ) {
		return (bool) wp_delete_post( $id, true );
	}

}
