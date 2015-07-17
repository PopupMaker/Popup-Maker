<?php

class Popmake_Upgrade_v1_3 {

	public function __construct() {
		add_action( 'popmake_process_upgrade', array( $this, 'process' ), 10, 2 );
	}

	public function process( $new, $old ) {

		global $wpdb;

		// Return if upgrade is not needed.
		if ( ! version_compare( $old, '1.2.2', '<=') ) {
			return;
		}

		$this->process_popups();
		$this->process_popup_themes();

	}

	public function process_popups() {
		global $wpdb;

		$popups = get_posts( array(
			'post_type' => 'popup',
			'post_status' => 'all',
			'posts_per_page' => -1,
		) );

		$popup_groups = array(
			'display' => popmake_popup_display_defaults(),
			'close' => popmake_popup_close_defaults(),
			'click_open' => popmake_popup_click_open_defaults(),
			'auto_open' => popmake_popup_auto_open_defaults(),
			'admin_debug' => popmake_popup_admin_debug_defaults()
		);

		$popup_ids = array();

		foreach ( $popups as $popup ) {

			$popup_ids[] = $popup->ID;

			foreach ( $popup_groups as $group => $defaults ) {
				$values = array_merge( $defaults, popmake_get_popup_meta_group( $group, $popup->ID ) );
				update_post_meta( $popup->ID, "popup_{$group}", $values );
			}

		}

		$popup_fields = array();

		foreach( $popup_groups as $group => $defaults ) {
			foreach( apply_filters( 'popmake_popup_meta_field_group_' . $group, array() ) as $field ) {
				$popup_fields[] = 'popup_' . $group . '_' . $field;
			}
		}

		$popup_ids = implode( ',', $popup_ids );
		$popup_fields = implode( "','", $popup_fields );

		$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id IN($popup_ids) AND meta_key IN('$popup_fields');" );

	}

	public function process_popup_themes() {
		global $wpdb;

		$themes = get_posts( array(
			'post_type' => 'popup_theme',
			'post_status' => 'all',
			'posts_per_page' => -1,
		) );

		$theme_groups = array(
			'overlay' => popmake_popup_theme_overlay_defaults(),
			'container' => popmake_popup_theme_container_defaults(),
			'title' => popmake_popup_theme_title_defaults(),
			'content' => popmake_popup_theme_content_defaults(),
			'close' => popmake_popup_theme_close_defaults(),
		);

		$theme_ids = array();

		foreach ( $themes as $theme ) {

			$theme_ids[] = $theme->ID;

			foreach ( $theme_groups as $group => $defaults ) {
				$values = array_merge( $defaults, popmake_get_popup_theme_meta_group( $group, $theme->ID ) );
				update_post_meta( $theme->ID, "popup_theme_{$group}", $values );
			}

		}

		$theme_fields = array();

		foreach( $theme_groups as $group => $defaults ) {
			foreach( apply_filters( 'popmake_popup_theme_meta_field_group_' . $group, array() ) as $field ) {
				$theme_fields[] = 'popup_theme_' . $group . '_' . $field;
			}
		}

		$theme_ids = implode( ',', $theme_ids );
		$theme_fields = implode( "','", $theme_fields );

		$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id IN($theme_ids) AND meta_key IN('$theme_fields');" );

	}

}

new Popmake_Upgrade_v1_3();