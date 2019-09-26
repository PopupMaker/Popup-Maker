<?php


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_WPML_Integration
 */
class PUM_WPML_Integration {

	/**
	 *
	 */
	public static function init() {
		add_action( 'icl_make_duplicate', array( __CLASS__, 'duplicate_post' ), 10, 4 );
		/*
		add_filter( 'pum_popup', array( __CLASS__, 'pum_popup' ), 10, 2 );
		TODO Further testing of this filter may prove 80+% of the following unneeded.
		*/
		add_filter( 'pum_popup_get_display', array( __CLASS__, 'popup_get_display' ), 10, 2 );
		add_filter( 'pum_popup_get_close', array( __CLASS__, 'popup_get_close' ), 10, 2 );
		add_filter( 'pum_popup_get_triggers', array( __CLASS__, 'popup_get_triggers' ), 10, 2 );
		add_filter( 'pum_popup_get_cookies', array( __CLASS__, 'popup_get_cookies' ), 10, 2 );
		add_filter( 'pum_popup_get_conditions', array( __CLASS__, 'popup_get_conditions' ), 10, 2 );
		add_filter( 'pum_popup_get_theme_id', array( __CLASS__, 'popup_get_theme_id' ), 10, 2 );
		add_filter( 'pum_popup_mobile_disabled', array( __CLASS__, 'popup_mobile_disabled' ), 10, 2 );
		add_filter( 'pum_popup_tablet_disabled', array( __CLASS__, 'popup_tablet_disabled' ), 10, 2 );
	}

	/**
	 * @param      $popup
	 * @param null $popup_id
	 *
	 * @return \PUM_Model_Popup
	 */
	public static function pum_popup( $popup, $popup_id = null ) {
		if ( self::is_new_popup_translation( $popup_id ) ) {
			remove_filter( 'pum_popup', array( __CLASS__, 'pum_popup' ), 10 );
			$popup = pum_get_popup( self::source_id( $popup_id ) );
			add_filter( 'pum_popup', array( __CLASS__, 'pum_popup' ), 10, 2 );
		}

		return $popup;

	}

	/**
	 * @param int $post_id
	 *
	 * @return bool
	 */
	public static function is_new_popup_translation( $post_id = 0 ) {
		global $pagenow, $sitepress;

		return is_admin() && $pagenow == 'post-new.php' && ! empty( $_GET['post_type'] ) && $_GET['post_type'] == 'popup' && self::source_id( $post_id ) > 0;
	}

	/**
	 * @param int $post_id
	 *
	 * @return int
	 */
	public static function source_id( $post_id = 0 ) {
		global $sitepress;

		static $source_id;

		if ( ! isset( $source_id ) && ! empty( $sitepress ) && method_exists( $sitepress, 'get_new_post_source_id' ) ) {
			$source_id = absint( $sitepress->get_new_post_source_id( $post_id ) );
		}

		return $source_id;
	}

	/**
	 * @param int $post_id
	 */
	public static function source_lang( $post_id = 0 ) {

	}

	/**
	 * @param int $post_id
	 *
	 * @return int
	 */
	public static function trid( $post_id = 0 ) {
		global $sitepress;

		static $trid;

		if ( ! isset( $trid )  && ! empty( $sitepress ) && method_exists( $sitepress, 'get_element_trid' )) {
			$trid = absint( $sitepress->get_element_trid( $post_id, 'post_popup' ) );
		}

		return $trid;
	}

	/**
	 * @param $disabled
	 * @param $post_id
	 *
	 * @return bool
	 */
	public static function popup_mobile_disabled( $disabled, $post_id ) {
		if ( self::is_new_popup_translation( $post_id ) ) {
			remove_filter( 'pum_popup_mobile_disabled', array( __CLASS__, 'popup_mobile_disabled' ), 10 );
			$disabled = pum_get_popup( self::source_id( $post_id ) )->mobile_disabled();
			add_filter( 'pum_popup_mobile_disabled', array( __CLASS__, 'popup_mobile_disabled' ), 10, 2 );
		}

		return $disabled;
	}

