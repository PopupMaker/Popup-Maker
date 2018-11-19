<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Model_Theme
 *
 * @since 1.8
 */
class PUM_Model_Theme extends PUM_Abstract_Model_Post {

	/** @var string */
	protected $required_post_type = 'popup_theme';

	/** @var array */
	public $settings;

	/** @var bool */
	public $doing_passive_migration = false;

	/**
	 * The current model version.
	 *
	 * 1 - v1.0.0
	 * 2 - v1.3.0
	 * 3 - v1.8.0
	 *
	 * @var int
	 */
	public $model_version = 3;

	/**
	 * The version of the data currently stored for the current item.
	 *
	 * 1 - v1.0.0
	 * 2 - v1.3.0
	 * 3 - v1.8.0
	 *
	 * @var int
	 */
	public $data_version;

	/**
	 * Returns array of all theme settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		$this->settings = $this->get_meta( 'popup_theme_settings' );

		if ( ! is_array( $this->settings ) ) {
			$this->settings = array();
		}

		return apply_filters( 'pum_theme_settings', $this->settings, $this->ID );
	}

	/**
	 * Returns a specific theme setting with optional default value when not found.
	 *
	 * @param      $key
	 * @param bool $default
	 *
	 * @return bool|mixed
	 */
	public function get_setting( $key, $default = false ) {
		$settings = $this->get_settings();

		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return bool|int
	 */
	public function update_setting( $key, $value ) {
		$settings = $this->get_settings();

		$settings[ $key ] = $value;

		return $this->update_meta( 'popup_theme_settings', $settings );
	}

	/**
	 * @param array $merge_settings
	 *
	 * @return bool|int
	 */
	public function update_settings( $merge_settings = array() ) {
		$settings = $this->get_settings();

		foreach ( $merge_settings as $key => $value ) {
			$settings[ $key ] = $value;
		}

		return $this->update_meta( 'popup_theme_settings', $settings );
	}

	/**
	 * Returns array of all google font variations used for this theme.
	 *
	 * @return array
	 */
	public function get_google_fonts_used() {
		$fonts_used = array();

		$settings = $this->get_settings();

		$google_fonts = PUM_Integration_GoogleFonts::fetch_fonts();

		if ( ! empty( $settings['title_font_family'] ) && is_string( $settings['title_font_family'] ) && array_key_exists( $settings['title_font_family'], $google_fonts ) ) {
			$variant = $settings['title_font_weight'] != 'normal' ? $settings['title_font_weight'] : '';
			if ( $settings['title_font_style'] == 'italic' ) {
				$variant .= 'italic';
			}
			$fonts_used[ $settings['title_font_family'] ][ $variant ] = $variant;
		}
		if ( ! empty( $settings['content_font_family'] ) && is_string( $settings['content_font_family'] ) && array_key_exists( $settings['content_font_family'], $google_fonts ) ) {
			$variant = $settings['content_font_weight'] != 'normal' ? $settings['content_font_weight'] : '';
			if ( $settings['content_font_style'] == 'italic' ) {
				$variant .= 'italic';
			}
			$fonts_used[ $settings['content_font_family'] ][ $variant ] = $variant;
		}
		if ( ! empty( $settings['close_font_family'] ) && is_string( $settings['close_font_family'] ) && array_key_exists( $settings['close_font_family'], $google_fonts ) ) {
			$variant = $settings['close_font_weight'] != 'normal' ? $settings['close_font_weight'] : '';
			if ( $settings['close_font_style'] == 'italic' ) {
				$variant .= 'italic';
			}
			$fonts_used[ $settings['close_font_family'] ][ $variant ] = $variant;
		}

		return $fonts_used;
	}

	/**
	 * @return array
	 */
	public function get_generated_styles() {

		$styles = array(
			'overlay'   => array(),
			'container' => array(),
			'title'     => array(),
			'content'   => array(),
			'close'     => array(),
		);

		/*
		 * Overlay Styles
		 */
		if ( ! empty( $this->get_setting( 'overlay_background_color' ) ) ) {
			$styles['overlay']['background-color'] = PUM_Utils_CSS::hex2rgba( $this->get_setting( 'overlay_background_color' ), $this->get_setting( 'overlay_background_opacity' ) );
		}

		/*
		 * Container Styles
		 */
		$styles['container'] = array(
			'padding'       => "{$this->get_setting('container_padding')}px",
			'border-radius' => "{$this->get_setting('container_border_radius')}px",
			'border'        => PUM_Utils_CSS::border_style( $this->get_setting( 'container_border_width' ), $this->get_setting( 'container_border_style' ), $this->get_setting( 'container_border_color' ) ),
			'box-shadow'    => PUM_Utils_CSS::box_shadow_style( $this->get_setting( 'container_boxshadow_horizontal' ), $this->get_setting( 'container_boxshadow_vertical' ), $this->get_setting( 'container_boxshadow_blur' ), $this->get_setting( 'container_boxshadow_spread' ), $this->get_setting( 'container_boxshadow_color' ), $this->get_setting( 'container_boxshadow_opacity' ), $this->get_setting( 'container_boxshadow_inset' ) ),
		);

		if ( ! empty( $this->get_setting( 'container_background_color' ) ) ) {
			$styles['container']['background-color'] = PUM_Utils_CSS::hex2rgba( $this->get_setting( 'container_background_color' ), $this->get_setting( 'container_background_opacity' ) );
		}

		/*
		 * Title Styles
		 */
		$styles['title'] = array(
			'color'       => $this->get_setting( 'title_font_color' ),
			'text-align'  => $this->get_setting( 'title_text_align' ),
			'text-shadow' => PUM_Utils_CSS::text_shadow_style( $this->get_setting( 'title_textshadow_horizontal' ), $this->get_setting( 'title_textshadow_vertical' ), $this->get_setting( 'title_textshadow_blur' ), $this->get_setting( 'title_textshadow_color' ), $this->get_setting( 'title_textshadow_opacity' ) ),
			'font-family' => $this->get_setting( 'title_font_family' ),
			'font-weight' => $this->get_setting( 'title_font_weight' ),
			'font-size'   => "{$this->get_setting( 'title_font_size' )}px",
			'font-style'  => $this->get_setting( 'title_font_style' ),
			'line-height' => "{$this->get_setting( 'title_line_height' )}px",
		);

		/*
		 * Content Styles
		 */
		$styles['content'] = array(
			'color'       => $this->get_setting( 'content_font_color' ),
			'font-family' => $this->get_setting( 'content_font_family' ),
			'font-weight' => $this->get_setting( 'content_font_weight' ),
			'font-style'  => $this->get_setting( 'content_font_style' ),
		);

		/*
		 * Close Styles
		 */
		$styles['close'] = array(
			'height'        => empty( $this->get_setting( 'close_height' ) ) || $this->get_setting( 'close_height' ) <= 0 ? 'auto' : "{$this->get_setting('close_height')}px",
			'width'         => empty( $this->get_setting( 'close_width' ) ) || $this->get_setting( 'close_width' ) <= 0 ? 'auto' : "{$this->get_setting('close_width')}px",
			'left'          => 'auto',
			'right'         => 'auto',
			'bottom'        => 'auto',
			'top'           => 'auto',
			'padding'       => "{$this->get_setting('close_padding')}px",
			'color'         => $this->get_setting( 'close_font_color' ),
			'font-family'   => $this->get_setting( 'close_font_family' ),
			'font-weight'   => $this->get_setting( 'close_font_weight' ),
			'font-size'     => "{$this->get_setting('close_font_size')}px",
			'font-style'    => $this->get_setting( 'close_font_style' ),
			'line-height'   => "{$this->get_setting('close_line_height')}px",
			'border'        => PUM_Utils_CSS::border_style( $this->get_setting( 'close_border_width' ), $this->get_setting( 'close_border_style' ), $this->get_setting( 'close_border_color' ) ),
			'border-radius' => "{$this->get_setting('close_border_radius')}px",
			'box-shadow'    => PUM_Utils_CSS::box_shadow_style( $this->get_setting( 'close_boxshadow_horizontal' ), $this->get_setting( 'close_boxshadow_vertical' ), $this->get_setting( 'close_boxshadow_blur' ), $this->get_setting( 'close_boxshadow_spread' ), $this->get_setting( 'close_boxshadow_color' ), $this->get_setting( 'close_boxshadow_opacity' ), $this->get_setting( 'close_boxshadow_inset' ) ),
			'text-shadow'   => PUM_Utils_CSS::text_shadow_style( $this->get_setting( 'close_textshadow_horizontal' ), $this->get_setting( 'close_textshadow_vertical' ), $this->get_setting( 'close_textshadow_blur' ), $this->get_setting( 'close_textshadow_color' ), $this->get_setting( 'close_textshadow_opacity' ) ),
		);

		if ( ! empty( $this->get_setting( 'close_background_color' ) ) ) {
			$styles['close']['background-color'] = PUM_Utils_CSS::hex2rgba( $this->get_setting( 'close_background_color' ), $this->get_setting( 'close_background_opacity' ) );
		}

		switch ( $this->get_setting( 'close_location' ) ) {
			case "topleft":
				$styles['close']['top']  = "{$this->get_setting('close_position_top')}px";
				$styles['close']['left'] = "{$this->get_setting('close_position_left')}px";
				break;
			case "topright":
				$styles['close']['top']   = "{$this->get_setting('close_position_top')}px";
				$styles['close']['right'] = "{$this->get_setting('close_position_right')}px";
				break;
			case "bottomleft":
				$styles['close']['bottom'] = "{$this->get_setting('close_position_bottom')}px";
				$styles['close']['left']   = "{$this->get_setting('close_position_left')}px";
				break;
			case "bottomright":
				$styles['close']['bottom'] = "{$this->get_setting('close_position_bottom')}px";
				$styles['close']['right']  = "{$this->get_setting('close_position_right')}px";
				break;
		}

		$theme = array(
			'overlay'   => $this->_dep_get_settings_group( 'overlay' ),
			'container' => $this->_dep_get_settings_group( 'container' ),
			'title'     => $this->_dep_get_settings_group( 'title' ),
			'content'   => $this->_dep_get_settings_group( 'content' ),
			'close'     => $this->_dep_get_settings_group( 'close' ),
		);

		/** @deprecated 1.8.0 filter */
		$styles = (array) apply_filters( 'popmake_generate_theme_styles', (array) $styles, $this->ID, $theme );

		return (array) apply_filters( 'pum_theme_get_generated_styles', (array) $styles, $this->ID );
	}


	/**
	 * Retrieve settings in the form of deprecated grouped arrays.
	 *
	 * @param      $group
	 * @param null $key
	 *
	 * @return mixed
	 */
	public function _dep_get_settings_group( $group, $key = null ) {
		if ( ! $this->$group ) {
			/**
			 * Remap old meta settings to new settings location for v1.7. This acts as a passive migration when needed.
			 */
			$remapped_keys = $this->remapped_meta_settings_keys( $group );

			// This will only return data from extensions as core data has been migrated already.
			$group_values = $this->get_meta( "popup_theme_$group" );

			if ( ! $group_values || ! is_array( $group_values ) ) {
				$group_values = array();
			}

			// Data manipulation begins here. We don't want any of this saved, only returned for backward compatibility.
			foreach ( $remapped_keys as $old_key => $new_key ) {
				$group_values[ $old_key ] = $this->get_setting( $new_key );
			}

			$deprecated_values = popmake_get_popup_theme_meta_group( $group, $this->ID );

			if ( ! empty( $deprecated_values ) ) {
				foreach ( $deprecated_values as $old_key => $value ) {

					if ( ! isset( $group_values[ $old_key ] ) ) {
						$group_values[ $old_key ] = $value;
					}

				}
			}


			$this->$group = $group_values;
		}

		$values = apply_filters( "pum_theme_get_$group", $this->$group, $this->ID );

		if ( ! $key ) {
			return $values;
		}

		$value = isset ( $values[ $key ] ) ? $values[ $key ] : null;

		if ( ! isset( $value ) ) {
			$value = $this->get_meta( "popup_theme_{$group}_{$key}" );
		}

		return apply_filters( "pum_theme_get_{$group}_" . $key, $value, $this->ID );
	}

	/**
	 * @param $group
	 *
	 * @return array|mixed
	 */
	public function remapped_meta_settings_keys( $group ) {
		$remapped_meta_settings_keys = array(
			'display' => array(
				'stackable'                 => 'stackable',
				'overlay_disabled'          => 'overlay_disabled',
				'scrollable_content'        => 'scrollable_content',
				'disable_reposition'        => 'disable_reposition',
				'size'                      => 'size',
				'responsive_min_width'      => 'responsive_min_width',
				'responsive_min_width_unit' => 'responsive_min_width_unit',
				'responsive_max_width'      => 'responsive_max_width',
				'responsive_max_width_unit' => 'responsive_max_width_unit',
				'custom_width'              => 'custom_width',
				'custom_width_unit'         => 'custom_width_unit',
				'custom_height'             => 'custom_height',
				'custom_height_unit'        => 'custom_height_unit',
				'custom_height_auto'        => 'custom_height_auto',
				'location'                  => 'location',
				'position_from_trigger'     => 'position_from_trigger',
				'position_top'              => 'position_top',
				'position_left'             => 'position_left',
				'position_bottom'           => 'position_bottom',
				'position_right'            => 'position_right',
				'position_fixed'            => 'position_fixed',
				'animation_type'            => 'animation_type',
				'animation_speed'           => 'animation_speed',
				'animation_origin'          => 'animation_origin',
				'overlay_zindex'            => 'overlay_zindex',
				'zindex'                    => 'zindex',
			),
			'close'   => array(
				'text'          => 'close_text',
				'button_delay'  => 'close_button_delay',
				'overlay_click' => 'close_on_overlay_click',
				'esc_press'     => 'close_on_esc_press',
				'f4_press'      => 'close_on_f4_press',
			),
		);

		return isset( $remapped_meta_settings_keys[ $group ] ) ? $remapped_meta_settings_keys[ $group ] : array();
	}

	/**
	 * @param $post WP_Post
	 */
	public function setup( $post ) {
		parent::setup( $post );

		if ( ! $this->is_valid() ) {
			return;
		}

		// REVIEW Does this need to be here or somewhere else like get_meta/get_setting?
		if ( ! isset( $this->data_version ) ) {
			$this->data_version = (int) $this->get_meta( 'data_version' );

			if ( ! $this->data_version ) {
				$theme_overlay_v1 = $this->get_meta( 'popup_theme_overlay_background_color' );
				$theme_overlay_v2 = $this->get_meta( 'popup_theme_overlay' );

				$is_v2 = ( ! empty( $theme_overlay_v2 ) && is_array( $theme_overlay_v2 ) );
				$is_v1 = ! $is_v2 && ( ! empty( $theme_overlay_v1 ) && is_array( $theme_overlay_v1 ) );

				// If there are existing settings set the data version to 1/2 so they can be updated.
				// Otherwise set to the current version as this is a new popup.
				$this->data_version = $is_v2 ? 2 : ( $is_v1 ? 1 : $this->model_version );

				$this->update_meta( 'data_version', $this->data_version );
			}
		}

		if ( $this->data_version < $this->model_version && pum_passive_popups_enabled() ) {
			/**
			 * Process passive settings migration as each popup is loaded. The will only run each migration routine once for each popup.
			 */
			$this->passive_migration();
		}
	}

	/**
	 * Allows for passive migration routines based on the current data version.
	 */
	public function passive_migration() {
		$this->doing_passive_migration = true;

		for ( $i = $this->data_version; $this->data_version < $this->model_version; $i ++ ) {
			do_action_ref_array( 'pum_theme_passive_migration_' . $this->data_version, array( &$this ) );
			$this->data_version ++;

			/**
			 * Update the themes data version.
			 */
			$this->update_meta( 'data_version', $this->data_version );
		}

		do_action_ref_array( 'pum_theme_passive_migration', array( &$this, $this->data_version ) );

		$this->doing_passive_migration = false;
	}
}

