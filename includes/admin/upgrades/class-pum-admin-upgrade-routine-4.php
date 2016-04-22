<?php
/**
 * Upgrade Routine 4
 *
 * @package     PUM
 * @subpackage  Admin/Upgrades
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PUM_Admin_Upgrade_Routine' ) ) {
	require_once POPMAKE_DIR . "includes/admin/upgrades/class-pum-admin-upgrade-routine.php";
}

/**
 * Class PUM_Admin_Upgrade_Routine_4
 */
final class PUM_Admin_Upgrade_Routine_4 extends PUM_Admin_Upgrade_Routine {

	/**
	 * Returns a description.
	 *
	 * @return mixed|void
	 */
	public static function description() {
		return __( 'Upgrade popup targeting conditions.', 'popup-maker' );
	}

	/**
	 * Upgrade popup targeting conditions.
	 *
	 * - Convert Conditions
	 * - Default popups with no conditions to draft
	 */
	public static function run() {
		if ( ! current_user_can( PUM_Admin_Upgrades::instance()->required_cap ) ) {
			wp_die( __( 'You do not have permission to do upgrades', 'popup-maker' ), __( 'Error', 'popup-maker' ), array( 'response' => 403 ) );
		}

		ignore_user_abort( true );

		if ( ! pum_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
			@set_time_limit( 0 );
		}

		$upgrades  = PUM_Admin_Upgrades::instance();
		$completed = $upgrades->get_arg( 'completed' );
		$total     = $upgrades->get_arg( 'total' );

		// Set the correct total.
		if ( $total <= 1 ) {
			$popups = wp_count_posts( 'popup' );
			$total  = 0;
			foreach ( $popups as $status ) {
				$total += $status;
			}
			$upgrades->set_arg( 'total', $total );
		}

		$popups = new PUM_Popup_Query( array(
			'number' => $upgrades->get_arg( 'number' ),
			'page'   => $upgrades->get_arg( 'step' ),
			'status' => array( 'any', 'trash', 'auto-draft' ),
			'order'  => 'ASC',
		) );
		$popups = $popups->get_popups();

		if ( $popups ) {

			foreach ( $popups as $popup ) {

				$_conditions = $conditions = array();

				// Convert Conditions
				$targeting_conditions = popmake_get_popup_targeting_condition( $popup->ID );

				if ( empty( $targeting_conditions ) ) {
					if ( $popup->post_status == 'publish' ) {
						// Default popups with no conditions to draft
						PUM_Admin_Upgrade_Routine_4::change_post_status( $popup->ID, 'draft' );
					}
					update_post_meta( $popup->ID, 'popup_conditions', $conditions );
					$completed ++;
					continue;
				}


				$sitewide = false;

				if ( array_key_exists( 'on_entire_site', $targeting_conditions ) ) {
					$sitewide             = true;
					$targeting_conditions = PUM_Admin_Upgrade_Routine_4::filter_excludes( $targeting_conditions );
				} else {
					$targeting_conditions = PUM_Admin_Upgrade_Routine_4::filter_includes( $targeting_conditions );
				}

				$targeting_conditions = PUM_Admin_Upgrade_Routine_4::parse_conditions( $targeting_conditions );

				$_group = array();

				foreach ( $targeting_conditions as $condition ) {

					// If sitewide is enabled then all conditions use the not_operand.
					$condition['not_operand'] = $sitewide ? 1 : 0;

					// Add a new AND condition group.
					if ( $sitewide ) {
						$_conditions[] = array( $condition );
					} // Add a new OR condition to the group.
					else {
						$_group[] = $condition;
					}

				}

				if ( ! $sitewide && ! empty( $_group ) ) {
					$_conditions[] = $_group;
				}

				foreach ( $_conditions as $group_key => $group ) {
					foreach ( $group as $condition_key => $condition ) {
						$validated = PUM_Conditions::instance()->validate_condition( $condition );
						if ( ! is_wp_error( $validated ) ) {
							$conditions[ $group_key ][ $condition_key ] = $validated;
						}
					}
				}

				update_post_meta( $popup->ID, 'popup_conditions', $conditions );

				$completed ++;
			}

			if ( $completed < $total ) {
				$upgrades->set_arg( 'completed', $completed );
				PUM_Admin_Upgrade_Routine_4::next_step();
			}

		}

		PUM_Admin_Upgrade_Routine_4::done();
	}

