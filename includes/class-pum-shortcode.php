<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Shortcode
 *
 * This is a base class for all popup maker & extension shortcodes.
 */
class PUM_Shortcode extends PUM_Fields {

	/**
	 * Shortcode supports inner content.
	 *
	 * @var bool
	 */
	public $has_content = false;

	/**
	 * @var bool Used to force ajax rendering of the shortcode.
	 */
	public $ajax_rendering = false;

	/**
	 * @var string
	 */
	public $inner_content_section = 'general';

	/**
	 * @var int
	 */
	public $inner_content_priority = 5;

	/**
	 * @var string
	 */
	public $field_prefix = 'attrs';

	/**
	 * @var string
	 */
	public $field_name_format = '{$prefix}[{$field}]';

	/**
	 * The shortcode tag.
	 */
	public function tag() {
		return '';
	}

	/**
	 * Class constructor will set the needed filter and action hooks
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {

		$args = array(
			'sections' => $this->sections(),
		);

		parent::__construct( $args );

		if ( ! did_action( 'init' ) ) {
			add_action( 'init', array( $this, 'register' ) );
		} elseif ( ! did_action( 'admin_head' ) && current_action() != 'init' ) {
			add_action( 'admin_head', array( $this, 'register' ) );
		} else {
			$this->register();
		}

		return $this;
	}

	/**
	 *
	 */
	public function register() {
		add_shortcode( $this->tag(), array( $this, 'handler' ) );
		add_action( 'print_media_templates', array( $this, '_template' ) );
		add_action( 'register_shortcode_ui', array( $this, 'register_shortcode_ui' ) );

		if ( is_admin() && pum_should_load_admin_scripts() ) {
			$fields = array();

			if ( $this->has_content ) {
				$inner_content_labels                                     = $this->inner_content_labels();
				$fields[ $this->inner_content_section ]['_inner_content'] = array(
					'label'    => $inner_content_labels['label'],
					'desc'     => $inner_content_labels['description'],
					'section'  => $this->inner_content_section,
					'type'     => 'textarea',
					'priority' => $this->inner_content_priority,
				);
			}

			$fields = array_merge_recursive( $fields, $this->fields() );

			$this->add_fields( $fields );
		}


		PUM_Shortcodes::instance()->add_shortcode( $this );
	}


	/*
	 * Limit this shortcode UI to specific posts. Optional.
	 */
	/**
	 * @return array
	 */
	public function post_types() {
		return array( 'post', 'page', 'popup' );
	}

	/*
	 * How the shortcode should be labeled in the UI. Required argument.
	 */
	/**
	 * @return string
	 */
	public function label() {
		return '';
	}

	/**
	 * @return string
	 */
	public function description() {
		return '';
	}

	/**
	 * @return array
	 */
	public function inner_content_labels() {
		return array(
			'label'       => $this->label(),
			'description' => $this->description(),
		);
	}

	/**
	 * @return array
	 */
	public function sections() {
		return array(
			'general' => __( 'General', 'popup-maker' ),
			'options' => __( 'Options', 'popup-maker' ),
		);
	}

	/*
	 * Include an icon with your shortcode. Optional.
	 * Use a dashicon, or full URL to image.
	 */
	/**
	 * @return string
	 */
	public function icon() {
		return 'dashicons-editor-quote';
	}

	/**
	 * @return array
	 */
	public function defaults() {
		return array();
	}

	/**
	 * @return array
	 */
	public function fields() {
		return array();
	}

	/**
	 * Shortcode handler
	 *
	 * @param  array $atts shortcode attributes
	 * @param  string $content shortcode content
	 *
	 * @return string
	 */
	public function handler( $atts, $content = null ) {
		return '';
	}

	/**
	 * @param $atts
	 *
	 * @return array
	 */
	public function shortcode_atts( $atts ) {
		return shortcode_atts( $this->defaults(), $atts, $this->tag() );
	}

	/**
	 *
	 */
	public function _template() {}

	/**
	 * @return string
	 */
	public function _template_styles() {
		return '';
	}

	/**
	 *
	 */
	public function register_shortcode_ui() {

		$shortcode_ui_args = array(
			'label'         => $this->label(),
			'listItemImage' => $this->icon(),
			'post_type'     => $this->post_types(),
			/*
			 * Register UI for the "inner content" of the shortcode. Optional.
			 * If no UI is registered for the inner content, then any inner content
			 * data present will be backed up during editing.
			 */
			'attrs'         => array(),
		);


		if ( $this->has_content ) {
			$shortcode_ui_args['inner_content'] = $this->inner_content_labels();
		}

		if ( count( $this->fields() ) ) {
			foreach ( $this->get_all_fields() as $section => $fields ) {
				foreach ( $fields as $id => $field ) {

					if ( '_inner_content' == $id ) {
						continue;
					}


					//text, checkbox, textarea, radio, select, email, url, number, date, attachment, color, post_select
					switch ( $field['type'] ) {
						case 'selectox':
							$shortcode_ui_args['attrs'][] = array(
								'label'   => esc_html( $field['label'] ),
								'attr'    => $id,
								'type'    => 'select',
								'options' => $field['options'],
							);
							break;

						case 'postselect':
						case 'objectselect':
							if ( empty( $field['post_type'] ) ) {
								break;
							}
							$shortcode_ui_args['attrs'][] = array(
								'label'   => esc_html( $field['label'] ),
								'attr'    => $id,
								'type'    => 'post_select',
								'options' => isset( $field['options'] ) ? $field['options'] : array(),
								'query'   => array( 'post_type' => $field['post_type'] ),
							);
							break;

						case 'taxonomyselect':
							break;

						case 'text';
						default:
							$shortcode_ui_args['attrs'][] = array(
								'label' => $field['label'],
								'attr'  => $id,
								'type'  => 'text',
								'value' => ! empty( $field['std'] ) ? $field['std'] : '',
								//'encode' => true,
								'meta'  => array(
									'placeholder' => $field['placeholder'],
								),
							);
							break;
					}

				}
			}
		}


		/**
		 * Register UI for your shortcode
		 *
		 * @param string $shortcode_tag
		 * @param array $ui_args
		 */
		if ( function_exists( 'shortcode_ui_register_for_shortcode' ) ) {
			shortcode_ui_register_for_shortcode( $this->tag(), $shortcode_ui_args );
		}
	}

}
