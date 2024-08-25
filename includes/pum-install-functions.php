<?php
/**
 * Install Functions
 *
 * @package     PUM
 * @subpackage  Functions/Install
 * @copyright   Copyright (c) 2023, Code Atlantic LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates the example popups
 *
 * @since 1.14.0
 *
 * @return void
 */
function pum_install_example_popups() {
	$example_popups = [
		'auto_open_announcement' => [
			'post_title'     => 'Example: Auto-opening announcement popup',
			'post_type'      => 'popup',
			'post_content'   => 'You can see how this popup was set up in our step-by-step guide: https://wppopupmaker.com/guides/auto-opening-announcement-popups/',
			'post_status'    => 'publish',
			'popup_title'    => 'Our Spring Sale Has Started',
			'popup_settings' => 'a:39:{s:8:"triggers";a:1:{i:0;a:2:{s:4:"type";s:9:"auto_open";s:8:"settings";a:2:{s:11:"cookie_name";a:1:{i:0;s:8:"pum-2094";}s:5:"delay";i:500;}}}s:7:"cookies";a:1:{i:0;a:2:{s:5:"event";s:14:"on_popup_close";s:8:"settings";a:5:{s:4:"name";s:8:"pum-2094";s:3:"key";s:0:"";s:7:"session";b:0;s:4:"time";s:7:"1 month";s:4:"path";s:1:"1";}}}s:10:"conditions";a:1:{i:0;a:1:{i:0;a:1:{s:6:"target";s:13:"is_front_page";}}}s:8:"theme_id";s:4:"2085";s:4:"size";s:6:"medium";s:20:"responsive_min_width";s:2:"0%";s:20:"responsive_max_width";s:4:"100%";s:12:"custom_width";s:5:"640px";s:13:"custom_height";s:5:"380px";s:14:"animation_type";s:4:"fade";s:15:"animation_speed";s:3:"350";s:16:"animation_origin";s:10:"center top";s:10:"open_sound";s:4:"none";s:12:"custom_sound";s:0:"";s:8:"location";s:6:"center";s:12:"position_top";s:3:"100";s:15:"position_bottom";s:1:"0";s:13:"position_left";s:1:"0";s:14:"position_right";s:1:"0";s:6:"zindex";s:10:"1999999999";s:10:"close_text";s:0:"";s:18:"close_button_delay";s:1:"0";s:30:"close_on_form_submission_delay";s:1:"0";s:17:"disable_on_mobile";b:0;s:17:"disable_on_tablet";b:0;s:18:"custom_height_auto";b:0;s:18:"scrollable_content";b:0;s:21:"position_from_trigger";b:0;s:14:"position_fixed";b:0;s:16:"overlay_disabled";b:0;s:9:"stackable";b:0;s:18:"disable_reposition";b:0;s:24:"close_on_form_submission";b:0;s:22:"close_on_overlay_click";b:0;s:18:"close_on_esc_press";b:0;s:17:"close_on_f4_press";b:0;s:19:"disable_form_reopen";b:0;s:21:"disable_accessibility";b:0;s:10:"theme_slug";s:13:"default-theme";}',
		],
		// Always append new popups to the end of the array. This will be required until we have key based installations.
	];

	$example_popup_count = count( $example_popups );

	/**
	 * Stores the number of example popups installed.
	 *
	 * @var int|false False if skipped, null if not set, or the number of example popups installed.
	 */
	$example_popups_installed = get_option( 'pum_example_popups_installed', 0 );

	if ( $example_popups_installed >= $example_popup_count || false === $example_popups_installed ) {
		return;
	}

	// Check if the popups are installed.
	// Check for the post meta key pum_example_popup.
	$popups = get_posts( [
		'post_type'      => 'popup',
		'post_status'    => 'any',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		'meta_query'     => [
			'relation' => 'AND',
			[
				'key'     => 'pum_example_popup',
				'compare' => 'EXISTS',
			],
		],
		'posts_per_page' => - 1,
	] );

	if ( count( $popups ) > 0 ) {
		$example_popup_count = count( $popups );
	} elseif ( 0 === $example_popups_installed ) {
		// Check by post_title `'Example: Auto-opening announcement popup'`.
		$popups = get_posts(
			[
				'post_type'      => 'popup',
				'post_status'    => 'any',
				'post_title'     => 'Example: Auto-opening announcement popup',
				'posts_per_page' => - 1,
			]
		);

		if ( count( $popups ) > 0 ) {
			// Set it to 1 as that is how many existed at the time this was written.
			$example_popups_installed = 1;
		}
	}

	if ( $example_popups_installed >= $example_popup_count ) {
		update_option( 'pum_example_popups_installed', $example_popups_installed, true );
		return;
	}

	// Remove the first X example popups where X is the number of example popups installed.
	$example_popups = array_slice( $example_popups, (int) $example_popups_installed );

	// Loop through the example popups.
	foreach ( $example_popups as $key => $popup ) {
		// Get post_title, type, content & status into a new array.
		$popup_post = array_intersect_key( $popup, array_flip( [ 'post_title', 'post_type', 'post_content', 'post_status' ] ) );

		// Get popup ID after inserting the post.
		$popup_id = wp_insert_post( $popup_post );

		// Set the post meta.
		update_post_meta( $popup_id, 'popup_title', $popup['popup_title'] );
		update_post_meta( $popup_id, 'enabled', 0 );
		update_post_meta( $popup_id, 'pum_example_popup', $key );

		// Sets up popup settings and saves it as the post meta.
		$popup_settings = maybe_unserialize( $popup['popup_settings'] );
		// Update cookie names to be prefixed with the popup ID.
		$popup_settings['triggers'][0]['settings']['cookie_name'] = "pum-$popup_id";
		$popup_settings['cookies'][0]['settings']['name']         = "pum-$popup_id";
		// Set the theme ID.
		$popup_settings['theme_id'] = pum_get_default_theme_id();
		// Update the post meta.
		update_post_meta( $popup_id, 'popup_settings', $popup_settings );

		++$example_popups_installed;
	}

	update_option( 'pum_example_popups_installed', $example_popups_installed, true );
}

