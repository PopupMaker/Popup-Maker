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
	public $has_content = false;

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

	// /**
	// * Labels for the inner content field.
	// *
	// * @return array
	// */
	// public function inner_content_labels() {
	// return [
	// 'label'       => __( 'Text', 'popup-maker' ),
	// 'description' => __( 'Button or link text.', 'popup-maker' ),
	// ];
	// }

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
					'cta_text' => [
						'type'     => 'text',
						'label'    => __( 'Enter text for your call to action.', 'popup-maker' ),
						'std'      => __( 'Learn more', 'popup-maker' ),
						'priority' => 0.1,
					],
				],
			],
			'appearance' => [
				'main' => [
					'element_type'    => [
						'type'         => 'radio',
						'label'        => __( 'Choose how this link appears.', 'popup-maker' ),
						'options'      => [
							'text'   => __( 'Text Link', 'popup-maker' ),
							'button' => __( 'Button', 'popup-maker' ),
						],
						'std'          => 'button',
						'priority'     => 1.1,
					],
					'element_classes' => [
						'type'         => 'text',
						'label'        => __( 'Additional CSS classes.', 'popup-maker' ),
						'std'          => '',
						'priority'     => 1.2,
					],
					'alignment'       => [
						'type'    => 'select',
						'label'   => __( 'Alignment', 'popup-maker' ),
						'options' => [
							'left'   => __( 'Left', 'popup-maker' ),
							'right'  => __( 'Right', 'popup-maker' ),
							'center' => __( 'Center', 'popup-maker' ),
						],
						'priority'     => 1.3,
					],
				],
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
			foreach ( $callToAction->get_fields() as $tab => $tab_fields ) {

				foreach ( $tab_fields as $field_id => $field ) {
					// Set the fields dependencies to include the cta_type matching.
					if ( ! isset( $field['dependencies']['cta_type'] ) || ! is_array( $field['dependencies']['cta_type'] ) ) {
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
		$atts = $this->shortcode_atts( $atts );

		$cta_type = $atts['cta_type'];

		$callToAction = $this->calltoactions->get( $cta_type );

		$atts['cta_text'] = ! empty( $atts['cta_text'] ) ? $atts['cta_text'] : $content;

		$cta_output = $callToAction ? $callToAction->render( $atts ) : '';

		ob_start();
		?>

		<div style="text-align:<?php echo esc_attr( $atts['alignment'] ); ?>;" class="pum-cta-wrapper align-<?php echo esc_attr( $atts['alignment'] ); ?>">
			<?php
			/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
			echo $cta_output;
			?>
		</div>

		<?php
		return ob_get_clean();
	}
}