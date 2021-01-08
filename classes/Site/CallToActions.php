<?php
/**
 * Call To Action handler class.
 *
 * @since       1.14
 * @package     PUM
 * @copyright   Copyright (c) 2020, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Site_CallToActions
 */
class PUM_Site_CallToActions {

	/**
	 * Initialize cta actions
	 */
	public static function init() {
		add_action( 'template_redirect', [ __CLASS__, 'template_redirect' ] );
	}

	/**
	 * Checks for valid requests and properly handles them.
	 *
	 * Redirects when needed.
	 */
	public static function template_redirect() {
		$valid_actions = apply_filters(
			'pum_valid_cta_actions',
			[
				'redirect',
			]
		);

		/* phpcs:disable WordPress.Security.NonceVerification.Recommended */
		$action   = ! empty( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'redirect';
		$popup_id = ! empty( $_GET['pid'] ) ? absint( $_GET['pid'] ) : 0;
		$cta_uuid = ! empty( $_GET['uuid'] ) ? sanitize_text_field( wp_unslash( $_GET['uuid'] ) ) : '';
		/* phpcs:enable WordPress.Security.NonceVerification.Recommended */

		$checks = [
			$popup_id > 0,
			'' !== $cta_uuid,
			in_array( $action, $valid_actions, true ),
		];

		if ( in_array( false, $checks, true ) ) {
			return;
		}

		$popup = pum_get_popup( $popup_id );

		if ( ! pum_is_popup( $popup ) ) {
			return;
		}

		$cta = null;

		/**
		 * Determine how user edits popups and look for the correct CTA.
		 */
		if ( pum_get_option( 'gutenberg_support_enabled' ) && has_block( 'pum/call-to-action', $popup->post_content ) ) {
			$blocks     = parse_blocks( $popup->post_content );
			$cta_blocks = PUM_Utils_Blocks::find_blocks( $blocks, 'pum/call-to-action' );

			$cta_block = false;

			foreach ( $cta_blocks as $block ) {
				if ( $cta_uuid === $block['attrs']['uuid'] ) {
					$cta_block = $block;
					break;
				}
			}

			$cta = isset( $cta_block['attrs'] ) ? $cta_block['attrs'] : false;
		} elseif ( has_shortcode( $popup->post_content, 'pum_cta' ) ) {
			$cta_shortcodes = PUM_Utils_Shortcodes::find_shortcodes_in_content( $popup->post_content, 'pum_cta' );

			/**
			 * TODO Left off here, this almost could work, but attribute mismatches (i think due to PUM_Shortcode::parse_atts) is causing it to fail matches.
			 *
			 * Relying on order in the content may not be wise as that could change either with out of date cached pages
			 * or because they use JS to manipulate things such as with Google Optimize.
			 *
			 * Some type of tokenized string based on the attributes might be best.
			 *
			 * Maybe something like
			 * 1. Parse specific attributes into a string, such as "$type-$text-$button"
			 * 2. MD5 that string and add it as part of the url.
			 * 3. Parse each cta shortcodes identifier string (md5) and compare it for match.
			 * 4. Use the matched CTA shortcode.
			 *
			 * ALTERNATIVE:
			 * - Unique hash/id generated at the time the block/shortcode is initially created, never changing.
			 * ^^ See if/how we can do this in Shortcodes, should be simple enough with blocks.
			 *
			 * Notes:
			 * - Using limited number of attributes that should be mostly unique should prevent oddities for out of order arguments.
			 * - We could order class the args alphanumerically and then md5 them all, that might be more efficient and give more unique tokens.
			 */
			foreach ( $cta_shortcodes as $cta_shortcode ) {
				$uuid = isset( $cta_shortcode['atts']['uuid'] ) ?
						$cta_shortcode['atts']['uuid'] :
						self::generate_cta_uuid( $popup_id, $cta_shortcode['atts']['type'], $cta_shortcode['atts']['text'] );

				if ( $cta_uuid === $uuid ) {
					$cta = $cta_shortcode['atts'];
				}
			}
		} else {
			$cta = false;
		}

		/**
		 * If no matched CTA was found bail.
		 */
		if ( ! $cta ) {
			return;
		}

		switch ( $action ) {
			case 'redirect':
				/**
				 * Track conversion with added value.
				 */
				pum_track_conversion_event( $popup_id );

				$url = esc_url_raw( $cta['url'] );

				wp_safe_redirect( $url );
				exit;
			default:
				do_action( 'pum_' . $action . '_action', $popup_id, $action );
				break;
		}
	}

	/**
	 * Generate a unique token for each CTA.
	 *
	 * @param int    $post_id Popup or post ID.
	 * @param string $cta_type Type of CTA.
	 * @param string $cta_text CTA text.
	 * @param array  $extras Extra args to make the token unique.
	 *
	 * @return string
	 */
	public static function generate_cta_uuid( $post_id, $cta_type, $cta_text, $extras = [] ) {
		ksort( $extras );
		$extras = wp_json_encode( $extras );

		return substr( md5( "$post_id-$cta_type-$cta_text-$extras" ), 0, 10 );
	}
}
