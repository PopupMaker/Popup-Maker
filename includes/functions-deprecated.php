<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#region Deprecated 1.7.0

/**
 * @deprecated 1.7.0 Use pum_load_popup
 *
 * @param $id
 */
function popmake_enqueue_popup( $id ) {
	pum_load_popup( $id );
}

/**
 * Get an option
 *
 * Looks to see if the specified setting exists, returns default if not
 *
 * @since       1.0
 * @deprecated  1.7.0
 *
 * @param string $key
 * @param bool   $default
 *
 * @return mixed
 */
function popmake_get_option( $key = '', $default = false ) {
	return pum_get_option( $key, $default );
}

/**
 * Get Settings
 *
 * Retrieves all plugin settings
 *
 * @since      1.0.0
 * @deprecated 1.7.0
 *
 * @return array $settings
 */
function popmake_get_settings() {
	return pum_get_options();
}

/**
 * Support Page
 *
 * Renders the support page contents.
 *
 * @since 1.5.0
 * @deprecated 1.7.0
 */
function pum_settings_page() {
	PUM_Admin_Settings::page();
}

/**
 * @deprecated 1.7.0
 *
 * @param string $string
 *
 * @return string
 */
function popmake_get_label_singular( $string = '' ) {
	return '';
}

/**
 * @deprecated 1.7.0
 *
 * @param string $string
 *
 * @return string
 */
function popmake_get_label_plural( $string = '' ) {
	return '';
}

/**
 * Retrieve the array of plugin settings
 *
 * @since      1.0
 * @deprecated 1.7.0
 *
 * @return array
 */
function popmake_get_registered_settings() {
	/**
	 * 'Whitelisted' POPMAKE settings, filters are provided for each settings
	 * section to allow extensions and other plugins to add their own settings
	 */
	$popmake_settings = array(
		/** General Settings */
		'general'    => apply_filters( 'popmake_settings_general', array() ),
		'assets'     => apply_filters( 'popmake_settings_assets', array() ),
		/** Extension Settings */
		'extensions' => apply_filters( 'popmake_settings_extensions', array() ),
		'licenses'   => apply_filters( 'popmake_settings_licenses', array() ),
		'misc'       => apply_filters( 'popmake_settings_misc', array() ),
	);

	return apply_filters( 'popmake_registered_settings', $popmake_settings );
}

/**
 *  Determines whether the current admin page is an POPMAKE admin page.
 *
 * @deprecated 1.7.0 Use pum_is_admin_page instead.
 *
 * @since 1.0
 *
 * @return bool True if POPMAKE admin page.
 */
function popmake_is_admin_page() {
	return pum_is_admin_page();
}

/**
 * Determines whether the current admin page is an admin popup page.
 *
 * @deprecated 1.7.0
 *
 * @since 1.0
 *
 * @return bool
 */
function popmake_is_admin_popup_page() {
	return pum_is_popup_editor();
}

/**
 * Determines whether the current admin page is an admin theme page.
 *
 * @deprecated 1.7.0 @see pum_is_popup_theme_editor
 *
 * @since 1.0
 *
 * @return bool
 */
function popmake_is_admin_popup_theme_page() {
	return pum_is_popup_theme_editor();
}

/**
 * @deprecated 1.7.0
 */
function popmake_output_pum_styles() {
	return '';
}

/**
 * Retrieve a list of all published pages
 *
 * On large sites this can be expensive, so only load if on the settings page or $force is set to true
 *
 * @since      1.0
 * @deprecated 1.7.0
 *
 * @param bool $force Force the pages to be loaded even if not on settings
 *
 * @return array $pages_options An array of the pages
 */
function popmake_get_pages( $force = false ) {

	$pages_options = array( 0 => '' ); // Blank option

	if ( ( ! isset( $_GET['page'] ) || 'pum-settings' != $_GET['page'] ) && ! $force ) {
		return $pages_options;
	}

	$pages = get_pages();
	if ( $pages ) {
		foreach ( $pages as $page ) {
			$pages_options[ $page->ID ] = $page->post_title;
		}
	}

	return $pages_options;
}

