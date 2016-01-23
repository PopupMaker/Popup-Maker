<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Woocommerce_Integration {

	public static function init() {
		if ( function_exists( 'WC' ) || class_exists( 'WooCommerce' ) ) {
			add_filter( 'pum_get_conditions', array( __CLASS__, 'get_conditions' ) );
			add_filter( 'pum_condition_sort_order', array( __CLASS__, 'condition_sort_order' ) );
		}
	}

	public static function get_conditions( $conditions = array() ) {

		// Add Additional Conditions
		$conditions['is_woocommerce']  = array(
			'group'    => __( 'WooCommerce', 'woocommerce' ),
			'labels'   => array(
				'name' => __( 'All WooCommerce', 'popup-maker' ),
			),
			'callback' => 'is_woocommerce',
		);
		$conditions['is_shop']         = array(
			'group'    => __( 'WooCommerce', 'woocommerce' ),
			'labels'   => array(
				'name' => __( 'Shop Page', 'popup-maker' ),
			),
			'callback' => 'is_shop',
		);
		$conditions['is_cart']         = array(
			'group'    => __( 'WooCommerce', 'woocommerce' ),
			'labels'   => array(
				'name' => __( 'Cart Page', 'popup-maker' ),
			),
			'callback' => 'is_cart',
		);
		$conditions['is_checkout']     = array(
			'group'    => __( 'WooCommerce', 'woocommerce' ),
			'labels'   => array(
				'name' => __( 'Checkout Page', 'popup-maker' ),
			),
			'callback' => 'is_checkout',
		);
		$conditions['is_account_page'] = array(
			'group'    => __( 'WooCommerce', 'woocommerce' ),
			'labels'   => array(
				'name' => __( 'Account Page', 'popup-maker' ),
			),
			'callback' => 'is_account_page',
		);

		return $conditions;
	}

	public static function condition_sort_order( $order = array() ) {
		$order[ __( 'WooCommerce', 'woocommerce' ) ] = 5;

		return $order;
	}

}

add_action( 'plugins_loaded', 'PUM_Woocommerce_Integration::init' );
