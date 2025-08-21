<?php
/**
 * Plugin controller.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Base;

defined( 'ABSPATH' ) || exit;

/**
 * HTTP Server-Sent Events (SSE) Stream class.
 *
 * Provides functionality for streaming real-time data to clients via SSE protocol.
 * Handles header configuration, buffer management, and message formatting.
 */
class Stream {

	/**
	 * Stream name identifier for this SSE connection.
	 *
	 * @var non-empty-string
	 */
	protected $stream_name;

	/**
	 * Stream class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Stream constructor.
	 *
	 * @param non-empty-string $stream_name Stream name identifier.
	 */
	public function __construct( $stream_name = 'stream' ) {
		$this->stream_name = $stream_name;
	}

	/**
	 * Start SSE stream.
	 *
	 * Configures PHP environment and sends initial headers for SSE connection.
	 * Prevents execution if headers have already been sent.
	 *
	 * @return void
	 */
	public function start() {
		if ( headers_sent() ) {
			// Do not start the stream if headers have already been sent.
			return;
		}

		// Disable default disconnect checks.
		ignore_user_abort( true );

		// phpcs:disable WordPress.PHP.IniSet.Risky, WordPress.PHP.NoSilencedErrors.Discouraged
		@ini_set( 'zlib.output_compression', '0' );
		@ini_set( 'implicit_flush', '1' );
		@ini_set( 'log_limit', '8096' );

		@ob_end_clean();
		set_time_limit( 0 );
		// phpcs:enable WordPress.PHP.IniSet.Risky, WordPress.PHP.NoSilencedErrors.Discouraged

		$this->send_headers();
	}

	/**
	 * Send SSE headers.
	 *
	 * Sends required HTTP headers for Server-Sent Events protocol including
	 * content type, stream name, caching directives, and connection settings.
	 *
	 * @return void
	 */
	public function send_headers() {
		header( 'Content-Type: text/event-stream' );
		header( 'Stream-Name: ' . $this->stream_name );
		header( 'Cache-Control: no-cache' );
		header( 'Connection: keep-alive' );
		// Nginx: unbuffered responses suitable for Comet and HTTP streaming applications.
		header( 'X-Accel-Buffering: no' );
		$this->flush_buffers();
	}

	/**
	 * Flush buffers.
	 *
	 * Sends padding data to reach minimum buffer size for immediate flushing,
	 * then flushes output buffers. Uses micro delay to prevent excessive flush rates.
	 *
	 * @return void
	 */
	protected function flush_buffers() {
		// This is for the buffer achieve the minimum size in order to flush data.

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo str_repeat( ' ', 1024 * 8 ) . PHP_EOL;

		flush(); // Unless both are called. Some browsers will still cache.

		// Neccessary to prevent the stream from flushing too quickly.
		usleep( 1000 );
	}

	/**
	 * Send general message/data to the client.
	 *
	 * Formats and sends data via SSE protocol. Automatically encodes non-string
	 * data as JSON. Follows SSE specification for data messages.
	 *
	 * @param string|int|float|bool|array<string, mixed>|object $data Data to send (string, array, object, scalar).
	 *
	 * @return void
	 */
	public function send_data( $data ) {
		$data = is_string( $data ) ? $data : \wp_json_encode( $data );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "data: {$data}" . PHP_EOL;
		echo PHP_EOL;

		$this->flush_buffers();
	}

	/**
	 * Send an event to the client.
	 *
	 * Sends a named event with optional data via SSE protocol. Follows SSE
	 * specification for event messages with event type and data fields.
	 *
	 * @param non-empty-string                                  $event Event name identifier.
	 * @param string|int|float|bool|array<string, mixed>|object $data Data to send with the event (string, array, object, scalar).
	 *
	 * @return void
	 */
	public function send_event( $event, $data = '' ) {
		$data = is_string( $data ) ? $data : \wp_json_encode( $data );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "event: {$event}" . PHP_EOL;
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "data: {$data}" . PHP_EOL;
		echo PHP_EOL;

		$this->flush_buffers();
	}

	/**
	 * Send an error to the client.
	 *
	 * Sends error information as a specialized 'error' event.
	 * Accepts either a simple error message string or structured error data.
	 *
	 * @param string|array{message: string, code?: int|string, details?: array<string, mixed>} $error Error message or structured error data.
	 *
	 * @return void
	 */
	public function send_error( $error ) {
		$this->send_event( 'error', $error );
	}

	/**
	 * Check if the connection should abort.
	 *
	 * Determines if the client has disconnected or the connection should be terminated.
	 * Useful for long-running streams to detect client disconnection.
	 *
	 * @return bool True if connection is aborted, false if still active.
	 */
	public function should_abort() {
		return (bool) connection_aborted();
	}
}
