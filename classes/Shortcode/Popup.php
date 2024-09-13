<?php
/**
 * Shortcode for Popup
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Shortcode_Popup
 *
 * Registers the popup_close shortcode.
 */
class PUM_Shortcode_Popup extends PUM_Shortcode {

	public $version = 2;

	public $has_content = true;

	public $inner_content_priority = 15;

	/**
	 * The shortcode tag.
	 */
	public function tag() {
		return 'popup';
	}

	public function label() {
		return __( 'Popup', 'popup-maker' );
	}

	public function description() {
		return __( 'Insert a popup inline rather. Great for simple popups used for supporting content.', 'popup-maker' );
	}

	public function inner_content_labels() {
		return [
			'label'       => __( 'Content', 'popup-maker' ),
			'description' => __( 'Can contain other shortcodes, images, text or html content.', 'popup-maker' ),
		];
	}

	public function post_types() {
		return [];
	}

	/**
	 * @return array
	 */
	public function tabs() {
		return [
			'general'   => __( 'General', 'popup-maker' ),
			'display'   => __( 'Display', 'popup-maker' ),
			'position'  => __( 'Position', 'popup-maker' ),
			'animation' => __( 'Animation', 'popup-maker' ),
			'close'     => __( 'Close', 'popup-maker' ),
		];
	}

	/**
	 * @return array
	 */
	public function subtabs() {
		return apply_filters(
			'pum_sub_form_shortcode_subtabs',
			[
				'general'   => [
					'main' => __( 'General', 'popup-maker' ),
				],
				'display'   => [
					'main' => __( 'Display', 'popup-maker' ),
				],
				'position'  => [
					'main' => __( 'Position', 'popup-maker' ),
				],
				'animation' => [
					'main' => __( 'Animation', 'popup-maker' ),
				],
				'close'     => [
					'main' => __( 'Close', 'popup-maker' ),
				],
			]
		);
	}

