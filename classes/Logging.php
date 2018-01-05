<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

/**
 * Class PUM_Logging
 *
 * @since 1.7.0
 */
class PUM_Logging {

	/**
	 * @var bool
	 */
	public $is_writable = true;

	/**
	 * @var string
	 */
	private $filename   = '';

	/**
	 * @var string
	 */
	private $file       ='';

	/**
	 * Get things started
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Get things started
	 */
	public function init() {
		$upload_dir       = wp_upload_dir( null, false );
		$this->filename   = 'pum-debug.log';
		$this->file       = trailingslashit( $upload_dir['basedir'] ) . $this->filename;

		if ( ! is_writeable( $upload_dir['basedir'] ) ) {
			$this->is_writable = false;
		}
	}

	/**
	 * Retrieve the log data
	 *
	 * @return string
	 */
	public function get_log() {
		return $this->get_file();
	}

	/**
	 * Log message to file
	 */
	public function log( $message = '' ) {
		$message = date( 'Y-n-d H:i:s' ) . ' - ' . $message . "\r\n";
		$this->write_to_log( $message );

	}

	/**
	 * Retrieve the file data is written to
	 *
	 * @return string
	 */
	protected function get_file() {
		$file = '';

		if ( @file_exists( $this->file ) ) {
			if ( ! is_writeable( $this->file ) ) {
				$this->is_writable = false;
			}

			$file = @file_get_contents( $this->file );
		} else {
			@file_put_contents( $this->file, '' );
			@chmod( $this->file, 0664 );
		}

		return $file;
	}

	/**
	 * Write the log message
	 */
	protected function write_to_log( $message = '' ) {
		$file = $this->get_file();
		$file .= $message;
		@file_put_contents( $this->file, $file );
	}

	/**
	 * Write the log message
	 */
	public function clear_log() {
		@unlink( $this->file );
	}
}
