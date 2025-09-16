<?php
/**
 * Function cache output for Query Monitor.
 *
 * @package PopupMaker\Integration\QueryMonitor
 */

namespace PopupMaker\Integration\QueryMonitor\Output\Html;

use QM_Output_Html;

/**
 * Query Monitor output class for function cache.
 */
class CacheFunc extends QM_Output_Html {

	/**
	 * Constructor.
	 *
	 * @param \QM_Collector $collector Collector.
	 */
	public function __construct( $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', [ $this, 'admin_menu' ], 30 );
	}

	/**
	 * Get name.
	 *
	 * @return string
	 */
	public function name() {
		return __( 'Function Cache', 'popup-maker' );
	}

	/**
	 * Output the content.
	 */
	public function output() {
		$data = $this->collector->get_data();

		$this->before_tabular_output();

		echo '<h3>Cache Statistics</h3>';
		echo '<table>';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html__( 'Function', 'popup-maker' ) . '</th>';
		echo '<th>' . esc_html__( 'Hits', 'popup-maker' ) . '</th>';
		echo '<th>' . esc_html__( 'Misses', 'popup-maker' ) . '</th>';
		echo '<th>' . esc_html__( 'Total', 'popup-maker' ) . '</th>';
		echo '<th>' . esc_html__( 'Hit Rate', 'popup-maker' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( ! empty( $data['counts']['by_fn'] ) ) {
			foreach ( $data['counts']['by_fn'] as $fn => $stats ) {
				$total    = $stats['hits'] + $stats['misses'];
				$hit_rate = $total ? round( ( $stats['hits'] / $total ) * 100, 1 ) : 0;

				echo '<tr>';
				echo '<td class="qm-ltr">' . esc_html( $fn ) . '</td>';
				echo '<td>' . esc_html( number_format_i18n( $stats['hits'] ) ) . '</td>';
				echo '<td>' . esc_html( number_format_i18n( $stats['misses'] ) ) . '</td>';
				echo '<td>' . esc_html( number_format_i18n( $total ) ) . '</td>';
				echo '<td>' . esc_html( $hit_rate ) . '%</td>';
				echo '</tr>';
			}
		}

		echo '</tbody>';
		echo '</table>';

		if ( ! empty( $data['value_history'] ) ) {
			echo '<h3>Value History</h3>';
			echo '<p class="qm-notice">' . esc_html__( 'Functions with changing return values across hooks. These may not be suitable for caching.', 'popup-maker' ) . '</p>';
			echo '<table>';
			echo '<thead>';
			echo '<tr>';
			echo '<th>' . esc_html__( 'Function', 'popup-maker' ) . '</th>';
			echo '<th>' . esc_html__( 'Hook', 'popup-maker' ) . '</th>';
			echo '<th>' . esc_html__( 'Value', 'popup-maker' ) . '</th>';
			echo '<th>' . esc_html__( 'Time', 'popup-maker' ) . '</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			foreach ( $data['value_history'] as $key => $history ) {
				foreach ( $history as $entry ) {
					$value_preview = is_scalar( $entry['value'] ) ?
						esc_html( substr( (string) $entry['value'], 0, 100 ) ) :
						'(' . gettype( $entry['value'] ) . ')';

					echo '<tr>';
					echo '<td class="qm-ltr">' . esc_html( $key ) . '</td>';
					echo '<td>' . esc_html( $entry['hook'] ) . '</td>';
					echo '<td>' . wp_kses_post( $value_preview ) . '</td>';
					echo '<td>' . esc_html( number_format( $entry['timestamp'], 4 ) ) . '</td>';
					echo '</tr>';
				}
			}

			echo '</tbody>';
			echo '</table>';
		}

		$this->after_tabular_output();
	}

	/**
	 * Add admin menu item.
	 *
	 * @param array $menu Menu items.
	 * @return array
	 */
	public function admin_menu( array $menu ) {
		$data = $this->collector->get_data();

		if ( empty( $data ) || empty( $data['by_function'] ) ) {
			return $menu;
		}

		$menu[ $this->collector->id() ] = $this->menu( [
			'title' => esc_html__( 'Function Cache', 'popup-maker' ) . ' (' . number_format_i18n( $data['total_calls'] ) . ')',
			'href'  => '#qm-cache-func',
		] );

		return $menu;
	}
}
