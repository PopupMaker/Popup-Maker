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
class PUM_Modules_Analytics {

	public static function init() {
		if ( popmake_get_option( 'disable_popup_open_tracking' ) ) {
			// Popup Open Tracking is disabled.
			return;
		}

		add_action( 'rest_api_init', array( __CLASS__, 'register_endpoints' ) );

		add_action( 'wp_ajax_pum_analytics', array( __CLASS__, 'ajax_request' ) );
		add_action( 'wp_ajax_nopriv_pum_analytics', array( __CLASS__, 'ajax_request' ) );

		add_action( 'pum_analytics_open', array( __CLASS__, 'track_open' ) );
	}

	public static function ajax_request() {

		$args = wp_parse_args( $_REQUEST, array(
			'type'   => null,
			'method' => null,
		) );

		if ( has_action( 'pum_analytics_' . $args['type'] ) ) {
			do_action( 'pum_analytics_' . $_REQUEST['type'] );
		}

		switch ( $args['method'] ) {
			case 'image':
				PUM_Ajax::serve_pixel();
				break;

			case 'json':
				PUM_Ajax::serve_json();
				break;

			default:
				PUM_Ajax::serve_no_content();
				break;
		}

	}

	public static function analytics_endpoint( WP_REST_Request $request ) {
		$params = $request->get_params();

		if ( ! $params || empty( $params['pid'] ) ) {
			return new WP_Error( 'missing_params', __( 'Missing Parameters.' ), array( 'status' => 404 ) );
		}

		do_action( 'pum_analytics_open', $params['pid'], $params );
	}

	public static function endpoint_absint( $param, $request, $key ) {
		return is_numeric( $param );
	}

	public static function register_endpoints() {
		$version   = 1;
		$namespace = 'pum/v' . $version;

		register_rest_route( $namespace, 'analytics/open', apply_filters( 'pum_rest_route_analytics/open', array(
			'methods'  => 'GET',
			'callback' => array( __CLASS__, 'analytics_endpoint' ),
			'args'     => apply_filters( 'pum_rest_route_analytics/open_args', array(
				'pid' => array(
					'required'            => true,
					'description'         => __( 'Popup ID', 'popup-maker' ),
					'type'                => 'integer',
					'validation_callback' => array( __CLASS__, 'endpoint_absint' ),
					'sanitize_callback'   => 'absint',
				),
			) ),
		) ) );
	}

	public static function track_open( $popup_id = 0 ) {

		if ( empty ( $popup_id ) || $popup_id <= 0 ) {
			return;
		}

		global $wpdb;

		$current_time = current_time( 'timestamp', 0 );

		$defaults = array(
			'popup_open_count'       => 0,
			'popup_open_count_total' => 0,
			'popup_last_opened'      => '',
		);

		$where = "WHERE meta_key in ('" . implode( "', '", array_keys( $defaults ) ) . "') AND post_id = '$popup_id'";

		// Tests for missing keys.
		$test = $wpdb->get_col( "SELECT meta_key FROM $wpdb->postmeta $where;" );

		$missing = $defaults;

		foreach ( $test as $not_missing ) {
			unset( $missing[ $not_missing ] );
		}

		if ( count( $missing ) ) {
			foreach ( $missing as $key => $value ) {
				$missing[ $key ] = "('$popup_id', '$key', '$value')";
			}

			$query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES " . implode( ', ', $missing );;

			$wpdb->query( $query );
		}

		/**
		 * Single query to update multiple post meta values at once.
		 */
		$query = "UPDATE $wpdb->postmeta SET meta_value = (
		case when meta_key = 'popup_open_count' then meta_value + 1
	         when meta_key = 'popup_open_count_total' then meta_value + 1
	         when meta_key = 'popup_last_opened' then '$current_time'
	    end
	) $where;";

		$wpdb->query( $query );

		$total_opens = get_option( 'pum_total_open_count', 0 );
		$total_opens ++;
		update_option( 'pum_total_open_count', $total_opens + 1 );

		// If is multisite add this blogs total to the site totals.
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$site_total_open_count = get_site_option( 'pum_site_total_open_count', false );
			if ( ! $site_total_open_count ) {
				$site_total_open_count = $total_opens;
			} else {
				$site_total_open_count ++;
			}
			update_site_option( 'pum_site_total_open_count', $site_total_open_count );
		}
	}

}

PUM_Modules_Analytics::init();
