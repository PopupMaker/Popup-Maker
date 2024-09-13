<?php
/**
 * Modules for reviews
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Modules_Reviews
 *
 * This class adds a review request system for your plugin or theme to the WP dashboard.
 */
class PUM_Modules_Reviews {

	/**
	 * Tracking API Endpoint.
	 *
	 * @var string
	 */
	public static $api_url = 'https://api.wppopupmaker.com/wp-json/pmapi/v1/review_action';

	/**
	 *
	 */
	public static function init() {
		// add_action( 'init', array( __CLASS__, 'hooks' ) );
		add_filter( 'pum_alert_list', [ __CLASS__, 'review_alert' ] );
		add_action( 'wp_ajax_pum_review_action', [ __CLASS__, 'ajax_handler' ] );
	}

	/**
	 * Hook into relevant WP actions.
	 */
	public static function hooks() {
		if ( is_admin() && current_user_can( 'edit_posts' ) ) {
			self::installed_on();
			add_action( 'admin_notices', [ __CLASS__, 'admin_notices' ] );
			add_action( 'network_admin_notices', [ __CLASS__, 'admin_notices' ] );
			add_action( 'user_admin_notices', [ __CLASS__, 'admin_notices' ] );
		}
	}

	/**
	 * Get the install date for comparisons. Sets the date to now if none is found.
	 *
	 * @return false|string
	 */
	public static function installed_on() {
		$installed_on = get_option( 'pum_reviews_installed_on', false );

		if ( ! $installed_on ) {
			$installed_on = current_time( 'mysql' );
			update_option( 'pum_reviews_installed_on', $installed_on );
		}

		return $installed_on;
	}

