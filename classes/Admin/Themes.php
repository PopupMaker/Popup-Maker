<?php
/**
 * Class for Admin Themes
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Admin_Themes
 */
class PUM_Admin_Themes {

	/**
	 * Hook the initialize method to the WP init action.
	 */
	public static function init() {
		/** Regitster Metaboxes */
		add_action( 'add_meta_boxes', [ __CLASS__, 'meta_box' ] );

		/** Process meta saving. */
		add_action( 'save_post', [ __CLASS__, 'save' ], 10, 2 );
	}

	/**
	 * Registers popup metaboxes.
	 */
	public static function meta_box() {
		/** Settings Box */
		add_meta_box( 'pum_theme_settings', __( 'Theme Settings', 'popup-maker' ), [ __CLASS__, 'render_settings_meta_box' ], 'popup_theme', 'normal', 'high' );

		/** Preview Window */
		add_meta_box( 'pum_theme_preview', __( 'Theme Preview', 'popup-maker' ), [ __CLASS__, 'render_preview_meta_box' ], 'popup_theme', 'side', 'high' );
	}

	/**
	 * Ensures integrity of values.
	 *
	 * @param array $values
	 *
	 * @return array
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
			$field = PUM_Utils_Fields::get_field( self::fields(), $field_id );
			if ( isset( $settings[ $field_id ] ) || in_array( $field['type'], $excluded_field_types ) ) {
				continue;
			}

			$settings[ $field_id ] = $default_value;
		}

		return $settings;
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
			$field = PUM_Utils_Fields::get_field( self::fields(), $key );

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
	 * Render the settings meta box wrapper and JS vars.
	 */
	public static function render_settings_meta_box() {
		global $post;

		$theme = pum_get_theme( $post->ID );

		// Get the meta directly rather than from cached object.
		$settings = self::parse_values( $theme->get_settings() );

		wp_nonce_field( basename( __FILE__ ), 'pum_theme_settings_nonce' );
		wp_enqueue_script( 'popup-maker-admin' );
		?>
		<script type="text/javascript">
			window.pum_theme_settings_editor = 
			<?php
			echo PUM_Utils_Array::safe_json_encode(
				apply_filters(
					'pum_theme_settings_editor_var',
					[
						'form_args'      => [
							'id'       => 'pum-theme-settings',
							'tabs'     => self::tabs(),
							'sections' => self::sections(),
							'fields'   => self::fields(),
						],
						'current_values' => self::render_form_values( $settings ),
					]
				)
			);
			?>
			;
		</script>

		<div id="pum-theme-settings-container" class="pum-theme-settings-container">
			<div class="pum-no-js" style="padding: 0 12px;">
				<p><?php printf( __( 'If you are seeing this, the page is still loading or there are Javascript errors on this page. %1$sView troubleshooting guide%2$s', 'popup-maker' ), '<a href="https://docs.wppopupmaker.com/article/373-checking-for-javascript-errors" target="_blank">', '</a>' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 *
	 */
	public static function render_preview_meta_box() {
		global $post;

		$theme = pum_get_theme( $post->ID );

		$deprecated_atb_enabled = class_exists( 'PUM_ATB' ) && ! pum_extension_enabled( 'advanced-theme-builder' );

		// Remove this div after PUM ATC updated properly
		if ( $deprecated_atb_enabled ) {
			echo '<div id="PopMake-Preview">';
		}

		?>

		<div class="pum-theme-preview">
			<div class="pum-popup-overlay <?php echo $deprecated_atb_enabled ? 'example-popup-overlay' : ''; ?>"></div>
			<div class="pum-popup-container <?php echo $deprecated_atb_enabled ? 'example-popup' : ''; ?>">
				<div class="pum-popup-title"><?php _e( 'Title Text', 'popup-maker' ); ?></div>
				<div class="pum-popup-content">
					<?php echo esc_html( apply_filters( 'pum_example_popup_content', '<p>Suspendisse ipsum eros, tincidunt sed commodo ut, viverra vitae ipsum. Etiam non porta neque. Pellentesque nulla elit, aliquam in ullamcorper at, bibendum sed eros. Morbi non sapien tellus, ac vestibulum eros. In hac habitasse platea dictumst. Nulla vestibulum, diam vel porttitor placerat, eros tortor ultrices lectus, eget faucibus arcu justo eget massa. Maecenas id tellus vitae justo posuere hendrerit aliquet ut dolor.</p>' ) ); ?>
				</div>
				<button type="button" class="pum-popup-close <?php echo $deprecated_atb_enabled ? 'close-popup' : ''; ?>" aria-label="<?php _e( 'Close', 'popup-maker' ); ?>">
					<?php echo esc_html( $theme->get_setting( 'close_text', '&#215;' ) ); ?>
				</button>
			</div>
			<p class="pum-desc">
			<?php
				$tips = [
					__( 'If you move this theme preview to the bottom of your sidebar here it will follow you down the page?', 'popup-maker' ),
					__( 'Clicking on an element in this theme preview will take you to its relevant settings in the editor?', 'popup-maker' ),
				];
				$key  = array_rand( $tips, 1 );
				?>
				<i class="dashicons dashicons-info"></i> <?php echo esc_html( '<strong>' . __( 'Did you know:', 'popup-maker' ) . '</strong>  ' . $tips[ $key ] ); ?>
			</p>
		</div>

		<?php
		// Remove this div after PUM ATC updated properly
		if ( $deprecated_atb_enabled ) {
			echo '</div>';
		}
	}

	/**
	 * Used to get deprecated fields for metabox saving of old extensions.
	 *
	 * @deprecated 1.8.0
	 *
	 * @return mixed
	 */
	public static function deprecated_meta_fields() {
		$fields = [];
		foreach ( self::deprecated_meta_field_groups() as $group ) {
			foreach ( apply_filters( 'popmake_popup_theme_meta_field_group_' . $group, [] ) as $field ) {
				$fields[] = 'popup_theme_' . $group . '_' . $field;
			}
		}

		return apply_filters( 'popmake_popup_theme_meta_fields', $fields );
	}

	/**
	 * Used to get field groups from extensions.
	 *
	 * @deprecated 1.8.0
	 *
	 * @return mixed
	 */
	public static function deprecated_meta_field_groups() {
		return apply_filters( 'popmake_popup_theme_meta_field_groups', [ 'display', 'close' ] );
	}

	/**
	 * @param $post_id
	 * @param $post
	 *
	 * @return bool
	 */
	public static function can_save( $post_id, $post ) {
		if ( isset( $post->post_type ) && 'popup_theme' !== $post->post_type ) {
			return false;
		}

		if ( ! isset( $_POST['pum_theme_settings_nonce'] ) || ! wp_verify_nonce( $_POST['pum_theme_settings_nonce'], basename( __FILE__ ) ) ) {
			return false;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
			return false;
		}

		if ( isset( $post->post_type ) && 'revision' === $post->post_type ) {
			return false;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param $post_id
	 * @param $post
	 */
	public static function save( $post_id, $post ) {

		if ( ! self::can_save( $post_id, $post ) ) {
			return;
		}

		$theme = pum_get_theme( $post_id );

		$settings = ! empty( $_POST['theme_settings'] ) ? $_POST['theme_settings'] : [];

		$settings = wp_parse_args( $settings, self::defaults() );

		$settings = apply_filters( 'pum_theme_setting_pre_save', $settings, $post->ID );

		// Sanitize form values.
		$settings = PUM_Utils_Fields::sanitize_fields( $settings, self::fields() );

		// Ensure data integrity.
		$settings = self::parse_values( $settings );

		// $theme->update_meta( 'popup_theme_settings', $settings );
		$theme->update_settings( $settings );

		// If this is a built in theme and the user has modified it set a key so that we know not to make automatic upgrades to it in the future.
		if ( get_post_meta( $post_id, '_pum_built_in', true ) !== false ) {
			update_post_meta( $post_id, '_pum_user_modified', true );
		}

		self::process_deprecated_saves( $post_id, $post );

		do_action( 'pum_save_theme', $post_id, $post );
	}

	/**
	 * @param $post_id
	 * @param $post
	 */
	public static function process_deprecated_saves( $post_id, $post ) {

		$field_prefix = 'popup_theme_';

		$old_fields = (array) apply_filters(
			'popmake_popup_theme_fields',
			[
				'overlay'   => [],
				'container' => [],
				'title'     => [],
				'content'   => [],
				'close'     => [],
			]
		);

		foreach ( $old_fields as $section => $fields ) {
			$section_prefix = "{$field_prefix}{$section}";
			$meta_values    = [];

			foreach ( $fields as $field => $args ) {
				$field_name = "{$section_prefix}_{$field}";
				if ( isset( $_POST[ $field_name ] ) ) {
					$meta_values[ $field ] = apply_filters( 'popmake_metabox_save_' . $field_name, $_POST[ $field_name ] );
				}
			}

			update_post_meta( $post_id, "popup_theme_{$section}", $meta_values );
		}

		// TODO Remove this and all other code here. This should be clean and all code more compartmentalized.
		foreach ( self::deprecated_meta_fields() as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$new = apply_filters( 'popmake_metabox_save_' . $field, $_POST[ $field ] );
				update_post_meta( $post_id, $field, $new );
			} else {
				delete_post_meta( $post_id, $field );
			}
		}
	}

	/**
	 * List of tabs & labels for the settings panel.
	 *
	 * @return array
	 */
	public static function tabs() {
		return apply_filters(
			'pum_theme_settings_tabs',
			[
				'general'   => __( 'General', 'popup-maker' ),
				'overlay'   => __( 'Overlay', 'popup-maker' ),
				'container' => __( 'Container', 'popup-maker' ),
				'title'     => __( 'Title', 'popup-maker' ),
				'content'   => __( 'Content', 'popup-maker' ),
				'close'     => __( 'Close', 'popup-maker' ),
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
			'pum_theme_settings_sections',
			[
				'general'   => [
					'main' => __( 'General', 'popup-maker' ),
				],
				'overlay'   => [
					'background' => __( 'Background', 'popup-maker' ),
				],
				'container' => [
					'main'       => __( 'Container', 'popup-maker' ),
					'background' => __( 'Background', 'popup-maker' ),
					'border'     => __( 'Border', 'popup-maker' ),
					'boxshadow'  => __( 'Drop Shadow', 'popup-maker' ),
				],
				'title'     => [
					'typography' => __( 'Font', 'popup-maker' ),
					'textshadow' => __( 'Text Shadow', 'popup-maker' ),
				],
				'content'   => [
					'typography' => __( 'Text', 'popup-maker' ),
				],
				'close'     => [
					'main'       => __( 'General', 'popup-maker' ),
					'size'       => __( 'Size', 'popup-maker' ),
					'position'   => __( 'Position', 'popup-maker' ),
					'background' => __( 'Background', 'popup-maker' ),
					'border'     => __( 'Border', 'popup-maker' ),
					'boxshadow'  => __( 'Drop Shadow', 'popup-maker' ),
					'typography' => __( 'Font', 'popup-maker' ),
					'textshadow' => __( 'Text Shadow', 'popup-maker' ),
				],
				'advanced'  => [
					'main' => __( 'Advanced', 'popup-maker' ),
				],
			]
		);
	}

	/**
	 * @return mixed
	 */
	public static function border_style_options() {
		return apply_filters(
			'pum_theme_border_style_options',
			[
				'none'   => __( 'None', 'popup-maker' ),
				'solid'  => __( 'Solid', 'popup-maker' ),
				'dotted' => __( 'Dotted', 'popup-maker' ),
				'dashed' => __( 'Dashed', 'popup-maker' ),
				'double' => __( 'Double', 'popup-maker' ),
				'groove' => __( 'Groove', 'popup-maker' ),
				'inset'  => __( 'Inset (inner shadow)', 'popup-maker' ),
				'outset' => __( 'Outset', 'popup-maker' ),
				'ridge'  => __( 'Ridge', 'popup-maker' ),
			]
		);
	}

	/**
	 * @return mixed
	 */
	public static function size_unit_options() {
		return apply_filters(
			'pum_theme_size_unit_options',
			[
				'px'  => 'px',
				'%'   => '%',
				'em'  => 'em',
				'rem' => 'rem',
			]
		);
	}

	/**
	 * @return mixed
	 */
	public static function font_family_options() {
		$fonts = [
			'inherit'                           => __( 'Use Your Themes', 'popup-maker' ),
			__( 'System Fonts', 'popup-maker' ) => [
				'Sans-Serif'      => 'Sans-Serif',
				'Tahoma'          => 'Tahoma',
				'Georgia'         => 'Georgia',
				'Comic Sans MS'   => 'Comic Sans MS',
				'Arial'           => 'Arial',
				'Lucida Grande'   => 'Lucida Grande',
				'Times New Roman' => 'Times New Roman',
			],
		];

		/** @deprecated 1.8.0 This filter is no longer in use */
		$old_fonts = apply_filters( 'popmake_font_family_options', [] );

		$fonts = array_merge( $fonts, array_flip( $old_fonts ) );

		return apply_filters( 'pum_theme_font_family_options', $fonts );
	}

	/**
	 * @return mixed
	 */
	public static function font_weight_options() {
		return apply_filters(
			'pum_theme_font_weight_options',
			[
				100 => 100,
				200 => 200,
				300 => 300,
				400 => __( 'Normal', 'popup-maker' ) . ' (400)',
				500 => 500,
				600 => 600,
				700 => __( 'Bold', 'popup-maker' ) . ' (700)',
				800 => 800,
				900 => 900,
			]
		);
	}

	/**
	 * Returns array of popup settings fields.
	 *
	 * @return mixed
	 */
	public static function fields() {
		static $fields;

		if ( ! isset( $fields ) ) {
			$size_unit_options    = self::size_unit_options();
			$border_style_options = self::border_style_options();
			$font_family_options  = self::font_family_options();
			$font_weight_options  = self::font_weight_options();

			$fields = apply_filters(
				'pum_theme_settings_fields',
				[
					'general'   => apply_filters(
						'pum_theme_general_settings_fields',
						[
							'main' => [],
						]
					),
					'overlay'   => apply_filters(
						'pum_theme_overlay_settings_fields',
						[
							'background' => [
								'overlay_background_color' => [
									'label'    => __( 'Color', 'popup-maker' ),
									'type'     => 'color',
									'std'      => '#ffffff',
									'priority' => 10,
								],
								'overlay_background_opacity' => [
									'label'        => __( 'Opacity', 'popup-maker' ),
									'type'         => 'rangeslider',
									'force_minmax' => true,
									'std'          => 100,
									'step'         => 1,
									'min'          => 0,
									'max'          => 100,
									'unit'         => '%',
									'priority'     => 20,
								],
							],
						]
					),
					'container' => apply_filters(
						'pum_theme_container_settings_fields',
						[
							'main'       => [
								'container_padding'       => [
									'label'    => __( 'Padding', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 18,
									'priority' => 10,
									'step'     => 1,
									'min'      => 1,
									'max'      => 100,
									'unit'     => 'px',
								],
								'container_border_radius' => [
									'label'    => __( 'Border Radius', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 0,
									'priority' => 20,
									'step'     => 1,
									'min'      => 1,
									'max'      => 80,
									'unit'     => 'px',
								],
							],
							'background' => [
								'container_background_color'   => [
									'label'    => __( 'Color', 'popup-maker' ),
									'type'     => 'color',
									'std'      => '#f9f9f9',
									'priority' => 10,
								],
								'container_background_opacity' => [
									'label'        => __( 'Opacity', 'popup-maker' ),
									'type'         => 'rangeslider',
									'force_minmax' => true,
									'std'          => 100,
									'priority'     => 20,
									'step'         => 1,
									'min'          => 0,
									'max'          => 100,
									'unit'         => '%',
								],
							],
							'border'     => [
								'container_border_style' => [
									'label'    => __( 'Style', 'popup-maker' ),
									'type'     => 'select',
									'std'      => 'none',
									'priority' => 10,
									'options'  => $border_style_options,
								],
								'container_border_color' => [
									'label'        => __( 'Color', 'popup-maker' ),
									'type'         => 'color',
									'std'          => '#000000',
									'priority'     => 20,
									'dependencies' => [
										'container_border_style' => array_keys( PUM_Utils_Array::remove_keys( $border_style_options, [ 'none' ] ) ),
									],
								],
								'container_border_width' => [
									'label'        => __( 'Thickness', 'popup-maker' ),
									'type'         => 'rangeslider',
									'std'          => 1,
									'priority'     => 30,
									'step'         => 1,
									'min'          => 1,
									'max'          => 5,
									'unit'         => 'px',
									'dependencies' => [
										'container_border_style' => array_keys( PUM_Utils_Array::remove_keys( $border_style_options, [ 'none' ] ) ),
									],
								],
							],
							'boxshadow'  => [
								'container_boxshadow_color' => [
									'label'    => __( 'Color', 'popup-maker' ),
									'type'     => 'color',
									'std'      => '#020202',
									'priority' => 10,
								],
								'container_boxshadow_opacity' => [
									'label'        => __( 'Opacity', 'popup-maker' ),
									'type'         => 'rangeslider',
									'std'          => 23,
									'priority'     => 20,
									'step'         => 1,
									'min'          => 0,
									'max'          => 100,
									'force_minmax' => true,
									'unit'         => '%',
								],
								'container_boxshadow_horizontal' => [
									'label'    => __( 'Horizontal Position', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 1,
									'priority' => 30,
									'step'     => 1,
									'min'      => - 50,
									'max'      => 50,
									'unit'     => 'px',
								],
								'container_boxshadow_vertical' => [
									'label'    => __( 'Vertical Position', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 1,
									'priority' => 40,
									'step'     => 1,
									'min'      => - 50,
									'max'      => 50,
									'unit'     => 'px',
								],
								'container_boxshadow_blur' => [
									'label'    => __( 'Blur Radius', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 3,
									'priority' => 50,
									'step'     => 1,
									'min'      => 0,
									'max'      => 100,
									'unit'     => 'px',
								],
								'container_boxshadow_spread' => [
									'label'    => __( 'Spread', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 0,
									'priority' => 60,
									'step'     => 1,
									'min'      => - 100,
									'max'      => 100,
									'unit'     => 'px',
								],
								'container_boxshadow_inset' => [
									'label'    => __( 'Inset (inner shadow)', 'popup-maker' ),
									'type'     => 'select',
									'std'      => 'no',
									'priority' => 70,
									'options'  => [
										'no'  => __( 'No', 'popup-maker' ),
										'yes' => __( 'Yes', 'popup-maker' ),
									],
								],
							],
						]
					),
					'title'     => apply_filters(
						'pum_theme_title_settings_fields',
						[
							'typography' => [
								'title_font_color'  => [
									'label'    => __( 'Color', 'popup-maker' ),
									'type'     => 'color',
									'std'      => '#000000',
									'priority' => 10,
								],
								'title_font_size'   => [
									'label'    => __( 'Font Size', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 32,
									'priority' => 20,
									'step'     => 1,
									'min'      => 8,
									'max'      => 48,
									'unit'     => 'px',
								],
								'title_line_height' => [
									'label'    => __( 'Line Height', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 36,
									'priority' => 30,
									'step'     => 1,
									'min'      => 8,
									'max'      => 54,
									'unit'     => 'px',
								],
								'title_font_family' => [
									'label'    => __( 'Font Family', 'popup-maker' ),
									'type'     => 'select',
									'select2'  => true,
									'std'      => 'inherit',
									'priority' => 40,
									'options'  => $font_family_options,
								],
								'title_font_weight' => [
									'label'    => __( 'Font Weight', 'popup-maker' ),
									'type'     => 'select',
									'std'      => 400,
									'priority' => 50,
									'options'  => $font_weight_options,
								],
								'title_font_style'  => [
									'label'    => __( 'Style', 'popup-maker' ),
									'type'     => 'select',
									'std'      => 'normal',
									'priority' => 60,
									'options'  => [
										''       => __( 'Normal', 'popup-maker' ),
										'italic' => __( 'Italic', 'popup-maker' ),
									],
								],
								'title_text_align'  => [
									'label'    => __( 'Alignment', 'popup-maker' ),
									'type'     => 'select',
									'std'      => 'left',
									'priority' => 70,
									'options'  => [
										'left'    => __( 'Left', 'popup-maker' ),
										'center'  => __( 'Center', 'popup-maker' ),
										'right'   => __( 'Right', 'popup-maker' ),
										'justify' => __( 'Justify', 'popup-maker' ),
									],
								],
							],
							'textshadow' => [
								'title_textshadow_color'   => [
									'label'    => __( 'Color', 'popup-maker' ),
									'type'     => 'color',
									'std'      => '#020202',
									'priority' => 10,
								],
								'title_textshadow_opacity' => [
									'label'        => __( 'Opacity', 'popup-maker' ),
									'type'         => 'rangeslider',
									'std'          => 23,
									'priority'     => 20,
									'step'         => 1,
									'min'          => 0,
									'max'          => 100,
									'force_minmax' => true,
									'unit'         => '%',
								],
								'title_textshadow_horizontal' => [
									'label'    => __( 'Horizontal Position', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 0,
									'priority' => 30,
									'step'     => 1,
									'min'      => - 50,
									'max'      => 50,
									'unit'     => 'px',
								],
								'title_textshadow_vertical' => [
									'label'    => __( 'Vertical Position', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 0,
									'priority' => 40,
									'step'     => 1,
									'min'      => - 50,
									'max'      => 50,
									'unit'     => 'px',
								],
								'title_textshadow_blur'    => [
									'label'    => __( 'Blur Radius', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 0,
									'priority' => 50,
									'step'     => 1,
									'min'      => 0,
									'max'      => 100,
									'unit'     => 'px',
								],
							],
						]
					),
					'content'   => apply_filters(
						'pum_theme_content_settings_fields',
						[
							'typography' => [
								'content_font_color'  => [
									'label'    => __( 'Color', 'popup-maker' ),
									'type'     => 'color',
									'std'      => '#8c8c8c',
									'priority' => 10,
								],
								'content_font_family' => [
									'label'    => __( 'Font Family', 'popup-maker' ),
									'type'     => 'select',
									'select2'  => true,
									'std'      => 'inherit',
									'priority' => 20,
									'options'  => $font_family_options,
								],
								'content_font_weight' => [
									'label'    => __( 'Font Weight', 'popup-maker' ),
									'type'     => 'select',
									'std'      => 400,
									'priority' => 30,
									'options'  => $font_weight_options,
								],
								'content_font_style'  => [
									'label'    => __( 'Style', 'popup-maker' ),
									'type'     => 'select',
									'std'      => 'inherit',
									'priority' => 40,
									'options'  => [
										''       => __( 'Normal', 'popup-maker' ),
										'italic' => __( 'Italic', 'popup-maker' ),
									],
								],
							],
						]
					),
					'close'     => apply_filters(
						'pum_theme_close_settings_fields',
						[
							'main'       => [
								'close_text'             => [
									'label'       => __( 'Close Button Text', 'popup-maker' ),
									'desc'        => __( 'To use a Font Awesome icon instead of text, enter the CSS classes such as "fas fa-camera".', 'popup-maker' ),
									'placeholder' => __( 'CLOSE', 'popup-maker' ),
									'std'         => __( 'CLOSE', 'popup-maker' ),
									'priority'    => 10,
								],
								'close_position_outside' => [
									'label'    => __( 'Position Outside Container', 'popup-maker' ),
									'desc'     => __( 'This moves the position of the close button outside the popup.', 'popup-maker' ),
									'type'     => 'checkbox',
									'priority' => 20,
								],
								'close_location'         => [
									'label'    => __( 'Location', 'popup-maker' ),
									'type'     => 'select',
									'std'      => 'topright',
									'priority' => 30,
									'options'  => [
										'topleft'      => __( 'Top Left', 'popup-maker' ),
										'topcenter'    => __( 'Top Center', 'popup-maker' ),
										'topright'     => __( 'Top Right', 'popup-maker' ),
										'middleleft'   => __( 'Middle Left', 'popup-maker' ),
										'middleright'  => __( 'Middle Right', 'popup-maker' ),
										'bottomleft'   => __( 'Bottom Left', 'popup-maker' ),
										'bottomcenter' => __( 'Bottom Center', 'popup-maker' ),
										'bottomright'  => __( 'Bottom Right', 'popup-maker' ),
									],
								],
								'close_position_top'     => [
									'label'        => __( 'Top', 'popup-maker' ),
									'type'         => 'rangeslider',
									'std'          => 0,
									'priority'     => 40,
									'step'         => 1,
									'min'          => - 100,
									'max'          => 100,
									'unit'         => 'px',
									'dependencies' => [
										'close_location' => [ 'topleft', 'topcenter', 'topright' ],
									],
								],

								'close_position_bottom'  => [
									'label'        => __( 'Bottom', 'popup-maker' ),
									'type'         => 'rangeslider',
									'std'          => 0,
									'priority'     => 50,
									'step'         => 1,
									'min'          => - 100,
									'max'          => 100,
									'unit'         => 'px',
									'dependencies' => [
										'close_location' => [ 'bottomleft', 'bottomcenter', 'bottomright' ],
									],
								],
								'close_position_left'    => [
									'label'        => __( 'Left', 'popup-maker' ),
									'type'         => 'rangeslider',
									'std'          => 0,
									'priority'     => 60,
									'step'         => 1,
									'min'          => - 100,
									'max'          => 100,
									'unit'         => 'px',
									'dependencies' => [
										'close_location' => [ 'topleft', 'middleleft', 'bottomleft' ],
									],
								],
								'close_position_right'   => [
									'label'        => __( 'Right', 'popup-maker' ),
									'type'         => 'rangeslider',
									'std'          => 0,
									'priority'     => 70,
									'step'         => 1,
									'min'          => - 100,
									'max'          => 100,
									'unit'         => 'px',
									'dependencies' => [
										'close_location' => [ 'topright', 'middleright', 'bottomright' ],
									],
								],

							],
							'size'       => [
								'close_padding'       => [
									'label'    => __( 'Padding', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 8,
									'priority' => 10,
									'step'     => 1,
									'min'      => 0,
									'max'      => 100,
									'unit'     => 'px',
								],
								'close_height'        => [
									'label'    => __( 'Height', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 0,
									'priority' => 20,
									'step'     => 1,
									'min'      => 0,
									'max'      => 100,
									'unit'     => 'px',
								],
								'close_width'         => [
									'label'    => __( 'Width', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 0,
									'priority' => 30,
									'step'     => 1,
									'min'      => 0,
									'max'      => 100,
									'unit'     => 'px',
								],
								'close_border_radius' => [
									'label'    => __( 'Border Radius', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 0,
									'priority' => 40,
									'step'     => 1,
									'min'      => 1,
									'max'      => 28,
									'unit'     => 'px',
								],
							],
							'background' => [
								'close_background_color'   => [
									'label'    => __( 'Color', 'popup-maker' ),
									'type'     => 'color',
									'std'      => '#00b7cd',
									'priority' => 10,
								],
								'close_background_opacity' => [
									'label'        => __( 'Opacity', 'popup-maker' ),
									'type'         => 'rangeslider',
									'std'          => 100,
									'priority'     => 20,
									'step'         => 1,
									'min'          => 0,
									'max'          => 100,
									'unit'         => '%',
									'force_minmax' => true,
								],
							],
							'typography' => [
								'close_font_color'  => [
									'label'    => __( 'Color', 'popup-maker' ),
									'type'     => 'color',
									'std'      => '#ffffff',
									'priority' => 10,
								],
								'close_font_size'   => [
									'label'    => __( 'Font Size', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 12,
									'priority' => 20,
									'step'     => 1,
									'min'      => 8,
									'max'      => 32,
									'unit'     => 'px',
								],
								'close_line_height' => [
									'label'    => __( 'Line Height', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 36,
									'priority' => 30,
									'step'     => 1,
									'min'      => 8,
									'max'      => 54,
									'unit'     => 'px',
								],
								'close_font_family' => [
									'label'    => __( 'Font Family', 'popup-maker' ),
									'type'     => 'select',
									'select2'  => true,
									'std'      => 'inherit',
									'priority' => 40,
									'options'  => $font_family_options,
								],
								'close_font_weight' => [
									'label'    => __( 'Font Weight', 'popup-maker' ),
									'type'     => 'select',
									'std'      => 400,
									'priority' => 50,
									'options'  => $font_weight_options,
								],
								'close_font_style'  => [
									'label'    => __( 'Style', 'popup-maker' ),
									'type'     => 'select',
									'std'      => 'inherit',
									'priority' => 60,
									'options'  => [
										''       => __( 'Normal', 'popup-maker' ),
										'italic' => __( 'Italic', 'popup-maker' ),
									],
								],
							],
							'border'     => [
								'close_border_style' => [
									'label'    => __( 'Style', 'popup-maker' ),
									'type'     => 'select',
									'std'      => 'none',
									'priority' => 10,
									'options'  => $border_style_options,
								],
								'close_border_color' => [
									'label'        => __( 'Color', 'popup-maker' ),
									'type'         => 'color',
									'std'          => '#ffffff',
									'priority'     => 20,
									'dependencies' => [
										'close_border_style' => array_keys( PUM_Utils_Array::remove_keys( $border_style_options, [ 'none' ] ) ),
									],
								],
								'close_border_width' => [
									'label'        => __( 'Thickness', 'popup-maker' ),
									'type'         => 'rangeslider',
									'std'          => 1,
									'priority'     => 30,
									'step'         => 1,
									'min'          => 1,
									'max'          => 5,
									'unit'         => 'px',
									'dependencies' => [
										'close_border_style' => array_keys( PUM_Utils_Array::remove_keys( $border_style_options, [ 'none' ] ) ),
									],
								],
							],
							'boxshadow'  => [
								'close_boxshadow_color'    => [
									'label'    => __( 'Color', 'popup-maker' ),
									'type'     => 'color',
									'std'      => '#020202',
									'priority' => 10,
								],
								'close_boxshadow_opacity'  => [
									'label'        => __( 'Opacity', 'popup-maker' ),
									'type'         => 'rangeslider',
									'std'          => 23,
									'priority'     => 20,
									'step'         => 1,
									'min'          => 0,
									'max'          => 100,
									'unit'         => '%',
									'force_minmax' => true,
								],
								'close_boxshadow_horizontal' => [
									'label'    => __( 'Horizontal Position', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 1,
									'priority' => 30,
									'step'     => 1,
									'min'      => - 50,
									'max'      => 50,
									'unit'     => 'px',
								],
								'close_boxshadow_vertical' => [
									'label'    => __( 'Vertical Position', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 1,
									'priority' => 40,
									'step'     => 1,
									'min'      => - 50,
									'max'      => 50,
									'unit'     => 'px',
								],
								'close_boxshadow_blur'     => [
									'label'    => __( 'Blur Radius', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 3,
									'priority' => 50,
									'step'     => 1,
									'min'      => 0,
									'max'      => 100,
									'unit'     => 'px',
								],
								'close_boxshadow_spread'   => [
									'label'    => __( 'Spread', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 0,
									'priority' => 60,
									'step'     => 1,
									'min'      => - 100,
									'max'      => 100,
									'unit'     => 'px',
								],
								'close_boxshadow_inset'    => [
									'label'    => __( 'Inset (inner shadow)', 'popup-maker' ),
									'type'     => 'select',
									'std'      => 'no',
									'priority' => 70,
									'options'  => [
										'no'  => __( 'No', 'popup-maker' ),
										'yes' => __( 'Yes', 'popup-maker' ),
									],
								],
							],
							'textshadow' => [
								'close_textshadow_color'   => [
									'label'    => __( 'Color', 'popup-maker' ),
									'type'     => 'color',
									'std'      => '#000000',
									'priority' => 10,
								],
								'close_textshadow_opacity' => [
									'label'        => __( 'Opacity', 'popup-maker' ),
									'type'         => 'rangeslider',
									'std'          => 23,
									'priority'     => 20,
									'step'         => 1,
									'min'          => 0,
									'max'          => 100,
									'force_minmax' => true,
									'unit'         => '%',
								],
								'close_textshadow_horizontal' => [
									'label'    => __( 'Horizontal Position', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 0,
									'priority' => 30,
									'step'     => 1,
									'min'      => - 50,
									'max'      => 50,
									'unit'     => 'px',
								],
								'close_textshadow_vertical' => [
									'label'    => __( 'Vertical Position', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 0,
									'priority' => 40,
									'step'     => 1,
									'min'      => - 50,
									'max'      => 50,
									'unit'     => 'px',
								],
								'close_textshadow_blur'    => [
									'label'    => __( 'Blur Radius', 'popup-maker' ),
									'type'     => 'rangeslider',
									'std'      => 0,
									'priority' => 50,
									'step'     => 1,
									'min'      => 0,
									'max'      => 100,
									'unit'     => 'px',
								],
							],
						]
					),
					'advanced'  => apply_filters(
						'pum_theme_advanced_settings_fields',
						[
							'main' => [],
						]
					),
				]
			);

			$fields = self::append_deprecated_fields( $fields );

			$fields = PUM_Utils_Fields::parse_tab_fields(
				$fields,
				[
					'has_sections' => true,
					'name'         => 'theme_settings[%s]',
				]
			);
		}

		return $fields;
	}

	public static function append_deprecated_fields( $fields = [] ) {
		global $post;

		if ( class_exists( 'PUM_ATB' ) && has_action( 'popmake_popup_theme_overlay_meta_box_fields' ) ) {
			ob_start();

			do_action( 'popmake_popup_theme_overlay_meta_box_fields', $post->ID );

			$content = self::fix_deprecated_fields( ob_get_clean() );

			$fields['overlay']['background']['deprecated_fields'] = [
				'type'     => 'html',
				'content'  => $content,
				'priority' => 999,
			];

			// Remove duplicate fields.
			unset( $fields['overlay']['background']['overlay_background_color'] );
			unset( $fields['overlay']['background']['overlay_background_opacity'] );
		}

		if ( class_exists( 'PUM_ATB' ) && has_action( 'popmake_popup_theme_container_meta_box_fields' ) ) {
			ob_start();

			do_action( 'popmake_popup_theme_container_meta_box_fields', $post->ID );

			$content = self::fix_deprecated_fields( ob_get_clean() );

			$fields['container']['background']['deprecated_fields'] = [
				'type'     => 'html',
				'content'  => $content,
				'priority' => 999,
			];

			// Remove duplicate fields.
			unset( $fields['container']['background']['container_background_color'] );
			unset( $fields['container']['background']['container_background_opacity'] );
		}

		if ( class_exists( 'PUM_ATB' ) && has_action( 'popmake_popup_theme_close_meta_box_fields' ) ) {
			ob_start();

			do_action( 'popmake_popup_theme_close_meta_box_fields', $post->ID );

			$content = self::fix_deprecated_fields( ob_get_clean() );

			$fields['close']['background']['deprecated_fields'] = [
				'type'     => 'html',
				'content'  => $content,
				'priority' => 999,
			];

			// Remove duplicate fields.
			unset( $fields['close']['background']['close_background_color'] );
			unset( $fields['close']['background']['close_background_opacity'] );
		}

		return $fields;
	}

	public static function fix_deprecated_fields( $content = '' ) {

		// Remove "Background" heading.
		$content = str_replace(
			'<tr class="title-divider">
			<th colspan="2">
				<h3 class="title">Background</h3>
			</th>
		</tr>',
			'',
			$content
		);

		// Fix broken opacity fields.
		$content = str_replace( [ 'class="bg_opacity"', 'class="bg_overlay_opacity"' ], [ 'class="bg_opacity pum-field-rangeslider"', 'class="bg_overlay_opacity pum-field-rangeslider"' ], $content );

		// TEMPORARY. REMOVE THIS
		$content = '<table class="form-table"><tbody>' . $content . '</tbody></table>';

		return $content;
	}

	/**
	 * @return array
	 */
	public static function defaults() {
		return PUM_Utils_Fields::get_form_default_values( self::fields() );
	}
}
