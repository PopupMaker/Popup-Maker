<?php
/**
 * Logging class.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Services;

use PopupMaker\Base\Service;
use function PopupMaker\get_fs;

defined( 'ABSPATH' ) || exit;

/**
 * Logging service class.
 */
class Logging extends Service {
	/**
	 * Whether the log file is writable.
	 *
	 * @var bool|null
	 */
	private $is_writable;

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
	 * Log file content.
	 *
	 * @var string|null
	 */
	private $content;

	/**
	 * Initialize logging.
	 */
	public function __construct( $container ) {
		parent::__construct( $container );

		if ( ! $this->enabled() ) {
			return;
		}

		$this->init();

		$this->register_hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
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
		if (
			( defined( '\PUM_DISABLE_LOGGING' ) && \PUM_DISABLE_LOGGING ) ||
			( defined( '\POPUP_MAKER_DISABLE_LOGGING' ) && \POPUP_MAKER_DISABLE_LOGGING )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Chek if logging is enabled.
	 *
	 * @return bool
	 */
	public function enabled() {
		return ! $this->disabled() && $this->is_writable();
	}

	/**
	 * Check if the log file is writable.
	 *
	 * @return boolean
	 */
	public function is_writable() {
		if ( isset( $this->is_writable ) ) {
			return $this->is_writable;
		}

		$file_system = get_fs();

		if ( false === $file_system ) {
			$this->is_writable = false;
			return $this->is_writable;
		}

		$this->is_writable = 'direct' === $file_system->method;

		$upload_dir = \PopupMaker\get_upload_dir();

		if ( ! $file_system->is_writable( $upload_dir['basedir'] ) ) {
			$this->is_writable = false;
		}

		return $this->is_writable;
	}

	/**
	 * Get things started
	 *
	 * @return void
	 */
	public function init() {
		$upload_dir  = \PopupMaker\get_upload_dir();
		$file_system = get_fs();

		if ( false === $upload_dir || false === $file_system ) {
			return;
		}

		$token_option_key = $this->container->get( 'option_prefix' ) . 'debug_log_token';

		$file_token = \get_option( $token_option_key );
		if ( false === $file_token ) {
			$file_token = uniqid( (string) wp_rand(), true );
			\update_option( $token_option_key, $file_token );
		}

		$this->filename = $this->container->get( 'slug' ) . "-debug-{$file_token}.log"; // ex. popup-maker-debug-5c2f6a9b9b5a3.log.
		$this->file     = trailingslashit( $upload_dir['basedir'] ) . $this->filename;

		// If the old log file exists, migrate it to the new location.
		$this->migrate_old_log_file();

		if ( ! $file_system->exists( $this->file ) ) {
			$this->setup_new_log();
		} else {
			$this->content = $this->get_file( $this->file );
		}

		// Truncate long log files.
		if ( $file_system->exists( $this->file ) && $file_system->size( $this->file ) >= 1048576 ) {
			$this->truncate_log();
		}
	}

	/**
	 * Migrate the old log file to the new location.
	 *
	 * @return void
	 */
	private function migrate_old_log_file() {
		$upload_dir  = \PopupMaker\get_upload_dir();
		$file_system = get_fs();

		if ( false === $upload_dir || false === $file_system ) {
			return;
		}

		$old_file = trailingslashit( $upload_dir['basedir'] ) . 'pum-debug.log';
		if ( $file_system->exists( $old_file ) ) {
			$old_content = $this->get_file( $old_file );
			$this->set_log_content( $old_content, true );

			// Move old log file to new obfuscated location() .
			$this->log_unique( 'Renaming log file.' );

			// Move old file to new location.
			$file_system->move( $old_file, $this->file );

			if ( $file_system->exists( $old_file ) ) {
				$file_system->delete( $old_file );
			}
		}
	}

	/**
	 * Get the log file path.
	 *
	 * @return string
	 */
	public function get_file_path() {
		return $this->file;
	}

	/**
	 * Retrieves the url to the file
	 *
	 * @return string|bool The url to the file or false on failure
	 */
	public function get_file_url() {
		if ( ! $this->enabled() ) {
			return false;
		}

		return \PopupMaker\get_upload_dir_url( $this->filename );
	}

	/**
	 * Retrieve the log data
	 *
	 * @return false|string
	 */
	public function get_log() {
		return $this->get_log_content();
	}

	/**
	 * Delete the log file and token.
	 *
	 * @return void
	 */
	public function delete_logs() {
		$file_system = get_fs();

		if ( false === $file_system ) {
			return;
		}

		$file_system->delete( $this->file );

		$token_option_key = $this->container->get( 'option_prefix' ) . 'debug_log_token';

		\delete_option( $token_option_key );
	}

	/**
	 * Log message to file
	 *
	 * @param string $message The message to log.
	 *
	 * @return void
	 */
	public function log( $message = '' ) {
		$this->write_to_log( wp_date( 'Y-n-d H:i:s' ) . ' - ' . $message );
	}

	/**
	 * Log unique message to file.
	 *
	 * @param string $message The unique message to log.
	 *
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
	 * @return false|string
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
	 * @return false|string
	 */
	protected function get_file( $file = false ) {
		$file = $file ? $file : $this->file;

		$file_system = get_fs();

		if ( false === $file_system || ! $this->enabled() ) {
			return '';
		}

		$content = '';

		if ( $file_system->exists( $file ) ) {
			$content = $file_system->get_contents( $file );
		}

		return $content;
	}

	/**
	 * Write the log message
	 *
	 * @param string $message The message to write.
	 *
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
		$file_system = get_fs();

		if ( false === $file_system || ! $this->enabled() ) {
			return;
		}

		$file_system->put_contents( $this->file, $this->content, FS_CHMOD_FILE );
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
	 *
	 * @return void
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
		$this->set_log_content( $this->container->get( 'name' ) . " Debug Logs:\r\n" . wp_date( 'Y-n-d H:i:s' ) . " - Log file initialized\r\n", true );
	}

	/**
	 * Delete the log file.
	 *
	 * @return void
	 */
	public function clear_log() {
		$file_system = get_fs();

		if ( false === $file_system ) {
			return;
		}

		// Delete the file.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@$file_system->delete( $this->file );

		if ( $this->enabled() ) {
			$this->setup_new_log();
		}
	}

	/**
	 * Log an informational message.
	 *
	 * @param string $message The message to log.
	 *
	 * @return void
	 */
	public function info( $message = '' ) {
		$this->log( '[INFO] ' . $message );
	}

	/**
	 * Log unique informational message.
	 *
	 * @param string $message The message to log.
	 *
	 * @return void
	 */
	public function log_unique_info( $message = '' ) {
		$this->log_unique( '[INFO] ' . $message );
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message The message to log.
	 *
	 * @return void
	 */
	public function warning( $message = '' ) {
		$this->log( '[WARNING] ' . $message );
	}

	/**
	 * Log unique warning message.
	 *
	 * @param string $message The message to log.
	 *
	 * @return void
	 */
	public function log_unique_warning( $message = '' ) {
		$this->log_unique( '[WARNING] ' . $message );
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message The message to log.
	 *
	 * @return void
	 */
	public function error( $message = '' ) {
		$this->log( '[ERROR] ' . $message );
	}

	/**
	 * Log unique error message.
	 *
	 * @param string $message The message to log.
	 *
	 * @return void
	 */
	public function log_unique_error( $message = '' ) {
		$this->log_unique( '[ERROR] ' . $message );
	}

	/**
	 * Log a deprecated notice.
	 *
	 * @param string $func_name Function name.
	 * @param string $version Versoin deprecated.
	 * @param string $replacement Replacement function (optional).
	 *
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
