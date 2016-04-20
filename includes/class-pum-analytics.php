<?php
/**
 * Analytics Initialization & Event Management
 *
 * @since       1.4
 * @package     PUM
 * @subpackage  PUM/includes
 * @author      Daniel Iser <danieliser@wizardinternetsolutions.com>
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controls the basic analytics methods for Popup Maker
 *
 */
class PUM_Analytics {

	/**
	 * @var PUM_Analytics The one true PUM_Analytics
	 * @since 1.0
	 */
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof PUM_Analytics ) ) {
			self::$instance = new PUM_Analytics;
			self::$instance->init();
		}

		return self::$instance;
	}

	public function init() {
		add_action( 'wp_ajax_pum_analytics', array( $this, 'ajax_request' ) );
		add_action( 'wp_ajax_nopriv_pum_analytics', array( $this, 'ajax_request' ) );

		add_action( 'pum_analytics_open', array( $this, 'track_open' ) );
	}

	public function ajax_request() {

		$args = wp_parse_args( $_REQUEST, array(
			'type'   => null,
			'method' => 'image',
		) );

		if ( has_action( 'pum_analytics_' . $args['type'] ) ) {
			do_action( 'pum_analytics_' . $_REQUEST['type'] );
		}

		if ( $args['method'] == 'image' ) {
			PUM_Ajax::serve_pixel();
		}
	}

	public function track_open() {

		if ( empty ( $_REQUEST['pid'] ) || $_REQUEST['pid'] <= 0 ) {
			return;
		}

		$popup = new PUM_Popup( $_REQUEST['pid'] );
		$popup->increase_open_count();
	}

}

PUM_Analytics::instance();
