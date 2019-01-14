<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implements a batch processor for migrating existing posts to new data structure.
 *
 * @since 1.7.0
 *
 * @see   PUM_Abstract_Upgrade
 * @see   PUM_Interface_Batch_PrefetchProcess
 * @see   PUM_Interface_Upgrade_Posts
 */
abstract class PUM_Abstract_Upgrade_Posts extends PUM_Abstract_Upgrade implements PUM_Interface_Upgrade_Posts {

	/**
	 * Batch process ID.
	 *
	 * @var    string
	 */
	public $batch_id;

	/**
	 * Post type.
	 *
	 * @var    string
	 */
	public $post_type = 'post';

	/**
	 * Post status to update.
	 *
	 * @var array
	 */
	public $post_status = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' );

	/**
	 * Number of posts to migrate per step.
	 *
	 * @var    int
	 */
	public $per_step = 1;

	/**
	 * @var array
	 */
	public $post_ids;

	/**
	 * @var array
	 */
	public $completed_post_ids;

	/**
	 * Allows disabling of the post_id array query prefetch for stepping.
	 *
	 * When true will prefetch all post_ids from the query and cache them, stepping through that array. WP_Query is only called once.
	 *
	 * When false the stepping will occur via a new WP_Query with pagination.
	 *
	 * True is useful if you are querying on data that will be changed during processing.
	 *
	 * False is useful if there may be a massive amount of post data to migrate.
	 * False is not useful when the query args are targeting data that will be changed.
	 * Ex: Query all posts with old_meta, then during each step moving old_meta to new_meta.
	 * In this example, the second query will not include posts updated in the first step, but then also sets an offset skipping posts that need update still.
	 *
	 * @var bool
	 */
	public $prefetch_ids = true;

	public function init( $data = null ) {
	}

	public function pre_fetch() {
		$total_to_migrate = $this->get_total_count();

		if ( ! $total_to_migrate ) {
			$posts = $this->get_posts( array(
				'fields'         => 'ids',
				'posts_per_page' => - 1,
			) );

			$posts = wp_parse_id_list( $posts );

			$total_to_migrate = count( $posts );

			if ( $this->prefetch_ids ) {
				$this->set_post_ids( $posts );
			}

			$this->set_total_count( $total_to_migrate );
		}
	}

	/**
	 * Gets the results of a custom post query.
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function get_posts( $args = array() ) {
		return get_posts( $this->query_args( $args ) );
	}

	/**
	 * Generates an array of query args for this upgrade.
	 *
	 * @uses self::custom_query_args();
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function query_args( $args = array() ) {

		$defaults = wp_parse_args( $this->custom_query_args(), array(
			'post_status' => $this->post_status,
			'post_type'   => $this->post_type,
		) );

		return wp_parse_args( $args, $defaults );
	}


	/**
	 * @return array
	 */
	public function custom_query_args() {
		return array();
	}

	/**
	 * Executes a single step in the batch process.
	 *
	 * @return int|string|WP_Error Next step number, 'done', or a WP_Error object.
	 */
	public function process_step() {
		$completed_post_ids = $this->get_completed_post_ids();

		if ( $this->prefetch_ids ) {
			$all_posts = $this->get_post_ids();
			$remaining_post_ids = array_diff( $all_posts, $completed_post_ids );
			$posts = array_slice( $remaining_post_ids, 0, $this->per_step );
		} else {
			$posts = $this->get_posts( array(
				'fields'         => 'ids',
				'posts_per_page' => $this->per_step,
				'offset'         => $this->get_offset(),
				'orderby'        => 'ID',
				'order'          => 'ASC',
			) );
		}

		if ( empty( $posts ) ) {
			return 'done';
		}

		foreach ( $posts as $post_id ) {
			$this->process_post( $post_id );
			$completed_post_ids[] = $post_id;
		}

		// Deduplicate.
		$completed_post_ids = wp_parse_id_list( $completed_post_ids );
		$this->set_completed_post_ids( $completed_post_ids );

		$this->set_current_count( count( $completed_post_ids ) );

		return ++ $this->step;
	}

	/**
	 * Retrieves a message for the given code.
	 *
	 * @param string $code Message code.
	 *
	 * @return string Message.
	 */
	public function get_message( $code ) {
		$post_type = get_post_type_object( $this->post_type );
		$labels = get_post_type_labels( $post_type );
		$singular = strtolower( $labels->singular_name );
		$plural = strtolower( $labels->name );

		switch ( $code ) {

			case 'start':
				$total_count = $this->get_total_count();

				$message = sprintf( _n( 'Updating %d %2$s.', 'Updating %d %3$s.', $total_count, 'popup-maker' ), number_format_i18n( $total_count ), $singular, $plural );
				break;

			case 'done':
				$final_count = $this->get_current_count();

				$message = sprintf( _n( '%s %2$s was updated successfully.', '%s %3$s were updated successfully.', $final_count, 'popup-maker' ), number_format_i18n( $final_count ), $singular, $plural );
				break;

			default:
				$message = '';
				break;
		}

		return $message;
	}

	/**
	 * Process needed upgrades on each post.
	 *
	 * @param int $post_id
	 */
	abstract public function process_post( $post_id = 0 );

	/**
	 * Full list of post_ids to be processed.
	 *
	 * @return array|bool Default false.
	 */
	protected function get_post_ids() {
		if ( ! isset( $this->post_ids ) || ! $this->post_ids ) {
			$this->post_ids =  PUM_DataStorage::get( "{$this->batch_id}_post_ids", false );

			if ( is_array( $this->post_ids ) ) {
				$this->post_ids = wp_parse_id_list( $this->post_ids );
			}
		}

		return $this->post_ids;
	}

	/**
	 * Sets list of post_ids to be processed.
	 *
	 * @param array $post_ids Full list of post_ids to be processed.
	 */
	protected function set_post_ids( $post_ids = array() ) {
		$this->post_ids = $post_ids;

		PUM_DataStorage::write( "{$this->batch_id}_post_ids", $post_ids );
	}

	/**
	 * Deletes the stored data for this process.
	 */
	protected function delete_post_ids() {
		$this->post_ids = false;
		PUM_DataStorage::delete( "{$this->batch_id}_post_ids" );
	}


	/**
	 * Full list of completed_post_ids to be processed.
	 *
	 * @return array|bool Default false.
	 */
	protected function get_completed_post_ids() {
		if ( ! isset( $this->completed_post_ids ) || ! $this->completed_post_ids ) {
			$completed_post_ids =  PUM_DataStorage::get( "{$this->batch_id}_completed_post_ids", array() );
			$this->completed_post_ids = wp_parse_id_list( $completed_post_ids );
		}

		return $this->completed_post_ids;
	}

	/**
	 * Sets list of completed_post_ids to be processed.
	 *
	 * @param array $completed_post_ids Full list of post_ids to be processed.
	 */
	protected function set_completed_post_ids( $completed_post_ids = array() ) {
		$this->completed_post_ids = wp_parse_id_list( $completed_post_ids );

		PUM_DataStorage::write( "{$this->batch_id}_completed_post_ids", $completed_post_ids );
	}

	/**
	 * Deletes the stored data for this process.
	 */
	protected function delete_completed_post_ids() {
		$this->completed_post_ids = false;
		PUM_DataStorage::delete( "{$this->batch_id}_completed_post_ids" );
	}
}
