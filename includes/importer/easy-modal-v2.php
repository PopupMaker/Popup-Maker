<?php
/**
 *  Function
 *
 * @package  POPMAKE_EMODAL
 * @subpackage  Functions/Import
 * @copyright   Copyright (c) 2019, Code Atlantic LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since   1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import
 *
 * Runs on plugin install by setting up the post types, custom taxonomies,
 * flushing rewrite rules also creates the plugin and populates the settings
 * fields for those plugin pages. After successful install, the user is
 * redirected to the POPMAKE_EMODAL Welcome screen.
 *
 * @since 1.0
 * @global $wpdb
 * @global $popmake_options
 * @global $wp_version
 * @return void
 */
function popmake_emodal_v2_import() {
	global $wpdb, $popmake_options, $wp_version, $popmake_tools_page;

	require_once POPMAKE_DIR . 'includes/importer/easy-modal-v2/functions.php';

	if ( ! class_exists( 'EModal_Model' ) ) {
		require_once POPMAKE_DIR . '/includes/importer/easy-modal-v2/model.php';
	}
	if ( ! class_exists( 'EModal_Model_Modal' ) ) {
		require_once POPMAKE_DIR . '/includes/importer/easy-modal-v2/model/modal.php';
	}
	if ( ! class_exists( 'EModal_Model_Theme' ) ) {
		require_once POPMAKE_DIR . '/includes/importer/easy-modal-v2/model/theme.php';
	}
	if ( ! class_exists( 'EModal_Model_Theme_Meta' ) ) {
		require_once POPMAKE_DIR . '/includes/importer/easy-modal-v2/model/theme/meta.php';
	}
	if ( ! class_exists( 'EModal_Model_Modal_Meta' ) ) {
		require_once POPMAKE_DIR . '/includes/importer/easy-modal-v2/model/modal/meta.php';
	}


	$themes       = get_all_modal_themes( '1 = 1' );
	$theme_id_map = array();
	foreach ( $themes as $Theme ) {
		$theme = $Theme->as_array();
		$meta  = $theme['meta'];

		$theme_meta = apply_filters( 'popmake_emodal_import_theme_meta', array(
			'popup_theme_defaults_set'                   => true,
			'popup_theme_overlay_background_color'       => $meta['overlay']['background']['color'],
			'popup_theme_overlay_background_opacity'     => $meta['overlay']['background']['opacity'],
			'popup_theme_container_padding'              => $meta['container']['padding'],
			'popup_theme_container_background_color'     => $meta['container']['background']['color'],
			'popup_theme_container_background_opacity'   => $meta['container']['background']['opacity'],
			'popup_theme_container_border_radius'        => $meta['container']['border']['radius'],
			'popup_theme_container_border_style'         => $meta['container']['border']['style'],
			'popup_theme_container_border_color'         => $meta['container']['border']['color'],
			'popup_theme_container_border_width'         => $meta['container']['border']['width'],
			'popup_theme_container_boxshadow_inset'      => $meta['container']['boxshadow']['inset'],
			'popup_theme_container_boxshadow_horizontal' => $meta['container']['boxshadow']['horizontal'],
			'popup_theme_container_boxshadow_vertical'   => $meta['container']['boxshadow']['vertical'],
			'popup_theme_container_boxshadow_blur'       => $meta['container']['boxshadow']['blur'],
			'popup_theme_container_boxshadow_spread'     => $meta['container']['boxshadow']['spread'],
			'popup_theme_container_boxshadow_color'      => $meta['container']['boxshadow']['color'],
			'popup_theme_container_boxshadow_opacity'    => $meta['container']['boxshadow']['opacity'],
			'popup_theme_title_font_color'               => $meta['title']['font']['color'],
			'popup_theme_title_line_height'              => $meta['title']['font']['size'],
			'popup_theme_title_font_size'                => $meta['title']['font']['size'],
			'popup_theme_title_font_family'              => $meta['title']['font']['family'],
			'popup_theme_title_font_weight'              => $meta['title']['font']['weight'],
			'popup_theme_title_font_style'               => $meta['title']['font']['style'],
			'popup_theme_title_text_align'               => $meta['title']['text']['align'],
			'popup_theme_title_textshadow_horizontal'    => $meta['title']['textshadow']['horizontal'],
			'popup_theme_title_textshadow_vertical'      => $meta['title']['textshadow']['vertical'],
			'popup_theme_title_textshadow_blur'          => $meta['title']['textshadow']['blur'],
			'popup_theme_title_textshadow_color'         => $meta['title']['textshadow']['color'],
			'popup_theme_title_textshadow_opacity'       => $meta['title']['textshadow']['opacity'],
			'popup_theme_content_font_color'             => $meta['content']['font']['color'],
			'popup_theme_content_font_family'            => $meta['content']['font']['family'],
			'popup_theme_content_font_weight'            => $meta['content']['font']['weight'],
			'popup_theme_content_font_style'             => $meta['content']['font']['style'],
			'popup_theme_close_text'                     => $meta['close']['text'],
			'popup_theme_close_padding'                  => $meta['close']['padding'],
			'popup_theme_close_location'                 => $meta['close']['location'],
			'popup_theme_close_position_top'             => $meta['close']['position']['top'],
			'popup_theme_close_position_left'            => $meta['close']['position']['left'],
			'popup_theme_close_position_bottom'          => $meta['close']['position']['bottom'],
			'popup_theme_close_position_right'           => $meta['close']['position']['right'],
			'popup_theme_close_line_height'              => $meta['close']['font']['size'],
			'popup_theme_close_font_color'               => $meta['close']['font']['color'],
			'popup_theme_close_font_size'                => $meta['close']['font']['size'],
			'popup_theme_close_font_family'              => $meta['close']['font']['family'],
			'popup_theme_close_font_weight'              => $meta['close']['font']['weight'],
			'popup_theme_close_font_style'               => $meta['close']['font']['style'],
			'popup_theme_close_background_color'         => $meta['close']['background']['color'],
			'popup_theme_close_background_opacity'       => $meta['close']['background']['opacity'],
			'popup_theme_close_border_radius'            => $meta['close']['border']['radius'],
			'popup_theme_close_border_style'             => $meta['close']['border']['style'],
			'popup_theme_close_border_color'             => $meta['close']['border']['color'],
			'popup_theme_close_border_width'             => $meta['close']['border']['width'],
			'popup_theme_close_boxshadow_inset'          => $meta['close']['boxshadow']['inset'],
			'popup_theme_close_boxshadow_horizontal'     => $meta['close']['boxshadow']['horizontal'],
			'popup_theme_close_boxshadow_vertical'       => $meta['close']['boxshadow']['vertical'],
			'popup_theme_close_boxshadow_blur'           => $meta['close']['boxshadow']['blur'],
			'popup_theme_close_boxshadow_spread'         => $meta['close']['boxshadow']['spread'],
			'popup_theme_close_boxshadow_color'          => $meta['close']['boxshadow']['color'],
			'popup_theme_close_boxshadow_opacity'        => $meta['close']['boxshadow']['opacity'],
			'popup_theme_close_textshadow_horizontal'    => $meta['close']['textshadow']['horizontal'],
			'popup_theme_close_textshadow_vertical'      => $meta['close']['textshadow']['vertical'],
			'popup_theme_close_textshadow_blur'          => $meta['close']['textshadow']['blur'],
			'popup_theme_close_textshadow_color'         => $meta['close']['textshadow']['color'],
			'popup_theme_close_textshadow_opacity'       => $meta['close']['textshadow']['opacity'],
		), $Theme );

		$new_theme_id = wp_insert_post(
			array(
				'post_title'     => $theme['name'],
				'post_status'    => $theme['is_trash'] ? 'trash' : 'publish',
				'post_author'    => get_current_user_id(),
				'post_type'      => 'popup_theme',
				'comment_status' => 'closed'
			)
		);
		foreach ( $theme_meta as $meta_key => $meta_value ) {
			update_post_meta( $new_theme_id, $meta_key, $meta_value );
		}
		update_post_meta( $new_theme_id, 'popup_theme_old_easy_modal_id', $theme['id'] );

		$theme_id_map[ $theme['id'] ] = $new_theme_id;
	}

	if ( count( $themes ) == 1 ) {
		update_post_meta( $new_theme_id, 'popup_theme_defaults_set', true );
		update_option( 'popmake_default_theme', $new_theme_id );
	}

	$modals = get_all_modals( '1 = 1' );

	//echo '<pre>'; var_export(popmake_popup_meta_fields()); echo '</pre>';

	foreach ( $modals as $Modal ) {
		$modal = $Modal->as_array();
		$meta  = $modal['meta'];

		$modal_meta = apply_filters( 'popmake_emodal_import_modal_meta', array(
			'popup_old_easy_modal_id'                 => $modal['id'],
			'popup_defaults_set'                      => true,
			'popup_theme'                             => isset( $theme_id_map[ $theme['id'] ] ) ? $theme_id_map[ $theme['id'] ] : null,
			'popup_title'                             => $modal['title'],
			'popup_display_scrollable_content'        => null,
			'popup_display_overlay_disabled'          => $meta['display']['overlay_disabled'],
			'popup_display_size'                      => $meta['display']['size'],
			'popup_display_responsive_min_width'      => '',
			'popup_display_responsive_min_width_unit' => 'px',
			'popup_display_responsive_max_width'      => '',
			'popup_display_responsive_max_width_unit' => 'px',
			'popup_display_custom_width'              => $meta['display']['custom_width'],
			'popup_display_custom_width_unit'         => $meta['display']['custom_width_unit'],
			'popup_display_custom_height'             => $meta['display']['custom_height'],
			'popup_display_custom_height_unit'        => $meta['display']['custom_height_unit'],
			'popup_display_custom_height_auto'        => $meta['display']['custom_height_auto'],
			'popup_display_location'                  => $meta['display']['location'],
			'popup_display_position_top'              => $meta['display']['position']['top'],
			'popup_display_position_left'             => $meta['display']['position']['left'],
			'popup_display_position_bottom'           => $meta['display']['position']['bottom'],
			'popup_display_position_right'            => $meta['display']['position']['right'],
			'popup_display_position_fixed'            => $meta['display']['position']['fixed'],
			'popup_display_animation_type'            => $meta['display']['animation']['type'],
			'popup_display_animation_speed'           => $meta['display']['animation']['speed'],
			'popup_display_animation_origin'          => $meta['display']['animation']['origin'],
			'popup_close_overlay_click'               => $meta['close']['overlay_click'],
			'popup_close_esc_press'                   => $meta['close']['esc_press'],
			'popup_close_f4_press'                    => null,
		), $Modal );

		if ( $modal['is_sitewide'] == 1 ) {
			$modal_meta['popup_targeting_condition_on_entire_site'] = true;
		}

		$new_modal_id = wp_insert_post(
			array(
				'post_title'     => $modal['name'],
				'post_status'    => $modal['is_trash'] ? 'trash' : 'publish',
				'post_content'   => $modal['content'],
				'post_author'    => get_current_user_id(),
				'post_type'      => 'popup',
				'comment_status' => 'closed'
			)
		);
		foreach ( $modal_meta as $meta_key => $meta_value ) {
			update_post_meta( $new_modal_id, $meta_key, $meta_value );
		}

	}
}


