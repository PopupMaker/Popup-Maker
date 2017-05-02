<?php

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
	 * @var PUM_Shortcodes The one true PUM_Shortcodes
	 * @since 1.0
	 */
	private static $instance;

	private $shortcodes = array();

	/**
	 * Main PUM_Shortcodes Instance
	 *
	 * @return PUM_Shortcodes
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof PUM_Shortcodes ) ) {
			self::$instance = new PUM_Shortcodes;
		}

		return self::$instance;
	}

	public function add_shortcode( PUM_Shortcode $shortcode ) {
		$this->shortcodes[ $shortcode->tag() ] = $shortcode;
	}

	/**
	 * @return array PUM_Shortcode
	 */
	public function get_shortcodes() {
		return $this->shortcodes;
	}

	public function get_shortcode( $tag ) {
		return isset( $this->shortcodes[ $tag ] ) ? $this->shortcodes[ $tag ] : false;
	}

}

PUM_Shortcodes::instance();