	/**
	 *
	 */
	public static function ajax_handler() {
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['nonce'] ) ), 'pum_review_action' ) ) {
			wp_send_json_error();
		}

		$args = wp_parse_args(
			$_REQUEST,
			[
				'group'  => self::get_trigger_group(),
				'code'   => self::get_trigger_code(),
				'pri'    => self::get_current_trigger( 'pri' ),
				'reason' => 'maybe_later',
			]
		);

		try {
			$user_id = get_current_user_id();

			$dismissed_triggers                   = self::dismissed_triggers();
			$dismissed_triggers[ $args['group'] ] = $args['pri'];
			update_user_meta( $user_id, '_pum_reviews_dismissed_triggers', $dismissed_triggers );
			update_user_meta( $user_id, '_pum_reviews_last_dismissed', current_time( 'mysql' ) );

			switch ( $args['reason'] ) {
				case 'maybe_later':
					update_user_meta( $user_id, '_pum_reviews_last_dismissed', current_time( 'mysql' ) );
					break;
				case 'am_now':
				case 'already_did':
					self::already_did( true );
					break;
			}

			wp_send_json_success();
		} catch ( Exception $e ) {
			wp_send_json_error( $e );
		}
	}

	/**
	 * @return int|string
	 */
	public static function get_trigger_group() {
		static $selected;

		if ( ! isset( $selected ) ) {
			$dismissed_triggers = self::dismissed_triggers();

			$triggers = self::triggers();

			foreach ( $triggers as $g => $group ) {
				foreach ( $group['triggers'] as $t => $trigger ) {
					if ( ! in_array( false, $trigger['conditions'], true ) && ( empty( $dismissed_triggers[ $g ] ) || $dismissed_triggers[ $g ] < $trigger['pri'] ) ) {
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
	public static function get_trigger_code() {
		static $selected;

		if ( ! isset( $selected ) ) {
			$dismissed_triggers = self::dismissed_triggers();

			foreach ( self::triggers() as $g => $group ) {
				foreach ( $group['triggers'] as $t => $trigger ) {
					if ( ! in_array( false, $trigger['conditions'], true ) && ( empty( $dismissed_triggers[ $g ] ) || $dismissed_triggers[ $g ] < $trigger['pri'] ) ) {
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
	 * @param string|null $key
	 *
	 * @return bool|mixed|void
	 */
	public static function get_current_trigger( $key = null ) {
		$group = self::get_trigger_group();
		$code  = self::get_trigger_code();

		if ( ! $group || ! $code ) {
			return false;
		}

		$trigger = self::triggers( $group, $code );

		return empty( $key ) ? $trigger : ( isset( $trigger[ $key ] ) ? $trigger[ $key ] : false );
	}

	/**
	 * Returns an array of dismissed trigger groups.
	 *
	 * Array contains the group key and highest priority trigger that has been shown previously for each group.
	 *
	 * $return = array(
	 *   'group1' => 20
	 * );
	 *
	 * @return array|mixed
	 */
	public static function dismissed_triggers() {
		$user_id = get_current_user_id();

		$dismissed_triggers = get_user_meta( $user_id, '_pum_reviews_dismissed_triggers', true );

		if ( ! $dismissed_triggers ) {
			$dismissed_triggers = [];
		}

		return $dismissed_triggers;
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
			update_user_meta( $user_id, '_pum_reviews_already_did', true );

			return true;
		}

		return (bool) get_user_meta( $user_id, '_pum_reviews_already_did', true );
	}

	/**
	 * Gets a list of triggers.
	 *
	 * @param string|null $group
	 * @param string|null $code
	 *
	 * @return bool|mixed
	 */
	public static function triggers( $group = null, $code = null ) {
		static $triggers;

		if ( ! isset( $triggers ) ) {
			/* translators: %s: number of days. */
			$time_message = __( 'Hi there! You\'ve been using Popup Maker on your site for %s - I hope it\'s been helpful. If you\'re enjoying my plugin, would you mind rating it 5-stars to help spread the word?', 'popup-maker' );
			$triggers     = [
				'time_installed' => [
					'triggers' => [
						'one_week'     => [
							'message'    => sprintf( $time_message, __( '1 week', 'popup-maker' ) ),
							'conditions' => [
								strtotime( self::installed_on() . ' +1 week' ) < time(),
							],
							'link'       => 'https://wordpress.org/support/plugin/popup-maker/reviews/?rate=5#rate-response',
							'pri'        => 10,
						],
						'one_month'    => [
							'message'    => sprintf( $time_message, __( '1 month', 'popup-maker' ) ),
							'conditions' => [
								strtotime( self::installed_on() . ' +1 month' ) < time(),
							],
							'link'       => 'https://wordpress.org/support/plugin/popup-maker/reviews/?rate=5#rate-response',
							'pri'        => 20,
						],
						'three_months' => [
							'message'    => sprintf( $time_message, __( '3 months', 'popup-maker' ) ),
							'conditions' => [
								strtotime( self::installed_on() . ' +3 months' ) < time(),
							],
							'link'       => 'https://wordpress.org/support/plugin/popup-maker/reviews/?rate=5#rate-response',
							'pri'        => 30,
						],

					],
					'pri'      => 10,
				],
				'open_count'     => [
					'triggers' => [],
					'pri'      => 50,
				],
			];

			$pri = 10;
			/* translators: %s: number of popup views. */
			$open_message = __( 'Hi there! You\'ve recently hit %s popup views on your site – that’s awesome!! If you\'d like to celebrate this milestone, rate Popup Maker 5-stars to help spread the word!', 'popup-maker' );
			foreach ( [ 50, 100, 500, 1000, 5000, 10000, 50000, 100000, 500000, 1000000, 5000000 ] as $num ) {
				$triggers['open_count']['triggers'][ $num . '_opens' ] = [
					'message'    => sprintf( $open_message, number_format( $num ) ),
					'conditions' => [
						get_option( 'pum_total_open_count', 0 ) > $num,
					],
					'link'       => 'https://wordpress.org/support/plugin/popup-maker/reviews/?rate=5#rate-response',
					'pri'        => $pri,
				];

				$pri += 10;
			}

			$triggers = apply_filters( 'pum_reviews_triggers', $triggers );

			// Sort Groups
			uasort( $triggers, [ __CLASS__, 'rsort_by_priority' ] );

			// Sort each groups triggers.
			foreach ( $triggers as $k => $v ) {
				uasort( $triggers[ $k ]['triggers'], [ __CLASS__, 'rsort_by_priority' ] );
			}
		}

		if ( isset( $group ) ) {
			if ( ! isset( $triggers[ $group ] ) ) {
				return false;
			}

			if ( ! isset( $code ) ) {
				return $triggers[ $group ];
			} else {
				return isset( $triggers[ $group ]['triggers'][ $code ] ) ? $triggers[ $group ]['triggers'][ $code ] : false;
			}
		}

		return $triggers;
	}

	/**
	 * Register alert when review request is available.
	 *
	 * @param array $alerts
	 *
	 * @return array
	 */
	public static function review_alert( $alerts = [] ) {
		if ( self::hide_notices() ) {
			return $alerts;
		}

		$trigger = self::get_current_trigger();

		// Used to anonymously distinguish unique site+user combinations in terms of effectiveness of each trigger.
		$uuid = wp_hash( home_url() . '-' . get_current_user_id() );

		ob_start();

		?>

		<script type="text/javascript">
			window.pum_review_nonce = '<?php echo esc_html( wp_create_nonce( 'pum_review_action' ) ); ?>';
			window.pum_review_api_url = '<?php echo esc_attr( self::$api_url ); ?>';
			window.pum_review_uuid = '<?php echo esc_attr( $uuid ); ?>';
			window.pum_review_trigger = {
				group: '<?php echo esc_attr( self::get_trigger_group() ); ?>',
				code: '<?php echo esc_attr( self::get_trigger_code() ); ?>',
				pri: '<?php echo esc_attr( self::get_current_trigger( 'pri' ) ); ?>'
			};
		</script>

		<ul>
			<li>
				<a class="pum-dismiss" target="_blank" href="<?php echo esc_attr( $trigger['link'] ); ?>" data-reason="am_now"> <strong><?php esc_html_e( 'Ok, you deserve it', 'popup-maker' ); ?></strong> </a>
			</li>
			<li>
				<a href="#" class="pum-dismiss" data-reason="maybe_later">
					<?php esc_html_e( 'Nope, maybe later', 'popup-maker' ); ?>
				</a>
			</li>
			<li>
				<a href="#" class="pum-dismiss" data-reason="already_did">
					<?php esc_html_e( 'I already did', 'popup-maker' ); ?>
				</a>
			</li>
		</ul>

		<?php

		$html = ob_get_clean();

		$alerts[] = [
			'code'    => 'review_request',
			'message' => '<strong>' . $trigger['message'] . '<br />~ danieliser</strong>',
			'html'    => $html,
			'type'    => 'success',
		];

		return $alerts;
	}


	/**
	 * Render admin notices if available.
	 *
	 * @deprecated 1.8.0
	 */
	public static function admin_notices() {
		if ( self::hide_notices() ) {
			return;
		}

		$group   = self::get_trigger_group();
		$code    = self::get_trigger_code();
		$pri     = self::get_current_trigger( 'pri' );
		$trigger = self::get_current_trigger();

		// Used to anonymously distinguish unique site+user combinations in terms of effectiveness of each trigger.
		$uuid = wp_hash( home_url() . '-' . get_current_user_id() );

		?>

		<script type="text/javascript">
			(function ($) {
				var trigger = {
					group: '<?php echo esc_attr( $group ); ?>',
					code: '<?php echo esc_attr( $code ); ?>',
					pri: '<?php echo esc_attr( $pri ); ?>'
				};

				function dismiss(reason) {
					$.ajax({
						method: "POST",
						dataType: "json",
						url: ajaxurl,
						data: {
							action: 'pum_review_action',
							nonce: '<?php echo esc_attr( wp_create_nonce( 'pum_review_action' ) ); ?>',
							group: trigger.group,
							code: trigger.code,
							pri: trigger.pri,
							reason: reason
						}
					});

					<?php if ( ! empty( self::$api_url ) ) : ?>
					$.ajax({
						method: "POST",
						dataType: "json",
						url: '<?php echo esc_attr( self::$api_url ); ?>',
						data: {
							trigger_group: trigger.group,
							trigger_code: trigger.code,
							reason: reason,
							uuid: '<?php echo esc_attr( $uuid ); ?>'
						}
					});
					<?php endif; ?>
				}

				$(document)
					.on('click', '.pum-notice .pum-dismiss', function (event) {
						var $this = $(this),
							reason = $this.data('reason'),
							notice = $this.parents('.pum-notice');

						notice.fadeTo(100, 0, function () {
							notice.slideUp(100, function () {
								notice.remove();
							});
						});

						dismiss(reason);
					})
					.ready(function () {
						setTimeout(function () {
							$('.pum-notice button.notice-dismiss').click(function (event) {
								dismiss('maybe_later');
							});
						}, 1000);
					});
			}(jQuery));
		</script>

		<style>
			.pum-notice p {
				margin-bottom: 0;
			}

			.pum-notice img.logo {
				float: right;
				margin-left: 10px;
				width: 128px;
				padding: 0.25em;
				border: 1px solid #ccc;
			}
		</style>

		<div class="notice notice-success is-dismissible pum-notice">

			<p>
				<img class="logo" src="<?php echo esc_attr( POPMAKE_URL ); ?>/assets/images/icon-256x256.jpg" />
				<strong>
					<?php echo esc_html( $trigger['message'] ); ?>
					<br />
					~ danieliser
				</strong>
			</p>
			<ul>
				<li>
					<a class="pum-dismiss" target="_blank" href="<?php echo esc_attr( $trigger['link'] ); ?>" data-reason="am_now">
						<strong><?php esc_html_e( 'Ok, you deserve it', 'popup-maker' ); ?></strong>
					</a>
				</li>
				<li>
					<a href="#" class="pum-dismiss" data-reason="maybe_later">
						<?php esc_html_e( 'Nope, maybe later', 'popup-maker' ); ?>
					</a>
				</li>
				<li>
					<a href="#" class="pum-dismiss" data-reason="already_did">
						<?php esc_html_e( 'I already did', 'popup-maker' ); ?>
					</a>
				</li>
			</ul>

		</div>

		<?php
	}

	/**
	 * Checks if notices should be shown.
	 *
	 * @return bool
	 */
	public static function hide_notices() {
		$trigger_code = self::get_trigger_code();

		$conditions = [
			self::already_did(),
			self::last_dismissed() && strtotime( self::last_dismissed() . ' +2 weeks' ) > time(),
			empty( $trigger_code ),
		];

		return in_array( true, $conditions, true );
	}

	/**
	 * Gets the last dismissed date.
	 *
	 * @return false|string
	 */
	public static function last_dismissed() {
		$user_id = get_current_user_id();

		return get_user_meta( $user_id, '_pum_reviews_last_dismissed', true );
	}

	/**
	 * Sort array by priority value
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	public static function sort_by_priority( $a, $b ) {
		if ( ! isset( $a['pri'] ) || ! isset( $b['pri'] ) || $a['pri'] === $b['pri'] ) {
			return 0;
		}

		return ( $a['pri'] < $b['pri'] ) ? - 1 : 1;
	}

	/**
	 * Sort array in reverse by priority value
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	public static function rsort_by_priority( $a, $b ) {
		if ( ! isset( $a['pri'] ) || ! isset( $b['pri'] ) || $a['pri'] === $b['pri'] ) {
			return 0;
		}

		return ( $a['pri'] < $b['pri'] ) ? 1 : - 1;
	}
}

PUM_Modules_Reviews::init();
