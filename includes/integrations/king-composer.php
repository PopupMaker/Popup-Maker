<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Popmake_KingComposer_Integration {

	public static function init() {
		add_filter( 'popmake_popup_post_type_args', array( __CLASS__, 'popup_post_type_args' ) );
		add_action( 'admin_init', array( __CLASS__, 'force_enable_kc' ), 99 );
	}

	public static function popup_post_type_args( $popup_args ) {
		if ( defined( 'KC_VERSION' ) || ( ! empty( $_GET['page'] ) && in_array( $_GET['page'], array( 'fl-builder-settings' ) ) ) ) {
			$popup_args['public']              = true;
			$popup_args['exclude_from_search'] = true;
			$popup_args['publicly_queryable']  = true;
			$popup_args['show_in_nav_menus']   = false;
		}

		return $popup_args;
	}

	public static function force_enable_kc() {
		if ( popmake_is_admin_popup_page() ) {
			if ( defined( 'KC_VERSION' ) ) {
				global $kc;
				$kc->add_content_type(array('popup'));
			}
		}
	}
}

Popmake_KingComposer_Integration::init();
