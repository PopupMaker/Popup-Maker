<?php
/**
 * Plugin controller.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Base;

defined( 'ABSPATH' ) || exit;

use Closure;
use stdClass;

/**
 * Base Upgrade class.
 */
abstract class Upgrade implements \PopupMaker\Interfaces\Upgrade {

	/**
	 * Type.
	 *
	 * @var string Uses data versioning types.
	 */
	const TYPE = '';

	/**
	 * Version.
	 *
	 * @var int
	 */
	const VERSION = 1;

	/**
	 * Stream.
	 *
	 * @var \PopupMaker\Services\UpgradeStream|null
	 */
	public $stream;

	/**
	 * Upgrade constructor.
	 *
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * Upgrade label
	 *
	 * @return string
	 */
	abstract public function label();

	/**
	 * Return full description for this upgrade.
	 *
	 * @return string
	 */
	public function description() {
		return '';
	}

	/**
	 * Check if the upgrade is required.
	 *
	 * @return bool
	 */
	public function is_required() {
		$current_version = \PopupMaker\get_data_version( static::TYPE );
		return $current_version && $current_version < static::VERSION;
	}

	/**
	 * Get the type of upgrade.
	 *
	 * @return string
	 */
	public function get_type() {
		return static::TYPE;
	}

	/**
	 * Check if the prerequisites are met.
	 *
	 * @return bool
	 */
	public function prerequisites_met() {
		return true;
	}

	/**
	 * Get the dependencies for this upgrade.
	 *
	 * @return string[]
	 */
	public function get_dependencies() {
		return [];
	}

	/**
	 * Run the upgrade.
	 *
	 * @return void|\WP_Error|false
	 */
	abstract public function run();

	/**
	 * Run the upgrade with stream support.
	 *
	 * @param \PopupMaker\Services\UpgradeStream $stream Stream for progress reporting and communication.
	 *
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 */
	public function stream_run( $stream ) {
		$this->stream = $stream;

		$return = $this->run();

		$this->stream = null;

		if ( is_bool( $return ) || is_wp_error( $return ) ) {
			return $return;
		}

		return true;
	}

	/**
	 * Return the stream.
	 *
	 * If no stream is available it returns a mock object whose methods are all
	 * no-ops so callers can invoke stream methods without a fatal.
	 *
	 * @return \PopupMaker\Services\UpgradeStream|object Stream instance or no-op mock.
	 */
	public function stream() {
		if ( is_a( $this->stream, '\PopupMaker\Services\UpgradeStream' ) ) {
			return $this->stream;
		}

		// A stdClass with closure properties cannot be called as methods, so use
		// an anonymous class whose __call swallows any stream method invocation.
		return new class() {
			/**
			 * No-op for any stream method call.
			 *
			 * @param string $name Method name.
			 * @param array  $args Arguments (ignored).
			 *
			 * @return void
			 */
			public function __call( $name, $args ) {}
		};
	}
}
