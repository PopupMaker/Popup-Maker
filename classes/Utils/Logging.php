<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

/**
 * Class PUM_Utils_Logging
 *
 * @since 1.8.0
 */
class PUM_Utils_Logging {

	/**
	 * @var bool
	 */
	public $is_writable = true;

	/**
	 * @var string
	 */
	private $filename = '';

	/**
	 * @var string
	 */
	private $file = '';

	/**
	 * @var string
	 */
	private $content;

	/**
	 * @var PUM_Utils_Logging
	 */
	public static $instance;

	/**
	 * @return PUM_Utils_Logging
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
		$upload_dir     = wp_upload_dir( null, false );
		$this->filename = 'pum-debug.log';
		$this->file     = trailingslashit( $upload_dir['basedir'] ) . $this->filename;

		if ( ! is_writeable( $upload_dir['basedir'] ) ) {
			$this->is_writable = false;
		}

		// Truncate long log files.
		if ( file_exists( $this->file ) && filesize( $this->file ) >= 1048576 ) {
			$this->truncate_log();
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
	 *
	 * @param string $message
	 */
	public function log( $message = '' ) {
		$this->write_to_log( date( 'Y-n-d H:i:s' ) . ' - ' . $message );
	}

	/**
	 * Log unique message to file.
	 *
	 * @param string $message
	 */
	public function log_unique( $message = '' ) {
		$contents = $this->get_file();

		if ( strpos( $contents, $message ) !== false ) {
			return;
		}

		$this->log( $message );
	}

	/**
	 * Retrieve the file data is written to
	 *
	 * @return string
	 */
	protected function get_file() {

		if ( ! isset( $this->content ) ) {
			$this->content = '';

			if ( @file_exists( $this->file ) ) {
				if ( ! is_writeable( $this->file ) ) {
					$this->is_writable = false;
				}

				$this->content = @file_get_contents( $this->file );
			} else {
				@file_put_contents( $this->file, '' );
				@chmod( $this->file, 0664 );
			}
		}

		return $this->content;
	}

	/**
	 * Write the log message
	 *
	 * @param string $message
	 */
	protected function write_to_log( $message = '' ) {

		// Disable logging by adding define( 'PUM_DISABLE_LOGGING', true );
		if ( defined( 'PUM_DISABLE_LOGGING' ) && PUM_DISABLE_LOGGING ) {
			return;
		}

		$file_contents = $this->get_file();

		$length = strlen( "\r\n" );

		// If it doesn't end with a new line, add one.
		if ( substr( $file_contents, - $length ) !== "\r\n" ) {
			$file_contents .= "\r\n";
		}

		$file_contents .= $message;

		$this->content = $file_contents;
		$this->save_logs();
	}

	/**
	 * Save the current contents to file.
	 */
	public function save_logs() {
		@file_put_contents( $this->file, $this->content );
	}

	/**
	 * Get a line count.
	 *
	 * @return int
	 */
	public function count_lines() {
		$file  = $this->get_file();
		$lines = explode( "\r\n", $file );

		return count( $lines );
	}

	/**
	 * Truncates a log file to maximum of 250 lines.
	 */
	public function truncate_log() {
		$content       = $this->get_file();
		$lines         = explode( "\r\n", $content );
		$lines         = array_slice( $lines, 0, 250 ); //50 is how many lines you want to keep
		$this->content = implode( "\r\n", $lines );
		$this->save_logs();
	}

	/**
	 * Delete the log file.
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

		$this->log_unique( $notice );
	}
}
