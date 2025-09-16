<?php
/**
 * Importer for easy-modal functions
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'get_all_modals' ) ) {
	function enqueue_modal( $id ) {
		if ( ! is_array( $id ) ) {
			EModal_Modals::enqueue_modal( $id );
		} else {
			foreach ( $id as $i ) {
				EModal_Modals::enqueue_modal( $i );
			}
		}
	}
}

if ( ! function_exists( 'emodal_get_option' ) ) {
	function emodal_get_option( $key ) {
		global $blog_id;
		if ( function_exists( 'is_multisite' ) && is_multisite() && $blog_id ) {
			return get_blog_option( $blog_id, $key );
		} else {
			return get_site_option( $key );
		}
	}
}


if ( ! function_exists( 'emodal_update_option' ) ) {
	function emodal_update_option( $key, $value ) {
		global $blog_id;
		if ( function_exists( 'is_multisite' ) && is_multisite() && $blog_id ) {
			return update_blog_option( $blog_id, $key, $value );
		} else {
			return update_site_option( $key, $value );
		}
	}
}

if ( ! function_exists( 'emodal_delete_option' ) ) {
	function emodal_delete_option( $key ) {
		global $blog_id;
		if ( function_exists( 'is_multisite' ) && is_multisite() && $blog_id ) {
			return delete_blog_option( $blog_id, $key );
		} else {
			return delete_site_option( $key );
		}
	}
}

if ( ! function_exists( 'emodal_get_license' ) ) {
	function emodal_get_license( $key = null ) {
		$license = emodal_get_option( EMCORE_SLUG . '-license' );
		if ( ! $license ) {
			$license = [
				'valid'  => false,
				'key'    => '',
				'status' => [
					'code'    => null,
					'message' => null,
					'expires' => null,
					'domains' => null,
				],
			];
			emodal_update_option( EMCORE_SLUG . '-license', $license );
		}

		return $license && $key ? emresolve( $license, $key ) : $license;
	}
}


if ( ! function_exists( 'emresolve' ) ) {
	function emresolve( array $a, $path, $default_value = null ) {
		$current = $a;
		$p       = strtok( $path, '.' );
		while ( false !== $p ) {
			if ( ! isset( $current[ $p ] ) ) {
				return $default_value;
			}
			$current = $current[ $p ];
			$p       = strtok( '.' );
		}

		return $current;
	}
}
