<?php
/**
 * Elementor stubs.
 *
 * @package ContentControl
 */

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

namespace Elementor;

/**
 * Plugin class.
 */
class Plugin {
	/**
	 * Plugin instance.
	 *
	 * @var \Elementor\Plugin|null
	 */
	public static $instance;

	/**
	 * Elementor Preview manager.
	 *
	 * @var \Elementor\Preview|null
	 */
	public $preview;
}

/**
 * Elementor Preview class.
 */
class Preview {
	/**
	 * Check if the page builder is active.
	 *
	 * @return boolean
	 */
	public function is_preview_mode() {
		return false;
	}
}
