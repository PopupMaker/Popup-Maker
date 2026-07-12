<?php
/**
 * Popup template: Flash Sale Promo.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

defined( 'ABSPATH' ) || exit;

return [
	'slug'           => 'flash-sale-promo',
	'name'           => __( 'Flash Sale Promo', 'popup-maker' ),
	'description'    => __( 'Time-sensitive discount offer with benefit highlights and prominent call-to-action.', 'popup-maker' ),
	'category'       => 'sales-promotions',
	'tier'           => 'free',
	'keywords'       => [ 'sales', 'promotion', 'discount', 'offer', 'flash', 'limited-time' ],
	'viewport_width' => 480,
	'content'        => implode( "\n\n", [
		sprintf(
			'<!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">%s</h2><!-- /wp:heading -->',
			esc_html__( 'Limited Time: 20% Off Today', 'popup-maker' )
		),
		sprintf(
			'<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">%s</p><!-- /wp:paragraph -->',
			esc_html__( 'Unlock your exclusive offer now.', 'popup-maker' )
		),
		'<!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li>' . esc_html__( 'Free shipping on orders over $50', 'popup-maker' ) . '</li><!-- /wp:list-item --><!-- wp:list-item --><li>' . esc_html__( 'Extended 90-day returns', 'popup-maker' ) . '</li><!-- /wp:list-item --><!-- wp:list-item --><li>' . esc_html__( 'VIP member benefits', 'popup-maker' ) . '</li><!-- /wp:list-item --></ul><!-- /wp:list -->',
		'<!-- wp:spacer {"height":"16px"} --><div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->',
		'<!-- wp:popup-maker/cta-buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-popup-maker-cta-buttons"><!-- wp:popup-maker/cta-button -->
<div class="wp-block-popup-maker-cta-button"><a class="pum-cta wp-block-popup-maker-cta-button__link wp-element-button">' . esc_html__( 'Get 20% Off', 'popup-maker' ) . '</a></div>
<!-- /wp:popup-maker/cta-button --></div>
<!-- /wp:popup-maker/cta-buttons -->',
		'<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline popmake-close"} -->
<div class="wp-block-button is-style-outline popmake-close"><a class="wp-block-button__link wp-element-button">' . esc_html__( 'Not Interested', 'popup-maker' ) . '</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->',
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
		],
		'cookies'  => [
			[
				'event'    => 'on_popup_close',
				'settings' => [
					'name'    => 'pum-{popup_id}',
					'time'    => '1 week',
					'session' => false,
					'path'    => true,
				],
			],
		],
		'notes'    => __( 'Position on product or shop pages to maximize conversion. 500–600px centered modal works best on desktop; mobile automatically adapts to bottom sheet. Set 7-day cookie to avoid over-exposure.', 'popup-maker' ),
	],
];
