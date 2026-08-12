<?php
/**
 * What's New notification tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Services\Notifications\WhatsNew;

/**
 * Verify release announcements retain per-user dismissal state.
 */
class Whats_New_Notifications_Test extends WP_UnitTestCase {

	/** @var WhatsNew */
	private $provider;

	/** @return void */
	public function setUp(): void {
		parent::setUp();

		delete_option( WhatsNew::SLOT_OPTION );
		delete_option( WhatsNew::LAST_SEEN_OPTION );

		$this->provider = new WhatsNew( \PopupMaker\plugin() );
	}

	/** @return void */
	public function tearDown(): void {
		delete_option( WhatsNew::SLOT_OPTION );
		delete_option( WhatsNew::LAST_SEEN_OPTION );
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/** @return void */
	public function test_dismissal_keeps_release_available_to_other_users() {
		$first_user  = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$second_user = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$code        = 'pm_whats_new_release_1_25';

		$this->provider->on_version_update( '1.24.0', '1.25.0' );

		wp_set_current_user( $first_user );
		$this->assertTrue( PUM_Utils_Alerts::action_handler( $code, 'dismiss', '' ) );
		$this->provider->on_dismiss( $code );

		$this->assertTrue( PUM_Utils_Alerts::has_dismissed_alert( $code ) );
		$this->assertSame( '1.25', get_user_meta( $first_user, WhatsNew::LAST_SEEN_USER_META, true ) );
		$this->assertNotEmpty( get_option( WhatsNew::SLOT_OPTION ) );

		wp_set_current_user( $second_user );
		$this->assertFalse( PUM_Utils_Alerts::has_dismissed_alert( $code ) );
		$this->assertCount( 1, $this->provider->register_alert( [] ) );
	}

	/** @return void */
	public function test_catch_up_copy_uses_each_users_last_seen_release() {
		$first_user  = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$second_user = $this->factory->user->create( [ 'role' => 'administrator' ] );

		$this->provider->on_version_update( '1.24.0', '1.25.0' );
		wp_set_current_user( $first_user );
		$this->provider->on_dismiss( 'pm_whats_new_release_1_25' );
		$this->provider->on_version_update( '1.25.0', '1.26.0' );

		$first_alert = $this->provider->register_alert( [] );
		$this->assertSame( 'since v1.25', $first_alert[0]['subtitle'] );

		wp_set_current_user( $second_user );
		$second_alert = $this->provider->register_alert( [] );
		$this->assertSame( 'since v1.24', $second_alert[0]['subtitle'] );
	}
}
