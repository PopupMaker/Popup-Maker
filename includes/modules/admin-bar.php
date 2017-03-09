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
		add_action( 'wp_footer', array( __CLASS__, 'admin_bar_styles' ), 999 );
		add_action( 'init', array( __CLASS__, 'show_debug_bar' ) );
	}

	/**
	 * Renders the admin debug bar when PUM Debug is enabled.
	 */
	public static function show_debug_bar() {
		if ( Popup_Maker::debug_mode() ) {
			show_admin_bar( true );
		}
	}

	public static function admin_bar_styles() {
		if ( is_admin_bar_showing() ) : ?>
			<style>
				#wpadminbar {
					z-index: 999999999999;
				}

				#wpadminbar #wp-admin-bar-popups > .ab-item::before {
					/*background: url("

				<?php echo POPMAKE_URL; ?>  /assets/images/admin/dashboard-icon.png") center center no-repeat transparent !important;*/
					background: url("<?php echo POPMAKE_URL; ?>/assets/images/admin/icon-info-21x21.png") center center no-repeat transparent !important;
					top: 3px;
					content: "";
					width: 20px;
					height: 20px;
				}

				#wpadminbar #wp-admin-bar-popups:hover > .ab-item::before {
					background-image: url("<?php echo POPMAKE_URL; ?>/assets/images/admin/icon-info-21x21.png") !important;
				}

			</style>
		<?php endif;
	}

	/**
	 * Add additional toolbar menu items to the front end.
	 *
	 * @param $wp_admin_bar
	 */
	public static function toolbar_links( $wp_admin_bar ) {

		if ( is_admin() || PUM_Options::get( 'disabled_admin_bar', false ) ) {
			return;
		}
		/*
				$wp_admin_bar->add_node( array(
					'id'    => 'popup-maker',
					'title' => __( 'Popup Maker', 'popup-maker' ),
					'href'  => '#',
					'meta'  => array( 'class' => 'popup-maker-toolbar' ),
				) );
		*/
		$popups = PUM_Modules_Admin_Bar::loaded_popups();


		$popups_url = current_user_can( 'edit_posts' ) ? admin_url( 'edit.php?post_type=popup' ) : '#';

		$wp_admin_bar->add_node( array(
			'id'     => 'popups',
			'title'  => __( 'Popups', 'popup-maker' ),
			'href'   => $popups_url,
			'parent' => false,
		) );

		if ( count( $popups ) ) {

			foreach ( $popups as $popup ) {
				/** @var WP_Post $popup */

				$node_id = 'popup-' . $popup->ID;

				$can_edit = current_user_can( 'edit_post', $popup->ID );

				$edit_url = $can_edit ? admin_url( 'post.php?post=' . $popup->ID . '&action=edit' ) : '#';

				// Single Popup Menu Node
				$wp_admin_bar->add_node( array(
					'id'     => $node_id,
					'title'  => $popup->post_title,
					'href'   => $edit_url,
					'parent' => 'popups',
				) );

				// Trigger Link
				$wp_admin_bar->add_node( array(
					'id'     => $node_id . '-open',
					'title'  => __( 'Open Popup', 'popup-maker' ),
					'meta'   => array(
						'onclick' => 'PUM.open(' . $popup->ID . ');',
					),
					'href'   => '#',
					'parent' => $node_id,
				) );

				$wp_admin_bar->add_node( array(
					'id'     => $node_id . '-close',
					'title'  => __( 'Close Popup', 'popup-maker' ),
					'meta'   => array(
						'onclick' => 'PUM.close(' . $popup->ID . ');',
					),
					'href'   => '#',
					'parent' => $node_id,
				) );

				if ( pum_popup( $popup->ID )->has_conditions( array( 'js_only' => true ) ) ) {
					$wp_admin_bar->add_node( array(
						'id'     => $node_id . '-close',
						'title'  => __( 'Check Conditions', 'popup-maker' ),
						'meta'   => array(
							'onclick' => 'alert(PUM.checkConditions(' . $popup->ID . ') ? "Pass" : "Fail");',
						),
						'href'   => '#',
						'parent' => $node_id,
					) );
				}

				$wp_admin_bar->add_node( array(
					'id'     => $node_id . '-reset-cookies',
					'title'  => __( 'Reset Cookies', 'popup-maker' ),
					'href'   => '#',
					'parent' => $node_id,
				) );

				if ( $can_edit ) {
					// Edit Popup Link
					$wp_admin_bar->add_node( array(
						'id'     => $node_id . '-edit',
						'title'  => __( 'Edit Popup', 'popup-maker' ),
						'href'   => $edit_url,
						'parent' => $node_id,
					) );
				}

			}
		} else {
			$wp_admin_bar->add_node( array(
				'id'     => 'no-popups-loaded',
				'title'  => __( 'No Popups Loaded', 'popup-maker' ) . '<strong style="color:#fff; margin-left: 5px;">?</strong>',
				'href'   => 'http://docs.wppopupmaker.com/article/140-conditions',
				'parent' => 'popups',
				'meta'   => array(
					'target' => '_blank',
				),

			) );
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