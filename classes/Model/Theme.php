<?php
/**
 * Model for Theme
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

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
			$this->settings = [];
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
	public function update_settings( $merge_settings = [] ) {
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
		$fonts_used = [];

		$settings = $this->get_settings();

		$google_fonts = PUM_Integration_GoogleFonts::fetch_fonts();

		if ( ! empty( $settings['title_font_family'] ) && is_string( $settings['title_font_family'] ) && array_key_exists( $settings['title_font_family'], $google_fonts ) ) {
			$variant = ! empty( $settings['title_font_weight'] ) && 'normal' !== $settings['title_font_weight'] ? $settings['title_font_weight'] : '';
			if ( isset( $settings['title_font_style'] ) && 'italic' === $settings['title_font_style'] ) {
				$variant .= 'italic';
			}
			$fonts_used[ $settings['title_font_family'] ][ $variant ] = $variant;
		}
		if ( ! empty( $settings['content_font_family'] ) && is_string( $settings['content_font_family'] ) && array_key_exists( $settings['content_font_family'], $google_fonts ) ) {
			$variant = ! empty( $settings['content_font_weight'] ) && 'normal' !== $settings['content_font_weight'] ? $settings['content_font_weight'] : '';
			if ( isset( $settings['content_font_style'] ) && 'italic' === $settings['content_font_style'] ) {
				$variant .= 'italic';
			}
			$fonts_used[ $settings['content_font_family'] ][ $variant ] = $variant;
		}
		if ( ! empty( $settings['close_font_family'] ) && is_string( $settings['close_font_family'] ) && array_key_exists( $settings['close_font_family'], $google_fonts ) ) {
			$variant = ! empty( $settings['close_font_weight'] ) && 'normal' !== $settings['close_font_weight'] ? $settings['close_font_weight'] : '';
			if ( isset( $settings['close_font_style'] ) && 'italic' === $settings['close_font_style'] ) {
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

		$styles = [
			'overlay'   => [],
			'container' => [],
			'title'     => [],
			'content'   => [],
			'close'     => [],
		];

		/*
		 * Overlay Styles
		 */
		if ( $this->get_setting( 'overlay_background_color' ) ) {
			$styles['overlay']['background-color'] = PUM_Utils_CSS::hex2rgba( $this->get_setting( 'overlay_background_color' ), $this->get_setting( 'overlay_background_opacity' ) );
		}

		/*
		 * Container Styles
		 */
		$styles['container'] = [
			'padding'       => "{$this->get_setting('container_padding')}px",
			'border-radius' => "{$this->get_setting('container_border_radius')}px",
			'border'        => PUM_Utils_CSS::border_style( $this->get_setting( 'container_border_width' ), $this->get_setting( 'container_border_style' ), $this->get_setting( 'container_border_color' ) ),
			'box-shadow'    => PUM_Utils_CSS::box_shadow_style( $this->get_setting( 'container_boxshadow_horizontal' ), $this->get_setting( 'container_boxshadow_vertical' ), $this->get_setting( 'container_boxshadow_blur' ), $this->get_setting( 'container_boxshadow_spread' ), $this->get_setting( 'container_boxshadow_color' ), $this->get_setting( 'container_boxshadow_opacity' ), $this->get_setting( 'container_boxshadow_inset' ) ),
		];

		if ( $this->get_setting( 'container_background_color' ) ) {
			$styles['container']['background-color'] = PUM_Utils_CSS::hex2rgba( $this->get_setting( 'container_background_color' ), $this->get_setting( 'container_background_opacity' ) );
		}

		/*
		 * Title Styles
		 */
		$styles['title'] = [
			'color'       => $this->get_setting( 'title_font_color' ),
			'text-align'  => $this->get_setting( 'title_text_align' ),
			'text-shadow' => PUM_Utils_CSS::text_shadow_style( $this->get_setting( 'title_textshadow_horizontal' ), $this->get_setting( 'title_textshadow_vertical' ), $this->get_setting( 'title_textshadow_blur' ), $this->get_setting( 'title_textshadow_color' ), $this->get_setting( 'title_textshadow_opacity' ) ),
			'font-family' => $this->get_setting( 'title_font_family' ),
			'font-weight' => $this->get_setting( 'title_font_weight' ),
			'font-size'   => "{$this->get_setting( 'title_font_size' )}px",
			'font-style'  => $this->get_setting( 'title_font_style' ),
			'line-height' => "{$this->get_setting( 'title_line_height' )}px",
		];

		/*
		 * Content Styles
		 */
		$styles['content'] = [
			'color'       => $this->get_setting( 'content_font_color' ),
			'font-family' => $this->get_setting( 'content_font_family' ),
			'font-weight' => $this->get_setting( 'content_font_weight' ),
			'font-style'  => $this->get_setting( 'content_font_style' ),
		];

		/*
		 * Close Styles
		 */
		$styles['close'] = [
			'position'      => $this->get_setting( 'close_position_outside' ) ? 'fixed' : 'absolute',
			'height'        => ! $this->get_setting( 'close_height' ) || $this->get_setting( 'close_height' ) <= 0 ? 'auto' : "{$this->get_setting('close_height')}px",
			'width'         => ! $this->get_setting( 'close_width' ) || $this->get_setting( 'close_width' ) <= 0 ? 'auto' : "{$this->get_setting('close_width')}px",
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
		];

		if ( $this->get_setting( 'close_background_color' ) ) {
			$styles['close']['background-color'] = PUM_Utils_CSS::hex2rgba( $this->get_setting( 'close_background_color' ), $this->get_setting( 'close_background_opacity' ) );
		}

		$top    = "{$this->get_setting('close_position_top')}px";
		$left   = "{$this->get_setting('close_position_left')}px";
		$right  = "{$this->get_setting('close_position_right')}px";
		$bottom = "{$this->get_setting('close_position_bottom')}px";

		switch ( $this->get_setting( 'close_location' ) ) {
			case 'topleft':
				$styles['close']['top']  = $top;
				$styles['close']['left'] = $left;
				break;
			case 'topcenter':
				$styles['close']['top']       = $top;
				$styles['close']['left']      = '50%';
				$styles['close']['transform'] = 'translateX(-50%)';
				break;
			case 'topright':
				$styles['close']['top']   = $top;
				$styles['close']['right'] = $right;
				break;
			case 'middleleft':
				$styles['close']['top']       = '50%';
				$styles['close']['left']      = $left;
				$styles['close']['transform'] = 'translate(0, -50%)';
				break;
			case 'middleright':
				$styles['close']['top']       = '50%';
				$styles['close']['right']     = $right;
				$styles['close']['transform'] = 'translate(0, -50%)';
				break;
			case 'bottomleft':
				$styles['close']['bottom'] = $bottom;
				$styles['close']['left']   = $left;
				break;
			case 'bottomcenter':
				$styles['close']['bottom']    = $bottom;
				$styles['close']['left']      = '50%';
				$styles['close']['transform'] = 'translateX(-50%)';
				break;
			case 'bottomright':
				$styles['close']['bottom'] = $bottom;
				$styles['close']['right']  = $right;
				break;
		}

		/** @deprecated 1.8.0 filter */
		$styles = (array) apply_filters( 'popmake_generate_theme_styles', (array) $styles, $this->ID, $this->get_deprecated_settings() );

		return (array) apply_filters( 'pum_theme_get_generated_styles', (array) $styles, $this->ID );
	}

	public function get_deprecated_settings() {
		return [
			'overlay'   => $this->_dep_get_settings_group( 'overlay' ),
			'container' => $this->_dep_get_settings_group( 'container' ),
			'title'     => $this->_dep_get_settings_group( 'title' ),
			'content'   => $this->_dep_get_settings_group( 'content' ),
			'close'     => $this->_dep_get_settings_group( 'close' ),
		];
	}


	/**
	 * Deprecated settings keys that have been remapped to new settings.
	 *
	 * @var array
	 */
	public $dep_groups = [];

	/**
	 * Retrieve settings in the form of deprecated grouped arrays.
	 *
	 * @param      $group
	 * @param null  $key
	 *
	 * @return mixed
	 */
	public function _dep_get_settings_group( $group, $key = null ) {
		if ( ! isset( $this->dep_groups[ $group ] ) ) {
			/**
			 * Remap old meta settings to new settings location for v1.7. This acts as a passive migration when needed.
			 */
			$remapped_keys = $this->remapped_meta_settings_keys( $group );

			// This will only return data from extensions as core data has been migrated already.
			$group_values = $this->get_meta( "popup_theme_$group" );

			if ( ! $group_values || ! is_array( $group_values ) ) {
				$group_values = [];
			}

			// Data manipulation begins here. We don't want any of this saved, only returned for backward compatibility.
			foreach ( $remapped_keys as $old_key => $new_key ) {
				$group_values[ $old_key ] = $this->get_setting( $new_key );
			}

			$deprecated_values = pum_get_theme_v1_meta( $group, $this->ID );

			if ( ! empty( $deprecated_values ) ) {
				foreach ( $deprecated_values as $old_key => $value ) {

					if ( ! isset( $group_values[ $old_key ] ) ) {
						$group_values[ $old_key ] = $value;
					}
				}
			}

			$this->dep_groups[ $group ] = $group_values;
		}

		$values = apply_filters( "pum_theme_get_$group", $this->dep_groups[ $group ], $this->ID );

		if ( ! $key ) {
			return $values;
		}

		$value = isset( $values[ $key ] ) ? $values[ $key ] : null;

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
		$remapped_meta_settings_keys = [
			'overlay'   => [
				'background_color'   => 'overlay_background_color',
				'background_opacity' => 'overlay_background_opacity',
			],
			'container' => [
				'padding'              => 'container_padding',
				'background_color'     => 'container_background_color',
				'background_opacity'   => 'container_background_opacity',
				'border_style'         => 'container_border_style',
				'border_color'         => 'container_border_color',
				'border_width'         => 'container_border_width',
				'border_radius'        => 'container_border_radius',
				'boxshadow_inset'      => 'container_boxshadow_inset',
				'boxshadow_horizontal' => 'container_boxshadow_horizontal',
				'boxshadow_vertical'   => 'container_boxshadow_vertical',
				'boxshadow_blur'       => 'container_boxshadow_blur',
				'boxshadow_spread'     => 'container_boxshadow_spread',
				'boxshadow_color'      => 'container_boxshadow_color',
				'boxshadow_opacity'    => 'container_boxshadow_opacity',
			],
			'title'     => [
				'font_color'            => 'title_font_color',
				'line_height'           => 'title_line_height',
				'font_size'             => 'title_font_size',
				'font_family'           => 'title_font_family',
				'font_weight'           => 'title_font_weight',
				'font_style'            => 'title_font_style',
				'text_align'            => 'title_text_align',
				'textshadow_horizontal' => 'title_textshadow_horizontal',
				'textshadow_vertical'   => 'title_textshadow_vertical',
				'textshadow_blur'       => 'title_textshadow_blur',
				'textshadow_color'      => 'title_textshadow_color',
				'textshadow_opacity'    => 'title_textshadow_opacity',
			],
			'content'   => [
				'font_color'  => 'content_font_color',
				'font_family' => 'content_font_family',
				'font_weight' => 'content_font_weight',
				'font_style'  => 'content_font_style',
			],
			'close'     => [
				'text'                  => 'close_text',
				'location'              => 'close_location',
				'position_top'          => 'close_position_top',
				'position_left'         => 'close_position_left',
				'position_bottom'       => 'close_position_bottom',
				'position_right'        => 'close_position_right',
				'padding'               => 'close_padding',
				'height'                => 'close_height',
				'width'                 => 'close_width',
				'background_color'      => 'close_background_color',
				'background_opacity'    => 'close_background_opacity',
				'font_color'            => 'close_font_color',
				'line_height'           => 'close_line_height',
				'font_size'             => 'close_font_size',
				'font_family'           => 'close_font_family',
				'font_weight'           => 'close_font_weight',
				'font_style'            => 'close_font_style',
				'border_style'          => 'close_border_style',
				'border_color'          => 'close_border_color',
				'border_width'          => 'close_border_width',
				'border_radius'         => 'close_border_radius',
				'boxshadow_inset'       => 'close_boxshadow_inset',
				'boxshadow_horizontal'  => 'close_boxshadow_horizontal',
				'boxshadow_vertical'    => 'close_boxshadow_vertical',
				'boxshadow_blur'        => 'close_boxshadow_blur',
				'boxshadow_spread'      => 'close_boxshadow_spread',
				'boxshadow_color'       => 'close_boxshadow_color',
				'boxshadow_opacity'     => 'close_boxshadow_opacity',
				'textshadow_horizontal' => 'close_textshadow_horizontal',
				'textshadow_vertical'   => 'close_textshadow_vertical',
				'textshadow_blur'       => 'close_textshadow_blur',
				'textshadow_color'      => 'close_textshadow_color',
				'textshadow_opacity'    => 'close_textshadow_opacity',
			],
		];

		return isset( $remapped_meta_settings_keys[ $group ] ) ? $remapped_meta_settings_keys[ $group ] : [];
	}

	/**
	 * @param WP_Post $post
	 */
	public function setup( $post ) {
		parent::setup( $post );

		if ( ! $this->is_valid() ) {
			return;
		}

		if ( ! isset( $this->data_version ) ) {
			$this->data_version = (int) $this->get_meta( 'popup_theme_data_version' );

			if ( ! $this->data_version ) {
				$theme_overlay_v1 = $this->get_meta( 'popup_theme_overlay_background_color' );
				$theme_overlay_v2 = $this->get_meta( 'popup_theme_overlay' );

				// If there are existing settings set the data version to 1/2 so they can be updated.
				// Otherwise set to the current version as this is a new popup.
				if ( ! empty( $theme_overlay_v1 ) ) {
					$this->data_version = 1;
				} elseif ( ! empty( $theme_overlay_v2 ) && is_array( $theme_overlay_v2 ) ) {
					$this->data_version = 2;
				} else {
					$this->data_version = $this->model_version;
				}

				$this->update_meta( 'popup_theme_data_version', $this->data_version );
			}
		}

		if ( $this->data_version < $this->model_version && pum_passive_theme_upgrades_enabled() ) {
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
			// Process migration for current version. ex. current version is 2, runs pum_theme_passive_migration_2.
			do_action_ref_array( 'pum_theme_passive_migration_' . $this->data_version, [ &$this ] );
			$this->data_version ++;

			/**
			 * Update the themes data version.
			 */
			$this->update_meta( 'popup_theme_data_version', $this->data_version );
		}

		do_action_ref_array( 'pum_theme_passive_migration', [ &$this, $this->data_version ] );

		$this->doing_passive_migration = false;
	}
}
