<?php
/**
 * Debug Logging Utility
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

/**
 * Class PUM_Utils_Logging
 *
 * @since 1.8.0
 */
class PUM_Utils_Logging {

	/**
	 * Whether the log file is writable.
	 *
	 * @var bool
	 */
	public $is_writable = false;

	/**
	 * Log file name.
	 *
	 * @var string
	 */
	private $filename = '';

	/**
	 * Log file path.
	 *
	 * @var string
	 */
	private $file = '';

	/**
	 * File system API.
	 *
	 * @var WP_Filesystem_Base|false
	 */
	private $fs;

	/**
	 * Log file content.
	 *
	 * @var string
	 */
	private $content;

	/**
	 * Instance.
	 *
	 * @var PUM_Utils_Logging
	 */
	public static $instance;

	/**
	 * Get instance.
	 *
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
		if ( $this->disabled() ) {
			return;
		}

		$this->init_fs();
		$this->init();

		// On shutdown, save the log file.
		add_action( 'shutdown', [ $this, 'save_logs' ] );
	}

	/**
	 * Check if logging is disabled.
	 *
	 * @return bool
	 */
	public function disabled() {
		// Disable logging by adding define( 'PUM_DISABLE_LOGGING', true );.
		return defined( 'PUM_DISABLE_LOGGING' ) && PUM_DISABLE_LOGGING;
	}

	/**
	 * Check if logging is writeable & not disabled.
	 *
	 * If this is true the $fs property will be set.
	 *
	 * @return bool
	 */
	public function enabled() {
		return ! $this->disabled() && $this->is_writable;
	}

	/**
	 * Initialize the file system.
	 *
	 * - Check if the file system is writable.
	 * - Check if the upload directory is writable.
	 * - Set the file system instance.
	 *
	 * @return void
	 */
	public function init_fs() {
		$this->fs = $this->file_system();

		// If the file system is not set, we can't check if it's writable.
		if ( ! $this->fs ) {
			return;
		}

		$this->is_writable = false !== $this->fs && 'direct' === $this->fs->method;

		if ( ! $this->is_writable ) {
			return;
		}

		$upload_dir = PUM_Helpers::get_upload_dir();

		if ( ! $upload_dir ) {
			$this->is_writable = false;
		}

		if (
			! method_exists( $this->fs, 'is_writable' ) ||
			! $this->fs->is_writable( $upload_dir['basedir'] )
		) {
			$this->is_writable = false;
		}
	}

	/**
	 * Get working WP Filesystem instance
	 *
	 * @return WP_Filesystem_Base|false
	 */
	public function file_system() {
		global $wp_filesystem;

		require_once ABSPATH . 'wp-admin/includes/file.php';

		// If for some reason the include doesn't work as expected just return false.
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			return false;
		}

		$writable = WP_Filesystem( false, '', true );

