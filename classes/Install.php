<?php
/**
 * Class for Install
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

use function PopupMaker\config;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Install
 *
 * @since 1.9.0
 */
class PUM_Install {

	/**
	 * @param $network_wide
	 */
	public static function activate_plugin( $network_wide ) {
		self::do_multisite( $network_wide, [ __CLASS__, 'activate_site' ] );
	}

	/**
	 * @param $network_wide
	 */
	public static function deactivate_plugin( $network_wide ) {
		self::do_multisite( $network_wide, [ __CLASS__, 'deactivate_site' ] );
	}

	/**
	 *
	 */
	public static function uninstall_plugin() {
		self::do_multisite( true, [ __CLASS__, 'uninstall_site' ] );
	}

	/**
	 * @param       $network_wide
	 * @param       $method
	 * @param array        $args
	 */
	private static function do_multisite( $network_wide, $method, $args = [] ) {
		global $wpdb;

		// Ensure all global functions are loaded.
		if ( ! function_exists( 'pum_is_func_disabled' ) ) {
			require_once __DIR__ . '/../includes/functions.php';
		}

		if ( is_multisite() && $network_wide ) {
			$activated = get_site_option( 'pum_activated', [] );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

			// Try to reduce the chances of a timeout with a large number of sites.
			if ( count( $blog_ids ) > 2 ) {
				ignore_user_abort( true );

				if ( ! pum_is_func_disabled( 'set_time_limit' ) ) {
					// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					@set_time_limit( 0 );
				}
			}

			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );
				call_user_func_array( $method, [ $args ] );

				$activated[] = $blog_id;

				restore_current_blog();
			}

