<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implements a batch processor for updating settings after new version.
 *
 * @since 1.7.0
 *
 * @see PUM_Abstract_Upgrade
 */
abstract class PUM_Abstract_Upgrade_Settings extends PUM_Abstract_Upgrade {

	/**
	 * Batch process ID.
	 *
	 * @var    string
	 */
	public $batch_id = '';

	/**
	 * Executes a single step in the batch process.
	 *
	 * @return int|string|WP_Error Next step number, 'done', or a WP_Error object.
	 */
	public function process_step() {

		// Allows sending a start & success message separately.
		if ( $this->step > 1 ) {
			return 'done';
		}

		$settings = pum_get_options();

		$this->process_settings( $settings );

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
				$message = sprintf( __( 'Updating settings for v%s compatibility.', 'popup-maker' ), '1.7' );
				break;

			case 'done':
				$message = __( 'Settings updated successfully.', 'popup-maker' );
				break;

			default:
				$message = '';
				break;
		}

		return $message;
	}

	/**
	 * Process needed upgrades on Popup Maker settings
	 *
	 * You need to handle saving!!!
	 *
	 * @param array $settings
	 */
	abstract public function process_settings( $settings = array() );
}