		// We consider the directory as writable if it uses the direct transport,
		// otherwise credentials would be needed.
		return ( $writable && 'direct' === $wp_filesystem->method ) ? $wp_filesystem : false;
	}

	/**
	 * Get things started.
	 *
	 * - Get filetoken & name.
	 * - Check if old log file exists, move it to new location.
	 * - Check if new log file exists, if not create it.
	 * - Set log content.
	 * - Truncate long log files.
	 *
	 * @return void
	 */
	public function init() {
		if ( ! $this->enabled() ) {
			return;
		}

		$upload_dir = PUM_Helpers::get_upload_dir();

		$file_token = get_option( 'pum_debug_log_token' );
		if ( false === $file_token ) {
			$file_token = uniqid( wp_rand(), true );
			update_option( 'pum_debug_log_token', $file_token );
		}

		$this->filename = "pum-debug-{$file_token}.log"; // ex. pum-debug-5c2f6a9b9b5a3.log.
		$this->file     = trailingslashit( $upload_dir['basedir'] ) . $this->filename;
		$old_file       = trailingslashit( $upload_dir['basedir'] ) . 'pum-debug.log';

		// If old file exists, move it.
		if ( $this->fs->exists( $old_file ) ) {
			$old_content = $this->get_file( $old_file );
			$this->set_log_content( $old_content, true );

			// Move old log file to new obfuscated location() .
			$this->log_unique( 'Renaming log file.' );

			// Move old file to new location.
			$this->fs->move( $old_file, $this->file );

			if ( $this->fs->exists( $old_file ) ) {
				$this->fs->delete( $old_file );
			}
		} elseif ( ! $this->fs->exists( $this->file ) ) {
			$this->setup_new_log();
		} else {
			$this->content = $this->get_file( $this->file );
		}

		// Truncate long log files.
		if ( $this->fs->exists( $this->file ) && $this->fs->size( $this->file ) >= 1048576 ) {
			$this->truncate_log();
		}
	}

	/**
	 * Retrieves the url to the file
	 *
	 * @returns string
	 * @since 1.12.0
	 */
	public function get_file_url() {
		return PUM_Helpers::get_upload_dir_url( $this->filename );
	}

	/**
	 * Retrieve the log data
	 *
	 * @return string
	 */
	public function get_log() {
		return $this->get_log_content();
	}

	/**
	 * Log message to file
	 *
	 * @param string $message The message to log.
	 */
	public function log( $message = '' ) {
		$this->write_to_log( wp_date( 'Y-n-d H:i:s' ) . ' - ' . $message );
	}

	/**
	 * Log unique message to file.
	 *
	 * @param string $message The unique message to log.
	 */
	public function log_unique( $message = '' ) {
		$contents = $this->get_log_content();

		if ( strpos( $contents, $message ) !== false ) {
			return;
		}

		$this->log( $message );
	}

	/**
	 * Get the log file contents.
	 *
	 * @return string
	 */
	public function get_log_content() {
		if ( ! isset( $this->content ) ) {
			$this->content = $this->get_file();
		}

		return $this->content;
	}

	/**
	 * Set the log file contents in memory.
	 *
	 * @param mixed $content The content to set.
	 * @param bool  $save    Whether to save the content to the file immediately.
	 * @return void
	 */
	private function set_log_content( $content, $save = false ) {
		$this->content = $content;

		if ( $save ) {
			$this->save_logs();
		}
	}

	/**
	 * Retrieve the contents of a file.
	 *
	 * @param string|boolean $file File to get contents of.
	 *
	 * @return string
	 */
	protected function get_file( $file = false ) {
		$file = $file ? $file : $this->file;

		if ( ! $this->enabled() ) {
			return '';
		}

		$content = '';

		if ( $this->fs->exists( $file ) ) {
			$content = $this->fs->get_contents( $file );
		}

		return $content;
	}

	/**
	 * Write the log message
	 *
	 * @param string $message The message to write.
	 */
	protected function write_to_log( $message = '' ) {
		if ( ! $this->enabled() ) {
			return;
		}

		$contents = $this->get_log_content();

		// If it doesn't end with a new line, add one. \r\n length is 2.
		if ( substr( $contents, -2 ) !== "\r\n" ) {
			$contents .= "\r\n";
		}

		$this->set_log_content( $contents . $message );
	}

	/**
	 * Save the current contents to file.
	 */
	public function save_logs() {
		if ( ! $this->enabled() ) {
			return;
		}

		$this->fs->put_contents( $this->file, $this->content, FS_CHMOD_FILE );
	}

	/**
	 * Get a line count.
	 *
	 * @return int
	 */
	public function count_lines() {
		$file  = $this->get_log_content();
		$lines = explode( "\r\n", $file );

		return count( $lines );
	}

	/**
	 * Truncates a log file to maximum of 250 lines.
	 */
	public function truncate_log() {
		$content           = $this->get_log_content();
		$lines             = explode( "\r\n", $content );
		$lines             = array_slice( $lines, 0, 250 ); // 50 is how many lines you want to keep
		$truncated_content = implode( "\r\n", $lines );
		$this->set_log_content( $truncated_content, true );
	}

	/**
	 * Set up a new log file.
	 *
	 * @return void
	 */
	public function setup_new_log() {
		$this->set_log_content( "Popup Maker Debug Logs:\r\n" . wp_date( 'Y-n-d H:i:s' ) . " - Log file initialized\r\n", true );
	}

	/**
	 * Delete the log file.
	 */
	public function clear_log() {
		// Delete the file.
		if ( $this->fs && method_exists( $this->fs, 'delete' ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@$this->fs->delete( $this->file );
		}

		if ( $this->enabled() ) {
			$this->setup_new_log();
		}
	}

	/**
	 * Log a deprecated notice.
	 *
	 * @param string $func_name Function name.
	 * @param string $version Versoin deprecated.
	 * @param string $replacement Replacement function (optional).
	 */
	public function log_deprecated_notice( $func_name, $version, $replacement = null ) {
		if ( ! is_null( $replacement ) ) {
			$notice = sprintf( '%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.', $func_name, $version, $replacement );
		} else {
			$notice = sprintf( '%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.', $func_name, $version );
		}

		$this->log_unique( $notice );
	}
}
