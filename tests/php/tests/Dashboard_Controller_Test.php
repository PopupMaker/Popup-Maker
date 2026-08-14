<?php
/**
 * Tests for the WordPress dashboard controller.
 *
 * @package Popup_Maker
 */

require_once dirname( __DIR__ ) . '/fixtures/class-pum-test-controller-container.php';

/**
 * Verify the Dashboard controller delegates analytics work to the repository.
 */
class Dashboard_Controller_Test extends WP_UnitTestCase {

	/**
	 * @return void
	 */
	public function test_dashboard_stats_delegate_to_popup_repository() {
		$expected_stats = [
			'total_views'        => 25,
			'total_conversions'  => 5,
			'conversion_rate'    => 20.0,
			'top_performer'      => null,
			'top_performer_rate' => 0.0,
		];
		$repository     = new class( $expected_stats ) {
			/**
			 * @var array<string,mixed>
			 */
			private $stats;

			/**
			 * @var int
			 */
			public $calls = 0;

			/**
			 * @param array<string,mixed> $stats Dashboard stats.
			 */
			public function __construct( $stats ) {
				$this->stats = $stats;
			}

			/**
			 * @return array<string,mixed>
			 */
			public function get_dashboard_stats() {
				++$this->calls;

				return $this->stats;
			}
		};
		$container      = new class( $repository ) {
			/**
			 * @var object
			 */
			private $repository;

			/**
			 * @var array<int,string>
			 */
			public $requested_services = [];

			/**
			 * @param object $repository Popup repository.
			 */
			public function __construct( $repository ) {
				$this->repository = $repository;
			}

			/**
			 * @param string $service Service key.
			 * @return object
			 */
			public function get( $service ) {
				$this->requested_services[] = $service;

				return $this->repository;
			}
		};

		$controller = new \PopupMaker\Controllers\WP\Dashboard( $container );
		$method     = new ReflectionMethod( $controller, 'get_dashboard_stats' );

		if ( PHP_VERSION_ID < 80100 ) {
			$method->setAccessible( true );
		}

		$this->assertSame( $expected_stats, $method->invoke( $controller ) );
		$this->assertSame( [ 'popups' ], $container->requested_services );
		$this->assertSame( 1, $repository->calls );
	}
}
