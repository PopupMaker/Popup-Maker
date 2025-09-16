<?php
/**
 * CacheIt Functions
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker;

defined( 'ABSPATH' ) || exit;

/**
 * Simple function result cache utility for repeating function calls.
 *
 * Usage:
 *
 * All examples are assuming the following function:
 *
 * Simplified example of internal static cache:
 *
 * function my_function() {
 *     return cacheit( __FUNCTION__, function() {
 *         return do_something();
 *     } );
 * }
 *
 * Or with a specific cache key:
 *
 * function my_function() {
 *     return cacheit( 'my_function', function() {
 *         return do_something();
 *     } );
 * }
 *
 * With arguments:
 *
 * function my_function( $arg1, $arg2 ) {
 *     return cacheit( __FUNCTION__ , function() use ( $arg1, $arg2 ) {
 *         return do_something( $arg1, $arg2 );
 *     }, array( $arg1, $arg2 ) );
 * }
 *
 * Arguments using spread operator:
 *
 * function my_function( $arg1, $arg2 ) {
 *     $args = func_get_args();
 *
 *     if ( $clear_cache ) {
 *         cacheit( __FUNCTION__, null, $args );
 *     }
 *
 *     return cacheit( __FUNCTION__ , function() use ( $args ) {
 *         return do_something( ...$args );
 *     }, $args );
 * }
 *
 * With a cache clear
 *
 * Clear cache.
 *
 * function my_function() {
 *     return pum_cache_func( __FUNCTION__, null );
 * }
 *
 * Clear cache with arguments.
 *
 * function my_function( $arg1, $arg2 ) {
 *     return pum_cache_func( __FUNCTION__, null, func_get_args());
 * }
 *
 * @since 1.21.0
 *
 * @param string|'get_cacheit_counts' $fn_name  Unique name for the function call.
 * @param callable|false|null         $callback Function to compute the result if not cached, false to get cached result, or null to clear cache.
 * @param array|null                  $args     Arguments to cache under. Used to determine cache key for different arguments.
 *
 * @return mixed The cached or computed result.
 */
function cacheit( $fn_name, $callback = false, $args = null ) {
	$log_counts = defined( 'PUM_CACHEIT_COUNTS' ) && PUM_CACHEIT_COUNTS;

	$default_counts = [
		'misses'        => 0,
		'hits'          => 0,
		'invalidations' => 0,
	];

	static $cache = [];
	static $count = [
		'misses'        => 0,
		'hits'          => 0,
		'invalidations' => 0,
		'by_fn'         => [],
		'by_args'       => [],
	];

	$key = $fn_name;
	if ( null !== $args ) {
		$key = $fn_name . '_' . wp_json_encode( $args );
	}

	// Get log counts if enabled.
	if ( $log_counts && 'get_cacheit_counts' === $fn_name ) {
		return $count;
	}

	// Set up counters.
	if ( ! isset( $count['by_fn'][ $fn_name ] ) ) {
		$count['by_fn'][ $fn_name ] = $default_counts;
	}

	// Set up counters.
	if ( ! isset( $count['by_args'][ $key ] ) ) {
		$count['by_args'][ $key ] = $default_counts;
	}

	// Check and clear cache.
	if ( null === $callback || '__return_null' === $callback ) {
		unset( $cache[ $key ] );

		if ( $log_counts ) {
			++$count['invalidations'];
			++$count['by_fn'][ $fn_name ]['invalidations'];
			++$count['by_args'][ $key ]['invalidations'];
		}

		return null;
	}

	if ( ! isset( $cache[ $key ] ) ) {
		if ( false !== $callback ) {
			$cache[ $key ] = $callback();
		}

		if ( $log_counts ) {
			++$count['misses'];
			++$count['by_fn'][ $fn_name ]['misses'];
			++$count['by_args'][ $key ]['misses'];
		}
	} elseif ( $log_counts ) {
		++$count['hits'];
		++$count['by_fn'][ $fn_name ]['hits'];
		++$count['by_args'][ $key ]['hits'];
	}

	return $cache[ $key ] ?? null;
}

/**
 * Track cache function counts.
 */
if ( defined( 'PUM_CACHEIT_COUNTS' ) && PUM_CACHEIT_COUNTS ) {
	add_action( 'plugins_loaded', function () {
		if ( class_exists( 'QM_Collectors' ) ) {
			// Register collector
			\QM_Collectors::add( new \PopupMaker\Integration\QueryMonitor\Collector\CacheFunc() );

			// Register output handler
			add_filter( 'qm/outputter/html', function ( $output ) {
				$collector = \QM_Collectors::get( 'cache-func' );

				if ( $collector ) {
					$output['cache-func'] = new \PopupMaker\Integration\QueryMonitor\Output\Html\CacheFunc( $collector );
				}

				return $output;
			} );
		}
	} );
}


$value_history = [];

// Add value history tracking to the earliest hook
add_action( 'plugins_loaded', function () {
	global $value_history;
	$value_history = [];
}, -999999 );

/**
 * Get value history.
 *
 * @return array
 */
function get_value_history() {
	global $value_history;
	return $value_history;
}
