<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.4
 * @package    PUM
 * @subpackage PUM/includes
 * @author     Daniel Iser <danieliser@wizardinternetsolutions.com>
 */
class PUM_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.4
	 */
	public static function deactivate( $network_wide = false ) {

		/**
		 * Process complete uninstall
		 */
		if ( pum_get_option( 'complete_uninstall' ) ) {
			global $wpdb;

			// Delete all popups and associated meta.
			$wpdb->query( "DELETE a,b,c FROM $wpdb->posts a LEFT JOIN $wpdb->term_relationships b ON (a.ID = b.object_id) LEFT JOIN $wpdb->postmeta c ON (a.ID = c.post_id) WHERE a.post_type IN ('popup', 'popup_theme')" );
			$wpdb->query( "DELETE FROM $wpdb->post_meta WHERE meta_key LIKE 'popup_%'" );

			/** Delete All the Taxonomies */
			foreach ( array( 'popup_category', 'popup_tag', ) as $taxonomy ) {
				// Prepare & excecute SQL, Delete Terms
				$wpdb->get_results( $wpdb->prepare( "DELETE t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ('%s')", $taxonomy ) );
				// Delete Taxonomy
				$wpdb->delete( $wpdb->term_taxonomy, array( 'taxonomy' => $taxonomy ), array( '%s' ) );
			}

			$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'popmake%' OR option_name LIKE '_pum_%' OR option_name LIKE 'pum_%' OR option_name LIKE 'popup_analytics_%'" );

			// Reset JS/CSS assets for regeneration.
			pum_reset_assets();

			// # TODO Delete AssetCache files and folder.

			do_action( 'pum_uninstall' );
		}
	}

}