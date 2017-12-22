<?php
// Exit if accessed directly

/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


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
		_deprecated_function( 'filter:popmake_get_the_popup_title', '1.7.0', 'filter:pum_popup_get_title' );
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

add_filter( 'pum_popup_get_title', 'pum_deprecated_get_the_popup_title', 10, 2 );


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
		_deprecated_function( 'filter:the_popup_content', '1.7.0', 'filter:pum_popup_content' );
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
		_deprecated_function( 'filter:popmake_get_the_popup_theme', '1.7.0', 'filter:pum_popup_get_theme_id' );
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
		_deprecated_function( 'filter:popmake_get_the_popup_classes', '1.7.0', 'filter:pum_popup_container_classes' );
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
		_deprecated_function( 'filter:popmake_get_the_popup_data_attr', '1.7.0', 'filter:pum_popup_data_attr' );
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

add_filter( 'pum_popup_data_attr', 'pum_deprecated_get_the_popup_classes_filter', 10, 2 );

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
		_deprecated_function( 'filter:popmake_show_close_button', '1.7.0', 'filter:pum_popup_show_close_button' );
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
