<?php
/**
 * Popup template: Discount Coupon.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

defined( 'ABSPATH' ) || exit;

return [
	'slug'           => 'discount-coupon',
	'name'           => __( 'Discount Coupon', 'popup-maker' ),
	'description'    => __( 'Announce a promotional offer with a prominent, copyable coupon code.', 'popup-maker' ),
	'category'       => 'sales-promotions',
	'tier'           => 'free',
	'keywords'       => [ 'coupon', 'discount', 'promo', 'sale', 'code' ],
	'viewport_width' => 480,
	'content'        => implode( "\n\n", [
		sprintf(
			'<!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">%s</h2><!-- /wp:heading -->',
			esc_html__( 'Limited-time offer', 'popup-maker' )
		),
		sprintf(
			'<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">%s</p><!-- /wp:paragraph -->',
			esc_html__( 'Get 20% off your order with this exclusive code.', 'popup-maker' )
		),
		sprintf(
			'<!-- wp:group {"style":{"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"border":{"width":"2px","color":"#e0e0e0","style":"dashed"}},"layout":{"type":"constrained"}} --><div class="wp-block-group" style="border:2px dashed #e0e0e0;padding:24px"><!-- wp:paragraph {"align":"center","fontSize":"large"} --><p class="has-text-align-center has-large-font-size" style="font-family:monospace;font-weight:600;letter-spacing:2px">SAVE20</p><!-- /wp:paragraph --><!-- wp:paragraph {"align":"center","fontSize":"small"} --><p class="has-text-align-center has-small-font-size">%s</p><!-- /wp:paragraph --></div><!-- /wp:group -->',
			esc_html__( 'Copy and paste at checkout', 'popup-maker' )
		),
		sprintf(
			'<!-- wp:popup-maker/cta-buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-popup-maker-cta-buttons"><!-- wp:popup-maker/cta-button -->
<div class="wp-block-popup-maker-cta-button"><a class="pum-cta wp-block-popup-maker-cta-button__link wp-element-button">%s</a></div>
<!-- /wp:popup-maker/cta-button --></div>
<!-- /wp:popup-maker/cta-buttons -->',
			esc_html__( 'Shop the Sale', 'popup-maker' )
		),
	] ),
	'recommended'    => [
		'triggers' => [
			[
				'type'     => 'auto_open',
				'settings' => [
					'delay'       => 3000,
					'cookie_name' => [ 'pum-{popup_id}' ],
				],
			],
			[
				'type'     => 'click_open',
				'settings' => [
					'extra_selectors' => '',
					'cookie_name'     => null,
				],
			],
		],
		'cookies'  => [
			[
				'event'    => 'on_popup_conversion',
				'settings' => [
					'name'    => 'pum-{popup_id}',
					'time'    => '1 week',
					'session' => false,
					'path'    => true,
				],
			],
		],
		'notes'    => __( 'Sized for standard desktop/tablet viewing. Connect the button to an Apply Discount call to action to track conversions and include a custom redirect URL.', 'popup-maker' ),
	],
];
