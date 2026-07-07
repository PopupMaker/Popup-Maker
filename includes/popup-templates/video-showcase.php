<?php
/**
 * Popup template: Video Showcase.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

defined( 'ABSPATH' ) || exit;

return [
	'slug'           => 'video-showcase',
	'name'           => __( 'Video Showcase', 'popup-maker' ),
	'description'    => __( 'Featured video with headline and CTA button.', 'popup-maker' ),
	'category'       => 'engagement',
	'tier'           => 'free',
	'keywords'       => [ 'video', 'demo', 'embed', 'engagement', 'showcase' ],
	'viewport_width' => 480,
	'content'        => implode( "\n\n", [
		sprintf(
			'<!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">%s</h2><!-- /wp:heading -->',
			esc_html__( 'See It in Action', 'popup-maker' )
		),
		sprintf(
			'<!-- wp:paragraph {"align":"center","fontSize":"small"} --><p class="has-text-align-center has-small-font-size">%s</p><!-- /wp:paragraph -->',
			esc_html__( 'Watch our 2-minute product demo.', 'popup-maker' )
		),
		'<!-- wp:spacer {"height":"16px"} --><div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->',
		'<!-- wp:embed /-->',
		'<!-- wp:spacer {"height":"16px"} --><div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->',
		'<!-- wp:popup-maker/cta-buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-popup-maker-cta-buttons"><!-- wp:popup-maker/cta-button -->
<div class="wp-block-popup-maker-cta-button"><a class="pum-cta wp-block-popup-maker-cta-button__link wp-element-button">' . esc_html__( 'Learn More', 'popup-maker' ) . '</a></div>
<!-- /wp:popup-maker/cta-button --></div>
<!-- /wp:popup-maker/cta-buttons -->',
		'<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline popmake-close"} -->
<div class="wp-block-button is-style-outline popmake-close"><a class="wp-block-button__link wp-element-button">' . esc_html__( 'Dismiss', 'popup-maker' ) . '</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->',
	] ),
	'recommended'    => [
		'triggers' => [
			[
				'type'     => 'click_open',
				'settings' => [
					'extra_selectors' => '',
					'cookie_name'     => null,
				],
			],
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
					'time'    => '1 hour',
					'session' => false,
					'path'    => true,
				],
			],
		],
		'notes'    => __( 'Ideal for product demos on landing pages or testimonial sections. Replace the embed block with your YouTube/Vimeo URL. Set a 1-hour cookie to prevent replay fatigue.', 'popup-maker' ),
	],
];