/**
 * Enqueues our install theme function on wp_loaded if Popup Maker core was updated.
 *
 * @since 1.11.0
 *
 * @return void
 */
function pum_install_new_themes_on_update() {
	add_action( 'wp_loaded', 'pum_install_built_in_themes' );
}

add_action( 'pum_update_core_version', 'pum_install_new_themes_on_update' );


/**
 * Installs the built in themes.
 *
 * @param bool $network_wide Whether to install the themes for all sites in the network.
 *
 * @return void
 */
function pum_install_built_in_themes( $network_wide = false ) {

	$installed_themes = get_option( '_pum_installed_themes', [] );

	$built_in_themes = [
		'lightbox'        => [
			'post_title' => __( 'Light Box', 'popup-maker' ),
			'meta_input' => [
				'popup_theme_settings'     => 'a:67:{s:24:"overlay_background_color";s:7:"#000000";s:26:"overlay_background_opacity";s:2:"60";s:17:"container_padding";s:2:"18";s:26:"container_background_color";s:7:"#ffffff";s:28:"container_background_opacity";s:3:"100";s:22:"container_border_style";s:5:"solid";s:22:"container_border_color";s:7:"#000000";s:22:"container_border_width";s:1:"8";s:23:"container_border_radius";s:1:"3";s:25:"container_boxshadow_inset";s:2:"no";s:30:"container_boxshadow_horizontal";s:1:"0";s:28:"container_boxshadow_vertical";s:1:"0";s:24:"container_boxshadow_blur";s:2:"30";s:26:"container_boxshadow_spread";s:1:"0";s:25:"container_boxshadow_color";s:7:"#020202";s:27:"container_boxshadow_opacity";s:3:"100";s:16:"title_font_color";s:7:"#000000";s:17:"title_line_height";s:2:"36";s:15:"title_font_size";s:2:"32";s:17:"title_font_family";s:7:"inherit";s:17:"title_font_weight";s:3:"100";s:16:"title_font_style";s:0:"";s:16:"title_text_align";s:4:"left";s:27:"title_textshadow_horizontal";s:1:"0";s:25:"title_textshadow_vertical";s:1:"0";s:21:"title_textshadow_blur";s:1:"0";s:22:"title_textshadow_color";s:7:"#020202";s:24:"title_textshadow_opacity";s:2:"23";s:18:"content_font_color";s:7:"#000000";s:19:"content_font_family";s:7:"inherit";s:19:"content_font_weight";s:3:"100";s:18:"content_font_style";s:0:"";s:10:"close_text";s:7:"&times;";s:14:"close_location";s:8:"topright";s:18:"close_position_top";s:3:"-13";s:19:"close_position_left";s:1:"0";s:21:"close_position_bottom";s:1:"0";s:20:"close_position_right";s:3:"-13";s:13:"close_padding";s:1:"0";s:12:"close_height";s:2:"26";s:11:"close_width";s:2:"26";s:22:"close_background_color";s:7:"#000000";s:24:"close_background_opacity";s:3:"100";s:16:"close_font_color";s:7:"#ffffff";s:17:"close_line_height";s:2:"24";s:15:"close_font_size";s:2:"24";s:17:"close_font_family";s:5:"Arial";s:17:"close_font_weight";s:3:"100";s:16:"close_font_style";s:0:"";s:18:"close_border_style";s:5:"solid";s:18:"close_border_color";s:7:"#ffffff";s:18:"close_border_width";s:1:"2";s:19:"close_border_radius";s:2:"26";s:21:"close_boxshadow_inset";s:2:"no";s:26:"close_boxshadow_horizontal";s:1:"0";s:24:"close_boxshadow_vertical";s:1:"0";s:20:"close_boxshadow_blur";s:2:"15";s:22:"close_boxshadow_spread";s:1:"1";s:21:"close_boxshadow_color";s:7:"#020202";s:23:"close_boxshadow_opacity";s:2:"75";s:27:"close_textshadow_horizontal";s:1:"0";s:25:"close_textshadow_vertical";s:1:"0";s:21:"close_textshadow_blur";s:1:"0";s:22:"close_textshadow_color";s:7:"#000000";s:24:"close_textshadow_opacity";s:2:"23";s:13:"atc_promotion";N;s:22:"close_position_outside";i:0;}',
				'popup_theme_data_version' => 3,
			],
		],
		'enterprise-blue' => [
			'post_title' => __( 'Enterprise Blue', 'popup-maker' ),
			'meta_input' => [
				'popup_theme_settings'     => 'a:67:{s:24:"overlay_background_color";s:7:"#000000";s:26:"overlay_background_opacity";s:2:"70";s:17:"container_padding";s:2:"28";s:26:"container_background_color";s:7:"#ffffff";s:28:"container_background_opacity";s:3:"100";s:22:"container_border_style";s:4:"none";s:22:"container_border_color";s:7:"#000000";s:22:"container_border_width";s:1:"1";s:23:"container_border_radius";s:1:"5";s:25:"container_boxshadow_inset";s:2:"no";s:30:"container_boxshadow_horizontal";s:1:"0";s:28:"container_boxshadow_vertical";s:2:"10";s:24:"container_boxshadow_blur";s:2:"25";s:26:"container_boxshadow_spread";s:1:"4";s:25:"container_boxshadow_color";s:7:"#020202";s:27:"container_boxshadow_opacity";s:2:"50";s:16:"title_font_color";s:7:"#315b7c";s:17:"title_line_height";s:2:"36";s:15:"title_font_size";s:2:"34";s:17:"title_font_family";s:7:"inherit";s:17:"title_font_weight";s:3:"100";s:16:"title_font_style";s:0:"";s:16:"title_text_align";s:4:"left";s:27:"title_textshadow_horizontal";s:1:"0";s:25:"title_textshadow_vertical";s:1:"0";s:21:"title_textshadow_blur";s:1:"0";s:22:"title_textshadow_color";s:7:"#020202";s:24:"title_textshadow_opacity";s:2:"23";s:18:"content_font_color";s:7:"#2d2d2d";s:19:"content_font_family";s:7:"inherit";s:19:"content_font_weight";s:3:"100";s:18:"content_font_style";s:0:"";s:10:"close_text";s:2:"×";s:14:"close_location";s:8:"topright";s:18:"close_position_top";s:1:"8";s:19:"close_position_left";s:1:"0";s:21:"close_position_bottom";s:1:"0";s:20:"close_position_right";s:1:"8";s:13:"close_padding";s:1:"4";s:12:"close_height";s:2:"28";s:11:"close_width";s:2:"28";s:22:"close_background_color";s:7:"#315b7c";s:24:"close_background_opacity";s:3:"100";s:16:"close_font_color";s:7:"#ffffff";s:17:"close_line_height";s:2:"20";s:15:"close_font_size";s:2:"20";s:17:"close_font_family";s:15:"Times New Roman";s:17:"close_font_weight";s:3:"100";s:16:"close_font_style";s:0:"";s:18:"close_border_style";s:4:"none";s:18:"close_border_color";s:7:"#ffffff";s:18:"close_border_width";s:1:"1";s:19:"close_border_radius";s:2:"42";s:21:"close_boxshadow_inset";s:2:"no";s:26:"close_boxshadow_horizontal";s:1:"0";s:24:"close_boxshadow_vertical";s:1:"0";s:20:"close_boxshadow_blur";s:1:"0";s:22:"close_boxshadow_spread";s:1:"0";s:21:"close_boxshadow_color";s:7:"#020202";s:23:"close_boxshadow_opacity";s:2:"23";s:27:"close_textshadow_horizontal";s:1:"0";s:25:"close_textshadow_vertical";s:1:"0";s:21:"close_textshadow_blur";s:1:"0";s:22:"close_textshadow_color";s:7:"#000000";s:24:"close_textshadow_opacity";s:2:"23";s:13:"atc_promotion";N;s:22:"close_position_outside";i:0;}',
				'popup_theme_data_version' => 3,
			],
		],
		'hello-box'       => [
			'post_title' => __( 'Hello Box', 'popup-maker' ),
			'meta_input' => [
				'popup_theme_settings'     => 'a:67:{s:24:"overlay_background_color";s:7:"#000000";s:26:"overlay_background_opacity";s:2:"75";s:17:"container_padding";s:2:"30";s:26:"container_background_color";s:7:"#ffffff";s:28:"container_background_opacity";s:3:"100";s:22:"container_border_style";s:5:"solid";s:22:"container_border_color";s:7:"#81d742";s:22:"container_border_width";s:2:"14";s:23:"container_border_radius";s:2:"80";s:25:"container_boxshadow_inset";s:2:"no";s:30:"container_boxshadow_horizontal";s:1:"0";s:28:"container_boxshadow_vertical";s:1:"0";s:24:"container_boxshadow_blur";s:1:"0";s:26:"container_boxshadow_spread";s:1:"0";s:25:"container_boxshadow_color";s:7:"#020202";s:27:"container_boxshadow_opacity";s:1:"0";s:16:"title_font_color";s:7:"#2d2d2d";s:17:"title_line_height";s:2:"36";s:15:"title_font_size";s:2:"32";s:17:"title_font_family";s:10:"Montserrat";s:17:"title_font_weight";s:3:"100";s:16:"title_font_style";s:0:"";s:16:"title_text_align";s:4:"left";s:27:"title_textshadow_horizontal";s:1:"0";s:25:"title_textshadow_vertical";s:1:"0";s:21:"title_textshadow_blur";s:1:"0";s:22:"title_textshadow_color";s:7:"#020202";s:24:"title_textshadow_opacity";s:2:"23";s:18:"content_font_color";s:7:"#2d2d2d";s:19:"content_font_family";s:7:"inherit";s:19:"content_font_weight";s:3:"100";s:18:"content_font_style";s:0:"";s:10:"close_text";s:2:"×";s:14:"close_location";s:8:"topright";s:18:"close_position_top";s:3:"-30";s:19:"close_position_left";s:3:"-30";s:21:"close_position_bottom";s:1:"0";s:20:"close_position_right";s:3:"-30";s:13:"close_padding";s:1:"0";s:12:"close_height";s:1:"0";s:11:"close_width";s:1:"0";s:22:"close_background_color";s:7:"#ffffff";s:24:"close_background_opacity";s:3:"100";s:16:"close_font_color";s:7:"#2d2d2d";s:17:"close_line_height";s:2:"28";s:15:"close_font_size";s:2:"32";s:17:"close_font_family";s:15:"Times New Roman";s:17:"close_font_weight";s:3:"100";s:16:"close_font_style";s:0:"";s:18:"close_border_style";s:4:"none";s:18:"close_border_color";s:7:"#ffffff";s:18:"close_border_width";s:1:"1";s:19:"close_border_radius";s:2:"28";s:21:"close_boxshadow_inset";s:2:"no";s:26:"close_boxshadow_horizontal";s:1:"0";s:24:"close_boxshadow_vertical";s:1:"0";s:20:"close_boxshadow_blur";s:1:"0";s:22:"close_boxshadow_spread";s:1:"0";s:21:"close_boxshadow_color";s:7:"#020202";s:23:"close_boxshadow_opacity";s:2:"23";s:27:"close_textshadow_horizontal";s:1:"0";s:25:"close_textshadow_vertical";s:1:"0";s:21:"close_textshadow_blur";s:1:"0";s:22:"close_textshadow_color";s:7:"#000000";s:24:"close_textshadow_opacity";s:2:"23";s:13:"atc_promotion";N;s:22:"close_position_outside";i:0;}',
				'popup_theme_data_version' => 3,
			],
		],
		'cutting-edge'    => [
			'post_title' => __( 'Cutting Edge', 'popup-maker' ),
			'meta_input' => [
				'popup_theme_settings'     => 'a:67:{s:24:"overlay_background_color";s:7:"#000000";s:26:"overlay_background_opacity";s:2:"50";s:17:"container_padding";s:2:"18";s:26:"container_background_color";s:7:"#1e73be";s:28:"container_background_opacity";s:3:"100";s:22:"container_border_style";s:4:"none";s:22:"container_border_color";s:7:"#000000";s:22:"container_border_width";s:1:"1";s:23:"container_border_radius";s:1:"0";s:25:"container_boxshadow_inset";s:2:"no";s:30:"container_boxshadow_horizontal";s:1:"0";s:28:"container_boxshadow_vertical";s:2:"10";s:24:"container_boxshadow_blur";s:2:"25";s:26:"container_boxshadow_spread";s:1:"0";s:25:"container_boxshadow_color";s:7:"#020202";s:27:"container_boxshadow_opacity";s:2:"50";s:16:"title_font_color";s:7:"#ffffff";s:17:"title_line_height";s:2:"28";s:15:"title_font_size";s:2:"26";s:17:"title_font_family";s:10:"Sans-Serif";s:17:"title_font_weight";s:3:"100";s:16:"title_font_style";s:0:"";s:16:"title_text_align";s:4:"left";s:27:"title_textshadow_horizontal";s:1:"0";s:25:"title_textshadow_vertical";s:1:"0";s:21:"title_textshadow_blur";s:1:"0";s:22:"title_textshadow_color";s:7:"#020202";s:24:"title_textshadow_opacity";s:2:"23";s:18:"content_font_color";s:7:"#ffffff";s:19:"content_font_family";s:7:"inherit";s:19:"content_font_weight";s:3:"100";s:18:"content_font_style";s:0:"";s:10:"close_text";s:2:"×";s:14:"close_location";s:8:"topright";s:18:"close_position_top";s:1:"0";s:19:"close_position_left";s:1:"0";s:21:"close_position_bottom";s:1:"0";s:20:"close_position_right";s:1:"0";s:13:"close_padding";s:1:"0";s:12:"close_height";s:2:"24";s:11:"close_width";s:2:"24";s:22:"close_background_color";s:7:"#eeee22";s:24:"close_background_opacity";s:3:"100";s:16:"close_font_color";s:7:"#1e73be";s:17:"close_line_height";s:2:"24";s:15:"close_font_size";s:2:"32";s:17:"close_font_family";s:15:"Times New Roman";s:17:"close_font_weight";s:3:"100";s:16:"close_font_style";s:0:"";s:18:"close_border_style";s:4:"none";s:18:"close_border_color";s:7:"#ffffff";s:18:"close_border_width";s:1:"1";s:19:"close_border_radius";s:1:"0";s:21:"close_boxshadow_inset";s:2:"no";s:26:"close_boxshadow_horizontal";s:2:"-1";s:24:"close_boxshadow_vertical";s:1:"1";s:20:"close_boxshadow_blur";s:1:"1";s:22:"close_boxshadow_spread";s:1:"0";s:21:"close_boxshadow_color";s:7:"#020202";s:23:"close_boxshadow_opacity";s:2:"10";s:27:"close_textshadow_horizontal";s:2:"-1";s:25:"close_textshadow_vertical";s:1:"1";s:21:"close_textshadow_blur";s:1:"1";s:22:"close_textshadow_color";s:7:"#000000";s:24:"close_textshadow_opacity";s:2:"10";s:13:"atc_promotion";N;s:22:"close_position_outside";i:0;}',
				'popup_theme_data_version' => 3,
			],
		],
		'framed-border'   => [
			'post_title' => __( 'Framed Border', 'popup-maker' ),
			'meta_input' => [
				'popup_theme_settings'     => 'a:67:{s:24:"overlay_background_color";s:7:"#ffffff";s:26:"overlay_background_opacity";s:2:"50";s:17:"container_padding";s:2:"18";s:26:"container_background_color";s:7:"#fffbef";s:28:"container_background_opacity";s:3:"100";s:22:"container_border_style";s:6:"outset";s:22:"container_border_color";s:7:"#dd3333";s:22:"container_border_width";s:2:"20";s:23:"container_border_radius";s:1:"0";s:25:"container_boxshadow_inset";s:3:"yes";s:30:"container_boxshadow_horizontal";s:1:"1";s:28:"container_boxshadow_vertical";s:1:"1";s:24:"container_boxshadow_blur";s:1:"3";s:26:"container_boxshadow_spread";s:1:"0";s:25:"container_boxshadow_color";s:7:"#020202";s:27:"container_boxshadow_opacity";s:2:"97";s:16:"title_font_color";s:7:"#000000";s:17:"title_line_height";s:2:"36";s:15:"title_font_size";s:2:"32";s:17:"title_font_family";s:7:"inherit";s:17:"title_font_weight";s:3:"100";s:16:"title_font_style";s:0:"";s:16:"title_text_align";s:4:"left";s:27:"title_textshadow_horizontal";s:1:"0";s:25:"title_textshadow_vertical";s:1:"0";s:21:"title_textshadow_blur";s:1:"0";s:22:"title_textshadow_color";s:7:"#020202";s:24:"title_textshadow_opacity";s:2:"23";s:18:"content_font_color";s:7:"#2d2d2d";s:19:"content_font_family";s:7:"inherit";s:19:"content_font_weight";s:3:"100";s:18:"content_font_style";s:0:"";s:10:"close_text";s:2:"×";s:14:"close_location";s:8:"topright";s:18:"close_position_top";s:3:"-20";s:19:"close_position_left";s:3:"-20";s:21:"close_position_bottom";s:1:"0";s:20:"close_position_right";s:3:"-20";s:13:"close_padding";s:1:"0";s:12:"close_height";s:2:"20";s:11:"close_width";s:2:"20";s:22:"close_background_color";s:7:"#000000";s:24:"close_background_opacity";s:2:"55";s:16:"close_font_color";s:7:"#ffffff";s:17:"close_line_height";s:2:"18";s:15:"close_font_size";s:2:"16";s:17:"close_font_family";s:6:"Tahoma";s:17:"close_font_weight";s:3:"700";s:16:"close_font_style";s:0:"";s:18:"close_border_style";s:4:"none";s:18:"close_border_color";s:7:"#ffffff";s:18:"close_border_width";s:1:"1";s:19:"close_border_radius";s:1:"0";s:21:"close_boxshadow_inset";s:2:"no";s:26:"close_boxshadow_horizontal";s:1:"0";s:24:"close_boxshadow_vertical";s:1:"0";s:20:"close_boxshadow_blur";s:1:"0";s:22:"close_boxshadow_spread";s:1:"0";s:21:"close_boxshadow_color";s:7:"#020202";s:23:"close_boxshadow_opacity";s:2:"23";s:27:"close_textshadow_horizontal";s:1:"0";s:25:"close_textshadow_vertical";s:1:"0";s:21:"close_textshadow_blur";s:1:"0";s:22:"close_textshadow_color";s:7:"#000000";s:24:"close_textshadow_opacity";s:2:"23";s:13:"atc_promotion";N;s:22:"close_position_outside";i:0;}',
				'popup_theme_data_version' => 3,
			],
		],
		'floating-bar'    => [
			'post_title' => __( 'Floating Bar - Soft Blue', 'popup-maker' ),
			'meta_input' => [
				'popup_theme_settings'     => 'a:67:{s:24:"overlay_background_color";s:7:"#ffffff";s:26:"overlay_background_opacity";s:1:"0";s:13:"atc_promotion";N;s:17:"container_padding";s:1:"8";s:23:"container_border_radius";s:1:"0";s:26:"container_background_color";s:7:"#eef6fc";s:28:"container_background_opacity";s:3:"100";s:22:"container_border_style";s:4:"none";s:22:"container_border_color";s:7:"#000000";s:22:"container_border_width";s:1:"1";s:25:"container_boxshadow_color";s:7:"#020202";s:27:"container_boxshadow_opacity";s:2:"23";s:30:"container_boxshadow_horizontal";s:1:"1";s:28:"container_boxshadow_vertical";s:1:"1";s:24:"container_boxshadow_blur";s:1:"3";s:26:"container_boxshadow_spread";s:1:"0";s:25:"container_boxshadow_inset";s:2:"no";s:16:"title_font_color";s:7:"#505050";s:15:"title_font_size";s:2:"32";s:17:"title_line_height";s:2:"36";s:17:"title_font_family";s:7:"inherit";s:17:"title_font_weight";s:3:"400";s:16:"title_font_style";s:0:"";s:16:"title_text_align";s:4:"left";s:22:"title_textshadow_color";s:7:"#020202";s:24:"title_textshadow_opacity";s:2:"23";s:27:"title_textshadow_horizontal";s:1:"0";s:25:"title_textshadow_vertical";s:1:"0";s:21:"title_textshadow_blur";s:1:"0";s:18:"content_font_color";s:7:"#505050";s:19:"content_font_family";s:7:"inherit";s:19:"content_font_weight";s:3:"400";s:18:"content_font_style";s:0:"";s:10:"close_text";s:2:"×";s:22:"close_position_outside";i:0;s:14:"close_location";s:11:"middleright";s:18:"close_position_top";s:1:"0";s:21:"close_position_bottom";s:1:"0";s:19:"close_position_left";s:1:"0";s:20:"close_position_right";s:1:"5";s:13:"close_padding";s:1:"0";s:12:"close_height";s:2:"18";s:11:"close_width";s:2:"18";s:19:"close_border_radius";s:2:"15";s:22:"close_background_color";s:7:"#ffffff";s:24:"close_background_opacity";s:1:"0";s:16:"close_font_color";s:7:"#505050";s:15:"close_font_size";s:2:"15";s:17:"close_line_height";s:2:"18";s:17:"close_font_family";s:10:"Sans-Serif";s:17:"close_font_weight";s:3:"700";s:16:"close_font_style";s:0:"";s:18:"close_border_style";s:5:"solid";s:18:"close_border_color";s:7:"#505050";s:18:"close_border_width";s:1:"1";s:21:"close_boxshadow_color";s:7:"#020202";s:23:"close_boxshadow_opacity";s:1:"0";s:26:"close_boxshadow_horizontal";s:1:"0";s:24:"close_boxshadow_vertical";s:1:"0";s:20:"close_boxshadow_blur";s:1:"0";s:22:"close_boxshadow_spread";s:1:"0";s:21:"close_boxshadow_inset";s:2:"no";s:22:"close_textshadow_color";s:7:"#000000";s:24:"close_textshadow_opacity";s:1:"0";s:27:"close_textshadow_horizontal";s:1:"0";s:25:"close_textshadow_vertical";s:1:"0";s:21:"close_textshadow_blur";s:1:"0";}',
				'popup_theme_data_version' => 3,
			],
		],
		'content-only'    => [
			'post_title' => __( 'Content Only - For use with page builders or block editor', 'popup-maker' ),
			'meta_input' => [
				'popup_theme_settings'     => 'a:67:{s:24:"overlay_background_color";s:7:"#000000";s:26:"overlay_background_opacity";s:2:"70";s:13:"atc_promotion";N;s:17:"container_padding";s:1:"0";s:23:"container_border_radius";s:1:"0";s:26:"container_background_color";s:0:"";s:28:"container_background_opacity";s:1:"0";s:22:"container_border_style";s:4:"none";s:22:"container_border_color";s:7:"#000000";s:22:"container_border_width";s:1:"1";s:25:"container_boxshadow_color";s:7:"#020202";s:27:"container_boxshadow_opacity";s:1:"0";s:30:"container_boxshadow_horizontal";s:1:"0";s:28:"container_boxshadow_vertical";s:1:"0";s:24:"container_boxshadow_blur";s:1:"0";s:26:"container_boxshadow_spread";s:1:"0";s:25:"container_boxshadow_inset";s:2:"no";s:16:"title_font_color";s:7:"#000000";s:15:"title_font_size";s:2:"32";s:17:"title_line_height";s:2:"36";s:17:"title_font_family";s:7:"inherit";s:17:"title_font_weight";s:3:"400";s:16:"title_font_style";s:0:"";s:16:"title_text_align";s:4:"left";s:22:"title_textshadow_color";s:7:"#020202";s:24:"title_textshadow_opacity";s:2:"23";s:27:"title_textshadow_horizontal";s:1:"0";s:25:"title_textshadow_vertical";s:1:"0";s:21:"title_textshadow_blur";s:1:"0";s:18:"content_font_color";s:7:"#8c8c8c";s:19:"content_font_family";s:7:"inherit";s:19:"content_font_weight";s:3:"400";s:18:"content_font_style";s:0:"";s:10:"close_text";s:2:"×";s:22:"close_position_outside";i:0;s:14:"close_location";s:8:"topright";s:18:"close_position_top";s:1:"7";s:21:"close_position_bottom";s:1:"0";s:19:"close_position_left";s:1:"0";s:20:"close_position_right";s:1:"7";s:13:"close_padding";s:1:"0";s:12:"close_height";s:2:"18";s:11:"close_width";s:2:"18";s:19:"close_border_radius";s:2:"15";s:22:"close_background_color";s:7:"#ffffff";s:24:"close_background_opacity";s:1:"0";s:16:"close_font_color";s:7:"#000000";s:15:"close_font_size";s:2:"20";s:17:"close_line_height";s:2:"20";s:17:"close_font_family";s:7:"inherit";s:17:"close_font_weight";s:3:"700";s:16:"close_font_style";s:0:"";s:18:"close_border_style";s:4:"none";s:18:"close_border_color";s:7:"#ffffff";s:18:"close_border_width";s:1:"1";s:21:"close_boxshadow_color";s:7:"#020202";s:23:"close_boxshadow_opacity";s:1:"0";s:26:"close_boxshadow_horizontal";s:1:"0";s:24:"close_boxshadow_vertical";s:1:"0";s:20:"close_boxshadow_blur";s:1:"0";s:22:"close_boxshadow_spread";s:1:"0";s:21:"close_boxshadow_inset";s:2:"no";s:22:"close_textshadow_color";s:7:"#000000";s:24:"close_textshadow_opacity";s:1:"0";s:27:"close_textshadow_horizontal";s:1:"0";s:25:"close_textshadow_vertical";s:1:"0";s:21:"close_textshadow_blur";s:1:"0";}',
				'popup_theme_data_version' => 3,
			],
		],
	];

	$new_theme_installed = false;

	foreach ( $built_in_themes as $post_name => $_theme ) {
		if ( ! in_array( $post_name, $installed_themes, true ) ) {
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
