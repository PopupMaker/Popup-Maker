<?php
/**
 * Popup template: Welcome Mat.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

defined( 'ABSPATH' ) || exit;

return [
	'slug'           => 'welcome-mat',
	'name'           => __( 'Welcome Mat', 'popup-maker' ),
	'description'    => __( 'Full-screen hero overlay welcoming new visitors with a headline, subheading, and prominent call-to-action.', 'popup-maker' ),
	'category'       => 'lead-capture',
	'tier'           => 'free',
	'keywords'       => [ 'welcome', 'hero', 'overlay', 'fullscreen', 'new-visitor', 'conversion' ],
	'viewport_width' => 480,
	'content'        => implode( "\n\n", [
		'<!-- wp:cover {"overlayColor":"black","dimRatio":50,"minHeight":500,"layout":{"type":"constrained"}} --><div class="wp-block-cover" style="min-height:500px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-50 has-background-dim"></span><div class="wp-block-cover__inner-container">',
		sprintf(
			'<!-- wp:heading {"textAlign":"center","level":2,"style":{"color":{"text":"#ffffff"},"typography":{"fontSize":"48px","lineHeight":"1.2"}}} --><h2 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffff;font-size:48px;line-height:1.2">%s</h2><!-- /wp:heading -->',
			esc_html__( 'Level Up Your Marketing', 'popup-maker' )
		),
		sprintf(
			'<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffff"},"typography":{"fontSize":"20px","lineHeight":"1.6"}}} --><p class="has-text-align-center has-text-color" style="color:#ffffff;font-size:20px;line-height:1.6">%s</p><!-- /wp:paragraph -->',
			esc_html__( 'Join 10,000+ marketers getting better results.', 'popup-maker' )
		),
		sprintf(
			'<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffff"},"typography":{"fontSize":"16px"}}} --><p class="has-text-align-center has-text-color" style="color:#ffffff;font-size:16px">%s</p><!-- /wp:paragraph -->',
			esc_html__( 'Grab exclusive strategies and case studies sent straight to your inbox.', 'popup-maker' )
		),
		'<!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->',
		'<!-- wp:popup-maker/cta-buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-popup-maker-cta-buttons"><!-- wp:popup-maker/cta-button --><div class="wp-block-popup-maker-cta-button"><a class="pum-cta wp-block-popup-maker-cta-button__link wp-element-button">' . esc_html__( 'Get Instant Access', 'popup-maker' ) . '</a></div><!-- /wp:popup-maker/cta-button --></div><!-- /wp:popup-maker/cta-buttons -->',
		'<!-- wp:spacer {"height":"16px"} --><div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->',
		sprintf(
			'<!-- wp:paragraph {"align":"center","className":"popmake-close","style":{"color":{"text":"#ffffff"},"typography":{"fontSize":"14px"}}} --><p class="has-text-align-center popmake-close has-text-color" style="color:#ffffff;font-size:14px">%s</p><!-- /wp:paragraph -->',
			esc_html__( 'Skip for now', 'popup-maker' )
		),
		'</div></div><!-- /wp:cover -->',
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
					'time'    => '60 days',
					'session' => false,
					'path'    => true,
				],
			],
		],
		'notes'    => __( 'Full-screen welcome mat sized for mobile and desktop. Best applied on homepage or landing pages; set to 3–5 second auto-open delay to let content load before triggering. Dismiss text link styled for white overlay. Connect the button to a newsletter signup or lead capture call-to-action.', 'popup-maker' ),
	],
];
