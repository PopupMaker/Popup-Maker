<?php
/**
 * Shortcodes class
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Shortcodes
 *
 * This class maintains a global set of all registered PUM shortcodes.
 */
class PUM_Shortcodes {

	/**
	 * @var PUM_Shortcodes Static Instance
	 */
	private static $instance;

	/**
	 * @var array Holds array of registered $shortcode_tags => $shortcode_objects.
	 */
	private $shortcodes = [];

	/**
	 * Main PUM_Shortcodes Instance
	 *
	 * @return PUM_Shortcodes
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof PUM_Shortcodes ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Add a shortcode object to the collection.
	 *
	 * @param PUM_Shortcode $shortcode
	 */
	public function add_shortcode( PUM_Shortcode $shortcode ) {
		$this->shortcodes[ $shortcode->tag() ] = $shortcode;
	}

	/**
	 * Get all shortcodes.
	 *
	 * @return array PUM_Shortcode
	 */
	public function get_shortcodes() {
		return $this->shortcodes;
	}

	/**
	 * Get shortcode by tag.
	 *
	 * @param $tag
	 *
	 * @return bool|mixed
	 */
	public function get_shortcode( $tag ) {
		return isset( $this->shortcodes[ $tag ] ) ? $this->shortcodes[ $tag ] : false;
	}
}
