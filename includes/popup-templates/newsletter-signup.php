<?php
/**
 * Popup template: Newsletter Signup.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

defined( 'ABSPATH' ) || exit;

return [
	'slug'           => 'newsletter-signup',
	'name'           => __( 'Newsletter Signup', 'popup-maker' ),
	'description'    => __( 'Minimalist two-field email signup with headline, subheading, and subscribe button.', 'popup-maker' ),
	'category'       => 'subscribe',
	'tier'           => 'free',
	'keywords'       => [ 'newsletter', 'signup', 'email', 'subscribe', 'lead', 'list', 'capture' ],
	'viewport_width' => 480,
	'content'        => implode( "\n\n", [
		sprintf(
			'<!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">%s</h2><!-- /wp:heading -->',
			esc_html__( 'Stay in the Loop', 'popup-maker' )
		),
		sprintf(
			'<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">%s</p><!-- /wp:paragraph -->',
			esc_html__( 'Get exclusive tips and updates delivered to your inbox weekly. No spam, ever.', 'popup-maker' )
		),
		sprintf(
			'<!-- wp:paragraph {"align":"center","fontSize":"small"} --><p class="has-text-align-center has-small-font-size">%s</p><!-- /wp:paragraph -->',
			esc_html__( '👉 Replace this paragraph with your form block (WPForms, Gravity Forms, Fluent Forms, etc.).', 'popup-maker' )
		),
		'<!-- wp:popup-maker/cta-buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-popup-maker-cta-buttons"><!-- wp:popup-maker/cta-button -->
<div class="wp-block-popup-maker-cta-button"><a class="pum-cta wp-block-popup-maker-cta-button__link wp-element-button">' . esc_html__( 'Subscribe Now', 'popup-maker' ) . '</a></div>
<!-- /wp:popup-maker/cta-button --></div>
<!-- /wp:popup-maker/cta-buttons -->',
	] ),
	'recommended'    => [
		'triggers' => [
			[
				'type'     => 'auto_open',
				'settings' => [
					'delay'       => 6000,
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
					'time'    => '1 month',
					'session' => false,
					'path'    => true,
				],
			],
		],
		'notes'    => __( 'Best on high-intent pages (blog, resources, homepage). Set auto-open to 6–10 seconds. Use form plugin integration for email capture; attach CTA to "Form Submission" or custom email provider action.', 'popup-maker' ),
	],
];
