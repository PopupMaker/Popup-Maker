<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Shortcode
 *
 * This is a base class for all popup maker & extension shortcodes.
 */
class PUM_Shortcode_CallToAction extends PUM_Shortcode {

	/**
	 * Shortcode API Version.
	 *
	 * @var int
	 */
	public $version = 2;

	/**
	 * Has inner content.
	 *
	 * @var bool
	 */
	public $has_content = true;

	/**
	 * Enable ajax rendering.
	 *
	 * @var boolean
	 */
	public $ajax_rendering = true;

	/**
	 * Instance of CallToActions library.
	 *
	 * @var PUM_CallToActions
	 */
	private $calltoactions;

	/**
	 * Constructor override.
	 */
	public function __construct() {
		parent::__construct();
		$this->calltoactions = PUM_CallToActions::instance();
		// add_filter( 'pum_shortcode_ui_vars', [ $this, 'shortcode_ui_vars' ] );
	}

	/**
	 * The shortcode tag.
	 */
	public function tag() {
		return 'pum_cta';
	}

	/**
	 * Shortcode label.
	 *
	 * @return string
	 */
	public function label() {
		return __( 'Popup Call to Action', 'popup-maker' );
	}

	/**
	 * Shortcode description.
	 *
	 * @return string
	 */
	public function description() {
		return __( 'Inserts a call to action.', 'popup-maker' );
	}

	/**
	 * Labels for the inner content field.
	 *
	 * @return array
	 */
	public function inner_content_labels() {
		return [
			'label'       => __( 'Text', 'popup-maker' ),
			'description' => __( 'Button or link text.', 'popup-maker' ),
		];
	}

	/**
	 * Post types this shortcode is enabled for.
	 *
	 * @return array
	 */
	public function post_types() {
		return [ 'popup' ];
	}

	/**
	 * Array of fields for the CTA shortcode.
	 *
	 * @return array
	 */
	public function fields() {
		// TODO This might best be handled as block textarea or shortcode inner content.
		// CONSIDER renaming this to inner_content to replace the built in.

		$fields = [
			'general'    => [
				'main' => [
					'cta_type' => [
						'type'     => 'select',
						'label'    => __( 'Type of CTA', 'popup-maker' ),
						'options'  => $this->calltoactions->get_select_list(),
						'std'      => 'link',
						'priority' => 0,
					],

				],
			],
			'appearance' => [
				'main' => [],
			],
			'extra'      => [
				'main' => [],
			],
		];

		/**
		 * Fields for call to actions are organized only by one grouping to allow support for the block editor sidebar.
		 *
		 * Because of this we need to remap these to the proper subtabs in our larger fields array.
		 *
		 * Further we are also adding field dependencies to each field so they only show for their appropriate types.
		 */
		foreach ( $this->calltoactions->get_all() as $key => $callToAction ) {
			/**
			 * Instance of a CallToAction object.
			 *
			 *  @var PUM_Abstract_CallToAction $callToAction
			 */
			$base_fields = $callToAction->get_base_fields();

			foreach ( $base_fields as $tab => $tab_fields ) {
				$fields[ $tab ]['main'] = array_merge( $fields[ $tab ]['main'], $tab_fields );
			}

			$cta_fields = $callToAction->get_fields();

			foreach ( $cta_fields as $tab => $tab_fields ) {

				foreach ( $tab_fields as $field_id => $field ) {
					// Set the fields dependencies to include the cta_type matching.
					if ( ! is_array( $field['dependencies']['cta_type'] ) ) {
						$field['dependencies']['cta_type'] = [];
					}

					// Set the fields dependencies to include the cta_type matching.
					$field['dependencies']['cta_type'][] = $key;

					// Add the field to the correct tab in the fields array.
					$fields[ $tab ]['main'][ $field_id ] = $field;
				}
			}
		}

		return $fields;
	}

	/**
	 * Shortcode handler.
	 *
	 * This calls our chosen CTA's render method.
	 *
	 * @param  array  $atts    Shortcode attributes.
	 * @param  string $content Shortcode content.
	 *
	 * @return string
	 */
	public function handler( $atts, $content = null ) {
		$cta_type = $atts['cta_type'];

		$callToAction = $this->calltoactions->get( $cta_type );

		return $callToAction ? $callToAction->render( $atts, $content ) : '';
	}

}
