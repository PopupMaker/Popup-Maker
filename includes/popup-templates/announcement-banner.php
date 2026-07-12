<?php
/**
 * Popup template: Announcement Banner.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

defined( 'ABSPATH' ) || exit;

return [
	'slug'           => 'announcement-banner',
	'name'           => __( 'Announcement Banner', 'popup-maker' ),
	'description'    => __( 'Sticky top bar with centered message and optional right-aligned CTA button.', 'popup-maker' ),
	'category'       => 'announcements',
	'tier'           => 'free',
	'keywords'       => [ 'announcement', 'banner', 'sticky', 'bar', 'top', 'alert', 'news' ],
	'viewport_width' => 480,
	'content'        => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"12px","right":"16px","bottom":"12px","left":"16px"}},"color":{"background":"#1f2937","text":"#ffffff"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","alignItems":"center"}} --><div class="wp-block-group has-text-color has-background" style="color:#ffffff;background-color:#1f2937;padding-top:12px;padding-right:16px;padding-bottom:12px;padding-left:16px"><!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">' . esc_html__( 'Check out our latest feature release and see what\'s new.', 'popup-maker' ) . '</p><!-- /wp:paragraph --><!-- wp:buttons {"layout":{"type":"flex"}} --><div class="wp-block-buttons"><!-- wp:popup-maker/cta-buttons --><div class="wp-block-popup-maker-cta-buttons"><!-- wp:popup-maker/cta-button --><div class="wp-block-popup-maker-cta-button"><a class="pum-cta wp-block-popup-maker-cta-button__link wp-element-button">' . esc_html__( 'Learn More', 'popup-maker' ) . '</a></div><!-- /wp:popup-maker/cta-button --></div><!-- /wp:popup-maker/cta-buttons --></div><!-- /wp:buttons --></div><!-- /wp:group -->',
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
				'event'    => 'on_popup_close',
				'settings' => [
					'name'    => 'pum-{popup_id}',
					'time'    => '1 week',
					'session' => false,
					'path'    => true,
				],
			],
		],
		'notes'    => __( 'Sticky top bar best positioned with Popup Maker popup position set to top-sticky. Inherits theme colors; customize background/text via block style editor. Dismiss via the close affordance (X).', 'popup-maker' ),
	],
];
