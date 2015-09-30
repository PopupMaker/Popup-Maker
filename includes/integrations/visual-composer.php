<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Popmake_VisualComposer_Integration {
	public static function init() {
		add_filter( 'popmake_popup_post_type_args', array( __CLASS__, 'popup_post_type_args' )  );
	}

	public static function popup_post_type_args( $popup_args ) {
		if ( ! empty( $_GET['page'] ) && $_GET['page'] == 'vc_settings' ) {
			$popup_args['public'] = true;
		}
		if ( ! empty( $_GET['page'] ) && $_GET['page'] == 'fl-builder-settings' ) {
			$popup_args['public'] = true;
		}


		return $popup_args;
	}
}

Popmake_VisualComposer_Integration::init();