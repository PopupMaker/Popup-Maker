<?php
/**
 * Dashboard controller.
 *
 * @author    Code Atlantic
 * @package   PopupMaker\Pro
 * @copyright (c) 2025, Code Atlantic LLC.
 */

namespace PopupMaker\Controllers\WP;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard controller class.
 *
 * @extends Controller<\PopupMaker\Plugin\Core>
 */
class Dashboard extends Controller {

	/**
	 * Initialize admin controller.
	 *
	 * @return void
	 */
	public function init() {
		// Register dashboard widgets
		add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widgets' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_dashboard_scripts' ] );
	}

	/**
	 * Register dashboard widgets.
	 *
	 * @return void
	 */
	public function register_dashboard_widgets() {
		// Basic Analytics Widget (will be moved to free plugin).
		wp_add_dashboard_widget(
			'pum_analytics_basic',
			__( 'Popup Analytics','popup-maker' ),
			[ $this, 'render_basic_analytics_widget' ]
		);
	}

	/**
	 * Enqueue dashboard scripts.
	 *
	 * @return void
	 */
	public function enqueue_dashboard_scripts() {
		$current_screen = get_current_screen();

		if ( ! $current_screen || $current_screen->id !== 'dashboard' ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_script( 'popup-maker-dashboard' );
		wp_enqueue_style( 'popup-maker-dashboard' );
	}	

	/**
	 * Render basic analytics widget.
	 *
	 * @return void
	 */
	public function render_basic_analytics_widget() {
		$upgrade_link = 'https://wppopupmaker.com/pricing/?utm_source=wp-dashboard&utm_medium=dashboard&utm_campaign=upgrade-to-pro';
		// Get analytics data

		$stats = $this->get_dashboard_stats();
		$total_views = $stats['total_views'];
		$total_conversions = $stats['total_conversions'];
		$conversion_rate = $stats['conversion_rate'];
		$top_performer = $stats['top_performer'];
		?>
		<div class="pum-dashboard-widget pum-analytics-basic">
			<span class="pum-widget-badge" style="display: none;"><?php esc_html_e( 'Free','popup-maker' ); ?></span>

			<div class="pum-stats-grid">
				<div class="pum-stat">
					<div class="pum-stat-value pum-stat-views"><?php echo esc_html( number_format( $total_views ) ); ?></div>
					<div class="pum-stat-label"><?php esc_html_e( 'Total Views','popup-maker' ); ?></div>
				</div>
				<div class="pum-stat">
					<div class="pum-stat-value pum-stat-conversions"><?php echo esc_html( number_format( $total_conversions ) ); ?></div>
					<div class="pum-stat-label"><?php esc_html_e( 'Conversions','popup-maker' ); ?></div>
				</div>
			</div>

			<div class="pum-conversion-rate">
				<div class="pum-rate-value"><?php echo esc_html( round( $conversion_rate, 1 ) ); ?>%</div>
				<div class="pum-rate-label"><?php esc_html_e( 'Overall Conversion Rate','popup-maker' ); ?></div>
			</div>

			<?php if ( $top_performer ) :
					$top_performer_rate = $stats['top_performer_rate'];
				?>
			<div class="pum-top-performer">
				<div class="pum-performer-label"><?php esc_html_e( 'Top Performer:','popup-maker' ); ?></div>
				<div class="pum-performer-name"><a href="<?php echo esc_url( admin_url( 'post.php?post=' . $top_performer->ID . '&action=edit' ) ); ?>"><?php echo esc_html( $top_performer->post_title ); ?></a> (<?php echo esc_html( round( $top_performer_rate, 1 ) ); ?>%)</div>
			</div>
			<?php endif; ?>

			<?php if ( ! \PopupMaker\is_pro_active() ) : ?>
			<div class="pum-upgrade-box">
				<div class="pum-upgrade-header">
					<span class="pum-upgrade-icon">👑</span>
					<span class="pum-upgrade-title"><?php esc_html_e( 'Upgrade to Pro','popup-maker' ); ?></span>
				</div>
				<p class="pum-upgrade-text"><?php esc_html_e( 'Get detailed analytics, revenue tracking, exit intent, advanced targeting, and more','popup-maker' ); ?></p>
				<a href="<?php echo esc_url( $upgrade_link ); ?>" class="pum-upgrade-button" target="_blank">
					<?php esc_html_e( 'Upgrade Now','popup-maker' ); ?>
				</a>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get top performing popup.
	 *
	 * @return \PUM_Model_Popup|null
	 */
	private function get_dashboard_stats() {
		$popups = pum_get_all_popups([
			'status' => 'publish',
			'posts_per_page' => -1,
			'meta_query' => [
				'relation' => 'AND',
				[
					'key' => 'enabled',
					'value' => 1,
					'compare' => '=',
				],
				[
					'key' => 'popup_open_count',
					'value' => 0,
					'compare' => '>',
					'type' => 'NUMERIC',
				],
			],
		]);

		if ( empty( $popups ) ) {
			return [
				'total_views' => 0,
				'total_conversions' => 0,
				'conversion_rate' => 0,
				'top_performer' => null,
				'top_performer_rate' => 0,
			];
		}

		// Sort by priority: conversion rate > conversions > views
		usort( $popups, function( $a, $b ) {
			$a_rate = (float) $a->get_meta( 'popup_conversion_rate' );
			$b_rate = (float) $b->get_meta( 'popup_conversion_rate' );
			
			// First priority: conversion rate
			if ( $a_rate !== $b_rate ) {
				return $b_rate <=> $a_rate; // Descending
			}
			
			$a_conversions = (int) $a->get_meta( 'popup_conversion_count' );
			$b_conversions = (int) $b->get_meta( 'popup_conversion_count' );
			
			// Second priority: conversions
			if ( $a_conversions !== $b_conversions ) {
				return $b_conversions <=> $a_conversions; // Descending
			}
			
			// Third priority: views
			$a_views = (int) $a->get_meta( 'popup_open_count' );
			$b_views = (int) $b->get_meta( 'popup_open_count' );
			
			return $b_views <=> $a_views; // Descending
		});

		$top_performer = $popups[0];
		$top_performer_rate = ( (int) $top_performer->get_meta( 'popup_conversion_count' ) / (int) $top_performer->get_meta( 'popup_open_count' ) ) * 100;

		$popup_views = 0;
		$popup_conversions = 0;

		foreach ( $popups as $popup ) {
			$popup_views += (int) $popup->get_meta( 'popup_open_count' );
			$popup_conversions += (int) $popup->get_meta( 'popup_conversion_count' );
		}

		return [
			'total_views' => $popup_views,
			'total_conversions' => $popup_conversions,
			'conversion_rate' => $popup_conversions / $popup_views * 100,
			'top_performer' => $top_performer,
			'top_performer_rate' => $top_performer_rate,
		];
	}

}
