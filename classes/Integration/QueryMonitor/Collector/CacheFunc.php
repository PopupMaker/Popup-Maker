<?php
/**
 * Function cache collector for Query Monitor.
 *
 * @package PopupMaker\Integration\QueryMonitor
 */

namespace PopupMaker\Integration\QueryMonitor\Collector;

use QM_Collector;

/**
 * @extends QM_Collector
 */
class CacheFunc extends QM_Collector {

	public $id = 'cache-func';

	public function name() {
		return __( 'Function Cache', 'popup-maker' );
	}

	public function process() {
		$stats = \PopupMaker\cacheit( 'get_cacheit_counts' );

		if ( empty( $stats ) ) {
			return;
		}

		$this->data = [
			'stats'         => $stats,
			'total_hits'    => $stats['hits'],
			'total_misses'  => $stats['misses'],
			'total_calls'   => $stats['hits'] + $stats['misses'],
			'invalidations' => $stats['invalidations'],
			'by_function'   => $stats['by_fn'] ?? [],
			'by_args'       => $stats['by_args'] ?? [],
		];

		if ( $this->data['total_calls'] > 0 ) {
			$this->data['hit_percentage'] = round( ( $this->data['total_hits'] / $this->data['total_calls'] ) * 100, 1 );
		} else {
			$this->data['hit_percentage'] = 0;
		}

		$this->data['counts']        = \PopupMaker\cacheit( 'get_cacheit_counts' );
		$this->data['value_history'] = \PopupMaker\get_value_history();
	}
}
