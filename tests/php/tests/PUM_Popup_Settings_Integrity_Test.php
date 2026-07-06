<?php
/**
 * Tests that guard against destructive loss of popup_settings.
 *
 * These reproduce the "popup settings deleted / reset" reports where a
 * saved popup ends up with only theme keys ({theme_id, theme_slug}) and all
 * triggers / conditions / display settings are gone. Each test isolates one
 * write path that can shrink popup_settings when it runs against an empty or
 * partially-loaded base.
 *
 * @package Popup_Maker
 */

/**
 * @group settings-integrity
 */
class PUM_Popup_Settings_Integrity_Test extends WP_UnitTestCase {

	/**
	 * A realistic, fully-populated settings array as stored by a live popup.
	 *
	 * @return array<string,mixed>
	 */
	private function full_settings() {
		return [
			'triggers'   => [
				[
					'type'     => 'click_open',
					'settings' => [ 'extra_selectors' => '.btn-buy' ],
				],
			],
			'conditions' => [
				[
					[ 'target' => 'is_front_page' ],
				],
			],
			'cookies'    => [
				[
					'event'    => 'on_popup_close',
					'settings' => [
						'name' => 'pum-1',
						'time' => '1 month',
					],
				],
			],
			'size'       => 'medium',
			'location'   => 'center',
			'theme_id'   => 12345,
			'theme_slug' => 'default-theme',
		];
	}

