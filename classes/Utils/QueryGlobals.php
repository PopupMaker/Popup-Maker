<?php
/**
 * WordPress query globals utility.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Capture and restore WordPress query and loop globals.
 *
 * Builders that render a secondary template may alter the pending main query.
 * Compatibility controllers can use this utility to isolate only the affected
 * rendering paths.
 */
class QueryGlobals {

	/**
	 * WordPress globals involved in query and loop state.
	 *
	 * @var string[]
	 */
	private static $global_names = [
		'wp_query',
		'wp_the_query',
		'post',
		'id',
		'authordata',
		'currentday',
		'currentmonth',
		'page',
		'pages',
		'multipage',
		'more',
		'numpages',
	];

	/**
	 * Capture the current WordPress query and loop globals.
	 *
	 * @return array{
	 *     globals: array<string, array{exists: bool, value: mixed}>,
	 *     queries: array<int, array{query: object, state: array<string, mixed>}>
	 * }
	 */
	public static function capture() {
		$global_state = [];
		$query_state  = [];

		foreach ( self::$global_names as $global_name ) {
			$exists = array_key_exists( $global_name, $GLOBALS );
			$value  = $exists ? $GLOBALS[ $global_name ] : null;

			$global_state[ $global_name ] = [
				'exists' => $exists,
				'value'  => $value,
			];

			if ( in_array( $global_name, [ 'wp_query', 'wp_the_query' ], true ) && is_object( $value ) ) {
				$object_id = spl_object_id( $value );

				if ( ! isset( $query_state[ $object_id ] ) ) {
					$query_state[ $object_id ] = [
						'query' => $value,
						'state' => self::snapshot_query( $value ),
					];
				}
			}
		}

		return [
			'globals' => $global_state,
			'queries' => $query_state,
		];
	}

	/**
	 * Restore a previously captured WordPress query and loop state.
	 *
	 * @param array{globals: array<string, array{exists: bool, value: mixed}>, queries: array<int, array{query: object, state: array<string, mixed>}>} $snapshot Query global snapshot.
	 * @return void
	 */
	public static function restore( $snapshot ) {
		foreach ( $snapshot['queries'] as $query_state ) {
			self::restore_query( $query_state['query'], $query_state['state'] );
		}

		foreach ( $snapshot['globals'] as $global_name => $global_state ) {
			if ( $global_state['exists'] ) {
				$GLOBALS[ $global_name ] = $global_state['value'];
			} else {
				unset( $GLOBALS[ $global_name ] );
			}
		}
	}

	/**
	 * Snapshot a query's public state without retaining referenced arrays.
	 *
	 * @param object $query Query to snapshot.
	 * @return array<string, mixed>
	 */
	private static function snapshot_query( $query ) {
		$state = [];

		foreach ( get_object_vars( $query ) as $property => $value ) {
			$state[ $property ] = is_array( $value ) ? self::copy_array( $value ) : $value;
		}

		return $state;
	}

	/**
	 * Copy an array without retaining element references.
	 *
	 * @param array<mixed> $values Values to copy.
	 * @return array<mixed>
	 */
	private static function copy_array( $values ) {
		$copy = [];

		foreach ( $values as $key => $value ) {
			$copy[ $key ] = is_array( $value ) ? self::copy_array( $value ) : $value;
		}

		return $copy;
	}

	/**
	 * Restore a query's public properties from a snapshot.
	 *
	 * @param object               $query Query to restore.
	 * @param array<string, mixed> $state Original query state.
	 * @return void
	 */
	private static function restore_query( $query, $state ) {
		foreach ( get_object_vars( $query ) as $property => $value ) {
			if ( ! array_key_exists( $property, $state ) ) {
				unset( $query->{$property} );
			}
		}

		foreach ( $state as $property => $value ) {
			unset( $query->{$property} );
			$query->{$property} = $value;
		}
	}
}
