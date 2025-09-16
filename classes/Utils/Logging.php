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
 * @deprecated 1.21.0 Use \PopupMaker\logging() instead.
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
	 * @var WP_Filesystem_Base|null
	 */
	private $fs;

	/**
	 * Log file content.
	 *
	 * @var string|null
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
	 * @deprecated 1.21.0 Use \PopupMaker\plugin()->get( 'logging' ) instead.
	 *
	 * @return \PopupMaker\Services\Logging
	 */
	public static function instance() {
		return \PopupMaker\plugin()->get( 'logging' );
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
	 * @deprecated 1.21.0 Use \PopupMaker\logging()->disabled() instead.
	 *
	 * @return bool
	 */
	public function disabled() {
		return \PopupMaker\logging()->disabled();
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
		$fs       = $this->file_system();
		$this->fs = false !== $fs ? $fs : null;

		// If the file system is not set, we can't check if it's writable.
		if ( null === $this->fs ) {
			return;
		}

		$this->is_writable = 'direct' === $this->fs->method;

		if ( ! $this->is_writable ) {
			return;
		}

		$upload_dir = PUM_Helpers::get_upload_dir();

		if ( false === $upload_dir ) {
			$this->is_writable = false;
			return;
		}

		if ( ! $this->fs->is_writable( $upload_dir['basedir'] ) ) {
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
		if ( ! $this->enabled() || null === $this->fs ) {
			return;
		}

		$upload_dir = \PopupMaker\get_upload_dir();

		if ( false === $upload_dir ) {
			return;
		}

		$file_token = get_option( 'pum_debug_log_token' );
		if ( false === $file_token ) {
			$file_token = uniqid( (string) wp_rand(), true );
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
	 * @return string
	 * @since 1.12.0
	 */
	public function get_file_url() {
		$url = \PopupMaker\get_upload_dir_url( $this->filename );
		return is_string( $url ) ? $url : '';
	}

	/**
	 * Retrieve the log data
	 *
	 * @return string|null
	 */
	public function get_log() {
		return $this->get_log_content();
	}

	/**
	 * Log message to file
	 *
	 * @param string $message The message to log.
	 * @return void
	 */
	public function log( $message = '' ) {
		$this->write_to_log( ( function_exists( 'wp_date' ) ? wp_date( 'Y-n-d H:i:s' ) : date( 'Y-n-d H:i:s', current_time( 'timestamp' ) ) ) . ' - ' . $message );
	}

	/**
	 * Log unique message to file.
	 *
	 * @param string $message The unique message to log.
	 * @return void
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
	 * @return string|null
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
	 * @param string $content The content to set.
	 * @param bool   $save    Whether to save the content to the file immediately.
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
	 * @param string|false $file File path to get contents of, or false to use default log file.
	 * @return string File contents or empty string on failure.
	 */
	protected function get_file( $file = false ) {
		$file = $file ? $file : $this->file;

		if ( ! $this->enabled() || null === $this->fs ) {
			return '';
		}

		$content = '';

		if ( $this->fs->exists( $file ) ) {
			$file_content = $this->fs->get_contents( $file );
			$content      = is_string( $file_content ) ? $file_content : '';
		}

		return $content;
	}

	/**
	 * Write the log message
	 *
	 * @param string $message The message to write.
	 * @return void
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
	 *
	 * @return void
	 */
	public function save_logs() {
		if ( ! $this->enabled() || null === $this->fs ) {
			return;
		}

		$this->fs->put_contents( $this->file, $this->content ?? '', FS_CHMOD_FILE );
	}

	/**
	 * Get a line count.
	 *
	 * @return int
	 */
	public function count_lines() {
		$file  = $this->get_log_content();
		$lines = explode( "\r\n", $file ?? '' );

		return count( $lines );
	}

	/**
	 * Truncates a log file to maximum of 250 lines.
	 *
	 * @return void
	 */
	public function truncate_log() {
		$content           = $this->get_log_content();
		$lines             = explode( "\r\n", $content ?? '' );
		$lines             = array_slice( $lines, 0, 250 ); // 250 is how many lines you want to keep
		$truncated_content = implode( "\r\n", $lines );
		$this->set_log_content( $truncated_content, true );
	}

	/**
	 * Set up a new log file.
	 *
	 * @return void
	 */
	public function setup_new_log() {
		$this->set_log_content( "Popup Maker Debug Logs:\r\n" . ( function_exists( 'wp_date' ) ? wp_date( 'Y-n-d H:i:s' ) : date( 'Y-n-d H:i:s', current_time( 'timestamp' ) ) ) . " - Log file initialized\r\n", true );
	}

	/**
	 * Delete the log file.
	 *
	 * @return void
	 */
	public function clear_log() {
		// Delete the file.
		if ( null !== $this->fs ) {
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
	 * @param non-empty-string $func_name   Function name.
	 * @param non-empty-string $version     Version deprecated.
	 * @param string|null      $replacement Replacement function (optional).
	 * @return void
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
