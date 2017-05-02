<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Admin_Shortcode_UI
 *
 * This class maintains a global set of all registered PUM shortcodes.
 */
class PUM_Admin_Shortcode_UI {

	/**
	 * @var PUM_Admin_Shortcode_UI The one true PUM_Admin_Shortcode_UI
	 * @since 1.0
	 */
	private static $instance;

	/**
	 * Main PUM_Admin_Shortcode_UI Instance
	 *
	 * @return PUM_Admin_Shortcode_UI
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof PUM_Admin_Shortcode_UI ) ) {
			self::$instance = new PUM_Admin_Shortcode_UI;
			self::$instance->init();
		}

		return self::$instance;
	}

	public function init() {
		add_action( 'admin_init', array( $this, 'init_editor' ), 20 );
		add_action( 'wp_ajax_pum_do_shortcode', array( $this, 'do_shortcode' ) );
	}

	public function init_editor() {
    	if ( current_user_can('edit_posts') || current_user_can('edit_pages') ) {
			add_action( 'print_media_templates', array( $this, 'print_media_templates' ) );
			add_action( 'admin_print_footer_scripts', array( $this, 'admin_print_footer_scripts' ), 100 );
			add_action( 'wp_ajax_pum_do_shortcode', array( $this, 'wp_ajax_pum_do_shortcode' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

			add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ) );
			add_filter( 'mce_buttons', array( $this, 'mce_buttons' ) );
			add_filter( 'pum_admin_var', array( $this, 'pum_admin_var' ) );
		}
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
	 * @return bool
	 */
	public function editor_enabled() {
		return isset( get_current_screen()->id ) && get_current_screen()->base == 'post';
	}


	/**
	 * Adds our tinymce plugin js
	 *
	 * @param  array $plugin_array
	 *
	 * @return array
	 */
	public function mce_external_plugins( $plugin_array ) {
		if ( ! $this->editor_enabled() || ! pum_should_load_admin_scripts() ) {
			return $plugin_array;
		}

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		$plugin_array['pum_shortcodes'] = POPMAKE_URL . '/assets/js/mce-buttons' . $suffix . '.js';

		return $plugin_array;
	}

	/**
	 * Adds our tinymce button
	 *
	 * @param  array $buttons
	 *
	 * @return array
	 */
	public function mce_buttons( $buttons ) {
		if ( ! $this->editor_enabled() || ! pum_should_load_admin_scripts() ) {
			return $buttons;
		}

		array_push( $buttons, 'pum_shortcodes' );

		return $buttons;
	}

	public function admin_enqueue_scripts() {
		if ( ! $this->editor_enabled() || ! pum_should_load_admin_scripts() ) {
            return;
        }

		define( 'PUM_FORCE_ADMIN_SCRIPTS_LOAD', true );

		$css_dir = POPMAKE_URL . '/assets/css/';
		$suffix  = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		//add_editor_style( $css_dir . 'wp-editor' . $suffix . '.css' );

		wp_enqueue_style( 'pum-shortcode-ui', $css_dir . 'shortcode-ui' . $suffix . '.css', array( 'popup-maker-admin' ), PUM::VER );
	}

    /**
     * Outputs the view inside the wordpress editor.
     */
	public function print_media_templates() {
        if ( ! $this->editor_enabled() || ! pum_should_load_admin_scripts() ) {
            return;
        }
		include_once plugin_dir_path( __FILE__ ) . 'templates/fields.php';
		include_once plugin_dir_path( __FILE__ ) . 'templates/helpers.php';
	}

    public function admin_print_footer_scripts() {
	    if ( ! $this->editor_enabled() || ! pum_should_load_admin_scripts() ) {
            return;
        }
        include_once plugin_dir_path( __FILE__ ) . 'footer-scripts.php';
    }

	public function shortcode_ui_var() {
		$shortcodes = array();

		foreach ( PUM_Shortcodes::instance()->get_shortcodes() as $tag => $shortcode ) {
			/**
			 * @var $shortcode PUM_Shortcode
			 */
			$shortcodes[ $tag ] = array(
				'label' => $shortcode->label(),
				'description' => $shortcode->description(),
				'sections' => $shortcode->sections(),
				'fields' => array(),
				'has_content' => $shortcode->has_content,
				'ajax_rendering' => $shortcode->ajax_rendering === true,
			);

			foreach( $shortcode->get_all_fields() as $section => $fields ) {
				foreach( $fields as $id => $field ) {
					$field['class'] = $shortcode->field_classes( $field );
					$shortcodes[ $tag ]['fields'][ $section ][] = $field;
				}
			}
		}

		return $shortcodes;
	}

	public function pum_admin_var( $var = array() ) {
		if ( ! $this->editor_enabled() || ! pum_should_load_admin_scripts() ) {
			return $var;
		}
		
		$var['shortcode_ui'] = array(
			'shortcodes' => $this->shortcode_ui_var(),
		);

		return $var;
	}

	/**
	 * Get a bunch of shortcodes to render in MCE preview.
	 */
	public function wp_ajax_pum_do_shortcode() {

		$shortcodes = $_REQUEST['queries'];

		if ( ! is_array( $shortcodes ) ) {
			// Don't sanitize shortcodes — can contain HTML kses doesn't allow (e.g. sourcecode shortcode)

			$shortcode = ! empty( $shortcodes ) ? stripslashes( $shortcodes ) : null;
			$post_id = isset( $_REQUEST['post_id'] ) ? intval( $_REQUEST['post_id'] ) : null;

			$responses = array(
				'query'    => $shortcodes,
				'html' => $this->render_shortcode_for_preview( $shortcode, $post_id ),
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

PUM_Admin_Shortcode_UI::instance();
