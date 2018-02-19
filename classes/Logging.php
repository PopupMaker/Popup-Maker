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
	 * @var PUM_Logging
	 */
	public static $instance;

	/**
	 * @return PUM_Logging
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

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

	/**
	 * @param      $function
	 * @param      $version
	 * @param null $replacement
	 */
	public function log_deprecated_notice( $function, $version, $replacement = null ) {
		if ( ! is_null( $replacement ) ) {
			$notice = sprintf( '%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.', $function, $version, $replacement );
		} else {
			$notice = sprintf( '%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.', $function, $version );
		}

		$this->log( $notice );
	}
}
