<?php
/**
 * Class for Admin Popups
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Admin_Popups
 *
 * @since 1.7.0
 */
class PUM_Admin_Popups {

	/**
	 * Hook the initialize method to the WP init action.
	 */
	public static function init() {

		// Adds ID to top of popup editor.
		add_action( 'edit_form_top', [ __CLASS__, 'add_popup_id' ] );

		// Change title to popup name.
		add_filter( 'enter_title_here', [ __CLASS__, 'default_title' ] );

		// Add popup title field.
		add_action( 'edit_form_advanced', [ __CLASS__, 'title_meta_field' ] );

		// Add Contextual help to post_name field.
		add_action( 'edit_form_before_permalink', [ __CLASS__, 'popup_post_title_contextual_message' ] );

		// Register Metaboxes.
		add_action( 'add_meta_boxes', [ __CLASS__, 'meta_box' ] );

		// Process meta saving.
		add_action( 'save_post', [ __CLASS__, 'save' ], 10, 2 );

		// Set the slug properly on save.
		add_filter( 'wp_insert_post_data', [ __CLASS__, 'set_slug' ], 99, 2 );

		// Dashboard columns & filters.
		add_filter( 'manage_edit-popup_columns', [ __CLASS__, 'dashboard_columns' ] );
		add_action( 'manage_posts_custom_column', [ __CLASS__, 'render_columns' ], 10, 2 );
		add_filter( 'manage_edit-popup_sortable_columns', [ __CLASS__, 'sortable_columns' ] );
		add_filter( 'default_hidden_columns', [ __CLASS__, 'hide_columns' ], 10, 2 );
		add_action( 'load-edit.php', [ __CLASS__, 'load' ], 9999 );
		add_action( 'restrict_manage_posts', [ __CLASS__, 'add_popup_filters' ], 100 );
		add_filter( 'post_row_actions', [ __CLASS__, 'add_id_row_actions' ], 100, 2 );

		add_action( 'post_submitbox_misc_actions', [ __CLASS__, 'add_enabled_toggle_editor' ], 10, 1 );

		add_filter( 'mce_buttons_2', [ __CLASS__, 'add_mce_buttons' ], 10, 1 );
		add_filter( 'tiny_mce_before_init', [ __CLASS__, 'increase_available_font_sizes' ], 10, 1 );
	}

	/**
	 * Adds our enabled state toggle to the "Publish" meta box.
	 *
	 * @since 1.12
	 * @param WP_Post $post The current post (i.e. the popup).
	 */
	public static function add_enabled_toggle_editor( $post ) {
		if ( 'publish' !== $post->post_status || 'popup' !== $post->post_type ) {
			return;
		}
		$popup   = pum_get_popup( $post->ID );
		$enabled = $popup->is_enabled();
		$nonce   = wp_create_nonce( 'pum_save_enabled_state' );
		?>
		<div class="misc-pub-section" style="display:flex;">
			<span style="font-weight: bold; margin-right: 10px;">Popup Enabled </span>
			<div class="pum-toggle-button">
				<input id="pum-enabled-toggle-<?php echo esc_attr( $popup->ID ); ?>" type="checkbox" <?php checked( true, $enabled ); ?> class="pum-enabled-toggle-button" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-popup-id="<?php echo esc_attr( $popup->ID ); ?>">
				<label for="pum-enabled-toggle-<?php echo esc_attr( $popup->ID ); ?>" aria-label="Switch to enable popup"></label>
			</div>
		</div>

		<?php
	}

	/**
	 * Adds the Popup ID right under the "Edit Popup" heading
	 *
	 * @param WP_Post $post Post object.
	 * @since 1.12.0
	 */
	public static function add_popup_id( $post ) {
		if ( 'popup' === $post->post_type ) {
			?>
			<p style="margin:0;font-size:12px;">ID: <span id="popup-id" data-popup-id="<?php echo esc_attr( $post->ID ); ?>"><?php echo esc_html( $post->ID ); ?></span></p>
			<?php
		}
	}

	/**
	 * Change default "Enter title here" input
	 *
	 * @param string $title Default title placeholder text.
	 * @return string $title New placeholder text
	 */
	public static function default_title( $title ) {

		if ( ! is_admin() ) {
			return $title;
		}

		$screen = get_current_screen();

		if ( 'popup_theme' === $screen->post_type ) {
			$label = 'popup' === $screen->post_type ? __( 'Popup', 'popup-maker' ) : __( 'Popup Theme', 'popup-maker' );
			$title = sprintf( '%s Name', $label );
		}

		if ( 'popup' === $screen->post_type ) {
			$title = __( 'Popup Name', 'popup-maker' );
		}

		return $title;
	}

	/**
	 * Renders the popup title meta field.
	 */
	public static function title_meta_field() {
		global $post, $pagenow, $typenow;

		if ( has_blocks( $post ) || ( function_exists( 'use_block_editor_for_post' ) && use_block_editor_for_post( $post ) ) ) {
			return;
		}

		if ( ! is_admin() ) {
			return;
		}

		if ( 'popup' === $typenow && in_array( $pagenow, [ 'post-new.php', 'post.php' ], true ) ) {
			?>

			<div id="popup-titlediv" class="pum-form">
				<div id="popup-titlewrap">
					<label class="screen-reader-text" id="popup-title-prompt-text" for="popup-title">
						<?php esc_html_e( 'Popup Title', 'popup-maker' ); ?>
					</label>
					<input tabindex="2" name="popup_title" size="30" value="<?php echo esc_attr( get_post_meta( $post->ID, 'popup_title', true ) ); ?>" id="popup-title" autocomplete="off" placeholder="<?php esc_attr_e( 'Popup Title', 'popup-maker' ); ?>" />
					<p class="pum-desc"><?php echo '(' . esc_html__( 'Optional', 'popup-maker' ) . ') ' . esc_html__( 'Shown as headline inside the popup. Can be left blank.', 'popup-maker' ); ?></p>
				</div>
				<div class="inside"></div>
			</div>
			<script>jQuery('#popup-titlediv').insertAfter('#titlediv');</script>
			<?php
		}
	}

