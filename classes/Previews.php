<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Previews
 *
 * This class sets up the necessary changes to allow admins & editors to preview popups on the front end.
 */
class PUM_Previews {

	/**
	 * Initiator method.
	 */
	public static function init() {
		// add_filter( 'template_include', array( __CLASS__, 'template_include' ), 1000, 2 );
		add_filter( 'pum_popup_is_loadable', array( __CLASS__, 'is_loadable' ), 1000, 2 );
		add_filter( 'pum_popup_data_attr', array( __CLASS__, 'data_attr' ), 1000, 2 );
		add_filter( 'pum_popup_get_public_settings', array( __CLASS__, 'get_public_settings' ), 1000, 2 );

	}

	/**
	 * This changes the template to a blank one to prevent duplicate content issues.
	 *
	 * @param $template
	 *
	 * @return string
	 */
	public static function template_include( $template ) {
		if ( ! is_singular( 'popup' ) ) {
			return $template;
		}

		return POPMAKE_DIR . 'templates/single-popup.php';
	}

	/**
	 * For popup previews this will force only the correct popup to load.
	 *
	 * @param bool $loadable
	 * @param int $popup_id
	 *
	 * @return bool
	 */
	public static function is_loadable( $loadable, $popup_id ) {
		return self::should_preview_popup( $popup_id ) ? true : $loadable;
	}

	/**
	 * Sets the Popup Post Type public arg to true for content editors.
	 *
	 * This enables them to use the built in preview links.
	 *
	 * @param int $popup_id
	 *
	 * @return bool
	 */
	public static function should_preview_popup( $popup_id = 0 ) {
		if ( defined( "DOING_AJAX" ) && DOING_AJAX ) {
			return false;
		}


		if ( isset( $_GET['popup_preview'] ) && $_GET['popup_preview'] && isset( $_GET['popup'] ) ) {

			static $popup;

			if ( ! isset( $popup ) ) {
				if ( is_numeric( $_GET['popup'] ) && absint( $_GET['popup'] ) > 0 ) {
					$popup = absint( $_GET['popup'] );
				} else {
					$post  = get_page_by_path( sanitize_text_field( $_GET['popup'] ), OBJECT, 'popup' );
					$popup = $post->ID;
				}
			}

			if ( $popup_id == $popup && current_user_can( 'edit_post', $popup ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * On popup previews add an admin debug trigger.
	 *
	 * @param $data_attr
	 * @param $popup_id
	 *
	 * @return mixed
	 */
	public static function data_attr( $data_attr, $popup_id ) {
		if ( ! self::should_preview_popup( $popup_id ) ) {
			return $data_attr;
		}

		$data_attr['triggers'] = array(
			array(
				'type' => 'admin_debug',
			),
		);

		return $data_attr;
	}

	/**
	 * On popup previews add an admin debug trigger.
	 *
	 * @param array $settings
	 * @param PUM_Model_Popup $popup
	 *
	 * @return array
	 */
	public static function get_public_settings( $settings, $popup ) {
		if ( ! self::should_preview_popup( $popup->ID ) ) {
			return $settings;
		}

		$settings['triggers'] = array(
			array(
				'type' => 'admin_debug',
			),
		);

		return $settings;
	}
}