/**
 * Returns the cookie fields used for cookie options.
 *
 * @deprecated 1.7.0 Use PUM_Cookies::instance()->cookie_fields() instead.
 *
 * @return array
 */
function pum_get_cookie_fields() {
	return PUM_Cookies::instance()->cookie_fields();
}

/**
 * Returns an array of args for registering coo0kies.
 *
 * @deprecated 1.7.0 Use PUM_Cookies::instance()->cookie_fields() instead.
 *
 * @return array
 */
function pum_get_cookies() {
	return PUM_Cookies::instance()->get_cookies();
}

/**
 * Returns the cookie fields used for trigger options.
 *
 * @deprecated v1.7.0 Use PUM_Triggers::instance()->cookie_fields() instead.
 *
 * @return array
 */
function pum_trigger_cookie_fields() {
	return PUM_Triggers::instance()->cookie_fields();
}

/**
 * Returns the cookie field used for trigger options.
 *
 * @deprecated v1.7.0 Use PUM_Triggers::instance()->cookie_field() instead.
 *
 * @return array
 */
function pum_trigger_cookie_field() {
	return PUM_Triggers::instance()->cookie_field();
}

/**
 * Returns an array of section labels for all triggers.
 *
 * @deprecated v1.7.0 Use PUM_Triggers::instance()->get_tabs() instead.
 *
 * @return array
 */
function pum_get_trigger_section_labels() {
	return PUM_Triggers::instance()->get_tabs();
}

#endregion

#region Deprecated 1.8.0


/**
 * Install Default Theme
 *
 * Installs the default theme and updates the option.
 *
 * @since 1.0
 * @deprecated 1.8.0
 */
function popmake_install_default_theme() {
	$defaults = PUM_Admin_Themes::defaults();

	$default_theme = @wp_insert_post( array(
		'post_title'     => __( 'Default Theme', 'popup-maker' ),
		'post_status'    => 'publish',
		'post_author'    => 1,
		'post_type'      => 'popup_theme',
		'comment_status' => 'closed',
		'meta_input'     => array(
			'_pum_built_in'        => 'default-theme',
			'_pum_default_theme'   => true,
			'popup_theme_settings' => $defaults,
			'popup_theme_data_version' => 3,
		),
	) );

	update_option( 'popmake_default_theme', $default_theme );
	pum_reset_assets();
}

/**
 * Checks if the db_ver is v1.4 compatible.
 *
 * v1.4 compatibility is db_ver 6 or higher.
 *
 * @depecated 1.8.0
 *
 * @uses pum_get_db_ver()
 *
 * @return bool
 */
function pum_is_v1_4_compatible() {
	return true;
}

/**
 * Deletes the theme css transient forcing it to refresh.
 *
 * @deprecated 1.8.0 Use pum_reset_assets()
 */
function pum_force_theme_css_refresh() {
	pum_reset_assets();
}

/**
 * @deprecated 1.8.0
 *
 * @param $hex
 *
 * @return array|string
 */
function popmake_hex2rgb( $hex ) {
	return PUM_Utils_CSS::hex2rgb( $hex, 'array' );
}

/**
 * @deprecated 1.8.0
 *
 * @param     $hex
 * @param int $opacity
 *
 * @return string
 */
function popmake_get_rgba_value( $hex, $opacity = 100 ) {
	return PUM_Utils_CSS::hex2rgba( $hex, $opacity );
}

/**
 * @deprecated 1.8.0
 *
 * @param int    $thickness
 * @param string $style
 * @param string $color
 *
 * @return string
 */
function popmake_get_border_style( $thickness = 1, $style = 'solid', $color = '#cccccc' ) {
	return PUM_Utils_CSS::border_style( $thickness, $style, $color );
}

/**
 * @deprecated 1.8.0
 *
 * @param int    $horizontal
 * @param int    $vertical
 * @param int    $blur
 * @param int    $spread
 * @param string $hex
 * @param int    $opacity
 * @param string $inset
 *
 * @return string
 */
