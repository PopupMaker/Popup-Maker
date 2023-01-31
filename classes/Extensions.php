<?php
/**
 * Extension handlers.
 *
 * @package PUM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PUM_Extensions
 */
class PUM_Extensions {

	/**
	 * Get everything going.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize actions & filters.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'rename_plugins' ] );
		add_action( 'upgrader_process_complete', [ $this, 'monitor_plugin_udpates_for_renaming' ], 10, 2 );
	}

	/**
	 * Get list of plugins that need renaming.
	 *
	 * @return string[]
	 */
	public function get_renamed_plugin_map() {
		// Array of old plugin name => new plugin name.
		$renamed_plugin_map = [
			// phpcs:disable Squiz.PHP.CommentedOutCode.Found
			// 'popup-maker-edd/popup-maker-edd.php' => 'popup-maker-edd-pro/popup-maker-edd-pro.php',
			// 'pum-aweber-integration/pum-aweber-integration.php' => 'popup-maker-aweber-integration/popup-maker-aweber-integration.php',
			// 'pum-mailchimp-integration/pum-mailchimp-integration.php' => 'popup-maker-mailchimp-integration/popup-maker-mailchimp-integration.php',
			'pum-scheduling/pum-scheduling.php' => 'popup-maker-scheduling/popup-maker-scheduling.php',
			// 'pum-videos/pum-videos.php'           => 'popup-maker-videos/popup-maker-videos.php',
			// phpcs:enable Squiz.PHP.CommentedOutCode.Found
		];

		return $renamed_plugin_map;
	}

	/**
	 * This function runs when WordPress completes its upgrade process.
	 * It iterates through each plugin updated to see if it is included in our renamed plugin map.
	 *
	 * @param array $upgrader_object Array of information regarding the upgrade process.
	 * @param array $options         Array of bulk item update data.
	 */
	public function monitor_plugin_udpates_for_renaming( $upgrader_object, $options ) {
		$renamed_plugin_map = $this->get_renamed_plugin_map();
		$renamed_plugins    = array_keys( $renamed_plugin_map );
		$_transient_data    = [];

		// If an update has taken place and the updated type is plugins and the plugins element exists.
		if ( 'update' === $options['action'] && 'plugin' === $options['type'] && isset( $options['plugins'] ) ) {
			// Iterate through the plugins being updated and check if ours is there.
			foreach ( $options['plugins'] as $plugin ) {
				if ( in_array( $plugin, $renamed_plugins, true ) ) {
					// Set a transient to record that our plugin has just been updated.
					$_transient_data[ $plugin ] = $renamed_plugin_map[ $plugin ];
				}
			}
		}

		if ( ! empty( $_transient_data ) ) {
			set_transient( 'pum_renamed_plugins', $_transient_data, DAY_IN_SECONDS );
		}
	}

	/**
	 * Forces reactivation of renamed plugin.
	 *
	 * @param string $plugin The plugin to check.
	 * @param bool   $network_wide Whether to check for network wide activation.
	 * @return void
	 */
	public function force_activate_renamed_plugin( $plugin, $network_wide ) {
		$renamed_plugin_map = $this->get_renamed_plugin_map();

		if ( isset( $renamed_plugin_map[ $plugin ] ) ) {
			$renamed_plugin = $renamed_plugin_map[ $plugin ];

			// Deactivate the old plugin.
			if ( is_plugin_active( $plugin ) ) {
				deactivate_plugins( $plugin, true, $network_wide );
			}

			// Activate the new plugin.
			activate_plugin( $renamed_plugin, '', $network_wide );
		}
	}

	/**
	 * This function reactivates plugins that have been renamed, without requireing user interaction.
	 *
	 * @return void
	 */
	public function rename_plugins() {
		$renamed_plugin_map = $this->get_renamed_plugin_map();

		// If we have no renamed plugins, return early.
		if ( empty( $renamed_plugin_map ) ) {
			return;
		}

		// Get the transient data.
		$_transient_data = get_transient( 'pum_renamed_plugins' );

		// If we have no transient data, return early.
		if ( empty( $_transient_data ) ) {
			return;
		}

		// Require neccesary functions if they don't exist.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Foreach plugin in pum_renamed_plugins transient as $old_plugin => $new_plugin.
		foreach ( $_transient_data as $old_plugin => $new_plugin ) {
			// 1. If the old file exists & new file does not, the migration we're fixing didn't occur, unset & continue.
			if ( file_exists( WP_PLUGIN_DIR . '/' . $old_plugin ) && ! file_exists( WP_PLUGIN_DIR . '/' . $new_plugin ) ) {
				unset( $_transient_data[ $old_plugin ] );
				continue;
			}

			// 2. Check if the old plugin was network active.
			$network_wide = is_multisite() && is_plugin_active_for_network( $old_plugin );

			// 3. If is multisite & not network wide, loop over every site and force activate the new plugin. Else just force activate the new plugin.
			if ( is_multisite() && ! $network_wide ) {
				$sites = get_sites();

				foreach ( $sites as $site ) {
					switch_to_blog( $site->blog_id );
					// Only force activate if the plugin was active on this site before.
					if ( is_plugin_active( $old_plugin ) ) {
						$this->force_activate_renamed_plugin( $old_plugin, false );
					}
					restore_current_blog();
				}
			} else {
				$this->force_activate_renamed_plugin( $old_plugin, $network_wide );
			}

			// 4. Remove $old_plugin from pum_renamed_plugins transient.
			unset( $_transient_data[ $old_plugin ] );
		}

		if ( empty( $_transient_data ) ) {
			delete_transient( 'pum_renamed_plugins' );
		} else {
			set_transient( 'pum_renamed_plugins', $_transient_data, DAY_IN_SECONDS );
		}
	}

}