	/**
	 * Converts old condition keys into new condition arrays.
	 *
	 * @param array $targeting_conditions
	 *
	 * @return array
	 */
	public static function parse_conditions( $targeting_conditions = array() ) {
		$conditions = array();

		$targeting_conditions = array_keys( $targeting_conditions );

		foreach ( $targeting_conditions as $index => $key ) {

			$condition = null;

			// Front Page
			if ( strpos( $key, 'on_home' ) !== false ) {
				$condition = array(
					'target' => 'is_front_page',
				);
			} // Blog Index
			elseif ( strpos( $key, 'on_blog' ) !== false ) {
				$condition = array(
					'target' => 'is_home',
				);
			} // Search Pages
			elseif ( strpos( $key, 'on_search' ) !== false ) {
				$condition = array(
					'target' => 'is_search',
				);
			} // 404 Pages
			elseif ( strpos( $key, 'on_404' ) !== false ) {
				$condition = array(
					'target' => 'is_404',
				);
			} // WooCommerce Pages
			elseif ( strpos( $key, 'on_woocommerce' ) !== false ) {
				$condition = array(
					'target' => 'is_woocommerce',
				);
			} // WooCommerce Shop Pages
			elseif ( strpos( $key, 'on_shop' ) !== false ) {
				$condition = array(
					'target' => 'is_shop',
				);
			}

			if ( $condition ) {
				unset( $targeting_conditions[ $index ] );
				$conditions[] = $condition;
			}

		}

		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $name => $post_type ) {
			$pt_conditions = PUM_Admin_Upgrade_Routine_4::filter_conditions( $targeting_conditions, '_' . $name );

			if ( empty( $pt_conditions ) ) {
				continue;
			}

			if ( in_array( "on_{$name}s", $pt_conditions ) && ! in_array( "on_specific_{$name}s", $pt_conditions ) ) {
				$conditions[] = array(
					'target' => $name . '_all',
				);
				continue;
			}

			// Remove non ID keys
			unset( $pt_conditions["on_{$name}s"] );
			unset( $pt_conditions["on_specific_{$name}s"] );

			$ids = array();

			// Convert the rest of the keys to post IDs.
			foreach ( $pt_conditions as $key ) {
				$id = intval( preg_replace( '/[^0-9]+/', '', $key ), 10 );
				if ( $id > 0 ) {
					$ids[] = $id;
				}
			}

			// Create a new post_type_selected condition with the ids.
			$conditions[] = array(
				'target'   => $name . '_selected',
				'selected' => $ids,
			);
		}

		foreach ( get_taxonomies( array( 'public' => true ), 'objects' ) as $tax_name => $taxonomy ) {

			$tax_conditions = PUM_Admin_Upgrade_Routine_4::filter_conditions( $targeting_conditions, '_' . $tax_name );

			if ( empty( $tax_conditions ) ) {
				continue;
			}

			if ( in_array( "on_{$tax_name}s", $tax_conditions ) && ! in_array( "on_specific_{$tax_name}s", $tax_conditions ) ) {
				$conditions[] = array(
					'target' => 'tax_' . $tax_name . '_all',
				);
				continue;
			}

			// Remove non ID keys
			unset( $tax_conditions["on_{$tax_name}s"] );
			unset( $tax_conditions["on_specific_{$tax_name}s"] );

			$ids = array();

			// Convert the rest of the keys to post IDs.
			foreach ( $tax_conditions as $key ) {
				$id = intval( preg_replace( '/[^0-9]+/', '', $key ), 10 );
				if ( $id > 0 ) {
					$ids[] = $id;
				}
			}

			// Create a new post_type_selected condition with the ids.
			$conditions[] = array(
				'target'   => 'tax_' . $tax_name . '_selected',
				'selected' => $ids,
			);
		}

		return $conditions;
	}

	/**
	 * Filters conditions for substrings and removes keys from original array.
	 *
	 * @param $targeting_conditions
	 * @param $string
	 *
	 * @return array
	 */
	public static function filter_conditions( &$targeting_conditions, $string ) {
		$conditions = array();

		foreach ( $targeting_conditions as $index => $key ) {
			if ( $string == '_post' && strpos( $key, '_post_tag' ) !== false ) {
				continue;
			}
			if ( strpos( $key, $string ) !== false ) {
				$key                = str_replace( 'exclude_', '', $key );
				$conditions[ $key ] = $key;
				unset( $targeting_conditions[ $index ] );
			}
		}

		return $conditions;
	}

	/**
	 * Change a post status for a specified post_id.
	 *
	 * @param $post_id
	 * @param $status
	 */
	public static function change_post_status( $post_id, $status ) {
		$current_post                = get_post( $post_id, 'ARRAY_A' );
		$current_post['post_status'] = $status;
		wp_update_post( $current_post );
	}

	/**
	 * Filters out only inclusionary conditions.
	 *
	 * @param array $conditions
	 *
	 * @return array
	 */
	public static function filter_includes( $conditions = array() ) {
		$includes = array();

		foreach ( $conditions as $condition => $value ) {
			if ( strpos( $condition, 'on_' ) === 0 ) {
				$includes[ $condition ] = $condition;
			}
		}

		return $includes;
	}

	/**
	 * Filters out only exclusionary conditions.
	 *
	 * @param array $conditions
	 *
	 * @return array
	 */
	public static function filter_excludes( $conditions = array() ) {
		$excludes = array();

		foreach ( $conditions as $condition => $value ) {
			if ( strpos( $condition, 'exclude_on_' ) === 0 ) {
				$excludes[ $condition ] = $condition;
			}
		}

		return $excludes;
	}

}