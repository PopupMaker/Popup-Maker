<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Here to manage extensions (PUM-Videos) which used this filter to load their assets.
 *
 * @since 1.7.0
 * @deprecated 1.7.0
 *
 * @param null $popup_id
 */
function popmake_enqueue_scripts( $popup_id = null ) {
	$scripts_needed = apply_filters( 'popmake_enqueue_scripts', array(), $popup_id );
	foreach ( $scripts_needed as $script ) {
		if ( wp_script_is( $script, 'registered' ) ) {
			wp_enqueue_script( $script );
		}
	}
	$styles_needed = apply_filters( 'popmake_enqueue_styles', array(), $popup_id );
	foreach ( $styles_needed as $style ) {
		if ( wp_style_is( $style, 'registered' ) ) {
			wp_enqueue_style( $style );
		}
	}
}

add_action( 'popmake_preload_popup', 'popmake_enqueue_scripts' );

/**
 * Process deprecated filters.
 *
 * @since 1.7.0
 * @deprecated 1.7.0
 *
 * @param $settings
 *
 * @return mixed
 */
function pum_deprecated_popmake_settings_extensions_sanitize_filter( $settings = array() ) {
	if ( has_filter( 'popmake_settings_extensions_sanitize' ) ) {
		PUM_Utils_Logging::instance()->log_deprecated_notice( 'filter:popmake_settings_extensions_sanitize', '1.7.0', 'filter:pum_settings_sanitize' );
		/**
		 * @deprecated 1.7
		 *
		 * @param array $settings
		 * @param int   $popup_id
		 */
		$settings = apply_filters( 'popmake_settings_extensions_sanitize', $settings );
	}

	return $settings;
}

add_filter( 'pum_sanitize_settings', 'pum_deprecated_popmake_settings_extensions_sanitize_filter' );

/**
 * Process deprecated filters.
 *
 * @since 1.7.0
 * @deprecated 1.7.0
 *
 * @param $content
 * @param $popup_id
 *
 * @return mixed
 */
function pum_deprecated_get_the_popup_content_filter( $content, $popup_id ) {
	if ( has_filter( 'the_popup_content' ) ) {
		PUM_Utils_Logging::instance()->log_deprecated_notice( 'filter:the_popup_content', '1.7.0', 'filter:pum_popup_content' );
		/**
		 * @deprecated 1.7
		 *
		 * @param string $content
		 * @param int    $popup_id
		 */
		$content = apply_filters( 'the_popup_content', $content, $popup_id );
	}

	return $content;
}

add_filter( 'pum_popup_content', 'pum_deprecated_get_the_popup_content_filter', 10, 2 );

/**
 * Process deprecated filters.
 *
 * @since 1.7.0
 * @deprecated 1.7.0
 *
 * @param $data_attr
 * @param $popup_id
 *
 * @return mixed
 */
function pum_deprecated_pum_popup_get_data_attr_filter( $data_attr, $popup_id ) {
	if ( has_filter( 'pum_popup_get_data_attr' ) ) {
		PUM_Utils_Logging::instance()->log_deprecated_notice( 'filter:pum_popup_get_data_attr', '1.7.0', 'filter:pum_popup_data_attr' );
		/**
		 * @deprecated 1.7
		 *
		 * @param string $content
		 * @param int    $popup_id
		 */
		$data_attr = apply_filters( 'pum_popup_get_data_attr', $data_attr, $popup_id );
	}

	return $data_attr;
}

add_filter( 'pum_popup_data_attr', 'pum_deprecated_pum_popup_get_data_attr_filter', 10, 2 );

/**
 * Process deprecated filters.
 *
 * @since 1.7.0
 * @deprecated 1.7.0
 *
 * @param array $classes
 * @param int   $popup_id
 *
 * @return array
 */
function pum_deprecated_pum_popup_get_classes_filter( $classes, $popup_id ) {
	if ( has_filter( 'pum_popup_get_classes' ) ) {
		PUM_Utils_Logging::instance()->log_deprecated_notice( 'filter:pum_popup_get_classes', '1.7.0', 'filter:pum_popup_container_classes' );
		/**
		 * @deprecated 1.7
		 *
		 * @param array $classes
		 * @param int   $popup_id
		 */
		$classes = apply_filters( 'pum_popup_get_classes', $classes, $popup_id );
	}

	return $classes;
}

add_filter( 'pum_popup_classes', 'pum_deprecated_pum_popup_get_classes_filter', 10, 2 );

/**
 * Process deprecated filters.
 *
 * @since 1.7.0
 * @deprecated 1.7.0
 *
 * @param array $data_attr
 * @param int   $popup_id
 *
 * @return array
 */
function pum_deprecated_get_the_popup_data_attr_filter( $data_attr, $popup_id ) {
	if ( has_filter( 'popmake_get_the_popup_data_attr' ) ) {
		PUM_Utils_Logging::instance()->log_deprecated_notice( 'filter:popmake_get_the_popup_data_attr', '1.7.0', 'filter:pum_popup_data_attr' );
		/**
		 * @deprecated 1.7
		 *
		 * @param array $data_attr
		 * @param int   $popup_id
		 */
		$data_attr = apply_filters( 'popmake_get_the_popup_data_attr', $data_attr, $popup_id );
	}

	return $data_attr;
}

add_filter( 'pum_popup_data_attr', 'pum_deprecated_get_the_popup_data_attr_filter', 10, 2 );

