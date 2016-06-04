<?php
/**
 * Cookie
 *
 * @package     PUM
 * @subpackage  Classes/PUM_Previews
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
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
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 1000, 2 );
		add_filter( 'pum_popup_is_loadable', array( __CLASS__, 'is_loadable' ), 1000, 2 );
		add_filter( 'pum_popup_get_data_attr', array( __CLASS__, 'data_attr' ), 1000, 2 );
		add_filter( 'popmake_popup_post_type_args', array( __CLASS__, 'post_type_args' ) );
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
	 * @param $loadable
	 * @param $popup_id
	 *
	 * @return bool
	 */
	public static function is_loadable( $loadable, $popup_id ) {
		if ( ! is_singular( 'popup' ) ) {
			return $loadable;
		}

		if ( absint( $popup_id ) == get_the_ID() ) {
			return true;
		}

		return false;
	}

	/**
	 * On popup previews replace triggers with an admin debug trigger.
	 *
	 * @param $data_attr
	 * @param $popup_id
	 *
	 * @return mixed
	 */
	public static function data_attr( $data_attr, $popup_id ) {
		if ( ! is_singular( 'popup' ) ) {
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
	 * Sets the Popup Post Type public arg to true for content editors.
	 *
	 * This enables them to use the built in preview links.
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	public static function post_type_args( $args = array() ) {
		global $pagenow;

		if ( defined( "DOING_AJAX" ) && DOING_AJAX ) {
			return $args;
		}

		if ( ( ( is_admin() && $pagenow == 'post.php' && ( isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == 'popup' ) ) || get_post_type() == 'popup' ) && current_user_can( 'edit_posts' ) ) {
			$args['public'] = true;
		}

		return $args;
	}
}

// Initiate the PUM_Preview Class.
PUM_Previews::init();
