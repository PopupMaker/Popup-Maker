<?php
/**
 * Tests for the legacy commercial extension updater.
 *
 * @package Popup_Maker
 */

/**
 * Test defensive handling of extension API responses.
 */
class PUM_Extension_Updater_Test extends WP_UnitTestCase {

	/**
	 * Updaters created during a test.
	 *
	 * @var array
	 */
	private $updaters = [];

	/**
	 * Remove updater hooks and caches after each test.
	 */
	public function tear_down() {
		foreach ( $this->updaters as $fixture ) {
			$updater = $fixture['updater'];

			delete_option( $fixture['cache_key'] );
			delete_option( $fixture['failed_request_cache_key'] );
			remove_filter( 'pre_set_site_transient_update_plugins', [ $updater, 'check_update' ] );
			remove_filter( 'plugins_api', [ $updater, 'plugins_api_filter' ] );
			remove_action( 'after_plugin_row', [ $updater, 'show_update_notification' ] );
			remove_action( 'admin_init', [ $updater, 'show_changelog' ] );
		}

		$this->updaters = [];

		parent::tear_down();
	}

	/**
	 * Create an updater and track its cache and hooks for cleanup.
	 *
	 * @param string $api_url Extension API URL.
	 * @return PUM_Extension_Updater
	 */
	private function create_updater( $api_url = '' ) {
		$license     = 'phpunit-extension-updater-' . count( $this->updaters );
		$plugin_file = WP_PLUGIN_DIR . '/popup-maker-pro/popup-maker-pro.php';
		$api_url     = $api_url ? $api_url : home_url();
		$updater     = new PUM_Extension_Updater(
			$api_url,
			$plugin_file,
			[
				'version'   => '1.0.0',
				'license'   => $license,
				'item_name' => 'Popup Maker Pro',
				'author'    => 'Popup Maker',
			]
		);

		$this->updaters[] = [
			'updater'                  => $updater,
			'cache_key'                => 'edd_sl_' . md5( maybe_serialize( 'popup-maker-pro' . $license ) ),
			'failed_request_cache_key' => 'edd_sl_failed_http_' . md5( trailingslashit( $api_url ) ),
		];

		return $updater;
	}

	/**
	 * A failed extension API request must remain false so WordPress can run its
	 * normal Plugin API fallback.
	 */
	public function test_plugins_api_filter_preserves_failed_response() {
		$updater = $this->create_updater();
		$result  = $updater->plugins_api_filter(
			false,
			'plugin_information',
			(object) [ 'slug' => 'popup-maker-pro' ]
		);

		$this->assertFalse( $result );
	}

	/**
	 * HTTP and payload failures must all use the WordPress Plugin API fallback.
	 *
	 * @dataProvider failed_remote_response_provider
	 * @param mixed $remote_response Preempted WordPress HTTP response.
	 */
	public function test_plugins_api_filter_rejects_failed_remote_responses( $remote_response ) {
		$api_url  = 'https://updates-' . wp_generate_uuid4() . '.example.test/';
		$updater  = $this->create_updater( $api_url );
		$requests = 0;
		$preempt  = static function ( $response, $args, $url ) use ( $api_url, $remote_response, &$requests ) {
			if ( $api_url !== $url ) {
				return $response;
			}

			++$requests;

			return $remote_response;
		};

		add_filter( 'pre_http_request', $preempt, 10, 3 );

		try {
			$result = $updater->plugins_api_filter(
				false,
				'plugin_information',
				(object) [ 'slug' => 'popup-maker-pro' ]
			);
		} finally {
			remove_filter( 'pre_http_request', $preempt, 10 );
		}

		$this->assertSame( 1, $requests );
		$this->assertFalse( $result );
	}

