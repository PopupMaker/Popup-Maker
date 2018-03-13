<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implements a batch processor for migrating existing popup themes to new data structure.
 *
 * @since 1.7.0
 *
 * @see PUM_Abstract_Upgrade
 * @see PUM_Interface_Batch_PrefetchProcess
 * @see PUM_Interface_Upgrade_Posts
 */
abstract class PUM_Abstract_Upgrade_Themes extends PUM_Abstract_Upgrade implements PUM_Interface_Upgrade_Posts {

	/**
	 * Batch process ID.
	 *
	 * @var    string
	 */
	public $batch_id = '';

	/**
	 * Number of popups to migrate per step.
	 *
	 * @var    int
	 */
	public $per_step = 1;

	public function init( $data = null ) {}

	/**
	 * Count all themes to be upgraded.
	 */
	public function pre_fetch() {
		$total_to_migrate = $this->get_total_count();

		if ( false === $total_to_migrate ) {
			$themes = $this->get_themes( array(
				'fields'         => 'ids',
				'posts_per_page' => - 1,
			) );

			$total_to_migrate = count( $themes );

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
	public function get_themes( $args = array() ) {
		return get_posts( $this->query_args( $args ) );
	}

	/**
	 * Generates an array of query args for this upgrade.
	 *
	 * @uses PUM_Abstract_Upgrade_Themes::custom_query_args();
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function query_args( $args = array() ) {

		$defaults = wp_parse_args( $this->custom_query_args(), array(
			'post_status'    => 'any',
			'post_type'      => 'popup_theme',
		) );

		return wp_parse_args( $args, $defaults );
	}


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

		$themes = $this->get_themes( array(
			'fields'         => 'ids',
			'posts_per_page' => $this->per_step,
			'offset'         => $this->get_offset(),
			'orderby'        => 'ID',
			'order'          => 'ASC',
		) );

		if ( empty( $themes ) ) {
			return 'done';
		}

		$updated = array();

		foreach ( $themes as $theme_id ) {
			$updated[] = $this->process_theme( $theme_id );
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

				$message = sprintf( _n( 'Updating %d popup theme for v%s compatibility.', 'Updating %d popup themes for v%s compatibility.', $total_count, 'popup-maker' ), number_format_i18n( $total_count ), '1.7' );
				break;

			case 'done':
				$final_count = $this->get_current_count();

				$message = sprintf( _n( '%s popup theme was updated successfully.', '%s popup themes were updated successfully.', $final_count, 'popup-maker' ), number_format_i18n( $final_count ) );
				break;

			default:
				$message = '';
				break;
		}

		return $message;
	}

	/**
	 * Process needed upgrades on each popup theme.
	 *
	 * @param int $theme_id
	 *
	 * @return int $theme_id
	 */
	abstract public function process_theme( $theme_id = 0 );
}
