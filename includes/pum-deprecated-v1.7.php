<?php
// Exit if accessed directly

/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get Default Labels
 *
 * @since 1.0
 * @deprecated 1.7
 *
 * @param string $post_type
 *
 * @return array $defaults Default labels
 */
function popmake_get_default_labels( $post_type = '' ) {
	$defaults = apply_filters( 'popmake_default_post_type_name', array() );
	return isset( $defaults[ $post_type ] ) ? $defaults[ $post_type ] : $defaults['popup'];
}

/**
 * Get Singular Label
 *
 * @since 1.0
 * @deprecated 1.7
 *
 * @param string $post_type
 * @param bool $lowercase
 *
 * @return string $defaults['singular'] Singular label
 */
function popmake_get_label_singular( $post_type = '', $lowercase = false ) {
	$defaults = popmake_get_default_labels( $post_type );
	return ( $lowercase ) ? strtolower( $defaults['singular'] ) : $defaults['singular'];
}

/**
 * Get Plural Label
 *
 * @since 1.0
 * @deprecated 1.7
 *
 * @param string $post_type
 * @param bool $lowercase
 *
 * @return string $defaults['plural'] Plural label
 */
function popmake_get_label_plural( $post_type = '', $lowercase = false ) {
	$defaults = popmake_get_default_labels( $post_type );
	return ( $lowercase ) ? strtolower( $defaults['plural'] ) : $defaults['plural'];
}