	/**
	 * Renders contextual help for title.
	 */
	public static function popup_post_title_contextual_message() {
		global $post, $pagenow, $typenow;

		if ( has_blocks( $post ) || ( function_exists( 'use_block_editor_for_post' ) && use_block_editor_for_post( $post ) ) ) {
			return;
		}

		if ( ! is_admin() ) {
			return;
		}

		if ( 'popup' === $typenow && in_array( $pagenow, [ 'post-new.php', 'post.php' ], true ) ) {
			?>
			<p class="pum-desc"><?php echo '(' . esc_html__( 'Required', 'popup-maker' ) . ') ' . esc_html__( 'Enter a name to help you remember what this popup is about. Only you will see this.', 'popup-maker' ); ?></p>
			<?php
		}
	}

	/**
	 * Registers popup metaboxes.
	 */
	public static function meta_box() {
		add_meta_box( 'pum_popup_settings', __( 'Popup Settings', 'popup-maker' ), [ __CLASS__, 'render_settings_meta_box' ], 'popup', 'normal', 'high' );
		add_meta_box( 'pum_popup_analytics', __( 'Analytics', 'popup-maker' ), [ __CLASS__, 'render_analytics_meta_box' ], 'popup', 'side', 'high' );
	}

	/**
	 * Ensures integrity of values.
	 *
	 * @param array $values Array of settings.
	 * @return array
	 *
	 * @deprecated 1.20.0 - Explicitly use ::defaults() and/or ::fill_missing_defaults() instead.
	 */
	public static function parse_values( $values = [] ) {
		$defaults = self::defaults();

		if ( empty( $values ) ) {
			return $defaults;
		}

		$values = self::fill_missing_defaults( $values );

		return $values;
	}

