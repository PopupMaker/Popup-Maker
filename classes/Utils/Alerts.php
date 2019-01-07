<?php
/*******************************************************************************
 * Copyright (c) 2019, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Utils_Alerts
 */
class PUM_Utils_Alerts {

	/**
	 *
	 */
	public static function init() {

		add_action( 'admin_init', array( __CLASS__, 'hooks' ) );
		add_action( 'wp_ajax_pum_alerts_action', array( __CLASS__, 'ajax_handler' ) );
		add_filter( 'pum_alert_list', array( __CLASS__, 'whats_new_alerts' ), 0 );
		add_filter( 'pum_alert_list', array( __CLASS__, 'integration_alerts' ), 5 );
		add_action( 'admin_menu', array( __CLASS__, 'append_alert_count' ), 999 );

	}

	/**
	 * Gets a count of current alerts.
	 *
	 * @return int
	 */
	public static function alert_count() {
		return count( self::get_alerts() );
	}

	/**
	 * Append alert count to Popup Maker menu item.
	 */
	public static function append_alert_count() {
		global $menu;
		$count = self::alert_count();
		foreach ( $menu as $key => $item ) {
			if ( $item[2] == 'edit.php?post_type=popup' ) {
				$menu[ $key ][0] .= $count ? ' <span class="update-plugins count-' . $count . '"><span class="plugin-count pum-alert-count" aria-hidden="true">' . $count . '</span></span>' : '';
			}
		}
	}

	/**
	 * @param array $alerts
	 *
	 * @return array
	 */
	public static function whats_new_alerts( $alerts = array() ) {

		$upgraded_from = PUM_Utils_Upgrades::$upgraded_from;

		if ( version_compare( $upgraded_from, '0.0.0', '>' ) ) {

			if ( version_compare( $upgraded_from, '1.8.0', '<' ) ) {
				$alerts[] = array(
					'code'     => 'whats_new_1_8_0',
					'type'     => 'success',
					'message'  => sprintf( '<strong>' . __( 'See whats new in v%s - (%sview all changes%s)', 'popup-maker' ) . '</strong>', '1.8.0', '<a href="'. add_query_arg( array( 'tab' => 'plugin-information', 'plugin' => 'popup-maker', 'section' => 'changelog', 'TB_iframe' => true, 'width' => 722, 'height' => 949 ), admin_url( 'plugin-install.php' ) ) .'" target="_blank">', '</a>' ),
					'html'     => "<ul class='ul-disc'>" .
					              "<li>" . 'Added support for Gutenberg editor when creating popups.' . "</li>" .
							      "<li>" . 'New close button positions: top center, bottom center, middle left & middle right.' . "</li>" .
					              "<li>" . 'New option to position close button outside of popup.' . "</li>" .
					              "</ul>",
					'priority' => 100,
				);
			}

		}

		return $alerts;
	}

	/**
	 * @param array $alerts
	 *
	 * @return array
	 */
	public static function integration_alerts( $alerts = array() ) {

		$integrations = array(
			'buddypress' => array(
				'label'          => __( 'BuddyPress', 'buddypress' ),
				'learn_more_url' => 'https://wppopupmaker.com/works-with/buddypress/',
				'conditions'     => ! class_exists( 'PUM_BuddyPress' ) && ( function_exists( 'buddypress' ) || class_exists( 'BuddyPress' ) ),
				'slug'           => 'popup-maker-buddypress-integration',
				'name'           => 'Popup Maker - BuddyPress Integration',
				'free'           => true,
			),
		);

		foreach ( $integrations as $key => $integration ) {

			if ( $integration['conditions'] ) {

				$path        = "{$integration['slug']}/{$integration['slug']}.php";
				$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $path, false, false );

				$installed = $plugin_data['Name'] === $integration['name'];

				$text = $installed ? __( 'activate it now', 'popup-maker' ) : __( 'install it now', 'popup-maker' );
				$url  = $installed ? esc_url( wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $path ), 'activate-plugin_' . $path ) ) : esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=popup-maker-buddypress-integration' ), 'install-plugin_popup-maker-buddypress-integration' ) );