	public function fields() {
		return [
			'general'   => [
				'main' => [
					'id'    => [
						'label'       => __( 'Unique Popup ID', 'popup-maker' ),
						'placeholder' => __( '`offer`, `more-info`', 'popup-maker' ),
						'desc'        => __( 'Used in popup triggers to target this popup', 'popup-maker' ),
						'priority'    => 5,
						'required'    => true,
					],
					'title' => [
						'label'       => __( 'Popup Title', 'popup-maker' ),
						'placeholder' => __( 'Enter popup title text,', 'popup-maker' ),
						'desc'        => __( 'This will be displayed above the content. Leave it empty to disable it.', 'popup-maker' ),
						'priority'    => 10,
					],
				],
			],
			'display'   => [
				'main' => [
					'theme_id'         => [
						'type'        => 'select',
						'label'       => __( 'Popup Theme', 'popup-maker' ),
						'placeholder' => __( 'Choose a theme,', 'popup-maker' ),
						'desc'        => __( 'Choose which popup theme will be used.', 'popup-maker' ),
						'std'         => pum_get_default_theme_id(),
						'select2'     => true,
						'options'     => pum_is_settings_page() ? PUM_Helpers::popup_theme_selectlist() : null,
						'required'    => true,
						'priority'    => 5,
					],
					'overlay_disabled' => [
						'label'       => __( 'Disable Overlay', 'popup-maker' ),
						'description' => __( 'Checking this will disable and hide the overlay for this popup.', 'popup-maker' ),
						'type'        => 'checkbox',
						'std'         => false,
						'priority'    => 10,
					],
					'size'             => [
						'label'       => __( 'Size', 'popup-maker' ),
						'description' => __( 'Select the size of the popup.', 'popup-maker' ),
						'type'        => 'select',
						'std'         => 'small',
						'options'     => array_flip( apply_filters( 'popmake_popup_display_size_options', [] ) ),
						'priority'    => 15,
					],
					'width'            => [
						'label'    => __( 'Width', 'popup-maker' ),
						'priority' => 20,
					],
					'width_unit'       => [
						'label'    => __( 'Width Unit', 'popup-maker' ),
						'type'     => 'select',
						'std'      => 'px',
						'options'  => array_flip( apply_filters( 'popmake_size_unit_options', [] ) ),
						'priority' => 25,
					],
					'height'           => [
						'label'    => __( 'Height', 'popup-maker' ),
						'priority' => 30,
					],
					'height_unit'      => [
						'label'    => __( 'Height Unit', 'popup-maker' ),
						'type'     => 'select',
						'std'      => 'px',
						'options'  => array_flip( apply_filters( 'popmake_size_unit_options', [] ) ),
						'priority' => 35,
					],
				],
			],
			'position'  => [
				'main' => [
					'location'        => [
						'label'       => __( 'Location', 'popup-maker' ),
						'description' => __( 'Choose where the popup will be displayed.', 'popup-maker' ),
						'type'        => 'select',
						'std'         => 'center top',
						'priority'    => 4,
						'options'     => array_flip( apply_filters( 'popmake_popup_display_location_options', [] ) ),
					],
					'position_top'    => [
						'label'       => __( 'Top', 'popup-maker' ),
						'description' => sprintf(
							/* translators: 1. Screen Edge: top, bottom. */
							_x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ),
							strtolower( __( 'Top', 'popup-maker' ) )
						),
						'type'        => 'rangeslider',
						'std'         => 100,
						'priority'    => 10,
						'step'        => 1,
						'min'         => 0,
						'max'         => 500,
						'unit'        => 'px',
					],
					'position_bottom' => [
						'label'       => __( 'Bottom', 'popup-maker' ),
						'description' => sprintf(
							/* translators: 1. Screen Edge: top, bottom. */
							_x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ),
							strtolower( __( 'Bottom', 'popup-maker' ) )
						),
						'type'        => 'rangeslider',
						'std'         => 0,
						'priority'    => 10,
						'step'        => 1,
						'min'         => 0,
						'max'         => 500,
						'unit'        => 'px',
					],
					'position_left'   => [
						'label'       => __( 'Left', 'popup-maker' ),
						'description' => sprintf(
							/* translators: 1. Screen Edge: top, bottom. */
							_x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ),
							strtolower( __( 'Left', 'popup-maker' ) )
						),
						'type'        => 'rangeslider',
						'std'         => 0,
						'priority'    => 10,
						'step'        => 1,
						'min'         => 0,
						'max'         => 500,
						'unit'        => 'px',
					],
					'position_right'  => [
						'label'       => __( 'Right', 'popup-maker' ),
						'description' => sprintf(
							/* translators: 1. Screen Edge: top, bottom. */
							_x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ),
							strtolower( __( 'Right', 'popup-maker' ) )
						),
						'type'        => 'rangeslider',
						'std'         => 0,
						'priority'    => 10,
						'step'        => 1,
						'min'         => 0,
						'max'         => 500,
						'unit'        => 'px',
					],
				],
			],
			'animation' => [
				'main' => [
					'animation_type'   => [
						'label'       => __( 'Animation Type', 'popup-maker' ),
						'description' => __( 'Select an animation type for your popup.', 'popup-maker' ),
						'type'        => 'select',
						'std'         => 'fade',
						'priority'    => 5,
						'options'     => array_flip( apply_filters( 'popmake_popup_display_animation_type_options', [] ) ),
					],
					'animation_speed'  => [
						'label'       => __( 'Animation Speed', 'popup-maker' ),
						'description' => __( 'Set the animation speed for the popup.', 'popup-maker' ),
						'type'        => 'rangeslider',
						'std'         => 350,
						'priority'    => 10,
						'step'        => 10,
						'min'         => 50,
						'max'         => 1000,
						'unit'        => __( 'ms', 'popup-maker' ),
					],
					'animation_origin' => [
						'label'       => __( 'Animation Origin', 'popup-maker' ),
						'description' => __( 'Choose where the animation will begin.', 'popup-maker' ),
						'type'        => 'select',
						'std'         => 'center top',
						'priority'    => 15,
						'options'     => array_flip( apply_filters( 'popmake_popup_display_animation_origin_options', [] ) ),
					],
				],
			],
			'close'     => [
				'main' => [
					'overlay_click' => [
						'label'       => __( 'Click Overlay to Close', 'popup-maker' ),
						'description' => __( 'Checking this will cause popup to close when user clicks on overlay.', 'popup-maker' ),
						'type'        => 'checkbox',
						'std'         => false,
						'priority'    => 5,
					],
				],
			],
		];
	}

	/**
	 * Shortcode handler
	 *
	 * @param  array  $atts    shortcode attributes
	 * @param  string $content shortcode content
	 *
	 * @return string
	 */
	public function handler( $atts, $content = null ) {
		global $popup;

		$atts = shortcode_atts(
			apply_filters(
				'pum_popup_shortcode_default_atts',
				[

					'id'               => '',
					'title'            => '',

					'theme_id'         => null,
					'theme'            => null,
					'overlay_disabled' => 0,
					'size'             => 'small',
					'width'            => '',
					'width_unit'       => 'px',
					'height'           => '',
					'height_unit'      => 'px',

					'location'         => 'center top',
					'position_top'     => 100,
					'position_left'    => 0,
					'position_bottom'  => 0,
					'position_right'   => 0,
					'position_fixed'   => 0,

					'animation_type'   => 'fade',
					'animation_speed'  => 1000,
					'animation_origin' => 'top',

					'overlay_click'    => 0,
					'esc_press'        => 1,
				]
			),
			$atts,
			'popup'
		);

		// We need to fake a popup using the PUM_Popup data model.
		$post_id              = wp_rand( -99999, - 1 ); // negative ID, to avoid clash with a valid post
		$post                 = new stdClass();
		$post->ID             = $post_id;
		$post->post_author    = 1;
		$post->post_date      = current_time( 'mysql' );
		$post->post_date_gmt  = current_time( 'mysql', 1 );
		$post->post_title     = $atts['title'];
		$post->post_content   = $content;
		$post->post_status    = 'publish';
		$post->comment_status = 'closed';
		$post->ping_status    = 'closed';
		$post->post_name      = $atts['id']; // append random number to avoid clash
		$post->post_type      = 'popup';
		$post->filter         = 'raw'; // important!
		$post->data_version   = 3;
		$post->mock           = true;

		// Convert to WP_Post object
		$wp_post = new WP_Post( $post );

		// Add the fake post to the cache
		wp_cache_add( $post_id, $wp_post, 'posts' );

		$popup = new PUM_Model_Popup( $wp_post );

		// Get Theme ID
		if ( ! $atts['theme_id'] ) {
			$atts['theme_id'] = $atts['theme'] ? $atts['theme'] : pum_get_default_theme_id();
		}

		$popup->title    = $atts['title'];
		$popup->settings = array_merge(
			PUM_Admin_Popups::defaults(),
			[
				'disable_analytics'      => true,
				'theme_id'               => $atts['theme_id'],
				'size'                   => $atts['size'],
				'overlay_disabled'       => $atts['overlay_disabled'],
				'custom_width'           => $atts['width'],
				'custom_width_unit'      => $atts['width_unit'],
				'custom_height'          => $atts['height'],
				'custom_height_unit'     => $atts['height_unit'],
				'custom_height_auto'     => $atts['width'] > 0 ? 0 : 1,
				'location'               => $atts['location'],
				'position_top'           => $atts['position_top'],
				'position_left'          => $atts['position_left'],
				'position_bottom'        => $atts['position_bottom'],
				'position_right'         => $atts['position_right'],
				'position_fixed'         => $atts['position_fixed'],
				'animation_type'         => $atts['animation_type'],
				'animation_speed'        => $atts['animation_speed'],
				'animation_origin'       => $atts['animation_origin'],
				'close_on_overlay_click' => $atts['overlay_click'],
				'close_on_esc_press'     => $atts['esc_press'],
				'triggers'               => [
					[
						'type'     => 'click_open',
						'settings' => [
							'extra_selectors' => '#popmake-' . $atts['id'],
						],
					],
				],
			]
		);

		$current_global_popup = pum()->current_popup;

		pum()->current_popup = $popup;

		$return = pum_get_template_part( 'popup' );

		// Small hack to move popup to body.
		$return .= "<script type='text/javascript' id='pum-move-popup-" . $post_id . "'>jQuery(document).ready(function () {
				 jQuery('#pum-" . $post_id . "').appendTo('body');
				 window.pum_vars.popups[ 'pum-" . $popup->ID . "' ] = " . PUM_Utils_Array::safe_json_encode( $popup->get_public_settings() ) . ";
				 window.pum_popups[ 'pum-" . $popup->ID . "' ] = " . PUM_Utils_Array::safe_json_encode( $popup->get_public_settings() ) . ";
				 jQuery('#pum-move-popup-" . $post_id . "').remove();
		});</script>";

		pum()->current_popup = $current_global_popup;

		return $return;
	}

	public function template() {
		?>
		<p class="pum-sub-form-desc">
			<?php esc_html_e( 'Popup', 'popup-maker' ); ?>: ID "{{attrs.id}}"
		</p>
		<?php
	}
}
