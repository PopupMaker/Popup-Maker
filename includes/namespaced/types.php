<?php
/**
 * Content Type functions.
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker;

use function PopupMaker\plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Get post type key.
 *
 * @param string $type The post type. Must be 'popup', 'pum_cta', 'popup_theme', 'popup_category', or 'popup_tag'.
 *
 * @return string
 *
 * @since 1.21.0
 */
function get_post_type_key( $type ) {
	return plugin( 'PostTypes' )->get_type_key( $type );
}

/**
 * Get post type labels.
 *
 * @param string $post_type The post type. Must be 'popup', 'pum_cta', 'popup_theme', 'popup_category', or 'popup_tag'.
 *
 * @return array<string,string>
 *
 * @since 1.21.0
 */
function get_post_type_labels( $post_type ) {
	if ( ! post_type_exists( $post_type ) ) {
		$post_type = get_post_type_key( $post_type );

		if ( ! post_type_exists( $post_type ) ) {
			return [];
		}
	}

	$post_type_object = get_post_type_object( $post_type );

	return $post_type_object ? $post_type_object->labels : [];
}

/**
 * Get a post type label.
 *
 * @param string $post_type The post type. Must be 'popup', 'pum_cta', 'popup_theme', 'popup_category', or 'popup_tag'.
 * @param string $key The label key.
 *
 * @return string
 *
 * @since 1.21.0
 */
function get_post_type_label( $post_type, $key ) {
	$labels = get_post_type_labels( $post_type );

	return $labels[ $key ] ?? '';
}
