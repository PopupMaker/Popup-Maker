<?php
/**
 * Upgrades class
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Controllers;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Upgrades controller class.
 *
 * @package PopupMaker\Controllers\Upgrades
 */
class Upgrades extends Controller {

	/**
	 * Initialize upgrades controller.
	 */
	public function init() {
		// Hook into version updates for migrations.
		add_action( 'pum_update_core_version', [ $this, 'handle_block_editor_migration' ] );
	}

	/**
	 * Handle block editor migration from gutenberg_support_enabled to enable_classic_editor.
	 *
	 * This migration handles the transition from beta block editor setting to default block editor.
	 * Users who previously had block editor disabled get a notice about the change.
	 *
	 * @param string $old_version The previous plugin version.
	 *
	 * @return void
	 * @since 1.21.0
	 */
	public function handle_block_editor_migration( $old_version ) {
		// Only run migration logic once per version update.
		if ( get_option( 'pum_block_editor_migration_handled', false ) ) {
			return;
		}

		// Check if user had a previous gutenberg_support_enabled setting.
		$previous_gutenberg_setting = $this->container->get_option( 'gutenberg_support_enabled', null );

		// Store migration state for notice targeting.
		if ( null !== $previous_gutenberg_setting ) {
			// User had the setting - track their choice.
			update_option( 'pum_gutenberg_legacy_choice', $previous_gutenberg_setting ? 'enabled' : 'disabled' );

			// If they had it disabled, they should see the migration notice.
			if ( ! $previous_gutenberg_setting ) {
				update_option( 'pum_show_block_editor_migration_notice', true );
			}

			// Clean up the old setting.
			pum_delete_option( 'gutenberg_support_enabled' );
		} else {
			// New user or no previous setting - no notice needed.
			update_option( 'pum_gutenberg_legacy_choice', 'new_user' );
		}

		// Mark migration as handled to prevent duplicate runs.
		update_option( 'pum_block_editor_migration_handled', true );
	}
}
