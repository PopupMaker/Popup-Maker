<?php
/**
 * Already-shipped Extension Framework offer callback fixture.
 *
 * @package PopupMaker
 */

namespace PopupMaker\ExtensionFramework\Controllers\Admin;

if ( ! class_exists( __NAMESPACE__ . '\\ProUpsell', false ) ) {
	/**
	 * Minimal callback fixture matching the historical controller class.
	 */
	class ProUpsell {

		/** @return void */
		public function admin_notice() {}

		/** @return void */
		public function enqueue_dismiss_script() {}

		/** @return void */
		public function plugin_row_meta() {}

		/** @return void */
		public function register_panel_notification() {}
	}
}
