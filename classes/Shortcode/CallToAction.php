<?php
/**
 * Call To Action shortcode class.
 *
 * @since       1.14
 * @package     PUM
 * @copyright   Copyright (c) 2020, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

		// TODO This might best be handled as block textarea or shortcode inner content.
		// CONSIDER renaming this to inner_content to replace the built in.

		$fields = [
			'general'    => [
				'main' => [
					'type' => [
						'type'     => 'select',
						'label'    => __( 'Type of CTA', 'popup-maker' ),
						'options'  => $this->calltoactions->get_select_list(),
						'std'      => 'link',
						'priority' => 0,
					],
					'text' => [
						'type'     => 'text',
						'label'    => __( 'Enter text for your call to action.', 'popup-maker' ),
						'std'      => __( 'Learn more', 'popup-maker' ),
						'priority' => 0.1,
					],
				],
			],
			'appearance' => [
				'main' => [
					'style' => [
						'type'     => 'radio',
						'label'    => __( 'Choose a style.', 'popup-maker' ),
						'options'  => [
							'fill'      => __( 'Fill' ),
							'outline'   => __( 'Outline' ),
							'text-only' => __( 'Text Only', 'popup-maker' ),
						],
						'std'      => 'button',
						'priority' => 1.1,
					],
					// 'element_classes' => [
					// 'type'     => 'text',
					// 'label'    => __( 'Additional CSS classes.', 'popup-maker' ),
					// 'std'      => '',
					// 'priority' => 1.2,
					// ],
					'align' => [
						'type'     => 'select',
						'label'    => __( 'Alignment', 'popup-maker' ),
						'options'  => [
							'left'   => __( 'Left', 'popup-maker' ),
							'center' => __( 'Center', 'popup-maker' ),
							'right'  => __( 'Right', 'popup-maker' ),
							'full'   => __( 'Full', 'popup-maker' ),
						],
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
		foreach ( $this->calltoactions->get_all() as $key => $callToAction ) {
			/**
			 * Instance of a CallToAction object.
			 *
			 *  @var PUM_Abstract_CallToAction $callToAction
			 */
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

		$type    = $atts['type'];
		$url     = $atts['url'];
		$target  = $atts['linkTarget'] ? '_blank' : '_self';
		$text    = ! empty( $atts['text'] ) ? $atts['text'] : $content;
		$uuid    = PUM_Site_CallToActions::generate_cta_uuid( pum_get_popup_id(), $type, $text );
		$classes = array_merge(
			[
				'pum-cta',
				'pum-cta--link',
				'button' === $atts['element_type'] ? 'pum-cta--button' : null,

			],
			explode( ',', $atts['element_classes'] )
		);

		/**
		 * If url is not a hash url, use redirect to accurately track conversions.
		 *
		 * Note this does not apply if links are #hash based or open in a new window.
		 * In those cases JavaScript async methods of tracking will be used.
		 */
		if ( $url && ! $atts['linkTarget'] && strpos( $url, '#' ) !== 0 ) {
			$url = add_query_arg(
				[
					'pid'  => pum_get_popup_id(),
					'uuid' => $uuid,
				]
			);
		}

		$callToAction = $this->calltoactions->get( $type );

		if ( ! method_exists( $callToAction, 'custom_renderer' ) ) {
			$cta_content = sprintf(
				"<a href='%s' class='%s' target='%s' data-pum-action='%s' rel='nofollow'>%s</a>",
				esc_url_raw( $url ),
				implode( ' ', array_filter( $classes ) ),
				$target,
				$type,
				$text
			);
		} else {
			$cta_output = $callToAction ? $callToAction->render( $atts ) : '';
		}

		ob_start();
		?>

		<div style="text-align:<?php echo esc_attr( $atts['alignment'] ); ?>;" class="pum-cta-wrapper align-<?php echo esc_attr( $atts['alignment'] ); ?>">
			<?php
			/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
			echo $cta_content;
			?>
		</div>

		<?php
		return ob_get_clean();

	}
}
