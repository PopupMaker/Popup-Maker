<?php
/**
 * Popup template: Social Follow.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

defined( 'ABSPATH' ) || exit;

return [
	'slug'           => 'social-follow',
	'name'           => __( 'Social Follow', 'popup-maker' ),
	'description'    => __( 'Compact modal with headline and social media links for followers.', 'popup-maker' ),
	'category'       => 'engagement',
	'tier'           => 'free',
	'keywords'       => [ 'social', 'follow', 'engagement', 'compact', 'links' ],
	'viewport_width' => 480,
	'content'        => implode( "\n\n", [
		sprintf(
			'<!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">%s</h2><!-- /wp:heading -->',
			esc_html__( 'Let\'s stay connected', 'popup-maker' )
		),
		sprintf(
			'<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">%s</p><!-- /wp:paragraph -->',
			esc_html__( 'Follow us for the latest updates and exclusive content.', 'popup-maker' )
		),
		'<!-- wp:spacer {"height":"12px"} --><div style="height:12px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->',
		'<!-- wp:social-links {"layout":{"type":"flex","justifyContent":"center"}} --><ul class="wp-block-social-links"><!-- wp:social-link {"url":"#","service":"facebook"} /--><!-- wp:social-link {"url":"#","service":"instagram"} /--><!-- wp:social-link {"url":"#","service":"x"} /--><!-- wp:social-link {"url":"#","service":"youtube"} /--></ul><!-- /wp:social-links -->',
		'<!-- wp:spacer {"height":"12px"} --><div style="height:12px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->',
		sprintf(
			'<!-- wp:paragraph {"align":"center","fontSize":"small","className":"popmake-close"} --><p class="has-text-align-center popmake-close has-small-font-size">%s</p><!-- /wp:paragraph -->',
			esc_html__( 'Dismiss', 'popup-maker' )
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
					'time'    => '1 month',
					'session' => false,
					'path'    => true,
				],
			],
		],
		'notes'    => __( 'Edit social links in the editor to point to your accounts. Auto-open after 5 seconds; adjust timing and repeat frequency via triggers tab.', 'popup-maker' ),
	],
];
