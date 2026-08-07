<?php
/**
 * Extension updater tests.
 *
 * @package PopupMaker
 * @subpackage Tests
 */

/**
 * Validates extension update API compatibility.
 */
class PUM_Extension_Updater_Test extends WP_UnitTestCase {

	/**
	 * A failed extension API request preserves WordPress's default false response.
	 */
	public function test_plugins_api_filter_preserves_false_when_api_request_fails() {
		global $edd_plugin_data;

		$slug    = 'popup-maker-test-extension';
		$updater = new PUM_Extension_Updater(
			home_url( '/' ),
			WP_PLUGIN_DIR . "/{$slug}/{$slug}.php",
			[
				'version' => '1.0.0',
				'license' => '',
				'author'  => 'Popup Maker',
			]
		);

		try {
			$result = $updater->plugins_api_filter(
				false,
				'plugin_information',
				(object) [ 'slug' => $slug ]
			);

			$this->assertFalse( $result );
		} finally {
			remove_filter( 'pre_set_site_transient_update_plugins', [ $updater, 'check_update' ] );
			remove_filter( 'plugins_api', [ $updater, 'plugins_api_filter' ] );
			remove_action( 'after_plugin_row', [ $updater, 'show_update_notification' ] );
			remove_action( 'admin_init', [ $updater, 'show_changelog' ] );
			delete_option( 'edd_sl_' . md5( maybe_serialize( $slug ) ) );
			delete_option( 'edd_api_request_' . md5( maybe_serialize( $slug ) ) );
			unset( $edd_plugin_data[ $slug ] );
		}
	}
}
