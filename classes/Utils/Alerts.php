<?php
/*******************************************************************************
 * Copyright (c) 2019, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Utils_Alerts {

	/**
	 *
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'hooks' ) );
		add_filter( 'pum_alert_list', array( __CLASS__, 'integration_alerts' ) );
		add_action( 'admin_menu', array( __CLASS__, 'pending_alert_count' ), 999 );
	}

	/**
	 * Gets a count of current alerts.
	 *
	 * @return int
	 */
	public static function alert_count() {
		return count( self::get_alerts() );
	}

	public static function pending_alert_count() {
		global $menu;
		$count = self::alert_count();
		foreach ( $menu as $key => $item ) {
			if ( $item[2] == 'edit.php?post_type=popup' ) {
				$menu[ $key ][0] .= $count ? ' <span class="update-plugins count-' . $count . '"><span class="plugin-count" aria-hidden="true">' . $count . '</span></span>' : '';
			}
		}
	}

	/**
	 * @param array $alerts
	 *
	 * @return array
	 */
	public static function integration_alerts( $alerts = array() ) {

		$integrations = array(
			'buddypress' => array(
				'conditions' => ! class_exists( 'PUM_BuddyPress' ) && ( function_exists( 'buddypress' ) || class_exists( 'BuddyPress' ) ),
				'slug' => 'popup-maker-buddypress-integration',
				'name' => 'Popup Maker - BuddyPress Integration',
				'free' => true,
			)
		);

		foreach( $integrations as $integration ) {

			if ( $integration['conditions'] ) {

				$path      = "{$integration['slug']}/{$integration['slug']}.php";
				$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $path, false, false );

				$installed = $plugin_data['Name'] === $integration['name'];

				$text = $installed ? __( 'activate it now', 'popup-maker' ) : __( 'install it now', 'popup-maker' );
				$url = $installed ? esc_url( wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $path ), 'activate-plugin_' . $path ) ) : esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=popup-maker-buddypress-integration' ), 'install-plugin_popup-maker-buddypress-integration' ) );

				$alerts[] = array(
					'message' => sprintf( __( '%sDid you know:%s Popup Maker has custom integrations with %s, %slearn more%s or %s%s%s!', 'popup-maker' ), '<strong>', '</strong>', __( 'BuddyPress', 'buddypress' ), '<a href="https://wppopupmaker.com/works-with/buddypress/" target="_blank">', '</a>', '<a href="' . $url . '" target="_blank">', $text, '</a>' ),
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
			\            add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
			add_action( 'network_admin_notices', array( __CLASS__, 'admin_notices' ) );
			add_action( 'user_admin_notices', array( __CLASS__, 'admin_notices' ) );
		}
	}

	/**
	 *
	 */
	public static function ajax_handler() {
		$args = wp_parse_args( $_REQUEST, array(
			'group'  => self::get_alert_group(),
			'code'   => self::get_alert_code(),
			'pri'    => self::get_current_alert( 'pri' ),
			'reason' => 'maybe_later',
		) );

		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'pum_alert_action' ) ) {
			wp_send_json_error();
		}

		try {
			$user_id = get_current_user_id();

			wp_send_json_success();

		} catch ( Exception $e ) {
			wp_send_json_error( $e );
		}
	}

	/**
	 * Render admin alerts if available.
	 */
	public static function admin_notices() {
		if ( self::should_hide_alerts() || ! count( self::get_alerts() ) ) {
			return;
		}

		wp_enqueue_script( 'pum-admin-general' );
		wp_enqueue_style( 'pum-admin-general' );

		$count = self::alert_count();

		?>

		<script type="text/javascript">
            (function ($) {
                function dismiss(alert, reason) {
                    $.ajax({
                        method: "POST",
                        dataType: "json",
                        url: ajaxurl,
                        data: {
                            action: 'pum_alert_action',
                            nonce: '<?php echo wp_create_nonce( 'pum_alert_action' ); ?>',
                            group: alert.group,
                            code: alert.code,
                            pri: alert.pri,
                            reason: reason
                        }
                    });
                }

                $(document)
                    .on('click', '.pum-alert .pum-dismiss', function (event) {
                        var $this = $(this),
                            reason = $this.data('reason'),
                            alert = $this.parents('.pum-alert');

                        alert.fadeTo(100, 0, function () {
                            alert.slideUp(100, function () {
                                alert.remove();
                            });
                        });

                        dismiss(alert, reason);
                    });
            }(jQuery));
		</script>

		<style>
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

			.pum-alert.pum-alert__warning {

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
				clip: rect(1px,1px,1px,1px);
				position: absolute!important;
				width: 1px;
				height: 1px;
				padding: 0;
				border: 0;
				word-wrap: normal!important;
				-webkit-clip-path: inset(50%);
				clip-path: inset(50%);
			}
		</style>

		<div class="pum-alerts">

			<h3><img class="logo" width="30" src="<?php echo Popup_Maker::$URL; ?>assets/images/logo.png" /> Notifications (<?php echo $count; ?>)</h3>

			<p>Check out the following notifications from Popup Maker.</p>

			<?php foreach ( self::get_alerts() as $alert ) : ?>

				<div class="pum-alert-holder">

					<div class="pum-alert <?php echo $alert['type'] != '' ? 'pum-alert__' . $alert['type'] : ''; ?> <?php echo $alert['is_dismissible'] ? 'is-dismissible' : ''; ?>">

						<p><?php echo $alert['message']; ?></p>

					</div>

					<?php if ( $alert['is_dismissible'] ) : ?>

						<button type="button" class="button dismiss">
							<span class="screen-reader-text"><?php _e( 'Dismiss this item.', 'popup-maker' ); ?></span>
							<span class="dashicons dashicons-no-alt"></span>
						</button>


					<?php endif; ?>

				</div>

			<?php endforeach; ?>

		</div>

		<?php
	}

	public static function get_alerts() {
		$alert_list = apply_filters( 'pum_alert_list', array() );

		/**
		 * $alert_list is an array of alerts & alert queues.
		 *
		 * $alert = array(
		 *     'pri' => 100 // 1-100+,
		 *     'message' => 'Some alert text.',
		 *     'is_dismissible' => true,
		 *     'type' => '', // success, error, warning
		 * );
		 */
		$alerts = array();

		foreach ( $alert_list as $key => $value ) {

			$alerts[] = wp_parse_args( $value, array(
				'pri'            => 10,
				'message'        => '',
				'is_dismissible' => true,
				'type'           => 'warning',
			) );

		}

		return $alerts;
	}


	/**
	 * @return int|string
	 */
	public static function get_alert_group() {
		static $selected;

		if ( ! isset( $selected ) ) {

			$dismissed_alerts = self::dismissed_alerts();

			$alerts = self::alerts();

			foreach ( $alerts as $g => $group ) {
				foreach ( $group['alerts'] as $t => $alert ) {
					if ( ! in_array( false, $alert['conditions'] ) && ( empty( $dismissed_alerts[ $g ] ) || $dismissed_alerts[ $g ] < $alert['pri'] ) ) {
						$selected = $g;
						break;
					}
				}

				if ( isset( $selected ) ) {
					break;
				}
			}
		}

		return $selected;
	}

	/**
	 * @return int|string
	 */
	public static function get_alert_code() {
		static $selected;

		if ( ! isset( $selected ) ) {

			$dismissed_alerts = self::dismissed_alerts();

			foreach ( self::alerts() as $g => $group ) {
				foreach ( $group['alerts'] as $t => $alert ) {
					if ( ! in_array( false, $alert['conditions'] ) && ( empty( $dismissed_alerts[ $g ] ) || $dismissed_alerts[ $g ] < $alert['pri'] ) ) {
						$selected = $t;
						break;
					}
				}

				if ( isset( $selected ) ) {
					break;
				}
			}
		}

		return $selected;
	}

	/**
	 * @param null $key
	 *
	 * @return bool|mixed
	 */
	public static function get_current_alert( $key = null ) {
		$group = self::get_alert_group();
		$code  = self::get_alert_code();

		if ( ! $group || ! $code ) {
			return false;
		}

		$alert = self::alerts( $group, $code );

		return empty( $key ) ? $alert : ( isset( $alert[ $key ] ) ? $alert[ $key ] : false );
	}

	/**
	 * Returns an array of dismissed alert groups.
	 *
	 * Array contains the group key and highest priority alert that has been shown previously for each group.
	 *
	 * $return = array(
	 *   'group1' => 20
	 * );
	 *
	 * @return array|mixed
	 */
	public static function dismissed_alerts() {
		$user_id = get_current_user_id();

		$dismissed_alerts = get_user_meta( $user_id, '_pum_alerts_dismissed_alerts', true );

		if ( ! $dismissed_alerts ) {
			$dismissed_alerts = array();
		}

		return $dismissed_alerts;
	}

	/**
	 * Returns true if the user has opted to never see this again. Or sets the option.
	 *
	 * @param bool $set If set this will mark the user as having opted to never see this again.
	 *
	 * @return bool
	 */
	public static function already_did( $set = false ) {
		$user_id = get_current_user_id();

		if ( $set ) {
			update_user_meta( $user_id, '_pum_alerts_already_did', true );

			return true;
		}

		return (bool) get_user_meta( $user_id, '_pum_alerts_already_did', true );
	}

	/**
	 * Gets a list of alerts.
	 *
	 * @param null $group
	 * @param null $code
	 *
	 * @return bool|mixed
	 */
	public static function alerts( $group = null, $code = null ) {
		static $alerts;

		if ( ! isset( $alerts ) ) {

			$time_message = __( 'Hi there! You\'ve been using Popup Maker on your site for %s - I hope it\'s been helpful. If you\'re enjoying my plugin, would you mind rating it 5-stars to help spread the word?', 'popup-maker' );
			$alerts       = array(
				'time_installed' => array(
					'alerts' => array(
						'one_week' => array(
							'message'    => sprintf( $time_message, __( '1 week', 'popup-maker' ) ),
							'conditions' => array(//strtotime( self::installed_on() . ' +1 week' ) < time(),
							),
							'link'       => 'https://wordpress.org/support/plugin/popup-maker/alerts/?rate=5#rate-response',
							'pri'        => 10,
						),
					),
					'pri'    => 10,
				),
				'open_count'     => array(
					'alerts' => array(),
					'pri'    => 50,
				),
			);

			$alerts = apply_filters( 'pum_alerts_alerts', $alerts );

			// Sort Groups
			$alerts = PUM_Utils_Array::sort( $alerts, 'priority', true );

			// Sort each groups alerts.
			foreach ( $alerts as $k => $v ) {
				$alerts[ $k ]['alerts'] = PUM_Utils_Array::sort( $alerts[ $k ]['alerts'], 'priority', true );
			}
		}

		if ( isset( $group ) ) {
			if ( ! isset( $alerts[ $group ] ) ) {
				return false;
			}

			return ! isset( $code ) ? $alerts[ $group ] : isset( $alerts[ $group ]['alerts'][ $code ] ) ? $alerts[ $group ]['alerts'][ $code ] : false;
		}

		return $alerts;
	}


	/**
	 * Checks if alerts should be shown.
	 *
	 * @return bool
	 */
	public static function should_hide_alerts() {
		$alert_code = self::get_alert_code();

		$conditions = array(
			self::already_did(),
			self::last_dismissed() && strtotime( self::last_dismissed() . ' +2 weeks' ) > time(),
			empty( $alert_code ),
		);

		return in_array( true, $conditions );
	}

	/**
	 * Gets the last dismissed date.
	 *
	 * @return false|string
	 */
	public static function last_dismissed() {
		$user_id = get_current_user_id();

		return get_user_meta( $user_id, '_pum_alerts_last_dismissed', true );
	}

}