	/**
	 * Render the settings meta box wrapper and JS vars.
	 */
	public static function render_settings_meta_box() {
		global $post;

		$popup = pum_get_popup( $post->ID );

		// Get the meta directly rather than from cached object.
		$settings = $popup->get_meta( 'popup_settings' );

		// If this is a new popup, use the defaults.
		if ( '' === $settings ) {
			$settings = self::defaults(); // Fallback to defaults as this is likely a new popup.
		}

		wp_nonce_field( basename( __FILE__ ), 'pum_popup_settings_nonce' );
		wp_enqueue_script( 'popup-maker-admin' );
		?>
		<script type="text/javascript">
			window.pum_popup_settings_editor =
			<?php
			// Ignored as this is a JSON string.
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			echo PUM_Utils_Array::safe_json_encode(
				apply_filters(
					'pum_popup_settings_editor_var',
					[
						'form_args'             => [
							'id'       => 'pum-popup-settings',
							'tabs'     => self::tabs(),
							'sections' => self::sections(),
							'fields'   => self::fields(),
						],
						'conditions'            => PUM_Conditions::instance()->get_conditions(),
						'conditions_selectlist' => PUM_Conditions::instance()->dropdown_list(),
						'triggers'              => PUM_Triggers::instance()->get_triggers(),
						'cookies'               => PUM_Cookies::instance()->get_cookies(),
						'current_values'        => self::render_form_values( $settings ),
						'preview_nonce'         => wp_create_nonce( 'popup-preview' ),
					]
				)
			);
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

			?>
			;
		</script>

		<div id="pum-popup-settings-container" class="pum-popup-settings-container">
			<div class="pum-no-js" style="padding: 0 12px;">
				<p>
				<?php
					printf(
					/* translators: 1. URL to view troubleshooting guide. 2. Closing HTML tag. */
						esc_html__( 'If you are seeing this, the page is still loading or there are Javascript errors on this page. %1$sView troubleshooting guide%2$s', 'popup-maker' ),
						'<a href="https://docs.wppopupmaker.com/article/373-checking-for-javascript-errors" target="_blank">',
						'</a>'
					);
				?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Used to get deprecated fields for metabox saving of old extensions.
	 *
	 * @deprecated 1.7.0
	 *
	 * @return mixed
	 */
	public static function deprecated_meta_fields() {
		$fields = [];
		foreach ( self::deprecated_meta_field_groups() as $group ) {
			foreach ( apply_filters( 'popmake_popup_meta_field_group_' . $group, [] ) as $field ) {
				$fields[] = 'popup_' . $group . '_' . $field;
			}
		}

		return apply_filters( 'popmake_popup_meta_fields', $fields );
	}

	/**
	 * Used to get field groups from extensions.
	 *
	 * @deprecated 1.7.0
	 *
	 * @return mixed
	 */
	public static function deprecated_meta_field_groups() {
		return apply_filters( 'popmake_popup_meta_field_groups', [ 'display', 'close' ] );
	}

	/**
	 * @param $post_id
	 * @param $post
	 */
	public static function save( $post_id, $post ) {

		if ( isset( $post->post_type ) && 'popup' !== $post->post_type ) {
			return;
		}

		if ( ! isset( $_POST['pum_popup_settings_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['pum_popup_settings_nonce'] ) ), basename( __FILE__ ) ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
			return;
		}

		if ( isset( $post->post_type ) && 'revision' === $post->post_type ) {
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

		$title = ! empty( $_POST['popup_title'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['popup_title'] ) ) ) : '';
		$popup->update_meta( 'popup_title', $title );

		// Ignored because this is a dynamic array and has sanitization applid to keys before usage.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$settings = ! empty( $_POST['popup_settings'] ) ? wp_unslash( $_POST['popup_settings'] ) : [];

		// Sanitize JSON values.
		$settings['conditions'] = isset( $settings['conditions'] ) ? self::sanitize_meta( $settings['conditions'] ) : [];
		$settings['triggers']   = isset( $settings['triggers'] ) ? self::sanitize_meta( $settings['triggers'] ) : [];
		$settings['cookies']    = isset( $settings['cookies'] ) ? self::sanitize_meta( $settings['cookies'] ) : [];

		$settings = apply_filters( 'pum_popup_setting_pre_save', $settings, $post->ID );

		$settings = self::sanitize_settings( $settings );

		$popup->update_settings( $settings, false );

		// TODO Remove this and all other code here. This should be clean and all code more compartmentalized.
		foreach ( self::deprecated_meta_fields() as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				// Ignored because this should no longer be used, has been deprecated nd we don't know the format of each value safely to sanitize.
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$new = apply_filters( 'popmake_metabox_save_' . $field, wp_unslash( $_POST[ $field ] ) );
				update_post_meta( $post_id, $field, $new );
			} else {
				delete_post_meta( $post_id, $field );
			}
		}

		do_action( 'pum_save_popup', $post_id, $post );
	}

	/**
	 * Parse & prepare values for form rendering.
	 *
	 * Add additional data for license_key fields, split the measure fields etc.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public static function render_form_values( $settings ) {
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
		return apply_filters(
			'pum_popup_settings_tabs',
			[
				'general'   => __( 'General', 'popup-maker' ),
				'display'   => __( 'Display', 'popup-maker' ),
				'close'     => __( 'Close', 'popup-maker' ),
				'triggers'  => __( 'Triggers', 'popup-maker' ),
				'targeting' => __( 'Targeting', 'popup-maker' ),
				'advanced'  => __( 'Advanced', 'popup-maker' ),
			]
		);
	}

	/**
	 * List of tabs & labels for the settings panel.
	 *
	 * @return array
	 */
	public static function sections() {
		return apply_filters(
			'pum_popup_settings_sections',
			[
				'general'   => [
					'main' => __( 'General Settings', 'popup-maker' ),
				],
				'triggers'  => [
					'main' => __( 'Triggers & Cookies', 'popup-maker' ),
				],
				'targeting' => [
					'main' => __( 'Conditions', 'popup-maker' ),
				],
				'display'   => [
					'preset'    => __( 'Display Presets', 'popup-maker' ),
					'main'      => __( 'Appearance', 'popup-maker' ),
					'size'      => __( 'Size', 'popup-maker' ),
					'animation' => __( 'Animation', 'popup-maker' ),
					'sound'     => __( 'Sounds', 'popup-maker' ),
					'position'  => __( 'Position', 'popup-maker' ),
					'advanced'  => __( 'Advanced', 'popup-maker' ),
				],
				'close'     => [
					'button'            => __( 'Button', 'popup-maker' ),
					'forms'             => __( 'Form Submission', 'popup-maker' ),
					'alternate_methods' => __( 'Alternate Methods', 'popup-maker' ),
				],
				'advanced'  => [
					'main' => __( 'Advanced', 'popup-maker' ),
				],
			]
		);
	}

	/**
	 * Returns array of popup settings fields.
	 *
	 * @return mixed
	 */
	public static function fields() {

		static $tabs;

		if ( ! isset( $tabs ) ) {
			$tabs = apply_filters(
				'pum_popup_settings_fields',
				[
					'general'   => apply_filters(
						'pum_popup_general_settings_fields',
						[
							'main' => [],
						]
					),
					'triggers'  => apply_filters(
						'pum_popup_triggers_settings_fields',
						[
							'main' => [
								'triggers'   => [
									'type'     => 'triggers',
									'std'      => [],
									'priority' => 10,
								],
								'separator1' => [
									'type'    => 'separator',
									'private' => true,
								],
								'cookies'    => [
									'type'     => 'cookies',
									'std'      => [],
									'priority' => 20,
								],
							],
						]
					),
					'targeting' => apply_filters(
						'pum_popup_targeting_settings_fields',
						[
							'main' => [
								'conditions'        => [
									'type'     => 'conditions',
									'std'      => [],
									'priority' => 10,
									'private'  => true,
								],
								'disable_on_mobile' => [
									'label'    => __( 'Disable this popup on mobile devices.', 'popup-maker' ),
									'type'     => 'checkbox',
									'priority' => 20,
								],
								'disable_on_tablet' => [
									'label'    => __( 'Disable this popup on tablet devices.', 'popup-maker' ),
									'type'     => 'checkbox',
									'priority' => 20,
								],
							],
						]
					),
					'display'   => apply_filters(
						'pum_popup_display_settings_fields',
						[
							'preset'    => [
								'explain'      => [
									'type'    => 'html',
									'content' => '<p>Select one of the types below to get started! Once selected, you can adjust the display settings using the tabs above.</p>',
								],
								'type_section' => [
									'type'    => 'section',
									'classes' => 'popup-types',
									'fields'  => [
										'<div class="popup-type" data-popup-type="center-popup"><img src="' . Popup_Maker::$URL . 'assets/images/admin/display-switcher/center-popup.png" alt="' . __( 'Center Popup', 'popup-maker' ) . '"/><button class="button">' . __( 'Center Popup', 'popup-maker' ) . '</button></div>',
										'<div class="popup-type" data-popup-type="right-bottom-slidein"><img src="' . Popup_Maker::$URL . 'assets/images/admin/display-switcher/right-bottom-slidein.png" alt="' . __( 'Right Bottom Slide-in', 'popup-maker' ) . '"/><button class="button">' . __( 'Right Bottom Slide-in', 'popup-maker' ) . '</button></div>',
										'<div class="popup-type" data-popup-type="top-bar"><img src="' . Popup_Maker::$URL . 'assets/images/admin/display-switcher/top-bar.png" alt="' . __( 'Top Bar', 'popup-maker' ) . '"/><button class="button">' . __( 'Top Bar', 'popup-maker' ) . '</button></div>',
										'<div class="popup-type" data-popup-type="left-bottom-notice"><img src="' . Popup_Maker::$URL . 'assets/images/admin/display-switcher/left-bottom-notice.png" alt="' . __( 'Left Bottom Notice', 'popup-maker' ) . '"/><button class="button">' . __( 'Left Bottom Notice', 'popup-maker' ) . '</button></div>',
									],
								],
							],
							'main'      => [
								'theme_id' => [
									'label'        => __( 'Popup Theme', 'popup-maker' ),
									'dynamic_desc' => sprintf( '%1$s<br/><a id="edit_theme_link" href="%3$s">%2$s</a>', __( 'Choose a theme for this popup.', 'popup-maker' ), __( 'Customize This Theme', 'popup-maker' ), admin_url( 'post.php?action=edit&post={{data.value}}' ) ),
									'type'         => 'select',
									'options'      => pum_is_popup_editor() ? PUM_Helpers::popup_theme_selectlist() : null,
									'std'          => pum_get_default_theme_id(),
								],
							],
							'size'      => [
								'size'                 => [
									'label'    => __( 'Size', 'popup-maker' ),
									'desc'     => __( 'Select the size of the popup.', 'popup-maker' ),
									'type'     => 'select',
									'std'      => 'medium',
									'priority' => 10,
									'options'  => [
										__( 'Responsive Sizes', 'popup-maker' ) => [
											'nano'   => __( 'Nano - 10%', 'popup-maker' ),
											'micro'  => __( 'Micro - 20%', 'popup-maker' ),
											'tiny'   => __( 'Tiny - 30%', 'popup-maker' ),
											'small'  => __( 'Small - 40%', 'popup-maker' ),
											'medium' => __( 'Medium - 60%', 'popup-maker' ),
											'normal' => __( 'Normal - 70%', 'popup-maker' ),
											'large'  => __( 'Large - 80%', 'popup-maker' ),
											'xlarge' => __( 'X Large - 95%', 'popup-maker' ),
										],
										__( 'Other Sizes', 'popup-maker' )      => [
											'auto'   => __( 'Auto', 'popup-maker' ),
											'custom' => __( 'Custom', 'popup-maker' ),
										],
									],
								],
								'responsive_min_width' => [
									'label'        => __( 'Min Width', 'popup-maker' ),
									'desc'         => __( 'Set a minimum width for the popup.', 'popup-maker' ),
									'type'         => 'measure',
									'std'          => '0%',
									'priority'     => 20,
									'dependencies' => [
										'size' => [ 'nano', 'micro', 'tiny', 'small', 'medium', 'normal', 'large', 'xlarge' ],
									],
								],
								'responsive_max_width' => [
									'label'        => __( 'Max Width', 'popup-maker' ),
									'desc'         => __( 'Set a maximum width for the popup.', 'popup-maker' ),
									'type'         => 'measure',
									'std'          => '100%',
									'priority'     => 30,
									'dependencies' => [
										'size' => [ 'nano', 'micro', 'tiny', 'small', 'medium', 'normal', 'large', 'xlarge' ],
									],
								],
								'custom_width'         => [
									'label'        => __( 'Width', 'popup-maker' ),
									'desc'         => __( 'Set a custom width for the popup.', 'popup-maker' ),
									'type'         => 'measure',
									'std'          => '640px',
									'priority'     => 40,
									'dependencies' => [
										'size' => 'custom',
									],
								],
								'custom_height_auto'   => [
									'label'        => __( 'Auto Adjusted Height', 'popup-maker' ),
									'desc'         => __( 'Checking this option will set height to fit the content.', 'popup-maker' ),
									'type'         => 'checkbox',
									'priority'     => 50,
									'dependencies' => [
										'size' => 'custom',
									],
								],
								'custom_height'        => [
									'label'        => __( 'Height', 'popup-maker' ),
									'desc'         => __( 'Set a custom height for the popup.', 'popup-maker' ),
									'type'         => 'measure',
									'std'          => '380px',
									'priority'     => 60,
									'dependencies' => [
										'size' => 'custom',
										'custom_height_auto' => false,
									],
								],
								'scrollable_content'   => [
									'label'        => __( 'Scrollable Content', 'popup-maker' ),
									'desc'         => __( 'Checking this option will add a scroll bar to your content.', 'popup-maker' ),
									'type'         => 'checkbox',
									'std'          => false,
									'priority'     => 70,
									'dependencies' => [
										'size' => 'custom',
										'custom_height_auto' => false,
									],
								],
							],
							'animation' => [
								'animation_type'   => [
									'label'    => __( 'Animation Type', 'popup-maker' ),
									'desc'     => __( 'Select an animation type for your popup.', 'popup-maker' ),
									'type'     => 'select',
									'std'      => 'fade',
									'priority' => 10,
									'options'  => [
										'none'         => __( 'None', 'popup-maker' ),
										'slide'        => __( 'Slide', 'popup-maker' ),
										'fade'         => __( 'Fade', 'popup-maker' ),
										'fadeAndSlide' => __( 'Fade and Slide', 'popup-maker' ),
										// 'grow'         => __( 'Grow', 'popup-maker' ),
										// 'growAndSlide' => __( 'Grow and Slide', 'popup-maker' ),
									],
								],
								'animation_speed'  => [
									'label'        => __( 'Animation Speed', 'popup-maker' ),
									'desc'         => __( 'Set the animation speed for the popup.', 'popup-maker' ),
									'type'         => 'rangeslider',
									'std'          => 350,
									'step'         => 10,
									'min'          => 50,
									'max'          => 1000,
									'unit'         => __( 'ms', 'popup-maker' ),
									'priority'     => 20,
									'dependencies' => [
										'animation_type' => [ 'slide', 'fade', 'fadeAndSlide', 'grow', 'growAndSlide' ],
									],
								],
								'animation_origin' => [
									'label'        => __( 'Animation Origin', 'popup-maker' ),
									'desc'         => __( 'Choose where the animation will begin.', 'popup-maker' ),
									'type'         => 'select',
									'std'          => 'center top',
									'options'      => [
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
									],
									'priority'     => 30,
									'dependencies' => [
										'animation_type' => [ 'slide', 'fadeAndSlide', 'grow', 'growAndSlide' ],
									],
								],
							],
							'sound'     => [
								'open_sound'   => [
									'label'    => __( 'Opening Sound', 'popup-maker' ),
									'desc'     => __( 'Select a sound to play when the popup opens.', 'popup-maker' ),
									'type'     => 'select',
									'std'      => 'none',
									'priority' => 10,
									'options'  => [
										'none'         => __( 'None', 'popup-maker' ),
										'beep.mp3'     => __( 'Beep', 'popup-maker' ),
										'beep-two.mp3' => __( 'Beep 2', 'popup-maker' ),
										'beep-up.mp3'  => __( 'Beep Up', 'popup-maker' ),
										'chimes.mp3'   => __( 'Chimes', 'popup-maker' ),
										'correct.mp3'  => __( 'Correct', 'popup-maker' ),
										'custom'       => __( 'Custom Sound', 'popup-maker' ),
									],
								],
								'custom_sound' => [
									'label'        => __( 'Custom Sound URL', 'popup-maker' ),
									'desc'         => __( 'Enter URL to sound file.', 'popup-maker' ),
									'type'         => 'text',
									'std'          => '',
									'priority'     => 10,
									'dependencies' => [
										'open_sound' => [ 'custom' ],
									],
								],
							],
							'position'  => [
								'location'              => [
									'label'    => __( 'Location', 'popup-maker' ),
									'desc'     => __( 'Choose where the popup will be displayed.', 'popup-maker' ),
									'type'     => 'select',
									'std'      => 'center top',
									'priority' => 10,
									'options'  => [
										'left top'      => __( 'Top Left', 'popup-maker' ),
										'center top'    => __( 'Top Center', 'popup-maker' ),
										'right top'     => __( 'Top Right', 'popup-maker' ),
										'left center'   => __( 'Middle Left', 'popup-maker' ),
										'center'        => __( 'Middle Center', 'popup-maker' ),
										'right center'  => __( 'Middle Right', 'popup-maker' ),
										'left bottom'   => __( 'Bottom Left', 'popup-maker' ),
										'center bottom' => __( 'Bottom Center', 'popup-maker' ),
										'right bottom'  => __( 'Bottom Right', 'popup-maker' ),
									],
								],
								'position_top'          => [
									'label'        => __( 'Top', 'popup-maker' ),
									'desc'         => sprintf(
										/* translators: 1. Screen Edge: top, bottom. */
										_x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ),
										strtolower( __( 'Top', 'popup-maker' ) )
									),
									'type'         => 'rangeslider',
									'std'          => 100,
									'step'         => 1,
									'min'          => 0,
									'max'          => 500,
									'unit'         => 'px',
									'priority'     => 20,
									'dependencies' => [
										'location' => [ 'left top', 'center top', 'right top' ],
									],
								],
								'position_bottom'       => [
									'label'        => __( 'Bottom', 'popup-maker' ),
									'desc'         => sprintf(
										/* translators: 1. Screen Edge: top, bottom. */
										_x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ),
										strtolower( __( 'Bottom', 'popup-maker' ) )
									),
									'type'         => 'rangeslider',
									'std'          => 0,
									'step'         => 1,
									'min'          => 0,
									'max'          => 500,
									'unit'         => 'px',
									'priority'     => 20,
									'dependencies' => [
										'location' => [ 'left bottom', 'center bottom', 'right bottom' ],
									],
								],
								'position_left'         => [
									'label'        => __( 'Left', 'popup-maker' ),
									'desc'         => sprintf(
										/* translators: 1. Screen Edge: top, bottom. */
										_x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ),
										strtolower( __( 'Left', 'popup-maker' ) )
									),
									'type'         => 'rangeslider',
									'std'          => 0,
									'step'         => 1,
									'min'          => 0,
									'max'          => 500,
									'unit'         => 'px',
									'priority'     => 30,
									'dependencies' => [
										'location' => [ 'left top', 'left center', 'left bottom' ],
									],
								],
								'position_right'        => [
									'label'        => __( 'Right', 'popup-maker' ),
									'desc'         => sprintf(
										/* translators: 1. Screen Edge: top, bottom. */
										_x( 'Distance from the %s edge of the screen.', 'Screen Edge: top, bottom', 'popup-maker' ),
										strtolower( __( 'Right', 'popup-maker' ) )
									),
									'type'         => 'rangeslider',
									'std'          => 0,
									'step'         => 1,
									'min'          => 0,
									'max'          => 500,
									'unit'         => 'px',
									'priority'     => 30,
									'dependencies' => [
										'location' => [ 'right top', 'right center', 'right bottom' ],
									],
								],
								'position_from_trigger' => [
									'label'    => __( 'Position from Trigger', 'popup-maker' ),
									'desc'     => sprintf(
										/* translators: 1. URL to documentation. 2. Closing HTML tag. */
										__( 'This will position the popup in relation to the %1$sClick Trigger%2$s.', 'popup-maker' ),
										'<a target="_blank" href="https://docs.wppopupmaker.com/article/395-trigger-click-open-overview-methods?utm_campaign=contextual-help&utm_medium=inline-doclink&utm_source=plugin-popup-editor&utm_content=position-from-trigger">',
										'</a>'
									),
									'type'     => 'checkbox',
									'std'      => false,
									'priority' => 40,
								],
								'position_fixed'        => [
									'label'    => __( 'Fixed Positioning', 'popup-maker' ),
									'desc'     => __( 'Checking this sets the positioning of the popup to fixed.', 'popup-maker' ),
									'type'     => 'checkbox',
									'priority' => 50,
								],
							],
							'advanced'  => [
								'overlay_disabled'   => [
									'label'    => __( 'Disable Overlay', 'popup-maker' ),
									'desc'     => __( 'Checking this will disable and hide the overlay for this popup.', 'popup-maker' ),
									'type'     => 'checkbox',
									'priority' => 10,
								],
								'stackable'          => [
									'label'    => __( 'Stackable', 'popup-maker' ),
									'desc'     => __( 'This enables other popups to remain open.', 'popup-maker' ),
									'type'     => 'checkbox',
									'priority' => 20,
								],
								'disable_reposition' => [
									'label'    => __( 'Disable Repositioning', 'popup-maker' ),
									'desc'     => __( 'This will disable automatic repositioning of the popup on window resizing.', 'popup-maker' ),
									'type'     => 'checkbox',
									'priority' => 30,
								],
								'zindex'             => [
									'label'    => __( 'Popup Z-Index', 'popup-maker' ),
									'desc'     => __( 'Change the z-index layer level for the popup.', 'popup-maker' ),
									'type'     => 'number',
									'min'      => 999,
									'max'      => 2147483647,
									'std'      => 1999999999,
									'priority' => 40,
								],
							],
						]
					),
					'close'     => apply_filters(
						'pum_popup_close_settings_fields',
						[
							'button'            => [
								'close_text'         => [
									'label'       => __( 'Close Text', 'popup-maker' ),
									'placeholder' => __( 'Close', 'popup-maker' ),
									'desc'        => __( 'Override the default close text. To use a Font Awesome icon instead of text, enter the CSS classes such as "fas fa-camera".', 'popup-maker' ),
									'priority'    => 10,
									'private'     => true,
								],
								'close_button_delay' => [
									'label'    => __( 'Close Button Delay', 'popup-maker' ),
									'desc'     => __( 'This delays the display of the close button.', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 0,
									'step'     => 100,
									'min'      => 0,
									'max'      => 3000,
									'unit'     => __( 'ms', 'popup-maker' ),
									'priority' => 20,
								],
							],
							'forms'             => [
								'close_on_form_submission' => [
									'label' => __( 'Close on Form Submission', 'popup-maker' ),
									'desc'  => __( 'Close the popup automatically after integrated form plugin submissions.', 'popup-maker' ),
									'type'  => 'checkbox',
								],
								'close_on_form_submission_delay' => [
									'type'         => 'rangeslider',
									'label'        => __( 'Delay', 'popup-maker' ),
									'desc'         => __( 'The delay before the popup will close after submission (in milliseconds).', 'popup-maker' ),
									'std'          => 0,
									'min'          => 0,
									'max'          => 10000,
									'step'         => 500,
									'unit'         => 'ms',
									'dependencies' => [
										'close_on_form_submission' => true,
									],
								],
							],
							'alternate_methods' => [
								'close_on_overlay_click' => [
									'label'    => __( 'Click Overlay to Close', 'popup-maker' ),
									'desc'     => __( 'Checking this will cause popup to close when user clicks on overlay.', 'popup-maker' ),
									'type'     => 'checkbox',
									'priority' => 10,
								],
								'close_on_esc_press'     => [
									'label'    => __( 'Press ESC to Close', 'popup-maker' ),
									'desc'     => __( 'Checking this will cause popup to close when user presses ESC key.', 'popup-maker' ),
									'type'     => 'checkbox',
									'priority' => 20,
								],
								'close_on_f4_press'      => [
									'label'    => __( 'Press F4 to Close', 'popup-maker' ),
									'desc'     => __( 'Checking this will cause popup to close when user presses F4 key.', 'popup-maker' ),
									'type'     => 'checkbox',
									'priority' => 30,
								],
							],
						]
					),
					'advanced'  => apply_filters(
						'pum_popup_advanced_settings_fields',
						[
							'main' => [
								'disable_form_reopen'   => [
									'label'    => __( 'Disable automatic re-triggering of popup after non-ajax form submission.', 'popup-maker' ),
									'type'     => 'checkbox',
									'priority' => 10,
								],
								'disable_accessibility' => [
									'label'    => __( 'Disable accessibility features.', 'popup-maker' ),
									'desc'     => __( 'This includes trapping the tab key & focus inside popup while open, force focus the first element when popup open, and refocus last click trigger when closed.', 'popup-maker' ),
									'type'     => 'checkbox',
									'priority' => 10,
								],
							],
						]
					),
				]
			);

			$tabs = PUM_Admin_Helpers::parse_tab_fields(
				$tabs,
				[
					'has_subtabs' => true,
					'name'        => 'popup_settings[%s]',
				]
			);
		}

		return $tabs;
	}