	/**
	 * @param $disabled
	 * @param $post_id
	 *
	 * @return bool
	 */
	public static function popup_tablet_disabled( $disabled, $post_id ) {
		if ( self::is_new_popup_translation( $post_id ) ) {
			remove_filter( 'pum_popup_tablet_disabled', array( __CLASS__, 'popup_tablet_disabled' ), 10 );
			$disabled = pum_get_popup( self::source_id( $post_id ) )->tablet_disabled();
			add_filter( 'pum_popup_tablet_disabled', array( __CLASS__, 'popup_tablet_disabled' ), 10, 2 );
		}

		return $disabled;
	}

	/**
	 * @param $triggers
	 * @param $post_id
	 *
	 * @return array
	 */
	public static function popup_get_triggers( $triggers, $post_id ) {
		if ( self::is_new_popup_translation( $post_id ) ) {
			remove_filter( 'pum_popup_get_triggers', array( __CLASS__, 'popup_get_triggers' ), 10 );
			$triggers = pum_get_popup( self::source_id( $post_id ) )->get_triggers();
			add_filter( 'pum_popup_get_triggers', array( __CLASS__, 'popup_get_triggers' ), 10, 2 );
		}

		return $triggers;
	}

	/**
	 * @param $display
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public static function popup_get_display( $display, $post_id ) {
		if ( self::is_new_popup_translation( $post_id ) ) {
			remove_filter( 'pum_popup_get_display', array( __CLASS__, 'popup_get_display' ), 10 );
			$display = pum_get_popup( self::source_id( $post_id ) )->get_display();
			add_filter( 'pum_popup_get_display', array( __CLASS__, 'popup_get_display' ), 10, 2 );
		}

		return $display;
	}

	/**
	 * @param $close
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public static function popup_get_close( $close, $post_id ) {
		if ( self::is_new_popup_translation( $post_id ) ) {
			remove_filter( 'pum_popup_get_close', array( __CLASS__, 'popup_get_close' ), 10 );
			$close = pum_get_popup( self::source_id( $post_id ) )->get_close();
			add_filter( 'pum_popup_get_close', array( __CLASS__, 'popup_get_close' ), 10, 2 );
		}

		return $close;
	}

	/**
	 * @param $cookies
	 * @param $post_id
	 *
	 * @return array
	 */
	public static function popup_get_cookies( $cookies, $post_id ) {
		if ( self::is_new_popup_translation( $post_id ) ) {
			remove_filter( 'pum_popup_get_cookies', array( __CLASS__, 'popup_get_cookies' ), 10 );
			$cookies = pum_get_popup( self::source_id( $post_id ) )->get_cookies();
			add_filter( 'pum_popup_get_cookies', array( __CLASS__, 'popup_get_cookies' ), 10, 2 );
		}

		return $cookies;
	}

	/**
	 * @param $theme_id
	 * @param $post_id
	 *
	 * @return int
	 */
	public static function popup_get_theme_id( $theme_id, $post_id ) {
		if ( self::is_new_popup_translation( $post_id ) ) {
			remove_filter( 'pum_popup_get_theme_id', array( __CLASS__, 'popup_get_theme_id' ), 10 );
			$theme_id = pum_get_popup( self::source_id( $post_id ) )->get_theme_id();
			add_filter( 'pum_popup_get_theme_id', array( __CLASS__, 'popup_get_theme_id' ), 10, 2 );
		}

		return $theme_id;
	}

