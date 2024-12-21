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
 * @since X.X.X
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
	public $id = 0;

	/**
	 * Call To Action slug.
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Call To Action label.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Call To Action status.
	 *
	 * @var string
	 */
	public $status;

	/**
	 * Data version.
	 *
	 * @var int
	 */
	public $data_version;

	/**
	 * Build a post.
	 *
	 * @param \WP_Post|array<string,mixed> $post Post data.
	 */
	public function __construct( $post ) {
		$this->post = $post;

		$properties = [
			'id'     => $post->ID,
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
	 * @return string
	 */
	public function get_edit_link() {
		if ( current_user_can( 'edit_post', $this->id ) ) {
			return admin_url( "post.php?action=edit&post_type={$this->post->post_type}&post=" . absint( $this->id ) );
		}

		return '';
	}

	/**
	 * Convert this call to action to an array.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array() {
		return [
			'id'     => $this->id,
			'slug'   => $this->slug,
			'title'  => $this->title,
			'status' => $this->status,
		];
	}
}