				$alerts[] = array(
					'code'        => $key . '_integration_available',
					'message'     => sprintf( __( '%sDid you know:%s Popup Maker has custom integrations with %s, %slearn more%s or %s%s%s!', 'popup-maker' ), '<strong>', '</strong>', $integration['label'], '<a href="' . $integration['learn_more_url'] . '" target="_blank">', '</a>', '<a href="' . $url . '">', $text, '</a>' ),
					'dismissible' => true,
					'global'      => false,
					'type'        => $installed ? 'warning' : 'info',
				);

			}

		}

		return $alerts;
	}

	/**
	 * Hook into relevant WP actions.
	 */
	public static function hooks() {
		if ( is_admin() && current_user_can( 'edit_posts' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
			add_action( 'network_admin_notices', array( __CLASS__, 'admin_notices' ) );
			add_action( 'user_admin_notices', array( __CLASS__, 'admin_notices' ) );
		}
	}

	/**
	 * @return bool
	 */
	public static function should_show_alerts() {
		return in_array( true, array(
			pum_is_admin_page(),
			count( self::get_global_alerts() ) > 0,
		) );
	}

	/**
	 * Render admin alerts if available.
	 */
	public static function admin_notices() {
		if ( ! self::should_show_alerts() ) {
			return;
		}

		$global_only = ! pum_is_admin_page();

		$alerts = $global_only ? self::get_global_alerts() : self::get_alerts();

		$count = count( $alerts );

		if ( ! $count ) {
			return;
		}

		wp_enqueue_script( 'pum-admin-general' );
		wp_enqueue_style( 'pum-admin-general' );


		?>

		<style>

			.pum-alerts {
				clear: both;
				top: 10px;
				margin-right: 20px !important;
			}

			.popup_page_pum-extensions .pum-alerts {
				top: 0;
			}

			.pum-alert-holder {
				display: flex;
				margin-bottom: .8em;
			}

			.pum-alert p {
			}

			.pum-alerts img.logo {
				width: 25px;
				margin: -2px 5px -2px 0;
			}

			.pum-alert {
				width: 100%;
				padding: 0 12px;
				border-left: 4px solid #fff;
				background: #fff;
				box-shadow: 0 1px 2px rgba(0, 0, 0, .2);
			}

			.pum-alerts .button.dismiss, .pum-alerts .button.restore {
				width: 45px;
				height: 45px;
				margin-left: 10px;
				padding: 0;
				outline: 0;
				line-height: inherit;
				cursor: pointer;
				-ms-flex: 0 0 45px;
				flex: 0 0 45px;
			}

			.screen-reader-text {
				overflow: hidden;
				clip: rect(1px, 1px, 1px, 1px);
				position: absolute !important;
				width: 1px;
				height: 1px;
				padding: 0;
				border: 0;
				word-wrap: normal !important;
				clip-path: inset(50%);
			}
		</style>

		<div class="pum-alerts">

			<h3>
				<img alt="" class="logo" width="30" src="<?php echo Popup_Maker::$URL; ?>assets/images/logo.png" /> <?php printf( '%s%s (%s)', ( $global_only ? __( 'Popup Maker', 'popup-maker' ) . ' ' : '' ), __( 'Notifications', 'popup-maker' ), '<span class="pum-alert-count">' . $count . '</span>' ); ?>
			</h3>

			<p><?php __( 'Check out the following notifications from Popup Maker.', 'popup-maker' ); ?></p>

			<?php foreach ( $alerts as $alert ) : ?>

				<div class="pum-alert-holder" data-code="<?php echo $alert['code']; ?>" class="<?php echo $alert['dismissible'] ? 'is-dismissible' : ''; ?>">

					<div class="pum-alert <?php echo $alert['type'] != '' ? 'pum-alert__' . $alert['type'] : ''; ?>">

						<?php if ( ! empty( $alert['message'] ) ) : ?>
							<p><?php echo $alert['message']; ?></p>
						<?php endif; ?>

						<?php if ( ! empty( $alert['html'] ) ) : ?>
							<?php echo wp_encode_emoji( $alert['html'] ); ?>
						<?php endif; ?>

					</div>

					<?php if ( $alert['dismissible'] ) : ?>

						<button type="button" class="button dismiss pum-dismiss">
							<span class="screen-reader-text"><?php _e( 'Dismiss this item.', 'popup-maker' ); ?></span> <span class="dashicons dashicons-no-alt"></span>
						</button>

					<?php endif; ?>

				</div>

			<?php endforeach; ?>

		</div>

		<script type="text/javascript">
            (function ($) {
                function dismiss(alert, reason) {
                    $.ajax({
                        method: "POST",
                        dataType: "json",
                        url: ajaxurl,
                        data: {
                            action: 'pum_alerts_action',
                            nonce: '<?php echo wp_create_nonce( 'pum_alerts_action' ); ?>',
                            code: alert.data('code')
                        }
                    });
                }

                var $alerts = $('.pum-alerts'),
                    count = <?php echo $count; ?>,
                    $notice_counts = $('.pum-alert-count');


                $(document)
                    .on('click', '.pum-alert-holder .pum-dismiss', function () {
                        var $this = $(this),
                            alert = $this.parents('.pum-alert-holder');

                        count--;

                        $notice_counts.text(count);

                        alert.fadeTo(100, 0, function () {
                            alert.slideUp(100, function () {
                                alert.remove();

                                if ($alerts.find('.pum-alert-holder').length === 0) {
                                    $alerts.slideUp(100, function () {
                                        $alerts.remove();
                                    });

                                    $('#menu-posts-popup .wp-menu-name .update-plugins').fadeOut();
                                }
                            });
                        });

                        dismiss(alert);
                    });
            }(jQuery));
		</script>

		<?php
	}

	/**
	 * @return array
	 */
	public static function get_global_alerts() {
		$alerts = self::get_alerts();

		$global_alerts = array();

		foreach ( $alerts as $alert ) {
			if ( $alert['global'] ) {
				$global_alerts[] = $alert;
			}
		}

		return $global_alerts;
	}

	/**
	 * @return array
	 */
	public static function get_alerts() {

		static $alert_list;

		if ( ! isset( $alert_list ) ) {
			$alert_list = apply_filters( 'pum_alert_list', array() );
		}

		$alerts = array();

		$dismissed = self::dismissed_alerts();

		foreach ( $alert_list as $alert ) {

			// Remove dismissed alerts.
			if ( in_array( $alert['code'], $dismissed ) ) {
				continue;
			}

			$alerts[] = wp_parse_args( $alert, array(
				'code'        => 'default',
				'priority'    => 10,
				'message'     => '',
				'type'        => 'info',
				'dismissible' => true,
				'global'      => false,
			) );

		}

		// Sort alerts by priority, highest to lowest.
		$alerts = PUM_Utils_Array::sort( $alerts, 'priority', true );

		return $alerts;
	}


	/**
	 *
	 */
	public static function ajax_handler() {
		$args = wp_parse_args( $_REQUEST, array(
			'code' => '',
		) );

		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'pum_alerts_action' ) ) {
			wp_send_json_error();
		}

		try {

			$dismissed_alerts   = self::dismissed_alerts();
			$dismissed_alerts[] = $args['code'];

			$user_id = get_current_user_id();
			update_user_meta( $user_id, '_pum_dismissed_alerts', $dismissed_alerts );
			wp_send_json_success();

		} catch ( Exception $e ) {
			wp_send_json_error( $e );
		}
	}

	/**
	 * Returns an array of dismissed alert groups.
	 *
	 * @return array
	 */
	public static function dismissed_alerts() {
		$user_id = get_current_user_id();

		$dismissed_alerts = get_user_meta( $user_id, '_pum_dismissed_alerts', true );

		if ( ! $dismissed_alerts ) {
			$dismissed_alerts = array();
		}

		return $dismissed_alerts;
	}

}