<?php
/**
 * Popup template: Age Verification.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

defined( 'ABSPATH' ) || exit;

return [
	'slug'           => 'age-verification',
	'name'           => __( 'Age Verification', 'popup-maker' ),
	'description'    => __( 'Modal gate requiring visitors to confirm their age before accessing age-restricted content.', 'popup-maker' ),
	'category'       => 'compliance',
	'tier'           => 'free',
	'keywords'       => [ 'age', 'gate', 'verification', 'compliance', 'modal', 'adult' ],
	'viewport_width' => 480,
	'content'        => implode( "\n\n", [
		sprintf(
			'<!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">%s</h2><!-- /wp:heading -->',
			esc_html__( 'Verify Your Age', 'popup-maker' )
		),
		sprintf(
			'<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">%s</p><!-- /wp:paragraph -->',
			esc_html__( 'This content is for adults only. Please verify your age to continue.', 'popup-maker' )
		),
		sprintf(
			'<!-- wp:paragraph {"align":"center","fontSize":"small"} --><p class="has-text-align-center has-small-font-size">%s</p><!-- /wp:paragraph -->',
			esc_html__( 'You must be at least 18 years old.', 'popup-maker' )
		),
		sprintf(
			'<!-- wp:paragraph {"align":"center","fontSize":"small","className":"popmake-form-placeholder"} --><p class="has-text-align-center has-small-font-size popmake-form-placeholder">%s</p><!-- /wp:paragraph -->',
			esc_html__( '👉 Replace this paragraph with a date-of-birth field (WPForms, Gravity Forms, etc.) or native HTML date input.', 'popup-maker' )
		),
		'<!-- wp:popup-maker/cta-buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-popup-maker-cta-buttons"><!-- wp:popup-maker/cta-button -->
<div class="wp-block-popup-maker-cta-button"><a class="pum-cta wp-block-popup-maker-cta-button__link wp-element-button">' . esc_html__( 'I Confirm My Age', 'popup-maker' ) . '</a></div>
<!-- /wp:popup-maker/cta-button --></div>
<!-- /wp:popup-maker/cta-buttons -->',
		'<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline popmake-close"} -->
<div class="wp-block-button is-style-outline popmake-close"><a class="wp-block-button__link wp-element-button">' . esc_html__( 'Leave Site', 'popup-maker' ) . '</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->',
		sprintf(
			'<!-- wp:paragraph {"align":"center","fontSize":"small"} --><p class="has-text-align-center has-small-font-size">%s</p><!-- /wp:paragraph -->',
			esc_html__( 'By continuing, you confirm you meet the age requirement and agree to our terms.', 'popup-maker' )
		),
	] ),
	'recommended'    => [
		'triggers' => [
			[
				'type'     => 'auto_open',
				'settings' => [
					'delay'       => 0,
					'cookie_name' => [ 'pum-{popup_id}' ],
				],
			],
		],
		'cookies'  => [
			[
				'event'    => 'on_popup_conversion',
				'settings' => [
					'name'    => 'pum-{popup_id}',
					'time'    => '365 days',
					'session' => false,
					'path'    => true,
				],
			],
		],
		'notes'    => __( 'Position as a blocking full-screen overlay on initial page load. No close affordance (X button) — use only "Leave Site" to enforce the gate. Set cookie on age confirmation to prevent re-verification for 1 year per compliance policy.', 'popup-maker' ),
	],
];
