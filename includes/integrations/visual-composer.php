<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Popmake_VisualComposer_Integration {

	public static function init() {
		add_filter( 'popmake_popup_post_type_args', array( __CLASS__, 'popup_post_type_args' ) );
		add_action( 'admin_init', array( __CLASS__, 'force_enable_vc' ) );
	}

	public static function popup_post_type_args( $popup_args ) {
		if ( defined( 'WPB_VC_VERSION' ) || ( ! empty( $_GET['page'] ) && in_array( $_GET['page'], array( 'fl-builder-settings' ) ) ) ) {
			$popup_args['public']              = true;
			$popup_args['exclude_from_search'] = true;
			$popup_args['publicly_queryable']  = true;
			$popup_args['show_in_nav_menus']   = false;
		}

		return $popup_args;
	}

	public static function force_enable_vc() {
		if ( popmake_is_admin_popup_page() ) {
			add_filter( 'vc_role_access_with_post_types_get_state', '__return_true' );
			add_filter( 'vc_role_access_with_backend_editor_get_state', '__return_true' );
			add_filter( 'vc_role_access_with_frontend_editor_get_state', '__return_false' );
			add_filter( 'vc_check_post_type_validation', '__return_true' );
		}
	}
}

Popmake_VisualComposer_Integration::init();