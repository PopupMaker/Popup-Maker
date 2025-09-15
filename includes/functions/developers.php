<?php
/**
 * Functions for developers
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

use function PopupMaker\plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Call this with a popup ID and it will trigger the
 * JS based forms.success function with your settings
 * on the next page load.
 *
 * @since 1.7.0
 *
 * @param int   $popup_id
 * @param array $settings
 */
function pum_trigger_popup_form_success( $popup_id = null, $settings = [] ) {
	if ( ! isset( $popup_id ) ) {
		// Ignored as this field is appended to existing forms and is limited to an absint.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$popup_id = isset( $_REQUEST['pum_form_popup_id'] ) && absint( $_REQUEST['pum_form_popup_id'] ) > 0 ? absint( $_REQUEST['pum_form_popup_id'] ) : false;
	}

	if ( $popup_id ) {
		PUM_Integrations::$form_success = [
			'popup_id' => $popup_id,
			'settings' => $settings,
		];
	}
}

/**
 * @param array $args {
 *      An array of parameters that customize the way the parser works.
 *
 *      @type string $form_provider Key indicating which form provider this form belongs to.
 *      @type string|int $form_id Form ID, usually numeric, but can be hash based.
 *      @type int $form_instance_id Optional form instance ID.
 *      @type int $popup_id Optional popup ID.
 *      @type bool $ajax If the submission was processed via AJAX. Generally gonna be false outside of JavaScript.
 *      @type bool $tracked Whether the submission has been handled by tracking code or not. Prevents duplicates.
 * }
 */
function pum_integrated_form_submission( $args = [] ) {
	$args = wp_parse_args(
		$args,
		[
			'popup_id'         => null,
			'form_provider'    => null,
			'form_id'          => null,
			'form_instance_id' => null,
			'ajax'             => false,
			'tracked'          => false,
		]
	);

	$args = apply_filters( 'pum_integrated_form_submission_args', $args );

	PUM_Integrations::$form_submission = $args;

	do_action( 'pum_integrated_form_submission', $args );
}

/**
 * Triggers a tracking event for a given popup.
 *
 * @param int   $popup_id Popup ID.
 * @param array $args Array of optional arguments.
 */
function pum_track_conversion_event( $popup_id = 0, $args = [] ) {
	/**
	 * Track conversion with added value.
	 */
	PUM_Analytics::track(
		array_merge(
			$args,
			[
				'event' => 'conversion',
				'pid'   => $popup_id,
			]
		)
	);
}

/**
 * Register a script for possible caching.
 *
 * @param string   $handle The script handle.
 * @param string   $src The script src.
 * @param string[] $deps The script dependencies.
 * @param string   $version The script version.
 * @param bool     $in_footer Whether to enqueue the script in the footer.
 *
 * @return bool
 *
 * @since 1.21.0
 */
function pum_register_script( $handle, $src, $deps = [], $version = null, $in_footer = true ) {
	return PUM_AssetCache::register_script( $handle, $src, $deps, $version, $in_footer );
}

/**
 * Register a style for possible caching.
 *
 * @param string   $handle The style handle.
 * @param string   $src The style src.
 * @param string[] $deps The style dependencies.
 * @param string   $version The style version.
 * @param string   $media The style media.
 *
 * @return bool
 *
 * @since 1.21.0
 */
function pum_register_style( $handle, $src, $deps = [], $version = false, $media = 'all' ) {
	return PUM_AssetCache::register_style( $handle, $src, $deps, $version, $media );
}

/**
 * Enqueue a script for possible caching.
 *
 * @param string   $handle The script handle.
 * @param string   $src The script src.
 * @param string[] $deps The script dependencies.
 * @param string   $version The script version.
 * @param bool     $in_footer Whether to enqueue the script in the footer.
 *
 * @return bool
 *
 * @since 1.21.0
 */
function pum_enqueue_script( $handle, $src = '', $deps = [], $version = null, $in_footer = false ) {
	return PUM_AssetCache::enqueue_script( $handle, $src, $deps, $version, $in_footer );
}

/**
 * Check if a script is enqueued.
 *
 * @param string $handle The script handle.
 * @param string $status The script status.
 *
 * @return bool
 *
 * @since 1.21.0
 */
function pum_script_is( $handle, $status = 'enqueued' ) {
	return PUM_AssetCache::script_is( $handle, $status );
}


/**
 * Enqueue a style for possible caching.
 *
 * @param string   $handle The style handle.
 * @param string   $src The style src.
 * @param string[] $deps The style dependencies.
 * @param string   $version The style version.
 * @param string   $media The style media.
 *
 * @return bool
 *
 * @since 1.21.0
 */
function pum_enqueue_style( $handle, $src = '', $deps = [], $version = null, $media = false ) {
	return PUM_AssetCache::enqueue_style( $handle, $src, $deps, $version, $media );
}

/**
 * Dequeue a script.
 *
 * @param string $handle The script handle.
 *
 * @return bool
 *
 * @since 1.21.0
 */
function pum_dequeue_script( $handle ) {
	return PUM_AssetCache::dequeue_script( $handle );
}

/**
 * Dequeue a style.
 *
 * @param string $handle The style handle.
 *
 * @return bool
 *
 * @since 1.21.0
 */
function pum_dequeue_style( $handle ) {
	return PUM_AssetCache::dequeue_style( $handle );
}

/**
 * Localize a script.
 *
 * @param string              $handle      Script handle the data will be attached to.
 * @param string              $object_name Name for the JavaScript object. Passed directly, so it should be qualified JS variable.
 *                                         Example: '/[a-zA-Z0-9_]+/'.
 * @param array<string,mixed> $value       The data itself. The data can be either a single or multi-dimensional array.
 *
 * @return bool
 *
 * @since 1.21.0
 */
function pum_localize_script( $handle, $object_name, $value ) {
	return PUM_AssetCache::localize_script( $handle, $object_name, $value );
}

/**
 * Get the asset meta for a file.
 *
 * @param string              $file The file path.
 * @param array<string,mixed> $default_args Default arguments to merge with the asset meta.
 *
 * @return array{
 *     dependencies: array<string,string>,
 *     version: string,
 * }
 */
function pum_get_asset_meta( $file, $default_args = [] ) {
	return file_exists( $file ) ? require $file : wp_parse_args(
		$default_args,
		[
			'dependencies' => [],
			'version'      => '',
		]
	);
}


/**
 * Get the asset meta for a file.
 *
 * @param string              $group The group name.
 * @param array<string,mixed> $default_args Default arguments to merge with the asset meta.
 *
 * @return array{
 *     dependencies: array<string,string>,
 *     version: string,
 * }
 */
function pum_get_asset_group_meta( $group, $default_args = [] ) {
	$file = plugin()->get_path( "dist/$group-assets.php" );

	$meta = (array) file_exists( $file ) ? require $file : [];

	foreach ( $meta as $key => $value ) {
		$meta[ $key ] = wp_parse_args( $value, $default_args );
	}

	return $meta;
}