function popmake_emodal_init() {
	if ( pum_get_option( 'enable_easy_modal_compatibility_mode' ) ) {
		if ( ! shortcode_exists( 'modal' ) ) {
			add_shortcode( 'modal', 'popmake_emodal_shortcode_modal' );
		}
		add_filter( 'pum_popup_data_attr', 'popmake_emodal_get_the_popup_data_attr', 10, 2 );
		add_filter( 'popmake_shortcode_popup_default_atts', 'popmake_emodal_shortcode_popup_default_atts', 10, 2 );
		add_filter( 'popmake_shortcode_data_attr', 'popmake_emodal_shortcode_data_attr', 10, 2 );

		add_filter( 'pum_popup_is_loadable', 'popmake_emodal_popup_is_loadable', 20, 2 );
	}
}

add_action( 'init', 'popmake_emodal_init' );


function popmake_emodal_popup_is_loadable( $return, $popup_id ) {
	global $post;
	if ( empty( $post ) || ! isset( $post->ID ) ) {
		return $return;
	}
	$easy_modal_id = get_post_meta( $popup_id, 'popup_old_easy_modal_id', true );
	$post_modals   = get_post_meta( $post->ID, 'easy-modal_post_modals', true );
	if ( ! $easy_modal_id || empty( $post_modals ) || ! in_array( $easy_modal_id, $post_modals ) ) {
		return $return;
	}

	return true;
}

