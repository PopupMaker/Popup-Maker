<?php
/**
 * Elementor stubs.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 *
 * phpcs:disable
 */

namespace Elementor {

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

		/**
		 * Elementor Admin Core.
		 *
		 * @var \Elementor\Core\Admin\Admin|null
		 */
		public $admin;

		/**
		 * Elementor plugin instance.
		 *
		 * @return \Elementor\Plugin
		 */
		public static function instance() {}
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

}

namespace Elementor\Core\Base {
	interface Component {}
}

namespace Elementor\Core\Admin {

	/**
	 * Elementor Admin Core.
	 */
	class Admin {
		/**
		 * Get a component.
		 *
		 * @param string $component Component name.
		 *
		 * @return \Elementor\Core\Base\Component|null
		 */
		public function get_component( $component ) {
			return null;
		}
	}

	/**
	 * Elementor Admin Notices.
	 */
	class Admin_Notices implements \Elementor\Core\Base\Component {
		/**
		 * Render admin notices.
		 *
		 * @return void
		 */
		public function admin_notices() {}
	}

}
