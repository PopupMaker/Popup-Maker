<?php
/**
 * Call To Action handler class.
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Controllers;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Class CallToActions
 *
 * @since X.X.X
 */
class CallToActions extends Controller {

	/**
	 * Initialize cta actions
	 */
	public function init() {
		add_action( 'template_redirect', [ $this, 'template_redirect' ] );
	}
	/**
	 * Checks for valid requests and properly handles them.
	 *
	 * Redirects when needed.
	 */
	public function template_redirect() {
		/* phpcs:disable WordPress.Security.NonceVerification.Recommended */
		$cta_uuid = ! empty( $_GET['cta'] ) ? sanitize_text_field( wp_unslash( $_GET['cta'] ) ) : '';
		$popup_id = ! empty( $_GET['pid'] ) ? absint( $_GET['pid'] ) : 0;
		/* phpcs:enable WordPress.Security.NonceVerification.Recommended */

		if ( empty( $cta_uuid ) ) {
			return;
		}

		// Check for matching cta_{uuid}.
		$cta_uuid = str_replace( 'cta_', '', $cta_uuid );

		// Check for matching popup_{uuid}.
		$cta = $this->container->get( 'ctas' )->get_by_uuid( $cta_uuid );

		// If no uuid is found, we don't have what we need, so return.
		if ( ! $cta ) {
			return;
		}

		$popup = $popup_id > 0 ? pum_get_popup( $popup_id ) : false;

		// TODO Is a switch for each CTA type needed, or should we do a named action? Or Simply a plain action with args.
		switch ( $cta->get_setting( 'type' ) ) {
			/**
			 * Built-ins.
			 */
			case 'link':
				/**
				 * Track conversion with added value.
				 *
				 * TODO This likely needs reworked to be more generic, not always requiring a popup id.
				 */
				pum_track_conversion_event( $popup_id, [
					'cta_id' => $cta->id,
				]);

				$url = esc_url_raw( $cta->get_setting( 'url' ) );

				wp_safe_redirect( $url );
				exit;

			/**
			 * Extension based handlers.
			 */
			default:
				do_action( 'pum_cta_' . $cta['type'] . '_action', $popup_id, $cta['type'] );
				break;
		}
	}

	/**
	 * DO NOT USE. Function is for internal alternative knowledge use only.
	 *
	 * @param \WP_Post|\PopupMaker\Base\Model\Post $post
	 */
	private function detect_cta_from_content( $post, $cta_uuid ) {
		$cta = false;

		/**
		 * Determine how user edits popups and look for the correct CTA.
		 */
		if ( pum_get_option( 'gutenberg_support_enabled' ) && has_block( 'popup-maker/call-to-action', $post->post_content ) ) {
			$blocks     = parse_blocks( $post->post_content );
			$cta_blocks = \PUM_Utils_Blocks::find_blocks( $blocks, 'popup-maker/call-to-action' );

			$cta_block = false;

			foreach ( $cta_blocks as $block ) {
				if ( $cta_uuid === $block['attrs']['uuid'] ) {
					$cta_block = $block;
					break;
				}
			}

			$cta = isset( $cta_block['attrs'] ) ? $cta_block['attrs'] : false;
		} elseif ( has_shortcode( $post->post_content, 'pum_cta' ) ) {
			$cta_shortcodes = \PUM_Utils_Shortcodes::find_shortcodes_in_content( $post->post_content, 'pum_cta' );

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
						$this->generate_cta_uuid( $post->ID ?? $post->id, $cta_shortcode['atts']['type'], $cta_shortcode['atts']['text'] );

				if ( $cta_uuid === $uuid ) {
					$cta = $cta_shortcode['atts'];
				}
			}
		} else {
			$cta = false;
		}

		return $cta;
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
	private function generate_cta_uuid( $post_id, $cta_type, $cta_text, $extras = [] ) {
		ksort( $extras );
		$extras = wp_json_encode( $extras );

		return substr( md5( "$post_id-$cta_type-$cta_text-$extras" ), 0, 10 );
	}
}
