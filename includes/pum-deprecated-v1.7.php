<?php
// Exit if accessed directly

/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
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

# region Filters
/**
 * Process deprecated filters.
 *
 * @param $title
 * @param $popup_id
 *
 * @return mixed
 */
function pum_deprecated_get_the_popup_title_filter( $title, $popup_id ) {
	if ( has_filter( 'popmake_get_the_popup_title' ) ) {
		PUM_Logging::instance()->log_deprecated_notice( 'filter:popmake_get_the_popup_title', '1.7.0', 'filter:pum_popup_get_title' );
		/**
		 * @deprecated 1.7
		 *
		 * @param string $title
		 * @param int $popup_id
		 */
		$title = apply_filters( 'popmake_get_the_popup_title', $title, $popup_id );
	}

	return $title;
}
add_filter( 'pum_popup_get_title', 'pum_deprecated_get_the_popup_title_filter', 10, 2 );

/**
 * Process deprecated filters.
 *
 * @param $content
 * @param $popup_id
 *
 * @return mixed
 */
function pum_deprecated_get_the_popup_content_filter( $content, $popup_id ) {
	if ( has_filter( 'the_popup_content' ) ) {
		PUM_Logging::instance()->log_deprecated_notice( 'filter:the_popup_content', '1.7.0', 'filter:pum_popup_content' );
		/**
		 * @deprecated 1.7
		 *
		 * @param string $content
		 * @param int $popup_id
		 */
		$content = apply_filters( 'the_popup_content', $content, $popup_id );
	}

	return $content;
}
add_filter( 'pum_popup_content', 'pum_deprecated_get_the_popup_content_filter', 10, 2 );

/**
 * Process deprecated filters.
 *
 * @param int $theme_id
 * @param int $popup_id
 *
 * @return int
 */
function pum_deprecated_get_the_popup_theme_filter( $theme_id, $popup_id ) {
	if ( has_filter( 'popmake_get_the_popup_theme' ) ) {
		PUM_Logging::instance()->log_deprecated_notice( 'filter:popmake_get_the_popup_theme', '1.7.0', 'filter:pum_popup_get_theme_id' );
		/**
		 * @deprecated 1.7
		 *
		 * @param int $theme_id
		 * @param int $popup_id
		 */
		$theme_id = apply_filters( 'popmake_get_the_popup_theme', $theme_id, $popup_id );
	}

	return $theme_id;
}
add_filter( 'pum_popup_get_theme_id', 'pum_deprecated_get_the_popup_theme_filter', 10, 2 );

/**
 * Process deprecated filters.
 *
 * @param array $classes
 * @param int $popup_id
 *
 * @return array
 */
function pum_deprecated_get_the_popup_classes_filter( $classes, $popup_id ) {
	if ( has_filter( 'popmake_get_the_popup_classes' ) ) {
		PUM_Logging::instance()->log_deprecated_notice( 'filter:popmake_get_the_popup_classes', '1.7.0', 'filter:pum_popup_container_classes' );
		/**
		 * @deprecated 1.7
		 *
		 * @param array $classes
		 * @param int $popup_id
		 */
		$classes = apply_filters( 'popmake_get_the_popup_classes', $classes, $popup_id );
	}

	return $classes;
}
add_filter( 'pum_popup_container_classes', 'pum_deprecated_get_the_popup_classes_filter', 10, 2 );

/**
 * Process deprecated filters.
 *
 * @param array $data_attr
 * @param int $popup_id
 *
 * @return array
 */
function pum_deprecated_get_the_popup_data_attr_filter( $data_attr, $popup_id ) {
	if ( has_filter( 'popmake_get_the_popup_data_attr' ) ) {
		PUM_Logging::instance()->log_deprecated_notice( 'filter:popmake_get_the_popup_data_attr', '1.7.0', 'filter:pum_popup_data_attr' );
		/**
		 * @deprecated 1.7
		 *
		 * @param array $data_attr
		 * @param int $popup_id
		 */
		$data_attr = apply_filters( 'popmake_get_the_popup_data_attr', $data_attr, $popup_id );
	}

	return $data_attr;
}
add_filter( 'pum_popup_data_attr', 'pum_deprecated_get_the_popup_data_attr_filter', 10, 2 );

/**
 * Process deprecated filters.
 *
 * @param bool $show
 * @param int $popup_id
 *
 * @return array
 */
function pum_deprecated_show_close_button_filter( $show, $popup_id ) {
	if ( has_filter( 'popmake_show_close_button' ) ) {
		PUM_Logging::instance()->log_deprecated_notice( 'filter:popmake_show_close_button', '1.7.0', 'filter:pum_popup_show_close_button' );
		/**
		 * @deprecated 1.7
		 *
		 * @param bool $show
		 * @param int $popup_id
		 */
		$show = apply_filters( 'popmake_show_close_button', $show, $popup_id );
	}

	return $show;
}
add_filter( 'pum_popup_show_close_button', 'pum_deprecated_show_close_button_filter', 10, 2 );
# endregion Filters

# region Functions
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
# endregion Functions