			update_site_option( 'pum_activated', $activated );
		} else {
			call_user_func_array( $method, [ $args ] );
		}
	}

	/**
	 * Installs the plugin
	 */
	public static function activate_site() {

		// Add default values where needed.
		$options = array_merge(
			get_option( 'popmake_settings', [] ),
			[
				'disable_popup_category_tag' => 1,
			]
		);

		// Setup some default options.
		add_option( 'popmake_settings', $options );

		add_option( 'pum_version', Popup_Maker::$VER );

		pum();

		// Setup the Popup & Theme Custom Post Type.
		// PUM_Types::register_post_types();.

		// Setup the Popup Taxonomies.
		// PUM_Types::register_taxonomies( true );.

		// Updates stored values for versioning.
		// PUM_Utils_Upgrades::update_plugin_version();.

		// We used transients before, but since the check for this option runs every admin page load it means 2 queries after its cleared.
		// To prevent that we flipped it, now we delete the following option, and check for it.
		// If its missing then we know its a fresh install.
		delete_option( '_pum_installed' );

		// Prepare to redirect to welcome screen, if not seen before.
		if ( false === get_option( 'pum_seen_welcome' ) ) {
			set_transient( 'pum_activation_redirect', 1, 60 );
		}

		pum_get_default_theme_id();

		// Allow disabling of built in themes.
		// Example add_filter'pum_disable_install_themes', '__return_true' );.
		$themes_disabled = DEFINED( 'PUM_DISABLE_INSTALL_THEMES' ) && PUM_DISABLE_INSTALL_THEMES ? true : false;
		$themes_disabled = apply_filters( 'pum_disable_install_themes', $themes_disabled );

		if ( true !== $themes_disabled ) {
			pum_install_built_in_themes();
		}

		// Allow disabling of example popups.
		// Example add_filter'pum_disable_install_examples', '__return_true' );.
		$examples_disabled = DEFINED( 'PUM_DISABLE_INSTALL_EXAMPLES' ) && PUM_DISABLE_INSTALL_EXAMPLES ? true : false;
		$examples_disabled = apply_filters( 'pum_disable_install_examples', $examples_disabled );

		if ( true !== $examples_disabled ) {
			pum_install_example_popups();
		}

		// Reset JS/CSS assets for regeneration.
		pum_reset_assets();
	}

	public static function get_option( $key, $default_value = false ) {
		if ( function_exists( 'pum_get_option' ) ) {
			return pum_get_option( $key, $default_value );
		}

		return PUM_Utils_Options::get( $key, $default_value );
	}

	/**
	 * Run when Popup Maker is deactivated. Completely deletes all data if complete_uninstall is set to true.
	 *
	 * @since    1.4
	 */
	public static function deactivate_site() {

		/**
		 * Process complete uninstall
		 */
		if ( self::get_option( 'complete_uninstall' ) ) {
			global $wpdb;

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery

			// Delete all popups and associated meta.
			$wpdb->query( "DELETE a,b,c FROM $wpdb->posts a LEFT JOIN $wpdb->term_relationships b ON (a.ID = b.object_id) LEFT JOIN $wpdb->postmeta c ON (a.ID = c.post_id) WHERE a.post_type IN ('popup', 'popup_theme')" );
			$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'popup_%'" );

			/** Delete All the Taxonomies */
			foreach ( [ 'popup_category', 'popup_tag' ] as $taxonomy ) {
				// Prepare & excecute SQL, Delete Terms.
				$wpdb->get_results(
					$wpdb->prepare(
						"DELETE t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN (%s)",
						$taxonomy
					)
				);

				// Delete Taxonomy.
				$wpdb->delete( $wpdb->term_taxonomy, [ 'taxonomy' => $taxonomy ], [ '%s' ] );
			}

			$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'popmake%' OR option_name LIKE '_pum_%' OR option_name LIKE 'pum_%' OR option_name LIKE 'popup_analytics_%'" );

			// Delete all Popup Maker related user meta.
			$wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE '_pum_%' OR meta_key lIKE 'pum_%'" );

			// Delete subscribers table.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pum_subscribers" );

			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery

			// Delete error log.
			PUM_Utils_Logging::instance()->clear_log();

			// Reset JS/CSS assets for regeneration.
			pum_reset_assets();

			// # TODO Delete AssetCache files and folder.

			do_action( 'pum_uninstall' );
		}
	}

	/**
	 * @since 1.9.0
	 */
	public static function uninstall_site() {
	}

	/**
	 * Returns an activation failure flag if one exists.
	 *
	 * @return string|null
	 */
	public static function get_activation_flag() {
		global $wp_version;

		$flag = null;

		if ( version_compare( PHP_VERSION, config( 'min_php_ver' ), '<' ) ) {
			$flag = 'PHP';
		} elseif ( version_compare( $wp_version, config( 'min_wp_ver' ), '<' ) ) {
			$flag = 'WordPress';
		}

		return $flag;
	}

	/**
	 * Checks if Popup Maker can activate safely.
	 *
	 * @return bool
	 */
	public static function meets_activation_requirements() {
		return self::get_activation_flag() === null;
	}

	/**
	 * Gets activation failure notice message.
	 *
	 * @return string
	 */
	public static function get_activation_failure_notice() {
		$flag    = self::get_activation_flag();
		$version = 'PHP' === $flag ? config( 'min_php_ver' ) : config( 'min_wp_ver' );

		return sprintf(
			/* translators: 1. Plugin name, 2. Required plugin name, 3. Version number, 4. Opening HTML tag, 5. Closing HTML tag. */
			__( 'The %4$s %1$s %5$s plugin requires %2$s version %3$s or greater.', 'popup-maker' ),
			config( 'name' ),
			$flag,
			$version,
			'<strong>',
			'</strong>'
		);
	}

	/**
	 *
	 */
	public static function activation_failure_admin_notice() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo wp_kses( self::get_activation_failure_notice(), [ 'strong' => [] ] ); ?></p>
		</div>
		<?php
	}

	/**
	 * Plugin Activation hook function to check for Minimum PHP and WordPress versions
	 *
	 * Cannot use static:: in case php 5.2 is used.
	 */
	public static function activation_check() {
		if ( self::meets_activation_requirements() ) {
			return;
		}

		// Deactivate automatically due to insufficient PHP or WP Version.
		deactivate_plugins( basename( __FILE__ ) );

		add_action( 'admin_notices', [ __CLASS__, 'activation_failure_admin_notice' ] );
	}
}
