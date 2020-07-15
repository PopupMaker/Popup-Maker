<?php
/**
 * Install Functions
 *
 * @package     PUM
 * @subpackage  Functions/Install
 * @copyright   Copyright (c) 2019, Code Atlantic LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues our install theme function on wp_loaded if Popup Maker core was updated.
 *
 * @since 1.11.0
 */
function pum_install_new_themes_on_update() {
	add_action( 'wp_loaded', 'pum_install_built_in_themes' );
}

add_action( 'pum_update_core_version', 'pum_install_new_themes_on_update' );


/**
 * @param bool $network_wide
 */
function pum_install_built_in_themes( $network_wide = false ) {

	$installed_themes = get_option( '_pum_installed_themes', array() );

	$built_in_themes = array(
		'lightbox'        => array(
			'post_title' => __( 'Light Box', 'popup-maker' ),
			'meta_input' => array(
				'popup_theme_settings' => 'a:67:{s:24:"overlay_background_color";s:7:"#000000";s:26:"overlay_background_opacity";s:2:"60";s:17:"container_padding";s:2:"18";s:26:"container_background_color";s:7:"#ffffff";s:28:"container_background_opacity";s:3:"100";s:22:"container_border_style";s:5:"solid";s:22:"container_border_color";s:7:"#000000";s:22:"container_border_width";s:1:"8";s:23:"container_border_radius";s:1:"3";s:25:"container_boxshadow_inset";s:2:"no";s:30:"container_boxshadow_horizontal";s:1:"0";s:28:"container_boxshadow_vertical";s:1:"0";s:24:"container_boxshadow_blur";s:2:"30";s:26:"container_boxshadow_spread";s:1:"0";s:25:"container_boxshadow_color";s:7:"#020202";s:27:"container_boxshadow_opacity";s:3:"100";s:16:"title_font_color";s:7:"#000000";s:17:"title_line_height";s:2:"36";s:15:"title_font_size";s:2:"32";s:17:"title_font_family";s:7:"inherit";s:17:"title_font_weight";s:3:"100";s:16:"title_font_style";s:0:"";s:16:"title_text_align";s:4:"left";s:27:"title_textshadow_horizontal";s:1:"0";s:25:"title_textshadow_vertical";s:1:"0";s:21:"title_textshadow_blur";s:1:"0";s:22:"title_textshadow_color";s:7:"#020202";s:24:"title_textshadow_opacity";s:2:"23";s:18:"content_font_color";s:7:"#000000";s:19:"content_font_family";s:7:"inherit";s:19:"content_font_weight";s:3:"100";s:18:"content_font_style";s:0:"";s:10:"close_text";s:7:"&times;";s:14:"close_location";s:8:"topright";s:18:"close_position_top";s:3:"-13";s:19:"close_position_left";s:1:"0";s:21:"close_position_bottom";s:1:"0";s:20:"close_position_right";s:3:"-13";s:13:"close_padding";s:1:"0";s:12:"close_height";s:2:"26";s:11:"close_width";s:2:"26";s:22:"close_background_color";s:7:"#000000";s:24:"close_background_opacity";s:3:"100";s:16:"close_font_color";s:7:"#ffffff";s:17:"close_line_height";s:2:"24";s:15:"close_font_size";s:2:"24";s:17:"close_font_family";s:5:"Arial";s:17:"close_font_weight";s:3:"100";s:16:"close_font_style";s:0:"";s:18:"close_border_style";s:5:"solid";s:18:"close_border_color";s:7:"#ffffff";s:18:"close_border_width";s:1:"2";s:19:"close_border_radius";s:2:"26";s:21:"close_boxshadow_inset";s:2:"no";s:26:"close_boxshadow_horizontal";s:1:"0";s:24:"close_boxshadow_vertical";s:1:"0";s:20:"close_boxshadow_blur";s:2:"15";s:22:"close_boxshadow_spread";s:1:"1";s:21:"close_boxshadow_color";s:7:"#020202";s:23:"close_boxshadow_opacity";s:2:"75";s:27:"close_textshadow_horizontal";s:1:"0";s:25:"close_textshadow_vertical";s:1:"0";s:21:"close_textshadow_blur";s:1:"0";s:22:"close_textshadow_color";s:7:"#000000";s:24:"close_textshadow_opacity";s:2:"23";s:13:"atc_promotion";N;s:22:"close_position_outside";i:0;}',
				'popup_theme_data_version' => 3,
			),
		),
		'enterprise-blue' => array(
			'post_title' => __( 'Enterprise Blue', 'popup-maker' ),
			'meta_input' => array(
				'popup_theme_settings' => 'a:67:{s:24:"overlay_background_color";s:7:"#000000";s:26:"overlay_background_opacity";s:2:"70";s:17:"container_padding";s:2:"28";s:26:"container_background_color";s:7:"#ffffff";s:28:"container_background_opacity";s:3:"100";s:22:"container_border_style";s:4:"none";s:22:"container_border_color";s:7:"#000000";s:22:"container_border_width";s:1:"1";s:23:"container_border_radius";s:1:"5";s:25:"container_boxshadow_inset";s:2:"no";s:30:"container_boxshadow_horizontal";s:1:"0";s:28:"container_boxshadow_vertical";s:2:"10";s:24:"container_boxshadow_blur";s:2:"25";s:26:"container_boxshadow_spread";s:1:"4";s:25:"container_boxshadow_color";s:7:"#020202";s:27:"container_boxshadow_opacity";s:2:"50";s:16:"title_font_color";s:7:"#315b7c";s:17:"title_line_height";s:2:"36";s:15:"title_font_size";s:2:"34";s:17:"title_font_family";s:7:"inherit";s:17:"title_font_weight";s:3:"100";s:16:"title_font_style";s:0:"";s:16:"title_text_align";s:4:"left";s:27:"title_textshadow_horizontal";s:1:"0";s:25:"title_textshadow_vertical";s:1:"0";s:21:"title_textshadow_blur";s:1:"0";s:22:"title_textshadow_color";s:7:"#020202";s:24:"title_textshadow_opacity";s:2:"23";s:18:"content_font_color";s:7:"#2d2d2d";s:19:"content_font_family";s:7:"inherit";s:19:"content_font_weight";s:3:"100";s:18:"content_font_style";s:0:"";s:10:"close_text";s:2:"×";s:14:"close_location";s:8:"topright";s:18:"close_position_top";s:1:"8";s:19:"close_position_left";s:1:"0";s:21:"close_position_bottom";s:1:"0";s:20:"close_position_right";s:1:"8";s:13:"close_padding";s:1:"4";s:12:"close_height";s:2:"28";s:11:"close_width";s:2:"28";s:22:"close_background_color";s:7:"#315b7c";s:24:"close_background_opacity";s:3:"100";s:16:"close_font_color";s:7:"#ffffff";s:17:"close_line_height";s:2:"20";s:15:"close_font_size";s:2:"20";s:17:"close_font_family";s:15:"Times New Roman";s:17:"close_font_weight";s:3:"100";s:16:"close_font_style";s:0:"";s:18:"close_border_style";s:4:"none";s:18:"close_border_color";s:7:"#ffffff";s:18:"close_border_width";s:1:"1";s:19:"close_border_radius";s:2:"42";s:21:"close_boxshadow_inset";s:2:"no";s:26:"close_boxshadow_horizontal";s:1:"0";s:24:"close_boxshadow_vertical";s:1:"0";s:20:"close_boxshadow_blur";s:1:"0";s:22:"close_boxshadow_spread";s:1:"0";s:21:"close_boxshadow_color";s:7:"#020202";s:23:"close_boxshadow_opacity";s:2:"23";s:27:"close_textshadow_horizontal";s:1:"0";s:25:"close_textshadow_vertical";s:1:"0";s:21:"close_textshadow_blur";s:1:"0";s:22:"close_textshadow_color";s:7:"#000000";s:24:"close_textshadow_opacity";s:2:"23";s:13:"atc_promotion";N;s:22:"close_position_outside";i:0;}',
				'popup_theme_data_version' => 3,
			),
		),
		'hello-box'       => array(
			'post_title' => __( 'Hello Box', 'popup-maker' ),
			'meta_input' => array(
				'popup_theme_settings' => 'a:67:{s:24:"overlay_background_color";s:7:"#000000";s:26:"overlay_background_opacity";s:2:"75";s:17:"container_padding";s:2:"30";s:26:"container_background_color";s:7:"#ffffff";s:28:"container_background_opacity";s:3:"100";s:22:"container_border_style";s:5:"solid";s:22:"container_border_color";s:7:"#81d742";s:22:"container_border_width";s:2:"14";s:23:"container_border_radius";s:2:"80";s:25:"container_boxshadow_inset";s:2:"no";s:30:"container_boxshadow_horizontal";s:1:"0";s:28:"container_boxshadow_vertical";s:1:"0";s:24:"container_boxshadow_blur";s:1:"0";s:26:"container_boxshadow_spread";s:1:"0";s:25:"container_boxshadow_color";s:7:"#020202";s:27:"container_boxshadow_opacity";s:1:"0";s:16:"title_font_color";s:7:"#2d2d2d";s:17:"title_line_height";s:2:"36";s:15:"title_font_size";s:2:"32";s:17:"title_font_family";s:10:"Montserrat";s:17:"title_font_weight";s:3:"100";s:16:"title_font_style";s:0:"";s:16:"title_text_align";s:4:"left";s:27:"title_textshadow_horizontal";s:1:"0";s:25:"title_textshadow_vertical";s:1:"0";s:21:"title_textshadow_blur";s:1:"0";s:22:"title_textshadow_color";s:7:"#020202";s:24:"title_textshadow_opacity";s:2:"23";s:18:"content_font_color";s:7:"#2d2d2d";s:19:"content_font_family";s:7:"inherit";s:19:"content_font_weight";s:3:"100";s:18:"content_font_style";s:0:"";s:10:"close_text";s:2:"×";s:14:"close_location";s:8:"topright";s:18:"close_position_top";s:3:"-30";s:19:"close_position_left";s:3:"-30";s:21:"close_position_bottom";s:1:"0";s:20:"close_position_right";s:3:"-30";s:13:"close_padding";s:1:"0";s:12:"close_height";s:1:"0";s:11:"close_width";s:1:"0";s:22:"close_background_color";s:7:"#ffffff";s:24:"close_background_opacity";s:3:"100";s:16:"close_font_color";s:7:"#2d2d2d";s:17:"close_line_height";s:2:"28";s:15:"close_font_size";s:2:"32";s:17:"close_font_family";s:15:"Times New Roman";s:17:"close_font_weight";s:3:"100";s:16:"close_font_style";s:0:"";s:18:"close_border_style";s:4:"none";s:18:"close_border_color";s:7:"#ffffff";s:18:"close_border_width";s:1:"1";s:19:"close_border_radius";s:2:"28";s:21:"close_boxshadow_inset";s:2:"no";s:26:"close_boxshadow_horizontal";s:1:"0";s:24:"close_boxshadow_vertical";s:1:"0";s:20:"close_boxshadow_blur";s:1:"0";s:22:"close_boxshadow_spread";s:1:"0";s:21:"close_boxshadow_color";s:7:"#020202";s:23:"close_boxshadow_opacity";s:2:"23";s:27:"close_textshadow_horizontal";s:1:"0";s:25:"close_textshadow_vertical";s:1:"0";s:21:"close_textshadow_blur";s:1:"0";s:22:"close_textshadow_color";s:7:"#000000";s:24:"close_textshadow_opacity";s:2:"23";s:13:"atc_promotion";N;s:22:"close_position_outside";i:0;}',
				'popup_theme_data_version' => 3,
			),
		),
		'cutting-edge'    => array(
			'post_title' => __( 'Cutting Edge', 'popup-maker' ),
			'meta_input' => array(
				'popup_theme_settings' => 'a:67:{s:24:"overlay_background_color";s:7:"#000000";s:26:"overlay_background_opacity";s:2:"50";s:17:"container_padding";s:2:"18";s:26:"container_background_color";s:7:"#1e73be";s:28:"container_background_opacity";s:3:"100";s:22:"container_border_style";s:4:"none";s:22:"container_border_color";s:7:"#000000";s:22:"container_border_width";s:1:"1";s:23:"container_border_radius";s:1:"0";s:25:"container_boxshadow_inset";s:2:"no";s:30:"container_boxshadow_horizontal";s:1:"0";s:28:"container_boxshadow_vertical";s:2:"10";s:24:"container_boxshadow_blur";s:2:"25";s:26:"container_boxshadow_spread";s:1:"0";s:25:"container_boxshadow_color";s:7:"#020202";s:27:"container_boxshadow_opacity";s:2:"50";s:16:"title_font_color";s:7:"#ffffff";s:17:"title_line_height";s:2:"28";s:15:"title_font_size";s:2:"26";s:17:"title_font_family";s:10:"Sans-Serif";s:17:"title_font_weight";s:3:"100";s:16:"title_font_style";s:0:"";s:16:"title_text_align";s:4:"left";s:27:"title_textshadow_horizontal";s:1:"0";s:25:"title_textshadow_vertical";s:1:"0";s:21:"title_textshadow_blur";s:1:"0";s:22:"title_textshadow_color";s:7:"#020202";s:24:"title_textshadow_opacity";s:2:"23";s:18:"content_font_color";s:7:"#ffffff";s:19:"content_font_family";s:7:"inherit";s:19:"content_font_weight";s:3:"100";s:18:"content_font_style";s:0:"";s:10:"close_text";s:2:"×";s:14:"close_location";s:8:"topright";s:18:"close_position_top";s:1:"0";s:19:"close_position_left";s:1:"0";s:21:"close_position_bottom";s:1:"0";s:20:"close_position_right";s:1:"0";s:13:"close_padding";s:1:"0";s:12:"close_height";s:2:"24";s:11:"close_width";s:2:"24";s:22:"close_background_color";s:7:"#eeee22";s:24:"close_background_opacity";s:3:"100";s:16:"close_font_color";s:7:"#1e73be";s:17:"close_line_height";s:2:"24";s:15:"close_font_size";s:2:"32";s:17:"close_font_family";s:15:"Times New Roman";s:17:"close_font_weight";s:3:"100";s:16:"close_font_style";s:0:"";s:18:"close_border_style";s:4:"none";s:18:"close_border_color";s:7:"#ffffff";s:18:"close_border_width";s:1:"1";s:19:"close_border_radius";s:1:"0";s:21:"close_boxshadow_inset";s:2:"no";s:26:"close_boxshadow_horizontal";s:2:"-1";s:24:"close_boxshadow_vertical";s:1:"1";s:20:"close_boxshadow_blur";s:1:"1";s:22:"close_boxshadow_spread";s:1:"0";s:21:"close_boxshadow_color";s:7:"#020202";s:23:"close_boxshadow_opacity";s:2:"10";s:27:"close_textshadow_horizontal";s:2:"-1";s:25:"close_textshadow_vertical";s:1:"1";s:21:"close_textshadow_blur";s:1:"1";s:22:"close_textshadow_color";s:7:"#000000";s:24:"close_textshadow_opacity";s:2:"10";s:13:"atc_promotion";N;s:22:"close_position_outside";i:0;}',
				'popup_theme_data_version' => 3,
			),
		),
		'framed-border'   => array(
			'post_title' => __( 'Framed Border', 'popup-maker' ),
			'meta_input' => array(
				'popup_theme_settings' => 'a:67:{s:24:"overlay_background_color";s:7:"#ffffff";s:26:"overlay_background_opacity";s:2:"50";s:17:"container_padding";s:2:"18";s:26:"container_background_color";s:7:"#fffbef";s:28:"container_background_opacity";s:3:"100";s:22:"container_border_style";s:6:"outset";s:22:"container_border_color";s:7:"#dd3333";s:22:"container_border_width";s:2:"20";s:23:"container_border_radius";s:1:"0";s:25:"container_boxshadow_inset";s:3:"yes";s:30:"container_boxshadow_horizontal";s:1:"1";s:28:"container_boxshadow_vertical";s:1:"1";s:24:"container_boxshadow_blur";s:1:"3";s:26:"container_boxshadow_spread";s:1:"0";s:25:"container_boxshadow_color";s:7:"#020202";s:27:"container_boxshadow_opacity";s:2:"97";s:16:"title_font_color";s:7:"#000000";s:17:"title_line_height";s:2:"36";s:15:"title_font_size";s:2:"32";s:17:"title_font_family";s:7:"inherit";s:17:"title_font_weight";s:3:"100";s:16:"title_font_style";s:0:"";s:16:"title_text_align";s:4:"left";s:27:"title_textshadow_horizontal";s:1:"0";s:25:"title_textshadow_vertical";s:1:"0";s:21:"title_textshadow_blur";s:1:"0";s:22:"title_textshadow_color";s:7:"#020202";s:24:"title_textshadow_opacity";s:2:"23";s:18:"content_font_color";s:7:"#2d2d2d";s:19:"content_font_family";s:7:"inherit";s:19:"content_font_weight";s:3:"100";s:18:"content_font_style";s:0:"";s:10:"close_text";s:2:"×";s:14:"close_location";s:8:"topright";s:18:"close_position_top";s:3:"-20";s:19:"close_position_left";s:3:"-20";s:21:"close_position_bottom";s:1:"0";s:20:"close_position_right";s:3:"-20";s:13:"close_padding";s:1:"0";s:12:"close_height";s:2:"20";s:11:"close_width";s:2:"20";s:22:"close_background_color";s:7:"#000000";s:24:"close_background_opacity";s:2:"55";s:16:"close_font_color";s:7:"#ffffff";s:17:"close_line_height";s:2:"18";s:15:"close_font_size";s:2:"16";s:17:"close_font_family";s:6:"Tahoma";s:17:"close_font_weight";s:3:"700";s:16:"close_font_style";s:0:"";s:18:"close_border_style";s:4:"none";s:18:"close_border_color";s:7:"#ffffff";s:18:"close_border_width";s:1:"1";s:19:"close_border_radius";s:1:"0";s:21:"close_boxshadow_inset";s:2:"no";s:26:"close_boxshadow_horizontal";s:1:"0";s:24:"close_boxshadow_vertical";s:1:"0";s:20:"close_boxshadow_blur";s:1:"0";s:22:"close_boxshadow_spread";s:1:"0";s:21:"close_boxshadow_color";s:7:"#020202";s:23:"close_boxshadow_opacity";s:2:"23";s:27:"close_textshadow_horizontal";s:1:"0";s:25:"close_textshadow_vertical";s:1:"0";s:21:"close_textshadow_blur";s:1:"0";s:22:"close_textshadow_color";s:7:"#000000";s:24:"close_textshadow_opacity";s:2:"23";s:13:"atc_promotion";N;s:22:"close_position_outside";i:0;}',
				'popup_theme_data_version' => 3,
			),
		),
		'floating-bar'   => array(
			'post_title' => __( 'Floating Bar - Soft Blue', 'popup-maker' ),
			'meta_input' => array(
				'popup_theme_settings' => 'a:67:{s:24:"overlay_background_color";s:7:"#ffffff";s:26:"overlay_background_opacity";s:1:"0";s:13:"atc_promotion";N;s:17:"container_padding";s:1:"8";s:23:"container_border_radius";s:1:"0";s:26:"container_background_color";s:7:"#eef6fc";s:28:"container_background_opacity";s:3:"100";s:22:"container_border_style";s:4:"none";s:22:"container_border_color";s:7:"#000000";s:22:"container_border_width";s:1:"1";s:25:"container_boxshadow_color";s:7:"#020202";s:27:"container_boxshadow_opacity";s:2:"23";s:30:"container_boxshadow_horizontal";s:1:"1";s:28:"container_boxshadow_vertical";s:1:"1";s:24:"container_boxshadow_blur";s:1:"3";s:26:"container_boxshadow_spread";s:1:"0";s:25:"container_boxshadow_inset";s:2:"no";s:16:"title_font_color";s:7:"#505050";s:15:"title_font_size";s:2:"32";s:17:"title_line_height";s:2:"36";s:17:"title_font_family";s:7:"inherit";s:17:"title_font_weight";s:3:"400";s:16:"title_font_style";s:0:"";s:16:"title_text_align";s:4:"left";s:22:"title_textshadow_color";s:7:"#020202";s:24:"title_textshadow_opacity";s:2:"23";s:27:"title_textshadow_horizontal";s:1:"0";s:25:"title_textshadow_vertical";s:1:"0";s:21:"title_textshadow_blur";s:1:"0";s:18:"content_font_color";s:7:"#505050";s:19:"content_font_family";s:7:"inherit";s:19:"content_font_weight";s:3:"400";s:18:"content_font_style";s:0:"";s:10:"close_text";s:2:"×";s:22:"close_position_outside";i:0;s:14:"close_location";s:11:"middleright";s:18:"close_position_top";s:1:"0";s:21:"close_position_bottom";s:1:"0";s:19:"close_position_left";s:1:"0";s:20:"close_position_right";s:1:"5";s:13:"close_padding";s:1:"0";s:12:"close_height";s:2:"18";s:11:"close_width";s:2:"18";s:19:"close_border_radius";s:2:"15";s:22:"close_background_color";s:7:"#ffffff";s:24:"close_background_opacity";s:1:"0";s:16:"close_font_color";s:7:"#505050";s:15:"close_font_size";s:2:"15";s:17:"close_line_height";s:2:"18";s:17:"close_font_family";s:10:"Sans-Serif";s:17:"close_font_weight";s:3:"700";s:16:"close_font_style";s:0:"";s:18:"close_border_style";s:5:"solid";s:18:"close_border_color";s:7:"#505050";s:18:"close_border_width";s:1:"1";s:21:"close_boxshadow_color";s:7:"#020202";s:23:"close_boxshadow_opacity";s:1:"0";s:26:"close_boxshadow_horizontal";s:1:"0";s:24:"close_boxshadow_vertical";s:1:"0";s:20:"close_boxshadow_blur";s:1:"0";s:22:"close_boxshadow_spread";s:1:"0";s:21:"close_boxshadow_inset";s:2:"no";s:22:"close_textshadow_color";s:7:"#000000";s:24:"close_textshadow_opacity";s:1:"0";s:27:"close_textshadow_horizontal";s:1:"0";s:25:"close_textshadow_vertical";s:1:"0";s:21:"close_textshadow_blur";s:1:"0";}',
				'popup_theme_data_version' => 3,
			),
		),
	);

	$new_theme_installed = false;

	foreach ( $built_in_themes as $post_name => $_theme ) {

		if ( ! in_array( $post_name, $installed_themes ) ) {
			$_theme['post_name']                   = $post_name;
			$_theme['post_type']                   = 'popup_theme';
			$_theme['post_status']                 = 'publish';
			$_theme['meta_input']['_pum_built_in'] = $post_name;

			foreach ( $_theme['meta_input'] as $key => $value ) {
				$_theme['meta_input'][ $key ] = maybe_unserialize( $value );
			}

			wp_insert_post( $_theme );

			$installed_themes[] = $post_name;

			$new_theme_installed = true;
		}

	}

	if ( $new_theme_installed ) {
		pum_reset_assets();
		update_option( '_pum_installed_themes', $installed_themes );
	}

}
