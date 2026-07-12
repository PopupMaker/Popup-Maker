<?php
/**
 * Popup template: Lead Magnet - Content Upgrade.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

defined( 'ABSPATH' ) || exit;

return [
	'slug'           => 'lead-magnet-download',
	'name'           => __( 'Lead Magnet - Content Upgrade', 'popup-maker' ),
	'description'    => __( 'Split-column capture popup with benefit bullets and email-to-download CTA.', 'popup-maker' ),
	'category'       => 'lead-capture',
	'tier'           => 'free',
	'keywords'       => [ 'lead', 'magnet', 'download', 'email', 'capture', 'content', 'upgrade' ],
	'viewport_width' => 600,
	'content'        => implode( "\n\n", [
		// Columns container for split layout.
		'<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column -->',
		// Left column: visual anchor with highlight.
		'<div class="wp-block-column">' .
		sprintf(
			'<!-- wp:group {"style":{"color":{"background":"#f5f5f5"},"spacing":{"padding":{"top":"32px","right":"24px","bottom":"32px","left":"24px"}}},"layout":{"type":"constrained"}} --><div class="wp-block-group has-background" style="background-color:#f5f5f5;padding-top:32px;padding-right:24px;padding-bottom:32px;padding-left:24px"><!-- wp:heading {"textAlign":"center","level":3,"fontSize":"large"} --><h3 class="wp-block-heading has-text-align-center has-large-font-size">%s</h3><!-- /wp:heading --><!-- wp:paragraph {"align":"center","fontSize":"small"} --><p class="has-text-align-center has-small-font-size">%s</p><!-- /wp:paragraph --></div><!-- /wp:group -->',
			esc_html__( 'Your Guide Awaits', 'popup-maker' ),
			esc_html__( 'Free instant download—no spam guarantee', 'popup-maker' )
		) .
		'</div><!-- /wp:column -->',
		// Right column: form and CTA.
		'<!-- wp:column --><div class="wp-block-column">' .
		sprintf(
			'<!-- wp:group {"style":{"spacing":{"padding":{"top":"24px","right":"24px","bottom":"24px","left":"24px"}}},"layout":{"type":"constrained"}} --><div class="wp-block-group" style="padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:heading {"level":2} --><h2 class="wp-block-heading">%s</h2><!-- /wp:heading --><!-- wp:paragraph --><p>%s</p><!-- /wp:paragraph --><!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li>%s</li><!-- /wp:list-item --><!-- wp:list-item --><li>%s</li><!-- /wp:list-item --><!-- wp:list-item --><li>%s</li><!-- /wp:list-item --></ul><!-- /wp:list --><!-- wp:spacer {"height":"16px"} --><div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">%s</p><!-- /wp:paragraph --></div><!-- /wp:group -->',
			esc_html__( 'Get Our Free Marketing Playbook', 'popup-maker' ),
			esc_html__( 'Used by 5000+ agencies worldwide.', 'popup-maker' ),
			esc_html__( 'Step-by-step checklists', 'popup-maker' ),
			esc_html__( 'Real campaign templates', 'popup-maker' ),
			esc_html__( 'Conversion optimization tips', 'popup-maker' ),
			esc_html__( 'Enter your email to download instantly', 'popup-maker' )
		) .
		sprintf(
			'<!-- wp:paragraph {"align":"center","fontSize":"small","className":"pum-form-placeholder"} --><p class="has-text-align-center pum-form-placeholder has-small-font-size">%s</p><!-- /wp:paragraph --><!-- wp:spacer {"height":"12px"} --><div style="height:12px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->',
			esc_html__( '👉 Replace this paragraph with your form block (WPForms, Gravity Forms, Fluent Forms, etc.).', 'popup-maker' )
		) .
		'<!-- wp:popup-maker/cta-buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-popup-maker-cta-buttons"><!-- wp:popup-maker/cta-button --><div class="wp-block-popup-maker-cta-button"><a class="pum-cta wp-block-popup-maker-cta-button__link wp-element-button">' .
		esc_html__( 'Download Free Guide', 'popup-maker' ) .
		'</a></div><!-- /wp:popup-maker/cta-button --></div><!-- /wp:popup-maker/cta-buttons -->' .
		'</div><!-- /wp:column --></div><!-- /wp:columns -->',
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
					'time'    => '30 days',
					'session' => false,
					'path'    => true,
				],
			],
		],
		'notes'    => __( 'Set modal width to 600px for optimal split layout. Connect CTA to a redirect-type call-to-action that links to your download or thank-you page. Mobile renders as single column; consider bottom-sheet positioning on smartphones.', 'popup-maker' ),
	],
];
