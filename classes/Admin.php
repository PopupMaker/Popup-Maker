<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

class PUM_Admin {

	public static function init() {
		PUM_Admin_Pages::init();
		PUM_Admin_Ajax::init();
		PUM_Admin_Assets::init();
		PUM_Admin_Popups::init();
		PUM_Admin_Themes::init();
		PUM_Admin_Subscribers::init();
		PUM_Admin_Settings::init();
		PUM_Admin_Tools::init();
		PUM_Admin_Shortcode_UI::init();
		PUM_Upsell::init();

		add_filter( 'user_has_cap', array( __CLASS__, 'prevent_default_theme_deletion' ), 10, 3 );
		add_filter( 'plugin_action_links', array( __CLASS__, 'plugin_action_links' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'after_install' ) );
	}

	/**
	 * Prevent user from deleting the current default popup_theme
	 *
	 * @param $allcaps
	 * @param $caps
	 * @param $args
	 *
	 * @return mixed
	 */
	public static function prevent_default_theme_deletion( $allcaps, $caps, $args ) {
		global $wpdb;
		if ( isset( $args[0] ) && isset( $args[2] ) && $args[2] == pum_get_option( 'default_theme' ) && $args[0] == 'delete_post' ) {
			$allcaps[ $caps[0] ] = false;
		}

		return $allcaps;
	}

	/**
	 * Render plugin action links.
	 *
	 * @param $links
	 * @param $file
	 *
	 * @return mixed
	 */
	public static function plugin_action_links( $links, $file ) {

		if ( $file == plugin_basename( POPMAKE ) ) {
			$plugin_action_links = apply_filters( 'pum_plugin_action_links', array(
				'extend' => '<a href="' . admin_url( 'edit.php?post_type=popup&page=pum-extensions' ) . '">' . __( 'Integrations', 'popup-maker' ) . '</a>',
				'settings'   => '<a href="' . admin_url( 'edit.php?post_type=popup&page=pum-settings' ) . '">' . __( 'Settings', 'popup-maker' ) . '</a>',
			) );

			foreach ( $plugin_action_links as $link ) {
				array_unshift( $links, $link );
			}
		}

		return $links;
	}

	/**
	 * Post-installation
	 *
	 * Runs just after plugin installation and exposes the
	 * popmake_after_install hook.
	 *
	 * @since 1.0
	 * @return void
	 */
	public static function after_install() {

		if ( ! is_admin() ) {
			return;
		}

		// Exit if not in admin or the transient doesn't exist
		if ( false === get_transient( '_pum_installed' ) ) {
			return;
		}

		// TODO Delete transient after neccessary actions perfromed.

		do_action( 'pum_after_install' );
	}
}