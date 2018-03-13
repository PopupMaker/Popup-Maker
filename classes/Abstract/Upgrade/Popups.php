<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implements a batch processor for migrating existing popups to new data structure.
 *
 * @since 1.7.0
 *
 * @see   PUM_Abstract_Upgrade
 * @see   PUM_Interface_Batch_PrefetchProcess
 * @see   PUM_Interface_Upgrade_Posts
 */
abstract class PUM_Abstract_Upgrade_Popups extends PUM_Abstract_Upgrade implements PUM_Interface_Upgrade_Posts {

	/**
	 * Batch process ID.
	 *
	 * @var    string
	 */
	public $batch_id;

	/**
	 * Number of popups to migrate per step.
	 *
	 * @var    int
	 */
	public $per_step = 1;

	public function init( $data = null ) {
	}

	public function pre_fetch() {
		$total_to_migrate = $this->get_total_count();

		if ( false === $total_to_migrate ) {
			$popups = $this->get_popups( array(
				'fields'         => 'ids',
				'posts_per_page' => - 1,
			) );

			$total_to_migrate = count( $popups );

			$this->set_total_count( $total_to_migrate );
		}
	}

	/**
	 * Gets the results of a custom popup query.
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function get_popups( $args = array() ) {
		return get_posts( $this->query_args( $args ) );
	}

	/**
	 * Generates an array of query args for this upgrade.
	 *
	 * @uses PUM_Abstract_Upgrade_Popups::custom_query_args();
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function query_args( $args = array() ) {

		$defaults = wp_parse_args( $this->custom_query_args(), array(
			'post_status' => array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ),
			'post_type'   => 'popup',
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
		$current_count = $this->get_current_count();

		$popups = $this->get_popups( array(
			'fields'         => 'ids',
			'posts_per_page' => $this->per_step,
			'offset'         => $this->get_offset(),
			'orderby'        => 'ID',
			'order'          => 'ASC',
		) );

		if ( empty( $popups ) ) {
			return 'done';
		}

		$updated = array();

		foreach ( $popups as $popup_id ) {
			$this->process_popup( $popup_id );
			$updated[] = $popup_id;
		}

		// Deduplicate.
		$updated = wp_parse_id_list( $updated );

		$this->set_current_count( absint( $current_count ) + count( $updated ) );

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

		switch ( $code ) {

			case 'start':
				$total_count = $this->get_total_count();

				$message = sprintf( _n( 'Updating %d popup for v%s compatibility.', 'Updating %d popups for v%s compatibility.', $total_count, 'popup-maker' ), number_format_i18n( $total_count ), '1.7' );
				break;

			case 'done':
				$final_count = $this->get_current_count();

				$message = sprintf( _n( '%s popup was updated successfully.', '%s popups were updated successfully.', $final_count, 'popup-maker' ), number_format_i18n( $final_count ) );
				break;

			default:
				$message = '';
				break;
		}

		return $message;
	}

	/**
	 * Process needed upgrades on each popup.
	 *
	 * @param int $popup_id
	 */
	abstract public function process_popup( $popup_id = 0 );
}
