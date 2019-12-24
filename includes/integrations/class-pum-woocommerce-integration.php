<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Woocommerce_Integration {

	public static function init() {
		add_filter( 'pum_registered_conditions', array( __CLASS__, 'register_conditions' ) );
		add_filter( 'pum_condition_sort_order', array( __CLASS__, 'condition_sort_order' ) );
	}

	public static function is_wc_endpoint_url( $settings = array() ) {
		$results = array();

		foreach ( $settings['selected'] as $key ) {
			$results[] = is_wc_endpoint_url( $key );
		}

		return in_array( true, $results );
	}

	public static function register_conditions( $conditions = array() ) {

		// Add Additional Conditions
		$conditions['is_woocommerce']  = array(
			'group'    => __( 'WooCommerce', 'woocommerce' ),
			'name'     => __( 'All WooCommerce', 'popup-maker' ),
			'callback' => 'is_woocommerce',
		);
		$conditions['is_shop']         = array(
			'group'    => __( 'WooCommerce', 'woocommerce' ),
			'name'     => __( 'Shop Page', 'popup-maker' ),
			'callback' => 'is_shop',
		);
		$conditions['is_cart']         = array(
			'group'    => __( 'WooCommerce', 'woocommerce' ),
			'name'     => __( 'Cart Page', 'popup-maker' ),
			'callback' => 'is_cart',
		);
		$conditions['is_checkout']     = array(
			'group'    => __( 'WooCommerce', 'woocommerce' ),
			'name'     => __( 'Checkout Page', 'popup-maker' ),
			'callback' => 'is_checkout',
		);
		$conditions['is_account_page'] = array(
			'group'    => __( 'WooCommerce', 'woocommerce' ),
			'name'     => __( 'Account Page', 'popup-maker' ),
			'callback' => 'is_account_page',
		);

		$conditions['is_wc_endpoint_url'] = array(
			'group'    => __( 'WooCommerce', 'woocommerce' ),
			'name'     => __( 'Is Endpoint', 'popup-maker' ),
			'fields'   => array(
				'selected' => array(
					'placeholder' => __( 'Selected Endpoints', 'popup-maker' ),
					'type'        => 'select',
					'select2'     => true,
					'multiple'    => true,
					'as_array'    => true,
					'options'     => array(
						'order-pay'                  => 'order-pay',
						'order-received'             => 'order-received',
						// My account actions.
						'orders'                     => 'orders',
						'view-order'                 => 'view-order',
						'downloads'                  => 'downloads',
						'edit-account'               => 'edit-account',
						'edit-address'               => 'edit-address',
						'payment-methods'            => 'payment-methods',
						'lost-password'              => 'lost-password',
						'customer-logout'            => 'customer-logout',
						'add-payment-method'         => 'add-payment-method',
						'delete-payment-method'      => 'delete-payment-method',
						'set-default-payment-method' => 'set-default-payment-method',
					),
				),
			),
			'callback' => array( __CLASS__, 'is_wc_endpoint_url' ),
		);

		return $conditions;
	}

	public static function condition_sort_order( $order = array() ) {
		$order[ __( 'WooCommerce', 'woocommerce' ) ] = 5.256;

		return $order;
	}

}

