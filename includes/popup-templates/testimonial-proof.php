<?php
/**
 * Popup template: Testimonial Proof.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

defined( 'ABSPATH' ) || exit;

return [
	'slug'           => 'testimonial-proof',
	'name'           => __( 'Testimonial Proof', 'popup-maker' ),
	'description'    => __( 'Compact notification showing recent customer activity to build social proof and FOMO.', 'popup-maker' ),
	'category'       => 'engagement',
	'tier'           => 'free',
	'keywords'       => [ 'social', 'proof', 'notification', 'testimonial', 'fomo', 'engagement' ],
	'viewport_width' => 340,
	'content'        => implode( "\n\n", [
		sprintf(
			'<!-- wp:group {"style":{"spacing":{"padding":{"top":"16px","right":"16px","bottom":"16px","left":"16px"}}},"layout":{"type":"constrained"}} --><div class="wp-block-group" style="padding-top:16px;padding-right:16px;padding-bottom:16px;padding-left:16px">%s</div><!-- /wp:group -->',
			implode( "\n\n", [
				sprintf(
					'<!-- wp:paragraph {"className":"has-small-font-size","fontSize":"small"} --><p class="has-small-font-size">%s</p><!-- /wp:paragraph -->',
					esc_html__( 'Just purchased', 'popup-maker' )
				),
				sprintf(
					'<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">%s</h3><!-- /wp:heading -->',
					esc_html__( 'Sarah M. in New York', 'popup-maker' )
				),
				sprintf(
					'<!-- wp:paragraph --><p>%s</p><!-- /wp:paragraph -->',
					esc_html__( 'Premium Plan', 'popup-maker' )
				),
				sprintf(
					'<!-- wp:paragraph {"className":"has-small-font-size","fontSize":"small"} --><p class="has-small-font-size">%s</p><!-- /wp:paragraph -->',
					esc_html__( '2 minutes ago', 'popup-maker' )
				),
			] )
		),
	] ),
	'recommended'    => [
		'triggers' => [
			[
				'type'     => 'auto_open',
				'settings' => [
					'delay'       => 5000,
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
		'notes'    => __( 'Compact corner notification (340px width) for rotating customer testimonials. Use trigger delay 5–10 seconds for page entry. Omit cookie to show every visit for max FOMO effect.', 'popup-maker' ),
	],
];
