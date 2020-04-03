<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
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

	private static $initialized = false;

	/**
	 * Here for backward compatibility with 3rd party plugins.
	 *
	 * @deprecated 1.7.0
	 */
	public static function instance() {
		self::init();
	}

	public static function init() {
		if ( ! self::$initialized ) {
			add_action( 'admin_init', array( __CLASS__, 'init_editor' ), 20 );
			self::$initialized = true;
		}
	}

	/**
	 * Initialize the editor button when needed.
	 */
	public static function init_editor() {
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
		add_filter( 'mce_buttons', array( __CLASS__, 'mce_buttons' ) );
		add_filter( 'mce_external_plugins', array( __CLASS__, 'mce_external_plugins' ) );

		// Add core site styles for form previews.
		add_editor_style( Popup_Maker::$URL . 'assets/css/pum-site.min.css' );

		// Process live previews.
		add_action( 'wp_ajax_pum_do_shortcode', array( __CLASS__, 'do_shortcode' ) );
		//add_action( 'wp_ajax_pum_do_shortcode', array( __CLASS__, 'wp_ajax_pum_do_shortcode' ) );
	}

	/**
	 * Adds our tinymce button
	 *
	 * @param  array $buttons
	 *
	 * @return array
	 */
	public static function mce_buttons( $buttons ) {
		// Enqueue scripts when editor is detected.

		if ( ! did_action( 'admin_enqueue_scripts' ) ) {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ), 120 ); // 120 because core styles are registered at 100 for some reason.
		} else {
			self::enqueue_scripts();
		}

		array_push( $buttons, 'pum_shortcodes' );

		return $buttons;
	}

	/**
	 * Enqueues needed assets.
	 */
	public static function enqueue_scripts() {
		// Register editor styles.
		add_editor_style( PUM_Admin_Assets::$css_url . 'pum-admin-editor-styles' . PUM_Admin_Assets::$suffix . '.css' );

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
			'shortcodes' => self::shortcode_ui_var(),
		) ) );
	}

	/**
	 * Generates a json object variable to pass to the Shortcode UI front end.
	 *
	 * @return array
	 */
	public static function shortcode_ui_var() {
		$type = pum_typenow();

		$shortcodes = array();

		foreach ( PUM_Shortcodes::instance()->get_shortcodes() as $tag => $shortcode ) {

			$post_types = apply_filters( 'pum_shortcode_post_types', $shortcode->post_types(), $shortcode );

			/**
			 * @var $shortcode PUM_Shortcode
			 */
			if ( ! in_array( '*', $post_types ) && ! in_array( $type, $post_types ) ) {
				continue;
			}

			$shortcodes[ $tag ] = array(
				'version'        => $shortcode->version,
				'label'          => $shortcode->label(),
				'description'    => $shortcode->description(),
				'tabs'           => $shortcode->_tabs(),
				'sections'        => $shortcode->_subtabs(),
				'fields'         => $shortcode->_fields(),
				'has_content'    => $shortcode->has_content,
				'ajax_rendering' => $shortcode->ajax_rendering === true,
			);
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
	public static function mce_external_plugins( $plugin_array ) {
		return array_merge( $plugin_array, array(
			'pum_shortcodes' => add_query_arg( array( 'version'=> Popup_Maker::$VER ), PUM_Admin_Assets::$js_url . 'mce-buttons' . PUM_Admin_Assets::$suffix . '.js' ),
		) );
	}

	public static function do_shortcode() {

		check_ajax_referer( 'pum-shortcode-ui-nonce', 'nonce' );

		$tag       = ! empty( $_REQUEST['tag'] ) ? sanitize_key( $_REQUEST['tag'] ) : false;
		$shortcode = ! empty( $_REQUEST['shortcode'] ) ? stripslashes( sanitize_text_field( $_REQUEST['shortcode'] ) ) : null;
		$post_id   = isset( $_REQUEST['post_id'] ) ? intval( $_REQUEST['post_id'] ) : null;

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return esc_html__( "You do not have access to preview this post.", 'popup-maker' );
		}

		/** @var PUM_Shortcode $shortcode */
		$shortcode_object = PUM_Shortcodes::instance()->get_shortcode( $tag );

		if ( ! defined( 'PUM_DOING_PREVIEW' ) ) {
			define( 'PUM_DOING_PREVIEW', true );
		}

		/**
		 * Often the global $post is not set yet. Set it in case for proper rendering.
		 */
		if ( ! empty( $post_id ) ) {
			global $post;
			$post = get_post( $post_id );
			setup_postdata( $post );
		}

		/** @var string $content Rendered shortcode content. */
		$content = PUM_Helpers::do_shortcode( $shortcode );

		/** If no matching tag or $content wasn't rendered die. */
		if ( ! $shortcode_object || $content == $shortcode ) {
			wp_send_json_error();
		}

		/** Generate inline styles when needed. */
		$styles = "<style>" . $shortcode_object->get_template_styles() . "</style>";

		wp_send_json_success( $styles . $content );
	}

}
