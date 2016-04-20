<?php
/**
 * Install Functions
 *
 * @package     PUM
 * @subpackage  Functions/Install
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pum_install_built_in_themes( $network_wide = false ) {

	$installed_themes = get_option( '_pum_installed_themes', array() );

	$built_in_themes = array(
		'lightbox'        => array(
			'post_title' => __( 'Light Box', 'popup-maker' ),
			'meta_input' => array(
				'popup_theme_overlay'   => 'a:2:{s:16:"background_color";s:7:"#000000";s:18:"background_opacity";s:2:"60";}',
				'popup_theme_container' => 'a:14:{s:16:"background_color";s:7:"#ffffff";s:7:"padding";s:2:"18";s:18:"background_opacity";s:3:"100";s:13:"border_radius";s:1:"3";s:12:"border_color";s:7:"#000000";s:12:"border_style";s:5:"solid";s:12:"border_width";s:1:"8";s:15:"boxshadow_inset";s:2:"no";s:20:"boxshadow_horizontal";s:1:"0";s:18:"boxshadow_vertical";s:1:"0";s:15:"boxshadow_color";s:7:"#020202";s:14:"boxshadow_blur";s:2:"30";s:17:"boxshadow_opacity";s:3:"100";s:16:"boxshadow_spread";s:1:"0";}',
				'popup_theme_title'     => 'a:12:{s:11:"line_height";s:2:"36";s:9:"font_size";s:2:"32";s:11:"font_family";s:7:"inherit";s:11:"font_weight";s:0:"";s:10:"font_style";s:0:"";s:10:"text_align";s:4:"left";s:21:"textshadow_horizontal";s:1:"0";s:19:"textshadow_vertical";s:1:"0";s:15:"textshadow_blur";s:1:"0";s:18:"textshadow_opacity";s:2:"23";s:10:"font_color";s:7:"#000000";s:16:"textshadow_color";s:7:"#020202";}',
				'popup_theme_content'   => 'a:4:{s:10:"font_color";s:7:"#000000";s:11:"font_family";s:7:"inherit";s:11:"font_weight";s:0:"";s:10:"font_style";s:0:"";}',
				'popup_theme_close'     => 'a:33:{s:4:"text";s:2:"×";s:15:"position_bottom";s:1:"0";s:14:"position_right";s:3:"-24";s:11:"line_height";s:2:"26";s:13:"position_left";s:1:"0";s:12:"position_top";s:3:"-24";s:7:"padding";s:1:"0";s:6:"height";s:2:"30";s:5:"width";s:2:"30";s:9:"font_size";s:2:"24";s:11:"font_family";s:7:"inherit";s:11:"font_weight";s:0:"";s:13:"border_radius";s:2:"30";s:10:"font_style";s:0:"";s:12:"border_color";s:7:"#ffffff";s:8:"location";s:8:"topright";s:12:"border_style";s:5:"solid";s:21:"textshadow_horizontal";s:1:"0";s:12:"border_width";s:1:"2";s:19:"textshadow_vertical";s:1:"0";s:15:"textshadow_blur";s:1:"0";s:15:"boxshadow_inset";s:2:"no";s:10:"font_color";s:7:"#ffffff";s:20:"boxshadow_horizontal";s:1:"0";s:18:"boxshadow_vertical";s:1:"0";s:14:"boxshadow_blur";s:2:"15";s:16:"boxshadow_spread";s:1:"1";s:16:"background_color";s:7:"#000000";s:18:"background_opacity";s:3:"100";s:15:"boxshadow_color";s:7:"#020202";s:17:"boxshadow_opacity";s:2:"75";s:16:"textshadow_color";s:7:"#000000";s:18:"textshadow_opacity";s:2:"23";}',
			),
		),
		'enterprise-blue' => array(
			'post_title' => __( 'Enterprise Blue', 'popup-maker' ),
			'meta_input' => array(
				'popup_theme_overlay'   => 'a:2:{s:16:"background_color";s:7:"#000000";s:18:"background_opacity";s:2:"70";}',
				'popup_theme_container' => 'a:14:{s:16:"background_color";s:7:"#ffffff";s:7:"padding";s:2:"28";s:18:"background_opacity";s:3:"100";s:13:"border_radius";s:1:"5";s:12:"border_color";s:7:"#000000";s:12:"border_style";s:4:"none";s:12:"border_width";s:1:"1";s:15:"boxshadow_inset";s:2:"no";s:20:"boxshadow_horizontal";s:1:"0";s:18:"boxshadow_vertical";s:2:"10";s:15:"boxshadow_color";s:7:"#020202";s:14:"boxshadow_blur";s:2:"25";s:17:"boxshadow_opacity";s:2:"50";s:16:"boxshadow_spread";s:1:"4";}',
				'popup_theme_title'     => 'a:12:{s:11:"line_height";s:2:"36";s:9:"font_size";s:2:"34";s:11:"font_family";s:7:"inherit";s:11:"font_weight";s:0:"";s:10:"font_style";s:0:"";s:10:"text_align";s:4:"left";s:21:"textshadow_horizontal";s:1:"0";s:19:"textshadow_vertical";s:1:"0";s:15:"textshadow_blur";s:1:"0";s:18:"textshadow_opacity";s:2:"23";s:10:"font_color";s:7:"#315b7c";s:16:"textshadow_color";s:7:"#020202";}',
				'popup_theme_content'   => 'a:4:{s:10:"font_color";s:7:"#2d2d2d";s:11:"font_family";s:7:"inherit";s:11:"font_weight";s:0:"";s:10:"font_style";s:0:"";}',
				'popup_theme_close'     => 'a:33:{s:4:"text";s:2:"×";s:15:"position_bottom";s:1:"0";s:14:"position_right";s:1:"8";s:11:"line_height";s:2:"20";s:13:"position_left";s:1:"0";s:12:"position_top";s:1:"8";s:7:"padding";s:1:"4";s:6:"height";s:2:"28";s:5:"width";s:2:"28";s:9:"font_size";s:2:"20";s:11:"font_family";s:7:"inherit";s:11:"font_weight";s:0:"";s:13:"border_radius";s:2:"42";s:10:"font_style";s:0:"";s:12:"border_color";s:7:"#ffffff";s:8:"location";s:8:"topright";s:12:"border_style";s:4:"none";s:21:"textshadow_horizontal";s:1:"0";s:12:"border_width";s:1:"1";s:19:"textshadow_vertical";s:1:"0";s:15:"textshadow_blur";s:1:"0";s:15:"boxshadow_inset";s:2:"no";s:10:"font_color";s:7:"#ffffff";s:20:"boxshadow_horizontal";s:1:"0";s:18:"boxshadow_vertical";s:1:"0";s:14:"boxshadow_blur";s:1:"0";s:16:"boxshadow_spread";s:1:"0";s:16:"background_color";s:7:"#315b7c";s:18:"background_opacity";s:3:"100";s:15:"boxshadow_color";s:7:"#020202";s:17:"boxshadow_opacity";s:2:"23";s:16:"textshadow_color";s:7:"#000000";s:18:"textshadow_opacity";s:2:"23";}',
			),
		),
		'hello-box'       => array(
			'post_title' => __( 'Hello Box', 'popup-maker' ),
			'meta_input' => array(
				'popup_theme_overlay'   => 'a:2:{s:16:"background_color";s:7:"#000000";s:18:"background_opacity";s:2:"75";}',
				'popup_theme_container' => 'a:14:{s:16:"background_color";s:7:"#ffffff";s:7:"padding";s:2:"30";s:18:"background_opacity";s:3:"100";s:13:"border_radius";s:2:"80";s:12:"border_color";s:7:"#81d742";s:12:"border_style";s:5:"solid";s:12:"border_width";s:2:"14";s:15:"boxshadow_inset";s:2:"no";s:20:"boxshadow_horizontal";s:1:"0";s:18:"boxshadow_vertical";s:1:"0";s:15:"boxshadow_color";s:7:"#020202";s:14:"boxshadow_blur";s:1:"0";s:17:"boxshadow_opacity";s:1:"0";s:16:"boxshadow_spread";s:1:"0";}',
				'popup_theme_title'     => 'a:12:{s:11:"line_height";s:2:"36";s:9:"font_size";s:2:"32";s:11:"font_family";s:10:"Montserrat";s:11:"font_weight";s:0:"";s:10:"font_style";s:0:"";s:10:"text_align";s:4:"left";s:21:"textshadow_horizontal";s:1:"0";s:19:"textshadow_vertical";s:1:"0";s:15:"textshadow_blur";s:1:"0";s:18:"textshadow_opacity";s:2:"23";s:10:"font_color";s:7:"#2d2d2d";s:16:"textshadow_color";s:7:"#020202";}',
				'popup_theme_content'   => 'a:4:{s:10:"font_color";s:7:"#2d2d2d";s:11:"font_family";s:7:"inherit";s:11:"font_weight";s:0:"";s:10:"font_style";s:0:"";}',
				'popup_theme_close'     => 'a:33:{s:4:"text";s:2:"×";s:15:"position_bottom";s:1:"0";s:14:"position_right";s:3:"-30";s:11:"line_height";s:2:"28";s:13:"position_left";s:3:"-30";s:12:"position_top";s:3:"-30";s:7:"padding";s:1:"0";s:6:"height";s:1:"0";s:5:"width";s:1:"0";s:9:"font_size";s:2:"32";s:11:"font_family";s:7:"inherit";s:11:"font_weight";s:0:"";s:13:"border_radius";s:2:"28";s:10:"font_style";s:0:"";s:12:"border_color";s:7:"#ffffff";s:8:"location";s:8:"topright";s:12:"border_style";s:4:"none";s:21:"textshadow_horizontal";s:1:"0";s:12:"border_width";s:1:"1";s:19:"textshadow_vertical";s:1:"0";s:15:"textshadow_blur";s:1:"0";s:15:"boxshadow_inset";s:2:"no";s:10:"font_color";s:7:"#2d2d2d";s:20:"boxshadow_horizontal";s:1:"0";s:18:"boxshadow_vertical";s:1:"0";s:14:"boxshadow_blur";s:1:"0";s:16:"boxshadow_spread";s:1:"0";s:16:"background_color";s:7:"#ffffff";s:18:"background_opacity";s:3:"100";s:15:"boxshadow_color";s:7:"#020202";s:17:"boxshadow_opacity";s:2:"23";s:16:"textshadow_color";s:7:"#000000";s:18:"textshadow_opacity";s:2:"23";}',
			),
		),
		'cutting-edge'    => array(
			'post_title' => __( 'Cutting Edge', 'popup-maker' ),
			'meta_input' => array(
				'popup_theme_overlay'   => 'a:2:{s:16:"background_color";s:7:"#000000";s:18:"background_opacity";s:2:"50";}',
				'popup_theme_container' => 'a:14:{s:16:"background_color";s:7:"#1e73be";s:7:"padding";s:2:"18";s:18:"background_opacity";s:3:"100";s:13:"border_radius";s:1:"0";s:12:"border_color";s:7:"#000000";s:12:"border_style";s:4:"none";s:12:"border_width";s:1:"1";s:15:"boxshadow_inset";s:2:"no";s:20:"boxshadow_horizontal";s:1:"0";s:18:"boxshadow_vertical";s:2:"10";s:15:"boxshadow_color";s:7:"#020202";s:14:"boxshadow_blur";s:2:"25";s:17:"boxshadow_opacity";s:2:"50";s:16:"boxshadow_spread";s:1:"0";}',
				'popup_theme_title'     => 'a:12:{s:11:"line_height";s:2:"28";s:9:"font_size";s:2:"26";s:11:"font_family";s:10:"Sans-Serif";s:11:"font_weight";s:0:"";s:10:"font_style";s:0:"";s:10:"text_align";s:4:"left";s:21:"textshadow_horizontal";s:1:"0";s:19:"textshadow_vertical";s:1:"0";s:15:"textshadow_blur";s:1:"0";s:18:"textshadow_opacity";s:2:"23";s:10:"font_color";s:7:"#ffffff";s:16:"textshadow_color";s:7:"#020202";}',
				'popup_theme_content'   => 'a:4:{s:10:"font_color";s:7:"#ffffff";s:11:"font_family";s:7:"inherit";s:11:"font_weight";s:0:"";s:10:"font_style";s:0:"";}',
				'popup_theme_close'     => 'a:33:{s:4:"text";s:2:"×";s:15:"position_bottom";s:1:"0";s:14:"position_right";s:1:"0";s:11:"line_height";s:2:"24";s:13:"position_left";s:1:"0";s:12:"position_top";s:1:"0";s:7:"padding";s:1:"0";s:6:"height";s:2:"24";s:5:"width";s:2:"24";s:9:"font_size";s:2:"32";s:11:"font_family";s:7:"inherit";s:11:"font_weight";s:0:"";s:13:"border_radius";s:1:"0";s:10:"font_style";s:0:"";s:12:"border_color";s:7:"#ffffff";s:8:"location";s:8:"topright";s:12:"border_style";s:4:"none";s:21:"textshadow_horizontal";s:2:"-1";s:12:"border_width";s:1:"1";s:19:"textshadow_vertical";s:1:"1";s:15:"textshadow_blur";s:1:"1";s:15:"boxshadow_inset";s:2:"no";s:10:"font_color";s:7:"#1e73be";s:20:"boxshadow_horizontal";s:2:"-1";s:18:"boxshadow_vertical";s:1:"1";s:14:"boxshadow_blur";s:1:"1";s:16:"boxshadow_spread";s:1:"0";s:16:"background_color";s:7:"#eeee22";s:18:"background_opacity";s:3:"100";s:15:"boxshadow_color";s:7:"#020202";s:17:"boxshadow_opacity";s:2:"10";s:16:"textshadow_color";s:7:"#000000";s:18:"textshadow_opacity";s:2:"10";}',
			),
		),
		'framed-border'   => array(
			'post_title' => __( 'Framed Border', 'popup-maker' ),
			'meta_input' => array(
				'popup_theme_overlay'   => 'a:2:{s:16:"background_color";s:7:"#ffffff";s:18:"background_opacity";s:2:"50";}',
				'popup_theme_container' => 'a:14:{s:16:"background_color";s:7:"#fffbef";s:7:"padding";s:2:"18";s:18:"background_opacity";s:3:"100";s:13:"border_radius";s:1:"0";s:12:"border_color";s:7:"#dd3333";s:12:"border_style";s:6:"outset";s:12:"border_width";s:2:"20";s:15:"boxshadow_inset";s:3:"yes";s:20:"boxshadow_horizontal";s:1:"1";s:18:"boxshadow_vertical";s:1:"1";s:15:"boxshadow_color";s:7:"#020202";s:14:"boxshadow_blur";s:1:"3";s:17:"boxshadow_opacity";s:2:"97";s:16:"boxshadow_spread";s:1:"0";}',
				'popup_theme_title'     => 'a:12:{s:11:"line_height";s:2:"36";s:9:"font_size";s:2:"32";s:11:"font_family";s:7:"inherit";s:11:"font_weight";s:0:"";s:10:"font_style";s:0:"";s:10:"text_align";s:4:"left";s:21:"textshadow_horizontal";s:1:"0";s:19:"textshadow_vertical";s:1:"0";s:15:"textshadow_blur";s:1:"0";s:18:"textshadow_opacity";s:2:"23";s:10:"font_color";s:7:"#000000";s:16:"textshadow_color";s:7:"#020202";}',
				'popup_theme_content'   => 'a:4:{s:10:"font_color";s:7:"#2d2d2d";s:11:"font_family";s:7:"inherit";s:11:"font_weight";s:0:"";s:10:"font_style";s:0:"";}',
				'popup_theme_close'     => 'a:33:{s:4:"text";s:2:"×";s:15:"position_bottom";s:1:"0";s:14:"position_right";s:3:"-20";s:11:"line_height";s:2:"20";s:13:"position_left";s:3:"-20";s:12:"position_top";s:3:"-20";s:7:"padding";s:1:"0";s:6:"height";s:2:"20";s:5:"width";s:2:"20";s:9:"font_size";s:2:"20";s:11:"font_family";s:4:"Acme";s:11:"font_weight";s:0:"";s:13:"border_radius";s:1:"0";s:10:"font_style";s:0:"";s:12:"border_color";s:7:"#ffffff";s:8:"location";s:8:"topright";s:12:"border_style";s:4:"none";s:21:"textshadow_horizontal";s:1:"0";s:12:"border_width";s:1:"1";s:19:"textshadow_vertical";s:1:"0";s:15:"textshadow_blur";s:1:"0";s:15:"boxshadow_inset";s:2:"no";s:10:"font_color";s:7:"#ffffff";s:20:"boxshadow_horizontal";s:1:"0";s:18:"boxshadow_vertical";s:1:"0";s:14:"boxshadow_blur";s:1:"0";s:16:"boxshadow_spread";s:1:"0";s:16:"background_color";s:7:"#000000";s:18:"background_opacity";s:2:"55";s:15:"boxshadow_color";s:7:"#020202";s:17:"boxshadow_opacity";s:2:"23";s:16:"textshadow_color";s:7:"#000000";s:18:"textshadow_opacity";s:2:"23";}',
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
		pum_force_theme_css_refresh();
		update_option( '_pum_installed_themes', $installed_themes );
	}

}