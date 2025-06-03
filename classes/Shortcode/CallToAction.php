<?php
/**
 * Call To Action shortcode class.
 *
 * @since       X.X.X
 * @package     PopupMaker
 * @copyright   Copyright (c) 2024, Code Atlantic LLC
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PUM_Shortcode_CallToAction
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
		return __( 'CTA Button', 'popup-maker' );
	}

	/**
	 * Shortcode description.
	 *
	 * @return string
	 */
	public function description() {
		return __( 'Insert a call to action to let users convert to a specific action.', 'popup-maker' );
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

		$ctas = [];

		// If we are in a post type editor fill the ctas array with the available CTA's.
		if ( is_admin() && pum_is_popup_editor() ) {
			// $ctas = \PopupMaker\plugin( 'ctas' )->generate_selectlist_query();
		}

		// TODO This might best be handled as block textarea or shortcode inner content.
		// CONSIDER renaming this to inner_content to replace the built in.

		$fields = [
			'general'    => [
				'main' => [
					'id'          => [
						'type'      => 'postselect',
						'post_type' => 'pum_cta',
						'label'     => __( 'Which type of CTA would you like to use?', 'popup-maker' ),
						// 'options'   => array_merge(
						// [
						// [
						// 'value' => '',
						// 'label' => __( 'Select a Call to Action', 'popup-maker' ),
						// ],
						// ],
						// $ctas
						// ),
						'std'       => '',
						'priority'  => 0,
					],
					'link_target' => [
						'type'     => 'radio',
						'label'    => __( 'Open in a new tab?', 'popup-maker' ),
						'options'  => [
							'_self'  => __( 'No', 'popup-maker' ),
							'_blank' => __( 'Yes', 'popup-maker' ),
						],
						'std'      => '_self',
						'priority' => 0.1,
					],
					'text'        => [
						'type'     => 'text',
						'label'    => __( 'Enter text for your call to action.', 'popup-maker' ),
						'std'      => __( 'Learn more', 'popup-maker' ),
						'priority' => 0.2,
					],
				],
			],
			'appearance' => [
				'main' => [
					'style'              => [
						'type'     => 'radio',
						'label'    => __( 'Choose a style.', 'popup-maker' ),
						'options'  => [
							'fill'      => __( 'Fill', 'default' ),
							'outline'   => __( 'Outline', 'default' ),
							'text-only' => __( 'Text Only', 'popup-maker' ),
						],
						'std'      => 'fill',
						'priority' => 1.1,
					],
					'align'              => [
						'type'     => 'select',
						'label'    => __( 'Alignment', 'popup-maker' ),
						'options'  => [
							'left'   => __( 'Left', 'popup-maker' ),
							'center' => __( 'Center', 'popup-maker' ),
							'right'  => __( 'Right', 'popup-maker' ),
							'full'   => __( 'Full', 'popup-maker' ),
						],
						'priority' => 1.2,
					],
					'extra_link_classes' => [
						'type'     => 'text',
						'label'    => __( 'Additional CSS classes.', 'popup-maker' ),
						'std'      => '',
						'priority' => 1.3,
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
		/*
		foreach ( $this->calltoactions->get_all() as $key => $callToAction ) {
			/**
			 * Instance of a CallToAction object.
			 *
			 *  @var PUM_Abstract_CallToAction $callToAction
			 */

			/*
			foreach ( $callToAction->get_fields() as $tab => $tab_fields ) {
				foreach ( $tab_fields as $field_id => $field ) {
					// Set the fields dependencies to include the type matching.
					if ( ! isset( $field['dependencies']['type'] ) || ! is_array( $field['dependencies']['type'] ) ) {
						$field['dependencies']['type'] = [];
					}

					// Set the fields dependencies to include the type matching.
					$field['dependencies']['type'][] = $key;

					// Add the field to the correct tab in the fields array.
					$fields[ $tab ]['main'][ $field_id ] = $field;
				}
			}
		}

		*/

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

		$cta_id = $atts['id'];
		$target = $atts['link_target'];

		$text  = ! empty( $atts['text'] ) ? $atts['text'] : $content;
		$style = $atts['style'];
		$align = $atts['align'];

		$cta = \PopupMaker\get_cta_by_id( $cta_id );

		if ( ! $cta ) {
			return 'Missing Call To Action';
		}

		$type = $cta->get_setting( 'type', 'link' );
		$uuid = $cta->get_uuid();

		// Get the current popup id.
		$popup_id = pum_get_popup_id();

		$url = $cta->generate_url('', [
			'pid' => $popup_id ? $popup_id : null,
		]);

		$wrapper_classes = [
			'pum-cta-wrapper',
			'align' . $align,
			'is-style-' . $style,
			'text-only' === $atts['style'] ? 'pum-cta--button' : null,
		];

		$cta_content = sprintf(
			"<a href='%s' class='pum-cta %s' target='%s' data-cta-type='%s' rel='noreferrer noopener'>%s</a>",
			esc_url_raw( $url ),
			esc_attr( $atts['extra_link_classes'] ),
			esc_attr( $target ),
			esc_attr( $type ),
			esc_html( $text )
		);

		ob_start();
		?>

		<div class="<?php echo esc_attr( implode( ' ', array_filter( $wrapper_classes ) ) ); ?>">
			<?php
			echo wp_kses(
				$cta_content,
				[
					'a' => [
						'href'          => true,
						'class'         => true,
						'target'        => true,
						'rel'           => true,
						'data-cta-type' => true,
					],
				]
			);
			?>
		</div>

		<?php
		return ob_get_clean();
	}
}
