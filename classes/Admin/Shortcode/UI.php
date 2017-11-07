<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Admin_Shortcode_UI
 *
 * This class maintains a global set of all registered PUM shortcodes.
 *
 * @since 1.7.0
 */
class PUM_Admin_Shortcode_UI {

	/**
	 * @var PUM_Admin_Shortcode_UI
	 */
	private static $instance;

	/**
	 * Main instance
	 *
	 * @return PUM_Admin_Shortcode_UI
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
			self::$instance->init();
		}

		return self::$instance;
	}

	public function init() {
		add_action( 'admin_init', array( $this, 'init_editor' ), 20 );
	}

	/**
	 * Initialize the editor button when needed.
	 */
	public function init_editor() {
		/*
		 * Check if the logged in WordPress User can edit Posts or Pages.
		 */
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		/*
		 * Check if the logged in WordPress User has the Visual Editor enabled.
		 */
		if ( get_user_option( 'rich_editing' ) !== 'true' ) {
			return;
		}

		/*
		 * Check if the shortcode ui disabled.
		 */
		if ( apply_filters( 'pum_disable_shortcode_ui', false ) || pum_get_option( 'disable_shortcode_ui' ) ) {
			return;
		}

		// Add shortcode ui button & js.
		add_filter( 'mce_buttons', array( $this, 'mce_buttons' ) );
		add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ) );

		// Process live previews.
		add_action( 'wp_ajax_pum_do_shortcode', array( $this, 'wp_ajax_pum_do_shortcode' ) );
		add_action( 'wp_ajax_pum_do_shortcode', array( $this, 'do_shortcode' ) );
	}

	/**
	 * Adds our tinymce button
	 *
	 * @param  array $buttons
	 *
	 * @return array
	 */
	public function mce_buttons( $buttons ) {
		// Enqueue scripts when editor is detected.
		$this->enqueue_scripts();

		array_push( $buttons, 'pum_shortcodes' );

		return $buttons;
	}

	/**
	 * Enqueues needed assets.
	 */
	public function enqueue_scripts() {
		// Register editor styles.
		add_editor_style( PUM_Admin_Assets::$css_url . 'admin-editor-styles' . PUM_Admin_Assets::$suffix . '.css' );

		wp_enqueue_style( 'pum-admin-shortcode-ui' );
		wp_enqueue_script( 'pum-admin-shortcode-ui' );
		wp_localize_script( 'pum-admin-shortcode-ui', 'pum_shortcode_ui_vars', apply_filters( 'pum_shortcode_ui_vars', array(
			'nonce'      => wp_create_nonce( "pum-shortcode-ui-nonce" ),
			'I10n'       => array(
				'insert'                          => __( 'Insert', 'popup-maker' ),
				'cancel'                          => __( 'Cancel', 'popup-maker' ),
				'shortcode_ui_button_tooltip'     => __( 'Popup Maker Shortcodes', 'popup-maker' ),
				'error_loading_shortcode_preview' => __( 'There was an error in generating the preview', 'popup-maker' ),
			),
			'shortcodes' => $this->shortcode_ui_var(),
		) ) );
	}

	public function shortcode_ui_var() {
		$shortcodes = array();

		foreach ( PUM_Shortcodes::instance()->get_shortcodes() as $tag => $shortcode ) {
			/**
			 * @var $shortcode PUM_Shortcode
			 */
			$shortcodes[ $tag ] = array(
				'label'          => $shortcode->label(),
				'description'    => $shortcode->description(),
				'sections'       => $shortcode->sections(),
				'fields'         => array(),
				'has_content'    => $shortcode->has_content,
				'ajax_rendering' => $shortcode->ajax_rendering === true,
			);

			foreach ( $shortcode->get_all_fields() as $section => $fields ) {
				foreach ( $fields as $id => $field ) {
					$field['class']                             = $shortcode->field_classes( $field );
					$shortcodes[ $tag ]['fields'][ $section ][] = $field;
				}
			}
		}

		return $shortcodes;
	}

	/**
	 * Adds our tinymce plugin js
	 *
	 * @param  array $plugin_array
	 *
	 * @return array
	 */
	public function mce_external_plugins( $plugin_array ) {
		return array_merge( $plugin_array, array(
			'pum_shortcodes' => PUM_Admin_Assets::$js_url . 'mce-buttons' . PUM_Admin_Assets::$suffix . '.js',
		) );
	}

	public function do_shortcode() {

		check_ajax_referer( 'pum-shortcode-ui-nonce', 'nonce' );

		$tag = ! empty( $_REQUEST['tag'] ) ? $_REQUEST['tag'] : false;

		/** @var PUM_Shortcode $shortcode */
		$shortcode = PUM_Shortcodes::instance()->get_shortcode( $tag );


		$code = stripslashes( $_REQUEST['shortcode'] );

		$content = do_shortcode( $code );

		if ( ! $shortcode || $content == $code ) {
			wp_send_json_error();
		}

		$styles = "<style>" . $shortcode->_template_styles() . "</style>";

		wp_send_json_success( $styles . $content );
	}

	/**
	 * Get a bunch of shortcodes to render in MCE preview.
	 */
	public function wp_ajax_pum_do_shortcode() {

		$shortcodes = $_REQUEST['queries'];

		if ( ! is_array( $shortcodes ) ) {
			// Don't sanitize shortcodes — can contain HTML kses doesn't allow (e.g. sourcecode shortcode)

			$shortcode = ! empty( $shortcodes ) ? stripslashes( $shortcodes ) : null;
			$post_id   = isset( $_REQUEST['post_id'] ) ? intval( $_REQUEST['post_id'] ) : null;

			$responses = array(
				'query' => $shortcodes,
				'html'  => $this->render_shortcode_for_preview( $shortcode, $post_id ),
			);

			wp_send_json_success( $responses );
			exit;
		}

		if ( count( $shortcodes ) ) {

			$responses = array();

			foreach ( $shortcodes as $posted_query ) {

				// Don't sanitize shortcodes — can contain HTML kses doesn't allow (e.g. sourcecode shortcode)
				if ( ! empty( $posted_query['shortcode'] ) ) {
					$shortcode = stripslashes( $posted_query['shortcode'] );
				} else {
					$shortcode = null;
				}
				if ( isset( $_REQUEST['post_id'] ) ) {
					$post_id = intval( $_REQUEST['post_id'] );
				} else {
					$post_id = null;
				}

				$responses[ $posted_query['counter'] ] = array(
					'query'    => $posted_query,
					'response' => $this->render_shortcode_for_preview( $shortcode, $post_id ),
				);
			}

			wp_send_json_success( $responses );
			exit;
		}

	}

	/**
	 * Render a shortcode body for preview.
	 *
	 * @param $shortcode
	 * @param null $post_id
	 *
	 * @return string
	 */
	private function render_shortcode_for_preview( $shortcode, $post_id = null ) {

		if ( ! defined( 'PUM_DOING_PREVIEW' ) ) {
			define( 'PUM_DOING_PREVIEW', true );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return esc_html__( "You do not have access to preview this post.", 'popup-maker' );
		}

		/**
		 * Often the global $post is not set yet. Set it in case for proper rendering.
		 */
		if ( ! empty( $post_id ) ) {
			global $post;
			$post = get_post( $post_id );
			setup_postdata( $post );
		}

		ob_start();

		/**
		 * Fires before shortcode is rendered in preview.
		 *
		 * @param string $shortcode Full shortcode including attributes
		 */
		do_action( 'pum_before_do_shortcode', $shortcode );

		echo do_shortcode( $shortcode ); // WPCS: xss ok

		/**
		 * Fires after shortcode is rendered in preview.
		 *
		 * @param string $shortcode Full shortcode including attributes
		 */
		do_action( 'pum_after_do_shortcode', $shortcode );

		return ob_get_clean();
	}
}
