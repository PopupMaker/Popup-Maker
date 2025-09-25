<?php
/**
 * Call to Actions Controller.
 *
 * @package PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Controllers\Admin;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Call to Actions Controller.
 *
 * @since 1.21.0
 */
class CallToActions extends Controller {

	/**
	 * Register actions.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', [ $this, 'register_page' ], 999 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// add_action( 'init', [ $this, 'register_cta_block' ] );
		// add_shortcode( 'popup_maker_cta', [ $this, 'cta_shortcode' ] );
	}

	/**
	 * Register admin options pages.
	 *
	 * @return void
	 */
	public function register_page() {
		add_submenu_page(
			'edit.php?post_type=popup',
			__( 'Call to Actions', 'popup-maker' ),
			__( 'Call to Actions', 'popup-maker' ),
			$this->container->get_permission( 'edit_ctas' ),
			'popup-maker-call-to-actions',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Render settings page title & container.
	 *
	 * @return void
	 */
	public function render_page() {
		?>
			<div id="popup-maker-call-to-actions-root-container"></div>
			<!-- <script>jQuery(() => window.popupMaker.settingsPage.init());</script> -->
		<?php
	}

	/**
	 * Enqueue assets for the settings page.
	 *
	 * @param string $hook Page hook name.
	 *
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'popup_page_popup-maker-call-to-actions' !== $hook ) {
			return;
		}

		// wp_enqueue_editor();
		wp_enqueue_script( 'popup-maker-cta-admin' );
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