function popmake_emodal_get_the_popup_data_attr( $data_attr, $popup_id ) {
	$easy_modal_id = get_post_meta( $popup_id, 'popup_old_easy_modal_id', true );
	if ( ! $easy_modal_id ) {
		return $data_attr;
	}

	return array_merge( $data_attr, array(
		'old_easy_modal_id' => $easy_modal_id
	) );
}

function popmake_emodal_shortcode_modal( $atts, $content = null ) {
	$atts = shortcode_atts(
		apply_filters( 'emodal_shortcode_modal_default_atts', array(
			'id'               => "",
			'theme_id'         => null,
			'title'            => null,
			'overlay_disabled' => null,
			'size'             => null,
			'width'            => null,
			'widthUnit'        => null,
			'height'           => null,
			'heightUnit'       => null,
			'location'         => null,
			'positionTop'      => null,
			'positionLeft'     => null,
			'positionBottom'   => null,
			'positionRight'    => null,
			'positionFixed'    => null,
			'animation'        => null,
			'animationSpeed'   => null,
			'animationOrigin'  => null,
			'overlayClose'     => null,
			'escClose'         => null,
			// Deprecated
			'theme'            => null,
			'duration'         => null,
			'direction'        => null,
			'overlayEscClose'  => null,
		) ),
		apply_filters( 'emodal_shortcode_modal_atts', $atts )
	);

	$new_shortcode_atts = array(
		'id'               => $atts['id'],
		'emodal_id'        => $atts['id'],
		'theme_id'         => $atts['theme_id'],
		'title'            => $atts['title'],
		'overlay_disabled' => $atts['overlay_disabled'],
		'size'             => $atts['size'],
		'width'            => $atts['width'],
		'width_unit'       => $atts['widthUnit'],
		'height'           => $atts['height'],
		'height_unit'      => $atts['heightUnit'],
		'location'         => $atts['location'],
		'position_top'     => $atts['positionTop'],
		'position_left'    => $atts['positionLeft'],
		'position_bottom'  => $atts['positionBottom'],
		'position_right'   => $atts['positionRight'],
		'position_fixed'   => $atts['positionFixed'],
		'animation_type'   => $atts['animation'],
		'animation_speed'  => $atts['animationSpeed'],
		'animation_origin' => $atts['animationOrigin'],
		'overlay_click'    => $atts['overlayClose'],
		'esc_press'        => $atts['escClose']
	);

	$shortcode = '[popup ';

	foreach ( $new_shortcode_atts as $attr => $val ) {
		if ( $val && ! empty( $val ) ) {
			$shortcode .= $attr . '="' . $val . '" ';
		}
	}

	$shortcode .= ']' . $content . '[/popup]';

	return do_shortcode( $shortcode );
}


function popmake_emodal_shortcode_popup_default_atts( $default_atts = array() ) {
	return array_merge( $default_atts, array(
		'emodal_id' => null,
	) );
}


function popmake_emodal_shortcode_data_attr( $data, $attr ) {
	if ( ! empty( $attr['emodal_id'] ) ) {
		$data['old_easy_modal_id'] = $attr['emodal_id'];
	}

	return $data;
}