	/**
	 * Create a real popup post with the given settings meta.
	 *
	 * @param array<string,mixed> $settings Settings to store.
	 * @return int Popup ID.
	 */
	private function make_popup( $settings ) {
		$id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Pricing Popup',
			]
		);
		update_post_meta( $id, 'popup_settings', $settings );
		// Mark as already current so passive migration does not fire unexpectedly.
		update_post_meta( $id, 'data_version', 3 );
		return $id;
	}

	/**
	 * Reload a popup's settings straight from the DB (bypassing any object cache).
	 *
	 * @param int $id Popup ID.
	 * @return array<string,mixed>
	 */
	private function stored_settings( $id ) {
		wp_cache_delete( $id, 'post_meta' );
		$settings = get_post_meta( $id, 'popup_settings', true );
		return is_array( $settings ) ? $settings : [];
	}

	// ------------------------------------------------------------------
	// P2 — REST/React editor merge save.
	// ------------------------------------------------------------------

	/**
	 * A merge save whose in-memory base was poisoned to empty must NOT
	 * collapse the stored record down to just the incoming partial keys.
	 *
	 * Reproduces: update_settings( $partial, merge=true ) after get_settings()
	 * cached an empty base.
	 */
	public function test_merge_save_does_not_wipe_when_base_empty() {
		$id    = $this->make_popup( $this->full_settings() );
		$popup = new PUM_Model_Popup( $id );

		// Simulate a poisoned/empty in-memory base (failed or premature read).
		$popup->settings = [];

		// A realistic partial save from the editor (e.g. only display size changed).
		$popup->update_settings( [ 'size' => 'large' ], true );

		$after = $this->stored_settings( $id );

		$this->assertArrayHasKey( 'triggers', $after, 'Triggers must survive a partial merge save.' );
		$this->assertNotEmpty( $after['triggers'], 'Triggers must not be emptied.' );
		$this->assertArrayHasKey( 'conditions', $after, 'Conditions must survive a partial merge save.' );
	}

	// ------------------------------------------------------------------
	// P3 — write-on-read theme_slug during front-end render.
	// ------------------------------------------------------------------

	/**
	 * Rendering a popup (which resolves theme_slug lazily) must never persist
	 * a shrunken settings record when the settings base is momentarily empty.
	 *
	 * Reproduces: get_theme_slug() -> update_setting('theme_slug', ...) on an
	 * empty base, writing {theme_id, theme_slug} as the whole record.
	 */
	public function test_theme_slug_resolution_does_not_persist_shrunken_settings() {
		$id    = $this->make_popup( $this->full_settings() );
		$popup = new PUM_Model_Popup( $id );

		// Force the empty-base condition, then trigger slug resolution via the
		// public API used during render (get_classes -> get_theme_slug).
		$popup->settings = [];
		$popup->get_classes();

		$after = $this->stored_settings( $id );

		$this->assertArrayHasKey( 'triggers', $after, 'Front-end render must not drop triggers.' );
		$this->assertNotEmpty( $after['triggers'], 'Front-end render must not empty triggers.' );
	}

	// ------------------------------------------------------------------
	// P1 — passive migration fold + delete source keys.
	// ------------------------------------------------------------------

	/**
	 * The passive migration folds legacy meta keys into popup_settings and then
	 * deletes the legacy keys. If the fold runs against an empty base it must
	 * not delete the legacy source data, leaving the popup with neither.
	 *
	 * Reproduces: pum_popup_migration_2 with a poisoned settings base.
	 */
	public function test_passive_migration_preserves_data_on_empty_base() {
		// Build a v2-era popup: real data lives in legacy meta keys.
		$id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Legacy Popup',
			]
		);
		update_post_meta( $id, 'popup_triggers', [
			[
				'type'     => 'auto_open',
				'settings' => [ 'delay' => 500 ],
			],
		] );
		update_post_meta( $id, 'popup_conditions', [ [ [ 'target' => 'is_front_page' ] ] ] );
		update_post_meta( $id, 'popup_theme', 12345 );
		update_post_meta( $id, 'data_version', 2 );

		$popup = new PUM_Model_Popup( $id );

		// Poison the settings base so the fold sees nothing to carry over.
		$popup->settings = [];

		pum_popup_migration_2( $popup );

		wp_cache_delete( $id, 'post_meta' );

		$triggers   = get_post_meta( $id, 'popup_triggers', true );
		$settings   = $this->stored_settings( $id );
		$has_in_new = ! empty( $settings['triggers'] );
		$has_in_old = ! empty( $triggers );

		$this->assertTrue(
			$has_in_new || $has_in_old,
			'Trigger data must survive migration in either the new or legacy location, never be deleted from both.'
		);
	}

	/**
	 * If the migration fold yields an empty/theme-only result while legacy
	 * source keys hold real data, it must abort rather than delete the sources.
	 */
	public function test_passive_migration_aborts_instead_of_deleting_sources() {
		$id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Legacy Popup 2',
			]
		);
		update_post_meta( $id, 'popup_triggers', [
			[
				'type'     => 'auto_open',
				'settings' => [ 'delay' => 500 ],
			],
		] );
		update_post_meta( $id, 'data_version', 2 );

		$popup = new PUM_Model_Popup( $id );

		// Directly exercise the guard: an empty proposed settings write that
		// would require deleting source keys must be refused.
		$is_destructive = PUM_Model_Popup::is_destructive_settings_write(
			[ 'triggers' => [ [ 'type' => 'auto_open' ] ] ],
			[
				'theme_id'   => 1,
				'theme_slug' => 'default-theme',
			]
		);

		$this->assertTrue( $is_destructive, 'Guard must flag a content->theme-only write as destructive.' );

		// And a normal fold (content present) must be allowed.
		$is_ok = PUM_Model_Popup::is_destructive_settings_write(
			[ 'triggers' => [ [ 'type' => 'auto_open' ] ] ],
			[
				'triggers' => [ [ 'type' => 'auto_open' ] ],
				'theme_id' => 1,
			]
		);

		$this->assertFalse( $is_ok, 'Guard must allow a fold that preserves content.' );
	}

	// ------------------------------------------------------------------
	// Guard unit behavior — new-popup and legitimate-clear cases.
	// ------------------------------------------------------------------

	/**
	 * A brand-new popup (no stored content) must be writable with anything,
	 * including a theme-only starter record.
	 */
	public function test_guard_allows_writes_to_new_popup() {
		$this->assertFalse(
			PUM_Model_Popup::is_destructive_settings_write( [], [
				'theme_id'   => 1,
				'theme_slug' => 'x',
			] ),
			'Writing to an empty/new popup is never destructive.'
		);
		$this->assertFalse(
			PUM_Model_Popup::is_destructive_settings_write( [ 'theme_id' => 1 ], [ 'theme_id' => 2 ] ),
			'A record with only incidental keys has no content to protect.'
		);
	}

	/**
	 * A merge save that keeps content while changing one key is allowed and
	 * persists correctly (no false-positive refusal).
	 */
	public function test_legit_partial_merge_persists() {
		$id    = $this->make_popup( $this->full_settings() );
		$popup = new PUM_Model_Popup( $id );

		$result = $popup->update_settings( [ 'size' => 'large' ], true );

		$this->assertNotWPError( $result, 'A legitimate partial save must not be refused.' );

		$after = $this->stored_settings( $id );
		$this->assertSame( 'large', $after['size'], 'The changed key must persist.' );
		$this->assertNotEmpty( $after['triggers'], 'Existing content must be retained.' );
	}

	// ------------------------------------------------------------------
	// P4 — classic metabox save with popup_settings absent from POST.
	// ------------------------------------------------------------------

	/**
	 * When a save_post fires without our popup_settings field (e.g. a page
	 * builder saving the post), the existing settings must be preserved rather
	 * than replaced with an empty/near-empty record.
	 *
	 * Reproduces: PUM_Admin_Popups::save_popup with $_POST['popup_settings'] absent.
	 */
	public function test_metabox_save_without_settings_field_preserves_existing() {
		$id = $this->make_popup( $this->full_settings() );

		$admin = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin );

		// Simulate a save_post where our settings field was never rendered/submitted.
		// The nonce action is basename( __FILE__ ) of classes/Admin/Popups.php.
		$_POST = [
			'pum_popup_settings_nonce' => wp_create_nonce( 'Popups.php' ),
		];

		$post = get_post( $id );
		PUM_Admin_Popups::save( $id, $post );

		$after = $this->stored_settings( $id );

		$this->assertArrayHasKey( 'triggers', $after, 'A builder save without our field must not wipe triggers.' );
		$this->assertNotEmpty( $after['triggers'], 'A builder save without our field must not empty triggers.' );

		$_POST = [];
	}
}
