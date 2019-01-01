<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
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
	pum_support_page();
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
 * @deprecated 1.7.0 Use pum_is_popup_theme_editor
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
	return pum_custom_settings_field_pum_styles();
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

#endregion

#region Deprecated 1.8.0

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

#endregion