function popmake_get_box_shadow_style( $horizontal = 0, $vertical = 0, $blur = 0, $spread = 0, $hex = '#000000', $opacity = 50, $inset = 'no' ) {
	return PUM_Utils_CSS::box_shadow_style( $horizontal, $vertical, $blur, $spread, $hex, $opacity, $inset );
}

/**
 * @deprecated 1.8.0
 *
 * @param int    $horizontal
 * @param int    $vertical
 * @param int    $blur
 * @param string $hex
 * @param int    $opacity
 *
 * @return string
 */
function popmake_get_text_shadow_style( $horizontal = 0, $vertical = 0, $blur = 0, $hex = '#000000', $opacity = 50 ) {
	return PUM_Utils_CSS::text_shadow_style( $horizontal, $vertical, $blur, $hex, $opacity );
}

/**
 * @deprecated 1.8.0
 *
 * @param $id
 */
function pum_load_popup( $id ) {
	PUM_Site_Popups::load_popup( $id );
};

/**
 * Retrieves a template part
 *
 * @deprecated 1.8.0 Use pum_get_template_part instead.
 *
 * @param string      $slug
 * @param null|string $name
 * @param bool        $load
 *
 * @return string
 */
function popmake_get_template_part( $slug, $name = null, $load = true ) {
	if ( $load ) {
		return pum_get_template_part( $slug, $name );
	} else {
		return PUM_Utils_Template::locate_part( $slug, $name, false );
	}
}

/**
 * Theme Overlay Metabox
 *
 * Extensions (as well as the core plugin) can add items to the theme overlay
 * configuration metabox via the `popmake_popup_theme_overlay_meta_box_fields` action.
 *
 * @since 1.0
 * @deprecated 1.8.0
 */
function popmake_render_popup_theme_overlay_meta_box() {
	if ( ! has_action( 'popmake_popup_theme_overlay_meta_box_fields' ) ) {
		return;
	}

	global $post;
	wp_nonce_field( basename( __FILE__ ), 'popmake_popup_theme_meta_box_nonce' ); ?>
	<input type="hidden" name="popup_theme_defaults_set" value="true"/>
	<div id="popmake_popup_theme_overlay_fields" class="popmake_meta_table_wrap">
	<table class="form-table">
		<tbody>
		<?php do_action( 'popmake_popup_theme_overlay_meta_box_fields', $post->ID ); ?>
		</tbody>
	</table>
	</div><?php
}

/**
 * Theme Container Metabox
 *
 * Extensions (as well as the core plugin) can add items to the theme container
 * configuration metabox via the `popmake_popup_theme_container_meta_box_fields` action.
 *
 * @since 1.0
 * @deprecated 1.8.0
 */
function popmake_render_popup_theme_container_meta_box() {
	if ( ! has_action( 'popmake_popup_theme_container_meta_box_fields' ) ) {
		return;
	}

	global $post; ?>
	<div id="popmake_popup_theme_container_fields" class="popmake_meta_table_wrap">
	<table class="form-table">
		<tbody>
		<?php do_action( 'popmake_popup_theme_container_meta_box_fields', $post->ID ); ?>
		</tbody>
	</table>
	</div><?php
}

/**
 * Theme Close Metabox
 *
 * Extensions (as well as the core plugin) can add items to the popup close
 * configuration metabox via the `popmake_popup_theme_close_meta_box_fields` action.
 *
 * @since 1.0
 * @deprecated 1.8.0
 */
function popmake_render_popup_theme_close_meta_box() {
	if ( ! has_action( 'popmake_popup_theme_close_meta_box_fields' ) ) {
		return;
	}

	global $post; ?>
	<div id="popmake_popup_theme_close_fields" class="popmake_meta_table_wrap">
	<table class="form-table">
		<tbody>
		<?php do_action( 'popmake_popup_theme_close_meta_box_fields', $post->ID ); ?>
		</tbody>
	</table>
	</div><?php
}


#endregion
