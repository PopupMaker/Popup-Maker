<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Woocommerce_Integration {

	public static function init() {
		add_filter( 'pum_get_conditions', array( __CLASS__, 'get_conditions' ) );
		add_filter( 'pum_condition_sort_order', array( __CLASS__, 'condition_sort_order' ) );
	}

	public static function get_conditions( $conditions = array() ) {

		// Modify WooCommerce Post Type Groups.
		//$conditions['is_woocommerce']

		// Add Additional Conditions
		$conditions['is_woocommerce'] = array(
			'group'    => __( 'WooCommerce', 'woocommerce' ),
			'labels'   => array(
				'name' => __( 'All WooCommerce', 'popup-maker' ),
			),
			'callback' => 'is_woocommerce',
		);
		$conditions['is_shop']        = array(
			'group'    => __( 'WooCommerce', 'woocommerce' ),
			'labels'   => array(
				'name' => __( 'Shop Page', 'popup-maker' ),
			),
			'callback' => 'is_shop',
		);

		return $conditions;
	}

	public static function condition_sort_order( $order = array() ) {
		$order[ __( 'WooCommerce', 'woocommerce' ) ] = 5;

		return $order;
	}

}

PUM_Woocommerce_Integration::init();
