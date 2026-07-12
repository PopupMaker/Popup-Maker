<?php
/**
 * Popup template: Cookie Notice.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

defined( 'ABSPATH' ) || exit;

return [
	'slug'           => 'cookie-notice',
	'name'           => __( 'Cookie Notice', 'popup-maker' ),
	'description'    => __( 'Lightweight GDPR-compliant cookie consent banner with accept and reject options.', 'popup-maker' ),
	'category'       => 'compliance',
	'tier'           => 'free',
	'keywords'       => [ 'gdpr', 'cookies', 'consent', 'privacy', 'compliance', 'banner' ],
	'viewport_width' => 480,
	'content'        => implode( "\n\n", [
		'<!-- wp:group {"style":{"color":{"background":"#ffffff","text":"#000000"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}}},"layout":{"type":"constrained"}} --><div class="wp-block-group has-text-color has-background" style="color:#000000;background-color:#ffffff">',
		sprintf(
			'<!-- wp:paragraph {"align":"left","fontSize":"small"} --><p class="has-text-align-left has-small-font-size">%s</p><!-- /wp:paragraph -->',
			esc_html__( 'We use cookies to improve your experience on our site. Learn more in our privacy policy.', 'popup-maker' )
		),
		'<!-- wp:spacer {"height":"16px"} --><div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->',
		'<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"space-between"}} -->',
		'<div class="wp-block-buttons">',
		'<!-- wp:button {"className":"is-style-outline popmake-close"} -->',
		sprintf(
			'<div class="wp-block-button is-style-outline popmake-close"><a class="wp-block-button__link wp-element-button">%s</a></div>',
			esc_html__( 'Reject All', 'popup-maker' )
		),
		'<!-- /wp:button -->',
		'<!-- wp:popup-maker/cta-buttons -->',
		'<div class="wp-block-popup-maker-cta-buttons"><!-- wp:popup-maker/cta-button -->',
		sprintf(
			'<div class="wp-block-popup-maker-cta-button"><a class="pum-cta wp-block-popup-maker-cta-button__link wp-element-button">%s</a></div>',
			esc_html__( 'Accept All', 'popup-maker' )
		),
		'<!-- /wp:popup-maker/cta-button --></div>',
		'<!-- /wp:popup-maker/cta-buttons -->',
		'</div>',
		'<!-- /wp:buttons -->',
		'</div><!-- /wp:group -->',
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
					'time'    => '1 year',
					'session' => false,
					'path'    => true,
				],
			],
			[
				'event'    => 'on_popup_close',
				'settings' => [
					'name'    => 'pum-{popup_id}',
					'time'    => '1 year',
					'session' => false,
					'path'    => true,
				],
			],
		],
		'notes'    => __( 'Connect both buttons to "Simple Close" call-to-action type. Position as sticky bottom bar for unobtrusive GDPR compliance. Fires immediately on page load; respects consent cookie for 1 year.', 'popup-maker' ),
	],
];