	public static function get_field( $id ) {
		$tabs = self::fields();

		foreach ( $tabs as $tab => $sections ) {
			if ( PUM_Admin_Helpers::is_field( $sections ) ) {
				$sections = [
					'main' => [
						$tab => $sections,
					],
				];
			}

			foreach ( $sections as $section => $fields ) {
				foreach ( $fields as $key => $args ) {
					if ( $key === $id ) {
						return $args;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Sanitizes fields after submission.
	 *
	 * Also handles pre save manipulations for some field types (measure/license).
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public static function sanitize_settings( $settings = [] ) {

		$fields = self::fields();
		$fields = PUM_Admin_Helpers::flatten_fields_array( $fields );

		foreach ( $fields as $field_id => $field ) {
			switch ( $field['type'] ) {
				case 'checkbox':
					if ( ! isset( $settings[ $field_id ] ) ) {
						$settings[ $field_id ] = false;
					}
					break;
			}
		}

		foreach ( $settings as $key => $value ) {
			$field = self::get_field( $key );

			if ( $field ) {

				// Sanitize every string value.
				if ( is_string( $value ) ) {
					$settings[ $key ] = sanitize_text_field( $value );
				}

				switch ( $field['type'] ) {
					default:
						$settings[ $key ] = is_string( $value ) ? trim( $value ) : $value;
						break;

					case 'measure':
						$settings[ $key ] .= $settings[ $key . '_unit' ];
						break;
				}
			} else {
				// Some custom field types include multiple additional fields that do not need to be saved, strip out any non-whitelisted fields.
				unset( $settings[ $key ] );
			}
		}

		return $settings;
	}

	/**
	 * @return array
	 */
	public static function defaults() {
		$tabs = self::fields();

		$defaults = [];

		foreach ( $tabs as $tab_id => $sections ) {
			foreach ( $sections as $section_id => $fields ) {
				foreach ( $fields as $key => $field ) {
					$defaults[ $key ] = isset( $field['std'] ) ? $field['std'] : ( 'checkbox' === $field['type'] ? false : null );
				}
			}
		}

		return $defaults;
	}

	/**
	 * Fills default settings only when missing.
	 *
	 * Excludes checkbox type fields where a false value is represented by the field being unset.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public static function fill_missing_defaults( $settings = [] ) {
		$excluded_field_types = [ 'checkbox', 'multicheck' ];

		$defaults = self::defaults();
		foreach ( $defaults as $field_id => $default_value ) {
			$field = self::get_field( $field_id );
			if ( isset( $settings[ $field_id ] ) || in_array( $field['type'], $excluded_field_types, true ) ) {
				continue;
			}

			$settings[ $field_id ] = $default_value;
		}

		return $settings;
	}

	/**
	 * Display analytics metabox
	 *
	 * @return void
	 */
	public static function render_analytics_meta_box() {
		global $post;

		$popup = pum_get_popup( $post->ID );
		?>
		<div id="pum-popup-analytics" class="pum-meta-box">

			<?php do_action( 'pum_popup_analytics_metabox_before', $post->ID ); ?>

			<?php
			$opens           = $popup->get_event_count( 'open' );
			$conversions     = $popup->get_event_count( 'conversion' );
			$conversion_rate = $opens > 0 && $opens >= $conversions ? $conversions / $opens * 100 : 0;
			?>

			<div id="pum-popup-analytics" class="pum-popup-analytics">

				<table class="form-table">
					<tbody>
					<tr>
						<td><?php esc_html_e( 'Opens', 'popup-maker' ); ?></td>
						<td><?php echo esc_html( $opens ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Conversions', 'popup-maker' ); ?></td>
						<td><?php echo esc_html( $conversions ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Conversion Rate', 'popup-maker' ); ?></td>
						<td><?php echo esc_html( round( $conversion_rate, 2 ) ); ?>%</td>
					</tr>
					<tr class="separator">
						<td colspan="2">
							<label> <input type="checkbox" name="popup_reset_counts" id="popup_reset_counts" value="1" />
								<?php esc_html_e( 'Reset Counts', 'popup-maker' ); ?>
							</label>
							<?php
							$reset = $popup->get_last_count_reset();
							if ( $reset ) :
								?>
								<br />
								<small>
									<strong><?php esc_html_e( 'Last Reset', 'popup-maker' ); ?>:</strong> <?php echo esc_html( wp_date( 'm-d-Y H:i', $reset['timestamp'] ) ); ?>
									<br /> <strong><?php esc_html_e( 'Previous Opens', 'popup-maker' ); ?>:</strong> <?php echo esc_html( $reset['opens'] ); ?>

									<?php if ( $reset['conversions'] > 0 ) : ?>
										<br />
										<strong><?php esc_html_e( 'Previous Conversions', 'popup-maker' ); ?>:</strong> <?php echo esc_html( $reset['conversions'] ); ?>
									<?php endif; ?>

									<br /> <strong><?php esc_html_e( 'Lifetime Opens', 'popup-maker' ); ?>:</strong> <?php echo esc_html( $popup->get_event_count( 'open', 'total' ) ); ?>

									<?php if ( $popup->get_event_count( 'conversion', 'total' ) > 0 ) : ?>
										<br />
										<strong><?php esc_html_e( 'Lifetime Conversions', 'popup-maker' ); ?>:</strong> <?php echo esc_html( $popup->get_event_count( 'conversion', 'total' ) ); ?>
									<?php endif; ?>
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
	public static function sanitize_meta( $meta = [] ) {
		if ( ! empty( $meta ) ) {
			foreach ( $meta as $key => $value ) {
				if ( is_array( $value ) ) {
					$meta[ $key ] = self::sanitize_meta( $value );
				} elseif ( is_string( $value ) ) {
					try {
						$value = json_decode( stripslashes( $value ) );
						if ( is_object( $value ) || is_array( $value ) ) {
							$meta[ $key ] = PUM_Admin_Helpers::object_to_array( $value );
						}
					} catch ( Exception $e ) {
						$e;
					}
				}
			}
		}

		return $meta;
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
		if ( 'popup' === $data['post_type'] ) {
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
		wp_enqueue_style( 'pum-admin-general' );
		$columns = [
			'cb'              => '<input type="checkbox"/>',
			'title'           => __( 'Name', 'popup-maker' ),
			'enabled'         => __( 'Enabled', 'popup-maker' ),
			'popup_title'     => __( 'Title', 'popup-maker' ),
			'class'           => __( 'CSS Class', 'popup-maker' ),
			'opens'           => __( 'Opens', 'popup-maker' ),
			'conversions'     => __( 'Conversions', 'popup-maker' ),
			'conversion_rate' => __( 'Conversion Rate', 'popup-maker' ),
		];

		// Add the date column preventing our own translation.
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
	 * @param int    $post_id     (Post) ID
	 */
	public static function render_columns( $column_name, $post_id ) {
		$post = get_post( $post_id );
		if ( 'popup' === $post->post_type ) {
			$popup = pum_get_popup( $post_id );

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
				case 'enabled':
					if ( 'publish' === $post->post_status ) {
						$enabled = $popup->is_enabled();
						$nonce   = wp_create_nonce( 'pum_save_enabled_state' );
						?>
						<div class="pum-toggle-button">
							<input id="pum-enabled-toggle-<?php echo esc_attr( $popup->ID ); ?>" type="checkbox" <?php checked( true, $enabled ); ?> class="pum-enabled-toggle-button" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-popup-id="<?php echo esc_attr( $popup->ID ); ?>">
							<label for="pum-enabled-toggle-<?php echo esc_attr( $popup->ID ); ?>" aria-label="Switch to enable popup"></label>
						</div>
						<?php
					} else {
						?>
						<p>Popup not published</p>
						<?php
					}
					break;
				case 'popup_category':
					echo get_the_term_list( $post_id, 'popup_category', '', ', ', '' );
					break;
				case 'popup_tag':
					echo get_the_term_list( $post_id, 'popup_tag', '', ', ', '' );
					break;
				case 'class':
					echo '<pre style="display:inline-block;margin:0;"><code>popmake-' . absint( $post_id ) . '</code></pre>';
					break;
				case 'opens':
					if ( ! pum_extension_enabled( 'popup-analytics' ) ) {
						echo esc_html( $popup->get_event_count( 'open' ) );
					}
					break;
				case 'conversions':
					if ( ! pum_extension_enabled( 'popup-analytics' ) ) {
						echo esc_html( $popup->get_event_count( 'conversion' ) );
					}
					break;
				case 'conversion_rate':
					if ( ! pum_extension_enabled( 'popup-analytics' ) ) {
						$opens       = $popup->get_event_count( 'open' );
						$conversions = $popup->get_event_count( 'conversion' );

						if ( $opens > 0 && $opens >= $conversions ) {
							$conversion_rate = round( $conversions / $opens * 100, 2 );
						} else {
							$conversion_rate = 0;
						}
						echo esc_html( $conversion_rate . '%' );
					}
					break;
			}
		}
	}

	/**
	 * Hide some of our columns by default
	 *
	 * @param array     $hidden Array of IDs of columns hidden by default.
	 * @param WP_Screen $screen WP_Screen object of the current screen.
	 * @return array Updated $hidden
	 */
	public static function hide_columns( $hidden, $screen ) {
		if ( isset( $screen->id ) && 'edit-popup' === $screen->id ) {
			$hidden[] = 'popup_title';
			$hidden[] = 'date';
		}
		return $hidden;
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
		if ( isset( $vars['post_type'] ) && 'popup' === $vars['post_type'] ) {
			// Check if 'orderby' is set to "name"
			if ( isset( $vars['orderby'] ) ) {
				switch ( $vars['orderby'] ) {
					case 'popup_title':
						$vars = array_merge(
							$vars,
							[
								// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
								'meta_key' => 'popup_title',
								'orderby'  => 'meta_value',
							]
						);
						break;
					case 'opens':
						if ( ! pum_extension_enabled( 'popup-analytics' ) ) {
							$vars = array_merge(
								$vars,
								[
									// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
									'meta_key' => 'popup_open_count',
									'orderby'  => 'meta_value_num',
								]
							);
						}
						break;
					case 'conversions':
						if ( ! pum_extension_enabled( 'popup-analytics' ) ) {
							$vars = array_merge(
								$vars,
								[
									// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
									'meta_key' => 'popup_conversion_count',
									'orderby'  => 'meta_value_num',
								]
							);
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
		add_filter( 'request', [ __CLASS__, 'sort_columns' ] );
	}

	/**
	 * Add Popup Filters
	 *
	 * Adds taxonomy drop down filters for popups.
	 */
	public static function add_popup_filters() {
		global $typenow;

		// Checks if the current post type is 'popup'
		if ( 'popup' === $typenow ) {
			if ( get_taxonomy( 'popup_category' ) ) {
				$terms = get_terms( 'popup_category' );

				if ( count( $terms ) > 0 ) {
					$category = '';

					if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'pum-popup-filter-nonce' ) ) {
						$category = isset( $_GET['popup_category'] ) ? sanitize_key( wp_unslash( $_GET['popup_category'] ) ) : '';
					}

					echo "<select name='popup_category' id='popup_category' class='postform'>";
					echo "<option value=''>" . esc_html__( 'Show all categories', 'popup-maker' ) . '</option>';
					foreach ( $terms as $term ) {
						$selected = $category === $term->slug ? 'selected="selected"' : '';
						echo '<option value="' . esc_attr( $term->slug ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $term->name ) . ' (' . esc_html( $term->count ) . ')</option>';
					}
					echo '</select>';
					wp_nonce_field( 'pum-popup-filter-nonce' );
				}
			}

			if ( get_taxonomy( 'popup_tag' ) ) {
				$terms = get_terms( 'popup_tag' );

				if ( count( $terms ) > 0 ) {
					$tag = '';

					if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'pum-popup-filter-nonce' ) ) {
						$tag = isset( $_GET['popup_tag'] ) ? sanitize_key( wp_unslash( $_GET['popup_tag'] ) ) : '';
					}

					echo "<select name='popup_tag' id='popup_tag' class='postform'>";
					echo "<option value=''>" . esc_html__( 'Show all tags', 'popup-maker' ) . '</option>';
					foreach ( $terms as $term ) {
						$selected = $tag === $term->slug ? 'selected="selected"' : '';
						echo '<option value="' . esc_attr( $term->slug ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $term->name ) . ' (' . esc_html( $term->count ) . ')</option>';
					}
					echo '</select>';
					wp_nonce_field( 'pum-popup-filter-nonce' );
				}
			}
		}
	}

	/**
	 * Prepends Popup ID to the action row on All Popups
	 *
	 * @param array         $actions The row actions.
	 * @param $post The post
	 *
	 * @return array The new actions.
	 */
	public static function add_id_row_actions( $actions, $post ) {
		// Only adjust if we are dealing with our popups.
		if ( 'popup' === $post->post_type ) {
			return array_merge( [ 'id' => 'ID: ' . $post->ID ], $actions );
		}

		return $actions;
	}

	/**
	 * Add font size and font select buttons to the editor.
	 *
	 * @param array $buttons The array of buttons.
	 *
	 * @return array
	 */
	public static function add_mce_buttons( $buttons ) {
		if ( ! pum_is_popup_editor() ) {
			return $buttons;
		}

		array_unshift( $buttons, 'fontselect' );
		array_unshift( $buttons, 'fontsizeselect' );

		return $buttons;
	}

	/**
	 * Increase the available font sizes.
	 *
	 * @param array $init_array The TinyMCE init array.
	 *
	 * @return array
	 */
	public static function increase_available_font_sizes( $init_array ) {
		$init_array['fontsize_formats'] = '9px 10px 12px 13px 14px 16px 18px 21px 24px 28px 32px 36px 42px 48px 54px 60px 66px 72px 80px 90px';
		return $init_array;
	}
}
