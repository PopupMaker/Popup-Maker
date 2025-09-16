<?php
/**
 * Base Model for versioned Post objects.
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Base\Model;

use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class Post
 *
 * @since 1.21.0
 */
class Post {

	/**
	 * Current model version.
	 *
	 * @var int
	 */
	const MODEL_VERSION = 1;

	/**
	 * Post object.
	 *
	 * @var \WP_Post
	 */
	private $post;

	/**
	 * Call To Action id.
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * Call To Action slug.
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * Call To Action label.
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * Call To Action status.
	 *
	 * @var string
	 */
	public $status = '';

	/**
	 * Data version.
	 *
	 * @var int
	 */
	public $data_version = 0;

	/**
	 * Build a post.
	 *
	 * @param WP_Post $post Post data.
	 */
	public function __construct( $post ) {
		$this->post = $post;

		$properties = [
			'ID'     => $post->ID,
			'slug'   => $post->post_name,
			'title'  => $post->post_title,
			'status' => $post->post_status,
		];

		foreach ( $properties as $key => $value ) {
			$this->$key = $value;
		}

		$this->data_version = get_post_meta( $post->ID, 'data_version', true );

		if ( ! $this->data_version ) {
			$this->data_version = self::MODEL_VERSION;
			update_post_meta( $post->ID, 'data_version', self::MODEL_VERSION );
		}
	}

	/**
	 * Get edit link.
	 *
	 * @return string Empty string if user cannot edit post, otherwise admin edit URL.
	 */
	public function get_edit_link() {
		if ( current_user_can( 'edit_post', $this->ID ) ) {
			return admin_url( "post.php?action=edit&post_type={$this->post->post_type}&post=" . absint( $this->ID ) );
		}

		return '';
	}

	/**
	 * Get post meta value.
	 *
	 * @param string $key Meta key.
	 * @param bool   $single Whether to return a single value.
	 *
	 * @return ($single is true ? mixed : mixed[])
	 */
	public function get_meta( $key, $single = true ) {
		return get_post_meta( $this->ID, $key, $single );
	}

	/**
	 * Update post meta value.
	 *
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 *
	 * @return int|bool Meta ID on success, true on update, false on failure.
	 */
	public function update_meta( $key, $value ) {
		return update_post_meta( $this->ID, $key, $value );
	}

	/**
	 * Convert this call to action to an array.
	 *
	 * @return array{
	 *     ID: int,
	 *     slug: string,
	 *     title: string,
	 *     status: string
	 * }
	 */
	public function to_array() {
		return [
			'ID'     => $this->ID,
			'slug'   => $this->slug,
			'title'  => $this->title,
			'status' => $this->status,
		];
	}
}