	/**
	 * @param      $conditions
	 * @param null $new_lang
	 *
	 * @return mixed
	 */
	public static function remap_conditions( $conditions, $new_lang = null ) {
		if ( ! isset( $new_lang ) && empty( $_GET['lang'] ) ) {
			return $conditions;
		}

		if ( ! isset( $new_lang ) ) {
			$new_lang = $_GET['lang'];
		}

		foreach ( $conditions as $group_key => $group ) {

			foreach ( $group as $key => $condition ) {

				$target = $condition['target'];

				$tests = array(
					strpos( $target, '_selected' ) !== false,
					strpos( $target, '_ID' ) !== false,
					strpos( $target, '_children' ) !== false,
					strpos( $target, '_ancestors' ) !== false,
					strpos( $target, '_w_' ) !== false,
				);

				if ( ! in_array( true, $tests ) ) {
					continue;
				}

				// Taxonomy
				if ( strpos( $target, 'tax_' ) === 0 ) {
					$t = explode( '_', $target );
					// Remove the tax_ prefix.
					array_shift( $t );
					// Assign the last key as the modifier _all, _selected
					$modifier = array_pop( $t );
					// Whatever is left is the taxonomy.
					$type = implode( '_', $t );
				} // Post by Tax
				elseif ( strpos( $target, '_w_' ) !== false ) {
					$t = explode( '_w_', $target );
					// First key is the post type.
					$post_type = array_shift( $t );
					// Last Key is the taxonomy
					$type = array_pop( $t );
				} // Post Type
				else {
					$t = explode( '_', $target );
					// Modifier should be the last key.
					$modifier = array_pop( $t );
					// Post type is the remaining keys combined.
					$type = implode( '_', $t );
				}

				// To hold the newly remapped selection.
				$selected = array();

				foreach ( wp_parse_id_list( $condition['selected'] ) as $object_id ) {
					// Insert the translated post_id or the original if no translation exists.
					$selected[] = wpml_object_id_filter( $object_id, $type, true, $new_lang );
				}

				// Replace the original conditions with the new remapped ones.
				$conditions[ $group_key ][ $key ]['selected'] = $selected;
			}
		}

		return $conditions;
	}

	/**
	 * @param $conditions
	 * @param $post_id
	 *
	 * @return array|mixed
	 */
	public static function popup_get_conditions( $conditions, $post_id ) {
		if ( self::is_new_popup_translation( $post_id ) ) {
			remove_filter( 'pum_popup_get_conditions', array( __CLASS__, 'popup_get_conditions' ), 10 );

			$popup = pum_get_popup(  self::source_id( $post_id ) );
			$conditions = $popup->get_conditions();

			$conditions = self::remap_conditions( $conditions, $post_id );

			add_filter( 'pum_popup_get_conditions', array( __CLASS__, 'popup_get_conditions' ), 10, 2 );
		}

		return $conditions;
	}

	/**
	 * @return mixed|void
	 */
	public static function untranslatable_meta_keys() {
		return apply_filters( 'pum_wpml_untranslatable_meta_keys', array(
			'popup_display',
			'popup_theme',
			'popup_triggers',
			'popup_cookies',
			'popup_conditions',
			'popup_mobile_disabled',
			'popup_tablet_disabled',
		) );
	}


	/**
	 * @return mixed|void
	 */
	public static function translatable_meta_keys() {
		return apply_filters( 'pum_wpml_translatable_meta_keys', array(
			'popup_close',
			'popup_title',
		) );
	}


	/**
	 * Copies post_meta for popups on duplication.
	 *
	 * Only copies untranslatable data.
	 *
	 * @param $master_post_id int Original post_ID.
	 * @param $lang string The new language.
	 * @param $post_array array The $post array for the new/duplicate post.
	 * @param $id int The post_ID for the new/duplicate post.
	 */
	public static function duplicate_post( $master_post_id, $lang, $post_array, $id ) {
		// Only do this for popups.
		if ( get_post_type( $master_post_id ) != 'popup' ) {
			return;
		}

		foreach ( self::untranslatable_meta_keys() as $key ) {
			$value = get_post_meta( $master_post_id, $key, true );
			if ( ! $value ) {
				continue;
			}

			if ( $key == 'popup_conditions' ) {
				$value = self::remap_conditions( $value, $lang );
			}

			update_post_meta( $id, $key, $value );
		}
	}

}
