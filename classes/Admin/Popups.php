<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Admin_Popups
 */
class PUM_Admin_Popups {

	/**
	 * Hook the initialize method to the WP init action.
	 */
	public static function init() {
		// Add popup title field.
		add_action( 'edit_form_advanced', array( __CLASS__, 'title_meta_field' ) );

		// Regitster Metaboxes
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_box' ) );

		// Process meta saving.
		add_action( 'save_post', array( __CLASS__, 'save' ), 10, 2 );

		// Set the slug properly on save.
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'set_slug' ), 99, 2 );

		// Dashboard columns & filters.
		add_filter( 'manage_edit-popup_columns', array( __CLASS__, 'dashboard_columns' ) );
		add_action( 'manage_posts_custom_column', array( __CLASS__, 'render_columns' ), 10, 2 );
		add_filter( 'manage_edit-popup_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
		add_action( 'load-edit.php', array( __CLASS__, 'load' ), 9999 );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'add_popup_filters' ), 100 );
	}

	/**
	 * Renders the popup title meta field.
	 */
	public static function title_meta_field() {
		global $post, $pagenow, $typenow;

		if ( ! is_admin() ) {
			return;
		}

		if ( 'popup' == $typenow && in_array( $pagenow, array( 'post-new.php', 'post.php' ) ) ) { ?>

			<div id="popup-titlediv">
				<div id="popup-titlewrap">
					<label class="screen-reader-text" id="popup-title-prompt-text" for="popup-title">
						<?php _e( 'Enter popup title here', 'popup-maker' ); ?>
					</label>
					<input tabindex="2" name="popup_title" size="30" value="<?php esc_attr_e( get_post_meta( $post->ID, 'popup_title', true ) ); ?>" id="popup-title" autocomplete="off" placeholder="<?php _e( 'Enter popup title here', 'popup-maker' ); ?>" />
				</div>
				<div class="inside"></div>
			</div>
			<script>jQuery('#popup-titlediv').insertAfter('#titlediv');</script>
			<?php
		}
	}

	/**
	 * Registers popup metaboxes.
	 */
	public static function meta_box() {
		add_meta_box( 'pum_popup_settings', __( 'Popup Settings', 'popup-maker' ), array( __CLASS__, 'render_settings_meta_box' ), 'popup', 'normal', 'high' );
		add_meta_box( 'pum_popup_analytics', __( 'Analytics', 'popup-maker' ), array( __CLASS__, 'render_analytics_meta_box' ), 'popup', 'side', 'high' );
	}

	/**
	 * Render the settings meta box wrapper and JS vars.
	 */
	public static function render_settings_meta_box() {
		global $post;

		// Get the meta directly rather than from cached object.
		$settings = get_post_meta( $post->ID, 'popup_setting', true );

		if ( empty( $settings ) ) {
			$settings = self::defaults();
		}

		wp_nonce_field( basename( __FILE__ ), 'pum_popup_settings_nonce' );

		wp_enqueue_script( 'popup-maker-admin' );
		wp_localize_script( 'popup-maker-admin', 'pum_popup_settings_editor', array(
			'form_args'             => array(
				'id'       => 'pum-popup-settings',
				'tabs'     => self::tabs(),
				'sections' => self::sections(),
				'fields'   => self::fields(),
			),
			'conditions'            => PUM_Conditions::instance()->get_conditions(),
			'conditions_selectlist' => PUM_Conditions::instance()->dropdown_list(),
			'current_values'        => self::parse_values( $settings ),
		) ); ?>

		<div id="pum-popup-settings-container" class="pum-popup-settings-container"></div><?php
	}

	/**
	 * @param $post_id
	 * @param $post
	 */
	public static function save( $post_id, $post ) {

		if ( isset( $post->post_type ) && 'popup' != $post->post_type ) {
			return;
		}

		if ( ! isset( $_POST['pum_popup_settings_nonce'] ) || ! wp_verify_nonce( $_POST['pum_popup_settings_nonce'], basename( __FILE__ ) ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
			return;
		}

		if ( isset( $post->post_type ) && 'revision' == $post->post_type ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$popup = pum_get_popup( $post_id );

		if ( isset( $_POST['popup_reset_counts'] ) ) {
			/**
			 * Reset popup open count, per user request.
			 */
			$popup->reset_counts();
		}


		$title = ! empty ( $_POST['popup_title'] ) ? trim( sanitize_text_field( $_POST['popup_title'] ) ) : '';
		$popup->update_meta( 'popup_title', $title );

		$settings = ! empty( $_POST['popup_settings'] ) ? $_POST['popup_settings'] : array();

		$settings = wp_parse_args( $settings, self::defaults() );

		// Sanitize JSON values.
		$settings['conditions'] = isset( $settings['conditions'] ) ? self::sanitize_meta( $settings['conditions'] ) : array();

		$settings = apply_filters( 'pum_popup_setting_pre_save', $settings, $post->ID );

		$settings = self::sanitize_settings( $settings );

		$popup->update_meta( 'popup_setting', $settings );

		do_action( 'pum_save_popup', $post_id, $post );
	}

	public static function parse_values( $settings ) {

		foreach ( $settings as $key => $value ) {
			$field = self::get_field( $key );


			if ( $field ) {
				switch ( $field['type'] ) {
					case 'measure':
						break;
				}
			}
		}

		return $settings;
	}

	/**
	 * List of tabs & labels for the settings panel.
	 *
	 * @return array
	 */
	public static function tabs() {
		return apply_filters( 'pum_popup_settings_box_tabs', array(
			'general'   => __( 'General', 'popup-maker' ),
			'display'   => __( 'Display', 'popup-maker' ),
			'close'     => __( 'Close', 'popup-maker' ),
			'targeting' => __( 'Targeting', 'popup-maker' ),
		) );
	}

	/**
	 * List of tabs & labels for the settings panel.
	 *
	 * @return array
	 */
	public static function sections() {
		return apply_filters( 'pum_popup_settings_box_tabs', array(
			'general'   => array(
				'main' => __( 'General Settings', 'popup-maker' ),
			),
			'display'   => array(
				'main'      => __( 'Display Settings', 'popup-maker' ),
				'size'      => __( 'Size', 'popup-maker' ),
				'animation' => __( 'Animation', 'popup-maker' ),
				'position'  => __( 'Position', 'popup-maker' ),
				'misc'      => __( 'Misc', 'popup-maker' ),
			),
			'close'     => array(
				'main'    => __( 'General', 'popup-maker' ),
				'methods' => __( 'Misc', 'popup-maker' ),
			),
			'targeting' => array(
				'main' => __( 'Conditions', 'popup-maker' ),
			),
		) );
	}

	/**
	 * Returns array of popup settings fields.
	 *
	 * @return mixed
	 */
	public static function fields() {

		$tabs = apply_filters( 'pum_popup_settings_fields', array(
			'general'   => apply_filters( 'pum_popup_general_settings_fields', array(
				'main' => array(),
			) ),
			'display'   => apply_filters( 'pum_popup_display_settings_fields', array(
				'size'      => array(
					'size'                 => array(
						'label'    => __( 'Size', 'popup-maker' ),
						'desc'     => __( 'Select the size of the popup.', 'popup-maker' ),
						'type'     => 'select',
						'std'      => 'medium',
						'priority' => 10,
						'options'  => array(
							__( 'Responsive Sizes', 'popup-maker' ) => array(
								'nano'   => __( 'Nano - 10%', 'popup-maker' ),
								'micro'  => __( 'Micro - 20%', 'popup-maker' ),
								'tiny'   => __( 'Tiny - 30%', 'popup-maker' ),
								'small'  => __( 'Small - 40%', 'popup-maker' ),
								'medium' => __( 'Medium - 60%', 'popup-maker' ),
								'normal' => __( 'Normal - 70%', 'popup-maker' ),
								'large'  => __( 'Large - 80%', 'popup-maker' ),
								'xlarge' => __( 'X Large - 95%', 'popup-maker' ),
							),
							__( 'Other Sizes', 'popup-maker' )      => array(
								'auto'   => __( 'Auto', 'popup-maker' ),
								'custom' => __( 'Custom', 'popup-maker' ),
							),
						),
					),
					'responsive_min_width' => array(
						'label'        => __( 'Min Width', 'popup-maker' ),
						'desc'         => __( 'Set a minimum width for the popup.', 'popup-maker' ),
						'type'         => 'measure',
						'std'          => '0%',
						'priority'     => 20,
						'dependencies' => array(
							'size' => array( 'nano', 'micro', 'tiny', 'small', 'medium', 'normal', 'large', 'xlarge' ),
						),
					),
					'responsive_max_width' => array(
						'label'        => __( 'Max Width', 'popup-maker' ),
						'desc'         => __( 'Set a maximum width for the popup.', 'popup-maker' ),
						'type'         => 'measure',
						'std'          => '100%',
						'priority'     => 30,
						'dependencies' => array(
							'size' => array( 'nano', 'micro', 'tiny', 'small', 'medium', 'normal', 'large', 'xlarge' ),
						),
					),
					'custom_width'         => array(
						'label'        => __( 'Width', 'popup-maker' ),
						'desc'         => __( 'Set a custom width for the popup.', 'popup-maker' ),
						'type'         => 'measure',
						'std'          => '640px',
						'priority'     => 40,
						'dependencies' => array(
							'size' => 'custom',
						),
					),
					'custom_height_auto'   => array(
						'label'        => __( 'Auto Adjusted Height', 'popup-maker' ),
						'desc'         => __( 'Checking this option will set height to fit the content.', 'popup-maker' ),
						'type'         => 'checkbox',
						'std'          => false,
						'priority'     => 50,
						'dependencies' => array(
							'size' => 'custom',
						),
					),
					'custom_height'        => array(
						'label'        => __( 'Height', 'popup-maker' ),
						'desc'         => __( 'Set a custom height for the popup.', 'popup-maker' ),
						'type'         => 'measure',
						'std'          => '380px',
						'priority'     => 60,
						'dependencies' => array(
							'size'               => 'custom',
							'custom_height_auto' => true,
						),
					),
					'scrollable_content'   => array(
						'label'        => __( 'Scrollable Content', 'popup-maker' ),
						'desc'         => __( 'Checking this option will add a scroll bar to your content.', 'popup-maker' ),
						'type'         => 'checkbox',
						'std'          => false,
						'priority'     => 70,
						'dependencies' => array(
							'size'               => 'custom',
							'custom_height_auto' => true,
						),
					),
				),
				'animation' => array(
					'animation_type'   => array(
						'label'    => __( 'Animation Type', 'popup-maker' ),
						'desc'     => __( 'Select an animation type for your popup.', 'popup-maker' ),
						'type'     => 'select',
						'std'      => 'fade',
						'priority' => 10,
						'options'  => array(
							'none'         => __( 'None', 'popup-maker' ),
							'slide'        => __( 'Slide', 'popup-maker' ),
							'fade'         => __( 'Fade', 'popup-maker' ),
							'fadeAndSlide' => __( 'Fade and Slide', 'popup-maker' ),
							// 'grow'         => __( 'Grow', 'popup-maker' ),
							// 'growAndSlide' => __( 'Grow and Slide', 'popup-maker' ),
						),
					),
					'animation_speed'  => array(
						'label'        => __( 'Animation Speed', 'popup-maker' ),
						'desc'         => __( 'Set the animation speed for the popup.', 'popup-maker' ),
						'type'         => 'rangeslider',
						'std'          => 350,
						'step'         => 10,
						'min'          => 50,
						'max'          => 1000,
						'unit'         => __( 'ms', 'popup-maker' ),
						'priority'     => 20,
						'dependencies' => array(
							'animation_type' => array( 'slide', 'fade', 'fadeAndSlide', 'grow', 'growAndSlide' ),
						),
					),
					'animation_origin' => array(
						'label'        => __( 'Animation Origin', 'popup-maker' ),
						'desc'         => __( 'Choose where the animation will begin.', 'popup-maker' ),
						'type'         => 'select',
						'std'          => 'center top',
						'options'      => array(
							'top'           => __( 'Top', 'popup-maker' ),
							'left'          => __( 'Left', 'popup-maker' ),
							'bottom'        => __( 'Bottom', 'popup-maker' ),
							'right'         => __( 'Right', 'popup-maker' ),
							'left top'      => __( 'Top Left', 'popup-maker' ),
							'center top'    => __( 'Top Center', 'popup-maker' ),
							'right top'     => __( 'Top Right', 'popup-maker' ),
							'left center'   => __( 'Middle Left', 'popup-maker' ),
							'center center' => __( 'Middle Center', 'popup-maker' ),
							'right center'  => __( 'Middle Right', 'popup-maker' ),
							'left bottom'   => __( 'Bottom Left', 'popup-maker' ),
							'center bottom' => __( 'Bottom Center', 'popup-maker' ),
							'right bottom'  => __( 'Bottom Right', 'popup-maker' ),
						),
						'priority'     => 30,
						'dependencies' => array(
							'animation_type' => array( 'slide', 'fadeAndSlide', 'grow', 'growAndSlide' ),
						),
					),
				),
				'position'  => array(
					'location'              => array(
						'label'    => __( 'Location', 'popup-maker' ),
						'desc'     => __( 'Choose where the popup will be displayed.', 'popup-maker' ),
						'type'     => 'select',
						'std'      => 'center top',
						'priority' => 10,
						'options'  => array(
							'left top'      => __( 'Top Left', 'popup-maker' ),
							'center top'    => __( 'Top Center', 'popup-maker' ),
							'right top'     => __( 'Top Right', 'popup-maker' ),
							'left center'   => __( 'Middle Left', 'popup-maker' ),
							'center '       => __( 'Middle Center', 'popup-maker' ),
							'right center'  => __( 'Middle Right', 'popup-maker' ),
							'left bottom'   => __( 'Bottom Left', 'popup-maker' ),
							'center bottom' => __( 'Bottom Center', 'popup-maker' ),
							'right bottom'  => __( 'Bottom Right', 'popup-maker' ),
						),
					),
					'position_top'          => array(
						'label'        => __( 'Top', 'popup-maker' ),
						'desc'         => sprintf( _x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ), strtolower( __( 'Top', 'popup-maker' ) ) ),
						'type'         => 'rangeslider',
						'std'          => 100,
						'step'         => 1,
						'min'          => 0,
						'max'          => 500,
						'unit'         => 'px',
						'priority'     => 20,
						'dependencies' => array(
							'location' => array( 'left top', 'center top', 'right top' ),
						),
					),
					'position_bottom'       => array(
						'label'        => __( 'Bottom', 'popup-maker' ),
						'desc'         => sprintf( _x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ), strtolower( __( 'Bottom', 'popup-maker' ) ) ),
						'type'         => 'rangeslider',
						'std'          => 0,
						'step'         => 1,
						'min'          => 0,
						'max'          => 500,
						'unit'         => 'px',
						'priority'     => 20,
						'dependencies' => array(
							'location' => array( 'left bottom', 'center bottom', 'right bottom' ),
						),
					),
					'position_left'         => array(
						'label'        => __( 'Left', 'popup-maker' ),
						'desc'         => sprintf( _x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ), strtolower( __( 'Left', 'popup-maker' ) ) ),
						'type'         => 'rangeslider',
						'std'          => 0,
						'step'         => 1,
						'min'          => 0,
						'max'          => 500,
						'unit'         => 'px',
						'priority'     => 30,
						'dependencies' => array(
							'location' => array( 'left top', 'left center', 'left bottom' ),
						),
					),
					'position_right'        => array(
						'label'        => __( 'Right', 'popup-maker' ),
						'desc'         => sprintf( _x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ), strtolower( __( 'Right', 'popup-maker' ) ) ),
						'type'         => 'rangeslider',
						'std'          => 0,
						'step'         => 1,
						'min'          => 0,
						'max'          => 500,
						'unit'         => 'px',
						'priority'     => 30,
						'dependencies' => array(
							'location' => array( 'right top', 'right center', 'right bottom' ),
						),
					),
					'position_from_trigger' => array(
						'label'    => __( 'Position from Trigger', 'popup-maker' ),
						'desc'     => sprintf( __( 'This will position the popup in relation to the %sClick Trigger%s.', 'popup-maker' ), '<a target="_blank" href="http://docs.wppopupmaker.com/article/144-trigger-click-open?utm_medium=inline-doclink&utm_campaign=ContextualHelp&utm_source=plugin-popup-editor&utm_content=position-from-trigger">', '</a>' ),
						'type'     => 'checkbox',
						'std'      => false,
						'priority' => 40,
					),
					'position_fixed'        => array(
						'label'    => __( 'Fixed Postioning', 'popup-maker' ),
						'desc'     => __( 'Checking this sets the positioning of the popup to fixed.', 'popup-maker' ),
						'type'     => 'checkbox',
						'priority' => 50,
					),
				),
				'misc'      => array(
					'overlay_disabled'   => array(
						'label'    => __( 'Disable Overlay', 'popup-maker' ),
						'desc'     => __( 'Checking this will disable and hide the overlay for this popup.', 'popup-maker' ),
						'type'     => 'checkbox',
						'priority' => 10,
					),
					'stackable'          => array(
						'label'    => __( 'Stackable', 'popup-maker' ),
						'desc'     => __( 'This enables other popups to remain open.', 'popup-maker' ),
						'type'     => 'checkbox',
						'priority' => 20,
					),
					'disable_reposition' => array(
						'label'    => __( 'Disable Repositioning', 'popup-maker' ),
						'desc'     => __( 'This will disable automatic repositioning of the popup on window resizing.', 'popup-maker' ),
						'type'     => 'checkbox',
						'priority' => 30,
					),
					'zindex'             => array(
						'label'    => __( 'Popup Z-Index', 'popup-maker' ),
						'desc'     => __( 'Change the z-index layer level for the popup.', 'popup-maker' ),
						'type'     => 'number',
						'std'      => 1999999999,
						'priority' => 40,
						'min'      => 999,
						'max'      => 2147483647,
					),
				),
			) ),
			'close'     => apply_filters( 'pum_popup_close_settings_fields', array(
				'main'    => array(
					'text'         => array(
						'label'       => __( 'Close Text', 'popup-maker' ),
						'placeholder' => __( 'Close', 'popup-maker' ),
						'desc'        => __( 'Override the default close text.', 'popup-maker' ),
						'priority'    => 10,
					),
					'button_delay' => array(
						'label'    => __( 'Close Button Delay', 'popup-maker' ),
						'desc'     => __( 'This delays the display of the close button.', 'popup-maker' ),
						'type'     => 'rangeslider',
						'std'      => 0,
						'step'     => 100,
						'min'      => 0,
						'max'      => 3000,
						'unit'     => __( 'ms', 'popup-maker' ),
						'priority' => 20,
					),
				),
				'methods' => array(
					'overlay_click' => array(
						'label'    => __( 'Click Overlay to Close', 'popup-maker' ),
						'desc'     => __( 'Checking this will cause popup to close when user clicks on overlay.', 'popup-maker' ),
						'type'     => 'checkbox',
						'priority' => 10,
					),
					'esc_press'     => array(
						'label'    => __( 'Press ESC to Close', 'popup-maker' ),
						'desc'     => __( 'Checking this will cause popup to close when user presses ESC key.', 'popup-maker' ),
						'type'     => 'checkbox',
						'priority' => 20,
					),
					'f4_press'      => array(
						'label'    => __( 'Press F4 to Close', 'popup-maker' ),
						'desc'     => __( 'Checking this will cause popup to close when user presses F4 key.', 'popup-maker' ),
						'type'     => 'checkbox',
						'priority' => 30,
					),
				),
			) ),
			'targeting' => array(
				'main' => array(
					'conditions' => array(
						'type'     => 'conditions',
						'std'      => array(),
						'priority' => 10,
					),
				),
			),
		) );

		foreach ( $tabs as $tab_id => $sections ) {

			foreach ( $sections as $section_id => $fields ) {

				if ( self::is_field( $fields ) ) {
					// Allow for flat tabs with no sections.
					$section_id = 'main';
					$fields     = array(
						$section_id => $fields,
					);
				}

				foreach ( $fields as $field_id => $field ) {
					if ( ! is_array( $field ) || ! self::is_field( $field ) ) {
						continue;
					}

					if ( empty( $field['id'] ) ) {
						$field['id'] = $field_id;
					}
					if ( empty( $field['name'] ) ) {
						$field['name'] = 'popup_settings[' . $field_id . ']';
					}

					$tabs[ $tab_id ][ $section_id ][ $field_id ] = wp_parse_args( $field, array(
						'section'      => 'main',
						'type'         => 'text',
						'id'           => null,
						'label'        => '',
						'desc'         => '',
						'name'         => null,
						'templ_name'   => null,
						'size'         => 'regular',
						'options'      => array(),
						'std'          => null,
						'rows'         => 5,
						'cols'         => 50,
						'min'          => 0,
						'max'          => 50,
						'force_minmax' => false,
						'step'         => 1,
						'select2'      => null,
						'object_type'  => 'post_type',
						'object_key'   => 'post',
						'post_type'    => null,
						'taxonomy'     => null,
						'multiple'     => null,
						'as_array'     => false,
						'placeholder'  => null,
						'checkbox_val' => 1,
						'allow_blank'  => true,
						'readonly'     => false,
						'required'     => false,
						'disabled'     => false,
						'hook'         => null,
						'unit'         => __( 'ms', 'popup-maker' ),
						'units'        => array(
							'px'  => 'px',
							'%'   => '%',
							'em'  => 'em',
							'rem' => 'rem',
						),
						'priority'     => null,
						'doclink'      => '',
						'button_type'  => 'submit',
						'class'        => '',

					) );

				}
			}
		}

		return $tabs;
	}

	public static function get_field( $id ) {
		$tabs = self::fields();

		foreach ( $tabs as $tab => $sections ) {

			if ( self::is_field( $sections ) ) {
				$sections = array(
					'main' => array(
						$tab => $sections,
					),
				);
			}

			foreach ( $sections as $section => $fields ) {

				foreach ( $fields as $key => $args ) {
					if ( $key == $id ) {
						return $args;
					}
				}
			}
		}

		return false;
	}

	public static function sanitize_settings( $settings = array() ) {


		foreach ( $settings as $key => $value ) {
			$field = self::get_field( $key );

			if ( $field ) {
				switch ( $field['type'] ) {
					case 'measure':
						$settings[ $key ] .= $settings[ $key . '_unit' ];
						break;
				}
			} else {
				unset( $settings[ $key ] );
			}


		}

		return $settings;
	}

	#endregion Done

	/**
	 * Display analytics metabox
	 *
	 * @return void
	 */
	public static function render_analytics_meta_box() {
		global $post;

		$popup = pum_get_popup( $post->ID ); ?>
		<div id="pum-popup-analytics" class="pum-meta-box">

		<?php do_action( 'pum_popup_analytics_metabox_before', $post->ID ); ?>

		<?php
		$opens       = $popup->get_event_count( 'open', 'current' );
		$conversions = $popup->get_event_count( 'conversion', 'current' );
		$conversion_rate = $opens > 0 && $opens >= $conversions ? $conversions / $opens * 100 : false;
		?>

		<div id="pum-popup-analytics" class="pum-popup-analytics">

			<table class="form-table">
				<tbody>
				<tr>
					<td><?php _e( 'Opens', 'popup-maker' ); ?></td>
					<td><?php echo $opens; ?></td>
				</tr>
				<tr>
					<td><?php _e( 'Conversions', 'popup-maker' ); ?></td>
					<td><?php echo $conversions; ?></td>
				</tr>
				<?php if ( $conversion_rate ) : ?>
					<tr>
						<td><?php _e( 'Conversion Rate', 'popup-maker' ); ?></td>
						<td><?php echo round( $conversion_rate, 2 ); ?>%</td>
					</tr>
				<?php endif; ?>
				<tr class="separator">
					<td colspan="2">
						<label>
							<input type="checkbox" name="popup_reset_counts" id="popup_reset_counts" value="1" />
							<?php _e( 'Reset Counts', 'popup-maker' ); ?>
						</label>
						<?php if ( ( $reset = $popup->get_last_count_reset() ) ) : ?><br />
							<small>
								<strong><?php _e( 'Last Reset', 'popup-maker' ); ?>:</strong> <?php echo date( 'm-d-Y H:i', $reset['timestamp'] ); ?>
								<br />
								<strong><?php _e( 'Previous Opens', 'popup-maker' ); ?>:</strong> <?php echo $reset['opens']; ?>
								<br />
								<strong><?php _e( 'Previous Conversions', 'popup-maker' ); ?>:</strong> <?php echo $reset['conversions']; ?>
								<br />
								<strong><?php _e( 'Lifetime Opens', 'popup-maker' ); ?>:</strong> <?php echo $popup->get_event_count( 'open', 'total' ); ?>
								<br />
								<strong><?php _e( 'Lifetime Conversions', 'popup-maker' ); ?>:</strong> <?php echo $popup->get_event_count( 'conversion', 'total' ); ?>
							</small>
						<?php endif; ?>
					</td>
				</tr>
				</tbody>
			</table>
		</div>

		<?php do_action( 'pum_popup_analytics_metabox_after', $post->ID ); ?>

	</div>

	<?php
	}

	/**
	 * @param array $meta
	 *
	 * @return array
	 */
	public static function sanitize_meta( $meta = array() ) {
		if ( ! empty( $meta ) ) {

			foreach ( $meta as $key => $value ) {

				if ( is_string( $value ) ) {
					try {
						$value = json_decode( stripslashes( $value ) );
					} catch ( \Exception $e ) {
					};
				}

				$meta[ $key ] = PUM_Admin_Helpers::object_to_array( $value );
			}
		}

		return $meta;
	}

	/**
	 * @return array
	 */
	public static function defaults() {
		$tabs = self::fields();

		$defaults = array();

		foreach ( $tabs as $section_id => $fields ) {
			foreach ( $fields as $key => $field ) {
				$defaults[ $key ] = isset( $field['std'] ) ? $field['std'] : null;
			}
		}

		return $defaults;
	}

	/**
	 * Checks if an array is a field.
	 *
	 * @param array $array
	 *
	 * @return bool
	 */
	public static function is_field( $array = array() ) {
		$field_tests = array(
			isset( $array['id'] ),
			isset( $array['label'] ),
			isset( $array['type'] ),
			isset( $array['options'] ),
			isset( $array['desc'] ),
		);

		return in_array( true, $field_tests );
	}

	/**
	 * Checks if an array is a section.
	 *
	 * @param array $array
	 *
	 * @return bool
	 */
	public static function is_section( $array = array() ) {
		return ! self::is_field( $array );
	}

	/**
	 * Ensures that the popups have unique slugs.
	 *
	 * @param $data
	 * @param $postarr
	 *
	 * @return mixed
	 */
	public static function set_slug( $data, $postarr ) {
		if ( $data['post_type'] == 'popup' ) {
			$data['post_name'] = wp_unique_post_slug( sanitize_title( popmake_post( 'popup_name' ) ), $postarr['ID'], $data['post_status'], $data['post_type'], $data['post_parent'] );
		}

		return $data;
	}


	/**
	 * Defines the custom columns and their order
	 *
	 * @param array $_columns Array of popup columns
	 *
	 * @return array $columns Updated array of popup columns for
	 *  Post Type List Table
	 */
	public static function dashboard_columns( $_columns ) {
		$columns = array(
			'cb'              => '<input type="checkbox"/>',
			'title'           => __( 'Name', 'popup-maker' ),
			'popup_title'     => __( 'Title', 'popup-maker' ),
			'class'           => __( 'CSS Classes', 'popup-maker' ),
			'opens'           => __( 'Opens', 'popup-maker' ),
			'conversions'     => __( 'Conversions', 'popup-maker' ),
			'conversion_rate' => __( 'Conversion Rate', 'popup-maker' ),
		);

		// Add the date column preventing our own translation
		if ( ! empty( $_columns['date'] ) ) {
			$columns['date'] = $_columns['date'];
		}

		if ( get_taxonomy( 'popup_tag' ) ) {
			$columns['popup_tag'] = __( 'Tags', 'popup-maker' );
		}

		if ( get_taxonomy( 'popup_category' ) ) {
			$columns['popup_category'] = __( 'Categories', 'popup-maker' );
		}

		// Deprecated filter.
		$columns = apply_filters( 'popmake_popup_columns', $columns );

		return apply_filters( 'pum_popup_columns', $columns );
	}

	/**
	 * Render Columns
	 *
	 * @param string $column_name Column name
	 * @param int $post_id (Post) ID
	 */
	public static function render_columns( $column_name, $post_id ) {
		if ( get_post_type( $post_id ) == 'popup' ) {

			$popup = pum_get_popup( $post_id );
			//setup_postdata( $popup );

			/**
			 * Uncomment if need to check for permissions on certain columns.
			 *          *
			 * $post_type_object = get_post_type_object( $popup->post_type );
			 * $can_edit_post    = current_user_can( $post_type_object->cap->edit_post, $popup->ID );
			 */

			switch ( $column_name ) {
				case 'popup_title':
					echo esc_html( $popup->get_title() );
					break;
				case 'popup_category':
					echo get_the_term_list( $post_id, 'popup_category', '', ', ', '' );
					break;
				case 'popup_tag':
					echo get_the_term_list( $post_id, 'popup_tag', '', ', ', '' );
					break;
				case 'class':
					echo '<pre style="display:inline-block;margin:0;"><code>popmake-' . absint( $post_id ) . '</code></pre>';
					if ( $popup->post_name != $popup->ID ) {
						echo '|';
						echo '<pre style="display:inline-block;margin:0;"><code>popmake-' . $popup->post_name . '</code></pre>';
					}
					break;
				case 'opens':
					if ( ! pum_extension_enabled( 'popup-analytics' ) ) {
						echo $popup->get_event_count( 'open' );
					}
					break;
				case 'conversions':
					if ( ! pum_extension_enabled( 'popup-analytics' ) ) {
						echo $popup->get_event_count( 'conversion' );
					}
					break;
				case 'conversion_rate':
					$views       = $popup->get_event_count( 'view', 'current' );
					$conversions = $popup->get_event_count( 'conversion', 'current' );

					$conversion_rate = $views > 0 && $views >= $conversions ? $conversions / $views * 100 : __( 'N/A', 'popup-maker' );
					echo round( $conversion_rate, 2 ) . '%';
					break;
			}
		}
	}

	/**
	 * Registers the sortable columns in the list table
	 *
	 * @param array $columns Array of the columns
	 *
	 * @return array $columns Array of sortable columns
	 */
	public static function sortable_columns( $columns ) {
		$columns['popup_title'] = 'popup_title';
		$columns['opens']       = 'opens';
		$columns['conversions'] = 'conversions';

		return $columns;
	}

	/**
	 * Sorts Columns in the List Table
	 *
	 * @param array $vars Array of all the sort variables
	 *
	 * @return array $vars Array of all the sort variables
	 */
	public static function sort_columns( $vars ) {
		// Check if we're viewing the "popup" post type
		if ( isset( $vars['post_type'] ) && 'popup' == $vars['post_type'] ) {
			// Check if 'orderby' is set to "name"
			if ( isset( $vars['orderby'] ) ) {
				switch ( $vars['orderby'] ) {
					case 'popup_title':
						$vars = array_merge( $vars, array(
							'meta_key' => 'popup_title',
							'orderby'  => 'meta_value',
						) );
						break;
					case 'opens':
						if ( ! pum_extension_enabled( 'popup-analytics' ) ) {
							$vars = array_merge( $vars, array(
								'meta_key' => 'popup_open_count',
								'orderby'  => 'meta_value_num',
							) );
						}
						break;
					case 'conversions':
						if ( ! pum_extension_enabled( 'popup-analytics' ) ) {
							$vars = array_merge( $vars, array(
								'meta_key' => 'popup_conversion_count',
								'orderby'  => 'meta_value_num',
							) );
						}
						break;
				}
			}
		}

		return $vars;
	}

	/**
	 * Initialize sorting
	 */
	public static function load() {
		add_filter( 'request', array( __CLASS__, 'sort_columns' ) );
	}

	/**
	 * Add Popup Filters
	 *
	 * Adds taxonomy drop down filters for popups.
	 */
	public static function add_popup_filters() {
		global $typenow;

		// Checks if the current post type is 'popup'
		if ( $typenow == 'popup' ) {

			if ( get_taxonomy( 'popup_category' ) ) {
				$terms = get_terms( 'popup_category' );
				if ( count( $terms ) > 0 ) {
					echo "<select name='popup_category' id='popup_category' class='postform'>";
					echo "<option value=''>" . __( 'Show all categories', 'popup-maker' ) . "</option>";
					foreach ( $terms as $term ) {
						$selected = isset( $_GET['popup_category'] ) && $_GET['popup_category'] == $term->slug ? 'selected="selected"' : '';
						echo '<option value="' . esc_attr( $term->slug ) . '" ' . $selected . '>' . esc_html( $term->name ) . ' (' . $term->count . ')</option>';
					}
					echo "</select>";
				}
			}

			if ( get_taxonomy( 'popup_tag' ) ) {
				$terms = get_terms( 'popup_tag' );
				if ( count( $terms ) > 0 ) {
					echo "<select name='popup_tag' id='popup_tag' class='postform'>";
					echo "<option value=''>" . __( 'Show all tags', 'popup-maker' ) . "</option>";
					foreach ( $terms as $term ) {
						$selected = isset( $_GET['popup_tag'] ) && $_GET['popup_tag'] == $term->slug ? 'selected="selected"' : '';
						echo '<option value="' . esc_attr( $term->slug ) . '" ' . $selected . '>' . esc_html( $term->name ) . ' (' . $term->count . ')</option>';
					}
					echo "</select>";
				}
			}
		}

	}

}

