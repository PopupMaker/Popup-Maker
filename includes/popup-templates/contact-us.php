<?php
/**
 * Popup template: Contact Us.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

defined( 'ABSPATH' ) || exit;

return [
	'slug'           => 'contact-us',
	'name'           => __( 'Contact Us', 'popup-maker' ),
	'description'    => __( 'Simple contact form popup with name, email, subject, and message fields.', 'popup-maker' ),
	'category'       => 'lead-capture',
	'tier'           => 'free',
	'keywords'       => [ 'contact', 'form', 'inquiry', 'message', 'lead' ],
	'viewport_width' => 480,
	'content'        => implode( "\n\n", [
		sprintf(
			'<!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">%s</h2><!-- /wp:heading -->',
			esc_html__( 'Get in Touch', 'popup-maker' )
		),
		sprintf(
			'<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">%s</p><!-- /wp:paragraph -->',
			esc_html__( 'We respond within 24 hours.', 'popup-maker' )
		),
		'<!-- wp:spacer {"height":"16px"} --><div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->',
		sprintf(
			'<!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">%s</p><!-- /wp:paragraph -->',
			esc_html__( '👉 Replace this paragraph with your form block (WPForms, Gravity Forms, Fluent Forms, etc.).', 'popup-maker' )
		),
		'<!-- wp:spacer {"height":"16px"} --><div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->',
		'<!-- wp:popup-maker/cta-buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-popup-maker-cta-buttons"><!-- wp:popup-maker/cta-button -->
<div class="wp-block-popup-maker-cta-button"><a class="pum-cta wp-block-popup-maker-cta-button__link wp-element-button">' . esc_html__( 'Send Message', 'popup-maker' ) . '</a></div>
<!-- /wp:popup-maker/cta-button --></div>
<!-- /wp:popup-maker/cta-buttons -->',
		sprintf(
			'<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline popmake-close"} -->
<div class="wp-block-button is-style-outline popmake-close"><a class="wp-block-button__link wp-element-button">%s</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->',
			esc_html__( 'Cancel', 'popup-maker' )
		),
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
				'event'    => 'on_popup_conversion',
				'settings' => [
					'name'    => 'pum-{popup_id}',
					'time'    => '1 day',
					'session' => false,
					'path'    => true,
				],
			],
		],
		'notes'    => __( 'Integrate your form block (WPForms, Gravity Forms, Fluent Forms, etc.) by replacing the placeholder paragraph. Connect the CTA button to a form submission handler or custom integration to capture lead data.', 'popup-maker' ),
	],
];
