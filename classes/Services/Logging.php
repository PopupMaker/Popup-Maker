<?php
/**
 * Logging class.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Services;

use PopupMaker\Base\Service;

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
	 * File system API.
	 *
	 * @var \WP_Filesystem_Base|null
	 */
	private $fs;

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
	 * Gets the Uploads directory
	 *
	 * @return bool|array{path: string, url: string, subdir: string, basedir: string, baseurl: string, error: string|false} An associated array with baseurl and basedir or false on failure
	 */
	public function get_upload_dir() {
		// Used if you only need to fetch data, not create missing folders.
		$wp_upload_dir = wp_get_upload_dir();

		// phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		// $wp_upload_dir = wp_upload_dir(); // Disable this on IS_WPCOM if used.

		if ( isset( $wp_upload_dir['error'] ) && false !== $wp_upload_dir['error'] ) {
			return false;
		} else {
			return $wp_upload_dir;
		}
	}

	/**
	 * Gets the uploads directory URL
	 *
	 * @param string $path A path to append to end of upload directory URL.
	 * @return bool|string The uploads directory URL or false on failure
	 */
	public function get_upload_dir_url( $path = '' ) {
		$upload_dir = $this->get_upload_dir();
		if ( false !== $upload_dir && isset( $upload_dir['baseurl'] ) ) {
			$url = preg_replace( '/^https?:/', '', $upload_dir['baseurl'] );
			if ( null === $url ) {
				return false;
			}
			if ( ! empty( $path ) ) {
				$url = trailingslashit( $url ) . $path;
			}
			return $url;
		} else {
			return false;
		}
	}

	/**
	 * Chek if logging is enabled.
	 *
	 * @return bool
	 */
	public function enabled() {
		$disabled = defined( '\POPUP_MAKER_DISABLE_LOGGING' ) && true === \POPUP_MAKER_DISABLE_LOGGING;

		return ! $disabled && $this->is_writable();
	}

	/**
	 * Get working WP Filesystem instance
	 *
	 * @return \WP_Filesystem_Base|false
	 */
	public function fs() {
		if ( isset( $this->fs ) ) {
			return $this->fs;
		}

		global $wp_filesystem;

		require_once ABSPATH . 'wp-admin/includes/file.php';

		// If for some reason the include doesn't work as expected just return false.
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			return false;
		}

		$writable = WP_Filesystem( false, '', true );

		// We consider the directory as writable if it uses the direct transport,
		// otherwise credentials would be needed.
		$this->fs = ( $writable && 'direct' === $wp_filesystem->method ) ? $wp_filesystem : false;

		return $this->fs;
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

		$file_system = $this->fs();

		if ( false === $file_system ) {
			$this->is_writable = false;
			return $this->is_writable;
		}

		$this->is_writable = 'direct' === $file_system->method;

		$upload_dir = $this->get_upload_dir();

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
		$upload_dir  = $this->get_upload_dir();
		$file_system = $this->fs();

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

		return $this->get_upload_dir_url( $this->filename );
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
		$file_system = $this->fs();

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

		$file_system = $this->fs();

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
		$file_system = $this->fs();

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
		$file_system = $this->fs();

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
