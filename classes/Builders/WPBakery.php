<?php
/**
 * WPBakery Page Builder integration.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Builders;

use PopupMaker\Base\PageBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Loads WPBakery's document-specific styles for popup content.
 *
 * @since 1.25.1
 */
class WPBakery extends PageBuilder {

	/** @var string */
	public $key = 'wpbakery';

	/** @var string */
	protected $label = 'WPBakery Page Builder';

	/**
	 * Popup documents whose styles need to be emitted.
	 *
	 * @var array<int,bool>
	 */
	private $collected_documents = [];

	/**
	 * Popup documents whose styles have already been emitted.
	 *
	 * @var array<int,bool>
	 */
	private $emitted_documents = [];

	/**
	 * Whether WPBakery's frontend CSS service is available.
	 *
	 * @return bool
	 */
	public function is_available() {
		return defined( 'WPB_VC_VERSION' ) && is_object( $this->get_vc_base() );
	}

	/**
	 * Emit preloaded styles in the head and preserve late-rendered styles.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'wp_head', [ $this, 'flush_preloaded_assets' ], 1000 );
		add_filter( 'pum_popup_content', [ $this, 'inject_late_document_styles' ], 12, 2 );
	}

	/**
	 * Whether a popup was saved with WPBakery.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public function owns_document( $popup_id ) {
		$popup_id = absint( $popup_id );

		return $popup_id &&
			'popup' === get_post_type( $popup_id ) &&
			'true' === get_post_meta( $popup_id, '_wpb_vc_js_status', true );
	}

	/**
	 * Collect visitor assets while preserving the normal shortcode pipeline.
	 *
	 * @param int  $popup_id         Popup ID.
	 * @param bool $is_editor_canvas Whether this is the native editor canvas.
	 *
	 * @return null
	 */
	public function render_document( $popup_id, $is_editor_canvas = false ) {
		unset( $is_editor_canvas );

		$this->collect_document_assets( $popup_id );

		return null;
	}

	/**
	 * Add a popup document to the pending style batch.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return void
	 */
	public function collect_document_assets( $popup_id ) {
		$popup_id = absint( $popup_id );

		if ( $popup_id && ! isset( $this->emitted_documents[ $popup_id ] ) ) {
			$this->collected_documents[ $popup_id ] = true;
		}
	}

	/**
	 * Output styles collected during Popup Maker's preload pass.
	 *
	 * @return void
	 */
	public function flush_preloaded_assets() {
		foreach ( array_keys( $this->collected_documents ) as $popup_id ) {
			echo $this->capture_document_styles( $popup_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WPBakery sanitizes its generated CSS.
		}
	}

	/**
	 * Prepend styles when a popup is first rendered after the document head.
	 *
	 * @param mixed $content  Rendered popup content.
	 * @param mixed $popup_id Popup ID.
	 *
	 * @return mixed
	 */
	public function inject_late_document_styles( $content, $popup_id = 0 ) {
		if ( ! is_string( $content ) || ! is_numeric( $popup_id ) || ! $this->head_has_rendered() ) {
			return $content;
		}

		$popup_id = absint( $popup_id );

		if (
			! $popup_id ||
			isset( $this->emitted_documents[ $popup_id ] ) ||
			( ! isset( $this->collected_documents[ $popup_id ] ) && ! $this->owns_document( $popup_id ) )
		) {
			return $content;
		}

		$this->collect_document_assets( $popup_id );

		return $this->capture_document_styles( $popup_id ) . $content;
	}

	/**
	 * Whether the document head has finished rendering.
	 *
	 * @return bool
	 */
	protected function head_has_rendered() {
		return did_action( 'wp_head' ) && ! doing_action( 'wp_head' );
	}

	/**
	 * Capture one popup document's generated styles once.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return string
	 */
	private function capture_document_styles( $popup_id ) {
		$popup_id = absint( $popup_id );

		if ( ! $popup_id || isset( $this->emitted_documents[ $popup_id ] ) ) {
			return '';
		}

		$this->emitted_documents[ $popup_id ] = true;
		unset( $this->collected_documents[ $popup_id ] );

		return $this->capture_provider_output( [ $this, 'output_page_custom_css' ], $popup_id ) .
			$this->capture_provider_output( [ $this, 'output_shortcodes_css' ], $popup_id );
	}

	/**
	 * Capture provider output without leaking it during popup filtering.
	 *
	 * @param callable $callback Provider output callback.
	 * @param int      $popup_id Popup ID.
	 *
	 * @return string
	 */
	private function capture_provider_output( $callback, $popup_id ) {
		ob_start();

		try {
			call_user_func( $callback, $popup_id );
		} catch ( \Throwable $error ) {
			unset( $error );
		}

		$output = ob_get_clean();

		return is_string( $output ) ? $output : '';
	}

	/**
	 * Output WPBakery's page-level custom CSS.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return void
	 */
	protected function output_page_custom_css( $popup_id ) {
		$module = $this->get_custom_css_module();

		if ( is_object( $module ) && method_exists( $module, 'output_custom_css_to_page' ) ) {
			$module->output_custom_css_to_page( $popup_id );

			return;
		}

		$base = $this->get_vc_base();

		if ( is_object( $base ) && method_exists( $base, 'addPageCustomCss' ) ) {
			$base->addPageCustomCss( $popup_id );
		}
	}

	/**
	 * Output WPBakery's shortcode-generated CSS.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return void
	 */
	protected function output_shortcodes_css( $popup_id ) {
		$base = $this->get_vc_base();

		if ( ! is_object( $base ) ) {
			return;
		}

		if ( method_exists( $base, 'addShortcodesCss' ) ) {
			$base->addShortcodesCss( $popup_id );

			return;
		}

		if ( method_exists( $base, 'addShortcodesCustomCss' ) ) {
			$base->addShortcodesCustomCss( $popup_id );
		}
	}

	/**
	 * Get WPBakery's base frontend service.
	 *
	 * @return object|null
	 */
	protected function get_vc_base() {
		if ( ! class_exists( '\Vc_Manager' ) || ! method_exists( '\Vc_Manager', 'getInstance' ) ) {
			return null;
		}

		try {
			$manager = \Vc_Manager::getInstance();

			return is_object( $manager ) && method_exists( $manager, 'vc' ) ? $manager->vc() : null;
		} catch ( \Throwable $error ) {
			unset( $error );

			return null;
		}
	}

	/**
	 * Get WPBakery's modern custom CSS module.
	 *
	 * @return object|null
	 */
	protected function get_custom_css_module() {
		if ( ! function_exists( 'vc_modules_manager' ) ) {
			return null;
		}

		try {
			$manager = vc_modules_manager();

			return is_object( $manager ) && method_exists( $manager, 'get_module' )
				? $manager->get_module( 'vc-custom-css' )
				: null;
		} catch ( \Throwable $error ) {
			unset( $error );

			return null;
		}
	}
}
