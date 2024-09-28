<?php

use function PopupMaker\plugin;

/**
 * Integration for Bricks BUilder
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

class PUM_Integration_Builder_Bricks extends PUM_Abstract_Integration {

	/**
	 * @var string
	 */
	public $key = 'bricks';

	/**
	 * @var string
	 */
	public $type = 'builder';

	/**
	 * @return string
	 */
	public function label() {
		return 'Bricks Builder';
	}

	/**
	 * @return bool
	 */
	public function enabled() {
		return defined( 'BRICKS_VERSION' );
	}

	/**
	 * Initializes this module.
	 */
	public function __construct() {
		add_filter( 'popmake_popup_post_type_args', [ $this, 'custom_post_type_args' ], 10, 1 );
		add_filter( 'pum_popup_content', [ $this, 'custom_popup_content' ], 10000, 2 );
		add_filter( 'template_include', [ $this, 'custom_template' ], 10000 );
		add_filter( 'pum_theme_css_selector', [ $this, 'custom_css_selector' ], 10000, 3 );
		add_action( 'wp_print_footer_scripts', [ $this, 'render_custom_editor_scripts' ] );
		add_filter( 'popmake_get_option', [ $this, 'disable_asset_caching' ], 10, 2 );
	}

	/**
	 * Modify post types for the Bricks editor.
	 *
	 * @param array<string,string|bool> $args The post type args.
	 *
	 * @return array<string,string|bool>
	 */
	public function custom_post_type_args( $args ) {
		if ( $this->is_bricks_editor() ) {
			$args['publicly_queryable'] = true;
		}

		return $args;
	}

	/**
	 * Disable asset caching for Bricks editor.
	 *
	 * @param mixed  $value The default value.
	 * @param string $key The option key.
	 *
	 * @return mixed
	 */
	public function disable_asset_caching( $value, $key ) {
		if ( $this->is_bricks_editor_canvas() && 'disable_asset_caching' === $key ) {
			return true;
		}

		return $value;
	}

	/**
	 * Check if the current page is the Bricks editor canvas.
	 *
	 * @return bool
	 */
	public function is_bricks_editor_canvas() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $this->is_bricks_editor() && isset( $_GET['brickspreview'] );
	}

	/**
	 * Check if the current page is the Bricks editor preview.
	 *
	 * @return bool
	 */
	public function is_bricks_editor_preview() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['bricks_preview'] );
	}

	/**
	 * Check if the current page is the Bricks editor.
	 *
	 * @return bool
	 */
	public function is_bricks_editor() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['bricks'] ) && 'run' === sanitize_key( wp_unslash( $_GET['bricks'] ) );
	}

	/**
	 * Custom template for the Bricks editor.
	 *
	 * @param string $template The template path.
	 *
	 * @return string
	 */
	public function custom_template( $template ) {
		if ( is_singular( 'popup' ) ) {
			// Define the path to your custom template file within your plugin
			$custom_template = Popup_Maker::$DIR . 'templates/single-popup.php';

			if ( $this->is_bricks_editor() && ! $this->is_bricks_editor_canvas() ) {
				remove_action( 'wp_footer', [ 'PUM_Site_Popups', 'render_popups' ] );
			}

			// Check if the file exists, then use it as the template
			if ( file_exists( $custom_template ) ) {
				return $custom_template;
			}
		}

		// If not singular 'popup', return the default template
		return $template;
	}

	/**
	 * Modify the CSS selector for the Bricks editor.
	 *
	 * @param string $css_selector The CSS selector.
	 * @param int    $theme_id The theme ID.
	 * @param string $element The element type.
	 *
	 * @return string
	 */
	public function custom_css_selector( $css_selector, $theme_id, $element ) {
		if ( $this->is_bricks_editor_canvas() ) {
			$popup_id = isset( $_GET['p'] ) ? intval( $_GET['p'] ) : false;

			if ( ! $popup_id ) {
				return $css_selector;
			}

			$popup = pum_get_popup( $popup_id );

			$popup_theme = $popup->get_theme_id();

			if ( $popup_theme !== $theme_id ) {
				return $css_selector;
			}

			if ( 'overlay' === $element ) {
				$css_selector = ', #brx-body.postid-' . $popup_id;
			} elseif ( 'container' === $element ) {
				$css_selector = '#bricks-blank-canvas, #brx-content';
			}
		}

		return $css_selector;
	}

	/**
	 * Render custom editor scripts for the Bricks editor.
	 *
	 * @return void
	 */
	public function render_custom_editor_scripts() {
		if ( ! $this->is_bricks_editor_canvas() ) {
			return;
		}

		$popup_id = isset( $_GET['p'] ) ? intval( $_GET['p'] ) : false;
		if ( ! $popup_id ) {
			return;
		}

		?>
		<style>
		#pum-<?php echo esc_attr( $popup_id ); ?> { display: none; }
		body.single-popup { display: initial!important; position: static!important; }
		.pum-container, #brx-content { flex: initial!important; }
		.pum-container .brxe-container { max-width: 100%; }
		</style>
		<script type="text/javascript">
			const $ = jQuery;
			const $popup = $('#pum-<?php echo esc_attr( $popup_id ); ?>');

			const copyElStyles = (sourceElement, targetElement) => {
				$(targetElement).addClass($(sourceElement).attr('class'));
				$(targetElement).attr('style', ($(targetElement).attr('style') || '') + ($(sourceElement).attr('style') || ''));
				$(targetElement).css('max-width', $(sourceElement).css('width'));
			}

			const redraw = () => {
				$('#bricks-blank-canvas, #brx-content').css({ width: 'initial'});
				copyElStyles($popup, $('body.single-popup'));
				copyElStyles($popup.find('.pum-container'), $('#bricks-blank-canvas, #brx-content'));
			}

			if ($popup.length) {
				// console.log('popup found', window.frames[0]);
				setTimeout(redraw, 100);

				let interval = setInterval(() => {
					redraw();
				}, 500);
			}
		</script>
		<?php
	}

	/**
	 * Custom popup content using Bricks.
	 *
	 * @param string $content The popup content.
	 * @param int    $popup_id The popup ID.
	 *
	 * @return string
	 */
	public function custom_popup_content( $content, $popup_id ) {
		global $post;

		if ( 'bricks' !== Bricks\Helpers::get_editor_mode( $popup_id ) ) {
			return $content;
		}

		$original_post = $post;
		setup_postdata( $popup_id );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( $popup_id );

		// $bricks_data = Bricks\Database::get_template_data( 'content' );

		$bricks_data = Bricks\Helpers::get_bricks_data( $popup_id, 'content' );
		if ( $bricks_data ) {
			ob_start();
			Bricks\Frontend::render_content( $bricks_data );
			// Enqueue bricks assets
			// Bricks\Theme_Styles::set_active_style( $popup_id );
			$content = ob_get_clean();
		}

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = $original_post;
		wp_reset_postdata();

		return $content;
	}
}
