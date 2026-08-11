<?php
/**
 * Minimal Elementor plugin test double.
 *
 * @package Popup_Maker
 */

namespace Elementor;

if ( ! class_exists( Plugin::class, false ) ) {
	/**
	 * Elementor plugin singleton test double.
	 */
	class Plugin {

		/**
		 * Frontend test double.
		 *
		 * @var object|null
		 */
		public $frontend;

		/**
		 * Active test instance.
		 *
		 * @var object|null
		 */
		public static $instance;
	}
}
