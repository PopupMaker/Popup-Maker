<?php
/**
 * Condition helpers.
 *
 * @package PopupMaker
 * @copyright Copyright (c) 2024, Daniel Iser
 */

namespace PopupMaker;

defined( 'ABSPATH' ) || exit;

/**
 * Get selected field.
 *
 * @param string $post_type Post type.
 * @param array  $args      Arguments.
 *
 * @return array
 */
function get_selected_field( $post_type, $args = [] ) {
	static $fields = [];

	$hash = $post_type . '-' . wp_json_encode( $args );

	if ( ! isset( $fields[ $hash ] ) ) {
		global $typenow;

		$preload_posts = 'popup' === $typenow;

		if ( ! isset( $args['placeholder'] ) ) {
			$args['placeholder'] = sprintf(
			/* translators: %s: Post type. */
				__( 'Select %s(s) (optional)', 'popup-maker' ),
				$post_type
			);
		}

		$field_args = array_merge( [
			'type'      => 'postselect',
			'post_type' => $post_type,
			'multiple'  => true,
			'as_array'  => true,
			'options'   => $preload_posts ? \PUM_Helpers::post_type_selectlist_query( $post_type ) : [],
		], $args );

		$fields[ $hash ] = $field_args;
	}

	return $fields[ $hash ];
}

/**
 * Get require all field.
 *
 * @param array $args Arguments.
 *
 * @return array
 */
function get_require_all_field( $args = [] ) {
	static $field;

	if ( ! isset( $field ) ) {
		$field = [
			'label' => __( 'Require all', 'popup-maker' ),
			'type'  => 'checkbox',
		];
	}

	return $field;
}

/**
 * Get more than field.
 *
 * @param array $args Arguments.
 *
 * @return array
 */
function get_morethan_field( $args = [] ) {
	static $field;

	if ( ! isset( $field ) ) {
		$field = array_merge( [
			'label' => __( 'More Than (optional)', 'popup-maker' ),
			'type'  => 'number',
			'std'   => 0,
		], $args );
	}

	return $field;
}

/**
 * Get less than field.
 *
 * @param array $args Arguments.
 *
 * @return array
 */
function get_lessthan_field( $args = [] ) {
	static $field;

	if ( ! isset( $field ) ) {
		$field = array_merge( [
			'label' => __( 'Less Than (optional)', 'popup-maker' ),
			'type'  => 'number',
			'std'   => 0,
		], $args );
	}

	return $field;
}

/**
 * Tests more or less than value and returns a boolean.
 *
 * @param float|int      $value Value to test.
 * @param float|int|bool $mt    More than value.
 * @param float|int|bool $lt    Less than value.
 * @param bool           $default_value Default value to return if no conditions are met.
 *
 * @return bool
 */
function test_more_less_than( $value, $mt = false, $lt = false, $default_value = false ) {
	$mt = absint( $mt ) > 0 ? absint( $mt ) : false;
	$lt = absint( $lt ) > 0 ? absint( $lt ) : false;

	if ( $mt && ! $lt ) {
		return $value > $mt;
	}

	if ( $lt && ! $mt ) {
		return $lt > $value;
	}

	if ( $lt && $mt ) {
		return $lt > $value && $value > $mt;
	}

	return $default_value;
}

/**
 * Tests if selected items are in a list of items. Optionally, requires all
 * selected items to be in the list.
 *
 * @param array $items List of items.
 * @param array $selected Selected items.
 * @param bool  $require_all Require all selected items.
 *
 * @return bool
 */
function test_list_matches( $items, $selected, $require_all ) {
	if ( empty( $selected ) || ( $require_all && count( $items ) < count( $selected ) ) ) {
		return false;
	}

	// Check each selected download against cart contents.
	foreach ( $selected as $id ) {
		$found = in_array( $id, $items, true );

		// If requiring all downloads and one is missing, fail immediately.
		if ( $require_all && ! $found ) {
			return false;
		}

		// If requiring any download and one is found, pass immediately.
		if ( ! $require_all && $found ) {
			return true;
		}
	}

	// At this point:
	// - If require_all=true, we've checked all downloads and none were missing (return true).
	// - If require_all=false, we've checked all downloads and none were found (return false).
	return $require_all;
}
