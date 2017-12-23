<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Shortcode
 *
 * This is a base class for all popup maker & extension shortcodes.
 */
abstract class PUM_Shortcode {

	/**
	 * Per instance version for compatibility fixes.
	 *
	 * @var int
	 */
	public $version = 1;

	/**
	 * Used to force ajax rendering of the shortcode.
	 *
	 * @var bool
	 */
	public $ajax_rendering = false;

	/**
	 * Shortcode supports inner content.
	 *
	 * @var bool
	 */
	public $has_content = false;

	/**
	 * Section/Tab where the content editor will be placed.
	 *
	 * @var string
	 */
	public $inner_content_section = 'general';

	/**
	 * Field priority of the content editor.
	 *
	 * @var int
	 */
	public $inner_content_priority = 5;

	/**
	 * @deprecated 1.7.0
	 * @var string
	 */
	public $field_prefix = 'attrs';

	/**
	 * @deprecated 1.7.0
	 * @var string
	 */
	public $field_name_format = '{$prefix}[{$field}]';

	/**
	 * Current version used for compatibility fixes.
	 *
	 * @var int
	 */
	public $current_version = 2;

	/**
	 * Class constructor will set the needed filter and action hooks
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		if ( ! did_action( 'init' ) ) {
			add_action( 'init', array( $this, 'register' ) );
		} elseif ( ! did_action( 'admin_head' ) && current_action() != 'init' ) {
			add_action( 'admin_head', array( $this, 'register' ) );
		} else {
			$this->register();
		}
	}

	/**
	 * @return mixed
	 */
	public static function init() {
		$class = get_called_class();
		return new $class;
	}

	/**
	 * Register this shortcode with Shortcode UI & Shortcake.
	 */
	public function register() {
		add_shortcode( $this->tag(), array( $this, 'handler' ) );
		add_action( 'print_media_templates', array( $this, 'render_template' ) );
		add_action( 'register_shortcode_ui', array( $this, 'register_shortcode_ui' ) );

		PUM_Shortcodes::instance()->add_shortcode( $this );
	}

	/**
	 * The shortcode tag.
	 */
	abstract public function tag();

	/**
	 * Shortcode handler
	 *
	 * @param  array $atts shortcode attributes
	 * @param  string $content shortcode content
	 *
	 * @return string
	 */
	abstract public function handler( $atts, $content = null );

	/**
	 * Sections.
	 *
	 * @deprecated 1.7.0 Use $this->tabs() instead.
	 *
	 * @todo Once all shortcodes are v2+ remove $this->sections()
	 *
	 * @return array
	 */
	public function sections() {
		return array(
			'general' => __( 'General', 'popup-maker' ),
			'options' => __( 'Options', 'popup-maker' ),
		);
	}

	/**
	 * Returns a list of tabs for this shortcodes editor.
	 *
	 * @return array
	 */
	public function tabs() {
		return array(
			'general' => __( 'General', 'popup-maker' ),
			'options' => __( 'Options', 'popup-maker' ),
		);
	}

	public function _tabs() {
		$tabs = $this->version < 2 ? $this->sections() : $this->tabs();

		return apply_filters( 'pum_shortcode_tabs', $tabs, $this->tag() );
	}

	/**
	 * Gets preprocessed shortcode attributes.
	 *
	 * @param $atts
	 *
	 * @return array
	 */
	public function shortcode_atts( $atts ) {
		return shortcode_atts( $this->defaults(), $atts, $this->tag() );
	}

	/**
	 * Array of default attribute values.
	 *
	 * @todo Convert this to pull from the std of $this->fields.
	 *
	 * @return array
	 */
	public function defaults() {
		return array();
	}

	/**
	 * Render the template based on shortcode classes methods.
	 */
	public function render_template() {
		$template = $this->template();

		if ( $template ) {
			$template = $this->get_style_block() . $template;
			echo '<script type="text/html" id="tmpl-pum-shortcode-view-' . $this->tag() . '">' . $template . '</script>';
		} else {
			/** @deprecated, here in case shortcode doesn't yet have the new $this->template() method. */
			$this->_template();
		}
	}

	/**
	 * Returns the inner contents of the JS templates.
	 *
	 * @todo Once all shortcodes have been updated to use template over _template make this abstract.
	 *
	 * @return bool|string
	 */
	public function template() {
		return false;
	}