	/**
	 * Failed extension API HTTP and payload responses.
	 *
	 * @return array
	 */
	public function failed_remote_response_provider() {
		$response = static function ( $code, $body ) {
			return [
				'headers'  => [],
				'body'     => $body,
				'response' => [
					'code'    => $code,
					'message' => '',
				],
				'cookies'  => [],
			];
		};

		return [
			'network error'     => [ new WP_Error( 'http_request_failed', 'Connection failed.' ) ],
			'non-200 status'    => [ $response( 503, 'Unavailable' ) ],
			'malformed JSON'    => [ $response( 200, '{' ) ],
			'null JSON'         => [ $response( 200, 'null' ) ],
			'empty JSON object' => [ $response( 200, '{}' ) ],
		];
	}

	/**
	 * Invalid cached values must fall back instead of reaching property access.
	 *
	 * @dataProvider invalid_cached_response_provider
	 * @param mixed $cached_response Cached plugin information.
	 */
	public function test_plugins_api_filter_rejects_invalid_cached_responses( $cached_response ) {
		$updater = $this->create_updater();
		$updater->set_version_info_cache( $cached_response );

		$result = $updater->plugins_api_filter(
			false,
			'plugin_information',
			(object) [ 'slug' => 'popup-maker-pro' ]
		);

		$this->assertFalse( $result );
	}

	/**
	 * Invalid cached plugin information values.
	 *
	 * @return array
	 */
	public function invalid_cached_response_provider() {
		return [
			'null'                => [ null ],
			'array'               => [ [ 'Popup Maker Pro' ] ],
			'empty object'        => [ (object) [] ],
			'object without name' => [ (object) [ 'sections' => (object) [] ] ],
		];
	}

	/**
	 * Errors supplied by an earlier Plugin API filter must remain untouched.
	 */
	public function test_plugins_api_filter_preserves_wp_error() {
		$updater = $this->create_updater();
		$error   = new WP_Error( 'upstream_failure', 'Upstream filter failed.' );

		$result = $updater->plugins_api_filter(
			$error,
			'plugin_information',
			(object) [ 'slug' => 'popup-maker-pro' ]
		);

		$this->assertSame( $error, $result );
	}

	/**
	 * Valid commercial plugin information must still be normalized for Core.
	 */
	public function test_plugins_api_filter_normalizes_valid_response() {
		$updater = $this->create_updater();
		$updater->set_version_info_cache(
			(object) [
				'name'         => 'Popup Maker Pro',
				'new_version'  => '1.2.0',
				'sections'     => (object) [ 'changelog' => 'Fixed.' ],
				'banners'      => (object) [ 'low' => 'banner.png' ],
				'icons'        => (object) [ '1x' => 'icon.png' ],
				'contributors' => (object) [ 'popupmaker' => 'Popup Maker' ],
			]
		);

		$result = $updater->plugins_api_filter(
			false,
			'plugin_information',
			(object) [ 'slug' => 'popup-maker-pro' ]
		);

		$this->assertSame( 'Popup Maker Pro', $result->name );
		$this->assertSame( 'popup-maker-pro/popup-maker-pro.php', $result->plugin );
		$this->assertSame( '1.2.0', $result->version );
		$this->assertIsArray( $result->sections );
		$this->assertIsArray( $result->banners );
		$this->assertIsArray( $result->icons );
		$this->assertIsArray( $result->contributors );
	}

	/**
	 * Commercial update transient records must always include their plugin file.
	 *
	 * @dataProvider update_transient_bucket_provider
	 * @param string $new_version Version returned by the commercial API.
	 * @param string $bucket      Expected update transient bucket.
	 */
	public function test_check_update_includes_plugin_file( $new_version, $bucket ) {
		$updater = $this->create_updater();
		$updater->set_version_info_cache(
			(object) [
				'name'        => 'Popup Maker Pro',
				'new_version' => $new_version,
			]
		);

		$result = $updater->check_update( new stdClass() );

		$this->assertSame(
			'popup-maker-pro/popup-maker-pro.php',
			$result->{$bucket}['popup-maker-pro/popup-maker-pro.php']->plugin
		);
	}

	/**
	 * Available and current commercial extension versions.
	 *
	 * @return array
	 */
	public function update_transient_bucket_provider() {
		return [
			'update available' => [ '2.0.0', 'response' ],
			'already current'  => [ '1.0.0', 'no_update' ],
		];
	}
}
