<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Modules_Admin_Bar
 *
 * This class adds admin bar menu for Popup Management.
 */
class PUM_Modules_Admin_Bar {

	/**
	 * Initializes this module.
	 */
	public static function init() {
		add_action( 'admin_bar_menu', array( __CLASS__, 'toolbar_links' ), 999 );
		//add_action( 'admin_bar_menu', array( __CLASS__, 'admin_toolbar_links' ), 999 );

		add_action( 'init', array( __CLASS__, 'show_debug_bar' ) );
	}

	/**
	 * Renders the admin debug bar when PUM Debug is enabled.
	 */
	public static function show_debug_bar() {
		if ( PUM_Debug::on() ) {
			show_admin_bar( true );
		}
	}


	/**
	 * Add additional toolbar menu items to the front end.
	 *
	 * @param $wp_admin_bar
	 */
	public static function toolbar_links( $wp_admin_bar ) {

		if ( is_admin() ) {
			return;
		}

		$wp_admin_bar->add_node( array(
			'id'    => 'popup-maker',
			'title' => __( 'Popup Maker', 'popup-maker' ),
			'href'  => '#',
			'meta'  => array( 'class' => 'popup-maker-toolbar' ),
		) );

		$popups = PUM_Modules_Admin_Bar::loaded_popups();

		if ( count( $popups ) ) {
			$wp_admin_bar->add_node( array(
				'id'    => 'loaded-popups',
				'title' => __( 'Loaded Popups', 'popup-maker' ),
				'href'  => '#',
				'parent' => 'popup-maker',
			) );

			foreach ( $popups as $popup ) {
				/** @var WP_Post $popup */

				$node_id = 'loaded-popup-' . $popup->ID;

				$can_edit = current_user_can( 'edit_post', $popup->ID );

				$edit_url = $can_edit ? admin_url( 'post.php?post=' . $popup->ID . '&action=edit' ) : '#';

				// Single Popup Menu Node
				$wp_admin_bar->add_node( array(
					'id'    => $node_id,
					'title' => $popup->post_title,
					'href'  => $edit_url,
					'parent' => 'loaded-popups',
				) );

				if ( $can_edit ) {
					// Edit Popup Link
					$wp_admin_bar->add_node( array(
						'id'    => $node_id . '-edit',
						'title' => __( 'Edit', 'popup-maker' ),
						'href'  => $edit_url,
						'parent' => $node_id,
					) );
				}

				// Trigger Link
				$wp_admin_bar->add_node( array(
					'id'    => $node_id . '-trigger',
					'title' => __( 'Trigger', 'popup-maker' ),
					'meta' => array(
						'onclick' => 'PUM.open(' . $popup->ID . ');',
					),
					'href'  => '#',
					'parent' => $node_id,
				) );

				$wp_admin_bar->add_node( array(
					'id'    => $node_id . '-trigger',
					'title' => __( 'Close', 'popup-maker' ),
					'meta' => array(
						'onclick' => 'PUM.close(' . $popup->ID . ');',
					),
					'href'  => '#',
					'parent' => $node_id,
				) );

				$wp_admin_bar->add_node( array(
					'id'    => $node_id . '-reset-cookies',
					'title' => __( 'Reset Cookies', 'popup-maker' ),
					'href'  => '#',
					'parent' => $node_id,
				) );
			}
		}

	}

	public static function loaded_popups() {
		static $popups;

		if ( ! isset( $popups ) ) {

			global $popmake_loaded_popups;

			if ( ! $popmake_loaded_popups instanceof WP_Query ) {
				$popmake_loaded_popups        = new WP_Query();
				$popmake_loaded_popups->posts = array();
			}

			$popups = $popmake_loaded_popups->posts;
		}

		return $popups;
	}
}

PUM_Modules_Admin_Bar::init();