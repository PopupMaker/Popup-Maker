<?php
/**
 * Regression tests for install-age consumers.
 *
 * @package Popup_Maker
 */

/** Verify stored install dates and their notice eligibility gates. */
class PUM_Install_Age_Test extends WP_UnitTestCase {
	/** Canonical dates survive removal of the obsolete options. */
	public function test_consumers_keep_install_age_after_legacy_cleanup() {
		$installed_on = '2020-01-01 00:00:00';
		update_option( 'popup_maker_version_info', [ 'installed_on' => $installed_on ] );
		update_option( 'pum_installed_on', $installed_on );
		\PopupMaker\cleanup_old_install_data();

		$this->assertFalse( get_option( 'pum_installed_on' ) );
		$this->assertSame( $installed_on, PUM_Telemetry::get_installed_on() );
		$this->assertSame( $installed_on, PUM_Admin_Onboarding::get_installed_on() );
		$this->assertSame( $installed_on, PUM_Modules_Reviews::installed_on() );
		$this->assertFalse( get_option( 'pum_reviews_installed_on' ) );
	}

	/** A review-specific reset retains its current cadence. */
	public function test_existing_review_timer_takes_precedence() {
		update_option( 'popup_maker_version_info', [ 'installed_on' => '2020-01-01 00:00:00' ] );
		update_option( 'pum_reviews_installed_on', '2021-01-01 00:00:00' );
		$this->assertSame( '2021-01-01 00:00:00', PUM_Modules_Reviews::installed_on() );
	}

	/** Canonical install data wins when obsolete data disagrees. */
	public function test_canonical_date_takes_precedence_over_legacy_date() {
		update_option( 'popup_maker_version_info', [ 'installed_on' => '2020-01-01 00:00:00' ] );
		update_option( 'pum_installed_on', '2021-01-01 00:00:00' );

		$this->assertSame( '2020-01-01 00:00:00', PUM_Telemetry::get_installed_on() );
		$this->assertSame( '2020-01-01 00:00:00', PUM_Admin_Onboarding::get_installed_on() );
	}

	/** Fresh Core initialization records one date for every consumer. */
	public function test_fresh_install_records_a_date_without_recreating_review_timer() {
		delete_option( 'popup_maker_version_info' );
		\PopupMaker\cleanup_old_install_data();
		update_option( 'timezone_string', 'UTC' );
		$check = new ReflectionMethod( \PopupMaker\Plugin\Core::class, 'check_version' );
		if ( PHP_VERSION_ID < 80100 ) {
			$check->setAccessible( true );
		}
		$check->invoke( \PopupMaker\plugin() );

		$installed_on = \PopupMaker\get_current_install_info( 'installed_on' );
		$this->assertLessThan( 3, abs( time() - strtotime( $installed_on ) ) );
		$this->assertSame( $installed_on, PUM_Telemetry::get_installed_on() );
		$this->assertSame( $installed_on, PUM_Admin_Onboarding::get_installed_on() );
		$this->assertSame( $installed_on, PUM_Modules_Reviews::installed_on() );
		$this->assertFalse( get_option( 'pum_installed_on' ) );
		$this->assertFalse( get_option( 'pum_reviews_installed_on' ) );
	}

	/** The real Core migration preserves age before deleting old options. */
	public function test_core_migrates_legacy_install_date_before_cleanup() {
		$installed_on = '2020-01-01 00:00:00';
		delete_option( 'popup_maker_version_info' );
		update_option( 'pum_installed_on', $installed_on );
		update_option( 'pum_reviews_installed_on', $installed_on );
		update_option( 'pum_ver', \PopupMaker\config( 'version' ) );
		$check = new ReflectionMethod( \PopupMaker\Plugin\Core::class, 'check_version' );
		if ( PHP_VERSION_ID < 80100 ) {
			$check->setAccessible( true );
		}
		$check->invoke( \PopupMaker\plugin() );

		$this->assertFalse( get_option( 'pum_installed_on' ) );
		$this->assertSame( $installed_on, \PopupMaker\get_current_install_info( 'installed_on' ) );
		$this->assertSame( $installed_on, PUM_Telemetry::get_installed_on() );
		$this->assertSame( $installed_on, PUM_Admin_Onboarding::get_installed_on() );
		$this->assertSame( $installed_on, PUM_Modules_Reviews::installed_on() );
		$this->assertFalse( get_option( 'pum_reviews_installed_on' ) );
	}

	/** Consumers still accept the old option before Core migrates it. */
	public function test_consumers_fall_back_to_legacy_date_before_migration() {
		delete_option( 'popup_maker_version_info' );
		update_option( 'pum_installed_on', '2020-01-01 00:00:00' );

		$this->assertSame( '2020-01-01 00:00:00', PUM_Telemetry::get_installed_on() );
		$this->assertSame( '2020-01-01 00:00:00', PUM_Admin_Onboarding::get_installed_on() );
	}

	/** Missing dates resolve to now independently of the site offset. */
	public function test_missing_date_falls_back_to_current_utc_at_nonzero_site_offsets() {
		delete_option( 'popup_maker_version_info' );
		delete_option( 'pum_installed_on' );
		update_option( 'timezone_string', '' );
		foreach ( [ -8, 10 ] as $offset ) {
			update_option( 'gmt_offset', $offset );
			$this->assertLessThan( 3, abs( time() - strtotime( PUM_Telemetry::get_installed_on() ) ) );
			$this->assertLessThan( 3, abs( time() - strtotime( PUM_Admin_Onboarding::get_installed_on() ) ) );
		}
	}

	/** Mature installs regain eligible notices while preserving user controls. */
	public function test_migrated_age_gates_preserve_consent_permissions_and_tip_controls() {
		update_option( 'popup_maker_version_info', [ 'installed_on' => '2020-01-01 00:00:00' ] );
		\PopupMaker\cleanup_old_install_data();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		set_current_screen( 'edit-popup' );
		pum_delete_option( 'telemetry' );
		pum_delete_option( 'disable_tips' );

		try {
			$this->assertTrue( PUM_Telemetry::should_show_alert() );
			$this->assertTrue( PUM_Admin_Onboarding::should_show_tip() );
			$this->assertFalse( PUM_Telemetry::has_opted_in() );

			pum_update_option( 'telemetry', true );
			pum_update_option( 'disable_tips', true );
			$this->assertFalse( PUM_Telemetry::should_show_alert() );
			$this->assertFalse( PUM_Admin_Onboarding::should_show_tip() );

			pum_delete_option( 'telemetry' );
			pum_delete_option( 'disable_tips' );
			wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );
			$this->assertFalse( PUM_Telemetry::should_show_alert() );
			$this->assertFalse( PUM_Admin_Onboarding::should_show_tip() );

			wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
			set_current_screen( 'dashboard' );
			$this->assertFalse( PUM_Admin_Onboarding::should_show_tip() );
		} finally {
			pum_delete_option( 'telemetry' );
			pum_delete_option( 'disable_tips' );
			set_current_screen( 'front' );
			wp_set_current_user( 0 );
		}
	}
}