	/**
	 * Render the template based on shortcode classes methods.
	 */
	public function get_style_block() {
		$template_styles = $this->template_styles();

		if ( ! $template_styles ) {
			/** @deprecated, here in case shortcode doesn't yet have the new $this->template() method. */
			$template_styles = $this->_template_styles();
		}

		return '<style>' . $template_styles . '</style>';
	}

	/**
	 * @deprecated 1.7.0 Use template() instead.
	 */
	public function _template() {
	}

	/**
	 * Returns the styles for inner contents of the JS templates.
	 *
	 * @todo Once all shortcodes have been updated to use template over _template make this abstract.
	 *
	 * @return bool|string
	 */
	public function template_styles() {
		return false;
	}

	/**
	 * @deprecated 1.7.0 use template_styles() instead.
	 *
	 * @return string
	 */
	public function _template_styles() {
		return '';
	}

	/**
	 * Register this shortcode in shortcake ui.
	 */
	public function register_shortcode_ui() {

		$shortcode_ui_args = array(
			'label'         => $this->label(),
			'listItemImage' => $this->icon(),
			'post_type'     => $this->post_types(),
			'attrs'         => array(),
		);

		/**
		 * Register UI for the "inner content" of the shortcode. Optional.
		 * If no UI is registered for the inner content, then any inner content
		 * data present will be backed up during editing.
		 */
		if ( $this->has_content ) {
			$shortcode_ui_args['inner_content'] = $this->inner_content_labels();
		}

		$fields = $this->_fields();

		if ( count( $fields ) ) {

			foreach ( $fields as $section_id => $section_fields ) {
				foreach ( $section_fields as $field_id => $field ) {

					if ( '_inner_content' == $field_id ) {
						continue;
					}

					//text, checkbox, textarea, radio, select, email, url, number, date, attachment, color, post_select
					switch ( $field['type'] ) {
						case 'select':
							$shortcode_ui_args['attrs'][] = array(
								'label'   => esc_html( $field['label'] ),
								'attr'    => $field_id,
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
								'attr'    => $field_id,
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
								'attr'  => $field_id,
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

	/**
	 * How the shortcode should be labeled in the UI. Required argument.
	 *
	 * @return string
	 */
	abstract public function label();

	/**
	 * Include an icon with your shortcode. Optional.
	 * Use a dashicon, or full URL to image.
	 *
	 * Only used by Shortcake
	 *
	 * @return string
	 */
	public function icon() {
		return 'dashicons-editor-quote';
	}

	/**
	 * Limit this shortcode UI to specific post_types. Optional.
	 *
	 * @return array
	 */
	public function post_types() {
		return array( 'post', 'page', 'popup' );
	}

	/**
	 * @todo Remove the inner function calls and just have this function define them directly.
	 *
	 * @return array
	 */
	public function inner_content_labels() {
		return array(
			'label'       => $this->label(),
			'description' => $this->description(),
		);
	}

	/**
	 * Used internally to merge the  inner content field with existing fields.
	 *
	 * @return array
	 */
	public function _fields() {
		$fields = $this->fields();

		if ( $this->has_content ) {
			$inner_content_labels = $this->inner_content_labels();

			$fields[ $this->inner_content_section ]['_inner_content'] = array(
				'label'    => $inner_content_labels['label'],
				'desc'     => $inner_content_labels['description'],
				'section'  => $this->inner_content_section,
				'type'     => 'textarea',
				'priority' => $this->inner_content_priority,
			);
		}

		foreach ( $fields as $section_id => $section_fields ) {
			foreach ( $section_fields as $field_id => $field ) {

				/**
				 * Apply field compatibility fixes for shortcodes still on v1.
				 */
				if ( $this->version < 2 ) {
					if ( ! empty( $field['type'] ) && in_array( $field['type'], array( 'select', 'postselect', 'radio', 'multicheck') ) ) {
						$fields[ $section_id ][ $field_id ]['options'] = ! empty( $field['options'] ) ? array_flip( $field['options'] ) : array();
					}
				}

				$fields[ $section_id ][ $field_id ]['id'] = $field_id;
				$fields[ $section_id ][ $field_id ]['name'] = 'attrs[' . $field_id . ']';
			}
		}

		return $fields;
	}

	/**
	 * @return string
	 */
	abstract public function description();

	/**
	 * Array of fields by tab.
	 *
	 * @return array
	 */
	abstract public function fields();

}
