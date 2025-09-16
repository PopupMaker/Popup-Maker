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

/**
 * Tests items against a condition with require_all logic.
 *
 * This function provides a reusable pattern for checking multiple items against
 * a condition, with support for "require all" vs "require any" logic.
 *
 * This helper eliminates repetitive code patterns found throughout condition callbacks
 * where we need to check multiple items (products, courses, memberships, etc.) against
 * a condition and determine if ALL items must match or just ANY item needs to match.
 *
 * Before this helper, condition callbacks contained repetitive patterns like:
 * ```php
 * $matches = 0;
 * foreach ( $products as $product_id ) {
 *     $result = $customer->has_purchased( $product_id );
 *     if ( $result ) {
 *         ++$matches;
 *     }
 *     // If we don't require all and found one match, return true.
 *     if ( ! $require_all && $matches > 0 ) {
 *         return true;
 *     }
 * }
 * // If require all, check if matches equal total products.
 * return $require_all ? count( $products ) === $matches : $matches > 0;
 * ```
 *
 * Now this can be simplified to:
 * ```php
 * return test_items_match(
 *     $products,
 *     function ( $product_id ) use ( $customer ) {
 *         return $customer->has_purchased( $product_id );
 *     },
 *     $require_all
 * );
 * ```
 *
 * @param array    $items       Array of items to check.
 * @param callable $check_fn    Function that returns true if item matches condition.
 *                              Receives one parameter: the current item.
 * @param bool     $require_all Whether all items must match (true) or just one (false).
 *
 * @return bool True if condition is met, false otherwise.
 *
 * @example
 * // Check if user has access to ANY of the specified products
 * $products = [123, 456, 789];
 * $user_id = get_current_user_id();
 * $has_access_any = test_items_match(
 *     $products,
 *     function( $product_id ) use ( $user_id ) {
 *         return user_has_product_access( $user_id, $product_id );
 *     },
 *     false // require_all = false (any match)
 * );
 *
 * @example
 * // Check if user has ALL required permissions
 * $permissions = ['edit_posts', 'manage_options', 'publish_pages'];
 * $user_id = get_current_user_id();
 * $has_all_permissions = test_items_match(
 *     $permissions,
 *     function( $capability ) use ( $user_id ) {
 *         return user_can( $user_id, $capability );
 *     },
 *     true // require_all = true (all must match)
 * );
 *
 * @example
 * // Check if user is member of ANY specified groups
 * $groups = [101, 102, 103];
 * $user_id = get_current_user_id();
 * $is_member_of_any = test_items_match(
 *     $groups,
 *     function( $group_id ) use ( $user_id ) {
 *         return is_user_member_of_group( $user_id, $group_id );
 *     },
 *     false // require_all = false (any match)
 * );
 *
 * @example
 * // Check if ALL posts are published
 * $post_ids = [201, 202, 203];
 * $all_published = test_items_match(
 *     $post_ids,
 *     function( $post_id ) {
 *         return 'publish' === get_post_status( $post_id );
 *     },
 *     true // require_all = true (all must be published)
 * );
 *
 * @example
 * // Check if user completed ANY of the specified courses
 * $courses = [301, 302, 303];
 * $user_id = get_current_user_id();
 * $completed_any = test_items_match(
 *     $courses,
 *     function( $course_id ) use ( $user_id ) {
 *         return is_course_completed( $user_id, $course_id );
 *     },
 *     false // require_all = false (any match)
 * );
 *
 * @since 1.XX.X
 */
function test_items_match( $items, $check_fn, $require_all = false ) {
	if ( empty( $items ) || ! is_callable( $check_fn ) ) {
		return false;
	}

	$matches = 0;
	foreach ( $items as $item ) {
		if ( $check_fn( $item ) ) {
			++$matches;
		}

		// If we don't require all and found one match, return true early.
		if ( ! $require_all && $matches > 0 ) {
			return true;
		}
	}

	// If require all, check if matches equal total items.
	return $require_all ? count( $items ) === $matches : $matches > 0;
}
