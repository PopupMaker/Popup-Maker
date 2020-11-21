<?php
/**
 * Call to Actions Controller.
 *
 * @package PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Controllers\Admin;

use PopupMaker\Base\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Call to Actions Controller.
 *
 * @since X.X.X
 */
class CallToActions extends Controller {

	/**
	 * Register actions.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'add_meta_boxes', [ $this, 'cta_meta_boxes' ] );

		add_action( 'init', [ $this, 'register_cta_block' ] );
		add_shortcode( 'popup_maker_cta', [ $this, 'cta_shortcode' ] );
	}

	/**
	 * Register meta boxes.
	 *
	 * @return void
	 */
	public function cta_meta_boxes() {
		add_meta_box(
			'popup_cta_settings',
			'CTA Configuration',
			[ $this, 'render_cta_meta_box' ],
			'pum_cta',
			'normal',
			'high'
		);
	}

	/**
	 * Render meta box.
	 *
	 * @return void
	 */
	public function render_cta_meta_box( $post ) {
		wp_nonce_field( 'popup_cta_settings', 'popup_cta_nonce' );

		// Basic configuration fields
		$label       = get_post_meta( $post->ID, 'cta_label', true );
		$tracking_id = get_post_meta( $post->ID, 'tracking_identifier', true )
			?: uniqid( 'cta_' );

		echo "<label>CTA Label: <input type='text' name='cta_label' value='" .
			esc_attr( $label ) . "' /></label>";
		echo "<input type='hidden' name='tracking_identifier' value='" .
			esc_attr( $tracking_id ) . "' />";
	}

	/**
	 * Register CTA block.
	 *
	 * @return void
	 */
	public function register_cta_block() {
		register_block_type('popup-maker/cta', [
			'attributes'      => [
				'ctaId'      => [ 'type' => 'string' ],
				'instanceId' => [ 'type' => 'string' ],
			],
			'render_callback' => [ $this, 'render_cta_block' ],
		]);
	}

	/**
	 * Render CTA block.
	 *
	 * @param array{ctaId: int, instanceId: string} $attributes
	 * @return string
	 */
	public function render_cta_block( $attributes ) {
		$cta_id      = $attributes['ctaId'] ?? null;
		$instance_id = $attributes['instanceId']
			?? uniqid( 'cta_instance_' );

		if ( ! $cta_id ) {
			return '';
		}

		$cta_post = get_post( $cta_id );
		$label    = get_post_meta( $cta_id, 'cta_label', true );

		// Basic rendering with tracking
		return sprintf(
			'<div class="popup-maker-cta" 
                data-cta-id="%s" 
                data-instance-id="%s">%s</div>',
			esc_attr( $cta_id ),
			esc_attr( $instance_id ),
			esc_html( $label )
		);
	}

	/**
	 * Render CTA shortcode.
	 *
	 * @param array{id: int} $atts Shortcode attributes.
	 * @return string
	 */
	public function cta_shortcode( $atts = [] ) {
		$atts = shortcode_atts([
			'id' => null,
		], $atts);

		if ( ! $atts['id'] ) {
			return '';
		}

		$instance_id = uniqid( 'cta_shortcode_' );

		// Reuse block rendering logic
		return $this->render_cta_block([
			'ctaId'      => $atts['id'],
			'instanceId' => $instance_id,
		]);
	}
}
